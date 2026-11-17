<?php
namespace Panels\Drivers;
use Panels\AbstractPanel;
use Core\Cache;
use Core\Helper;

/**
 * کپی دقیق ساختار اتصال PasarGuard از فاکسیما (PasarGuard.php)
 * - پشتیبانی از دو حالت احراز هویت: api_key و password
 * - کش توکن با JWT TTL و قفل
 * - درخواست‌ها دقیقاً مانند pasarguardRequest / pasarguardLogin
 */
class PasarGuardPanel extends AbstractPanel {
    private static array $memCache = [];

    private function baseUrl(): string { return rtrim($this->config['url'] ?? $this->config['url_panel'] ?? '', '/'); }
    private function username(): string { return (string)($this->config['username'] ?? $this->config['username_panel'] ?? ''); }
    private function password(): string { return (string)($this->config['password'] ?? $this->config['password_panel'] ?? ''); }
    private function apiKey(): ?string {
        $k = $this->config['api_key'] ?? $this->config['token'] ?? null;
        $k = is_string($k) ? trim($k) : $k;
        return $k !== '' ? (string)$k : null;
    }
    private function authMode(): string {
        $m = $this->config['pasarguard_auth_mode'] ?? null;
        if ($m === 'api_key' || $m === 'password') return $m;
        return $this->apiKey() !== null ? 'api_key' : 'password';
    }
    private function tokenCacheKey(): string {
        return 'pasarguard_token:' . md5($this->baseUrl() . '|' . $this->username());
    }
    private function jwtExp(?string $token): ?int {
        if (!$token) return null;
        $parts = explode('.', $token);
        if (count($parts) !== 3) return null;
        $payload = base64_decode(strtr($parts[1], '-_', '+/') . str_repeat('=', (4 - strlen($parts[1]) % 4) % 4), true);
        $d = $payload !== false ? json_decode($payload, true) : null;
        return is_array($d) && isset($d['exp']) && is_numeric($d['exp']) ? (int)$d['exp'] : null;
    }
    private function tokenTtl(string $token, int $ttl = 3000): int {
        $exp = $this->jwtExp($token);
        if ($exp !== null) $ttl = min($ttl, $exp - time() - 60);
        return $ttl;
    }
    private function cachedTokenInternal(?string $rejected = null): ?string {
        $key = $this->tokenCacheKey();
        // 1. APCu/file cache via Core\Cache
        $c = Cache::get($key);
        if (is_string($c) && $c !== '' && $c !== $rejected) {
            $ttl = $this->tokenTtl($c, 3000);
            if ($ttl > 0) return $c;
            Cache::delete($key);
        }
        // 2. حافظه درخواست (مانند $GLOBALS['__faoxima_panel_token_cache'])
        $e = self::$memCache[$key] ?? null;
        if (is_array($e) && isset($e['token'], $e['expires_at']) && $e['expires_at'] > time() && $e['token'] !== $rejected) {
            return (string)$e['token'];
        }
        unset(self::$memCache[$key]);
        return null;
    }
    private function storeToken(string $token): void {
        if ($token === '') return;
        $ttl = $this->tokenTtl($token, 3000);
        if ($ttl <= 0) return;
        $key = $this->tokenCacheKey();
        Cache::set($key, $token, $ttl);
        self::$memCache[$key] = ['token'=>$token, 'expires_at'=>time()+$ttl];
        $this->cachedToken = $token;
        // ذخیره در DB برای ماندگاری (اختیاری) - اگر ستون datelogin وجود داشت
        try {
            $pdo = \Core\Database::pdo();
            if ($pdo) {
                $payload = json_encode(['pasarguard_token'=>$token,'pasarguard_key'=>$key,'pasarguard_time'=>time()]);
                $stmt = $pdo->prepare("UPDATE panels SET last_error=? WHERE id=?");
                // ما last_error را دست نمیزنیم، فقط لاگ - برای سازگاری با فاکسیما datelogin نداریم
            }
        } catch (\Throwable $e) {}
    }
    private function clearToken(?string $onlyToken = null): void {
        $key = $this->tokenCacheKey();
        if ($onlyToken === null) {
            Cache::delete($key);
            unset(self::$memCache[$key]);
            return;
        }
        $e = self::$memCache[$key] ?? null;
        if (is_array($e) && ($e['token'] ?? null) === $onlyToken) unset(self::$memCache[$key]);
        $c = Cache::get($key);
        if ($c === $onlyToken) Cache::delete($key);
    }

    // دقیقاً مانند pasarguardLogin در فاکسیما
    private function login(): array {
        $url = $this->baseUrl() . '/api/admin/token';
        $data = ['username'=>$this->username(), 'password'=>$this->password(), 'grant_type'=>'password'];
        $headers = ['accept: application/json', 'Content-Type: application/x-www-form-urlencoded'];
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER=>true,
            CURLOPT_POST=>true,
            CURLOPT_POSTFIELDS=>http_build_query($data),
            CURLOPT_HTTPHEADER=>$headers,
            CURLOPT_TIMEOUT=>10,
            CURLOPT_CONNECTTIMEOUT=>5,
            CURLOPT_SSL_VERIFYPEER=>false,
            CURLOPT_SSL_VERIFYHOST=>0,
        ]);
        $body = curl_exec($ch);
        $err = curl_error($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($err) return ['status'=>false,'msg'=>$err];
        if ($code >=400) {
            $dec = json_decode((string)$body, true);
            $msg = is_array($dec) && isset($dec['detail']) ? $dec['detail'] : "HTTP $code";
            return ['status'=>false,'msg'=> is_array($msg)? json_encode($msg, JSON_UNESCAPED_UNICODE):$msg];
        }
        $dec = json_decode((string)$body, true);
        if (!is_array($dec) || empty($dec['access_token'])) return ['status'=>false,'msg'=>'Login response did not include access_token'];
        return ['status'=>true,'access_token'=>(string)$dec['access_token']];
    }

    private function getValidToken(?string $rejected = null, bool $forceRefresh=false): ?array {
        $mayReuse = !$forceRefresh || $rejected !== null;
        if ($mayReuse) {
            $c = $this->cachedTokenInternal($rejected);
            if ($c !== null) return ['status'=>true,'access_token'=>$c];
        }
        if ($this->authenticating) return null;
        $this->authenticating = true;
        try {
            if ($mayReuse) {
                $c = $this->cachedTokenInternal($rejected);
                if ($c !== null) return ['status'=>true,'access_token'=>$c];
            }
            $res = $this->login();
            if (empty($res['status'])) return $res;
            $this->storeToken($res['access_token']);
            return $res;
        } finally { $this->authenticating = false; }
    }

    public function authenticate(): bool {
        if ($this->authMode() === 'api_key') {
            $k = $this->apiKey();
            if (empty($k)) return false;
            $this->cachedToken = $k;
            return true;
        }
        // password mode
        if ($this->cachedToken) {
            $ttl = $this->tokenTtl($this->cachedToken, 3000);
            if ($ttl > 0) return true;
        }
        $c = $this->cachedTokenInternal();
        if ($c !== null) { $this->cachedToken=$c; return true; }
        $res = $this->getValidToken();
        return !empty($res['status']);
    }

    public function getToken(): ?string {
        if ($this->cachedToken) {
            $ttl = $this->tokenTtl($this->cachedToken, 3000);
            if ($ttl > 0) return $this->cachedToken;
        }
        $c = $this->cachedTokenInternal();
        if ($c !== null) { $this->cachedToken=$c; return $c; }
        if ($this->authenticating) return null;
        if ($this->authMode() === 'api_key') {
            $k=$this->apiKey();
            if ($k) { $this->cachedToken=$k; return $k; }
            return null;
        }
        $res = $this->getValidToken();
        return $res['access_token'] ?? $this->cachedToken;
    }

    // دقیقاً مانند pasarguardRequest در فاکسیما — با 401 retry
    private function pasarguardRequest(string $method, string $path, $data = null): array {
        $url = $this->baseUrl() . $path;
        $isJson = $data !== null;
        $baseHeaders = ['accept: application/json'];
        if ($isJson) $baseHeaders[] = 'Content-Type: application/json';
        $do = function($bearer, $apiKey) use ($url, $baseHeaders, $method, $data) {
            $ch = curl_init($url);
            $headers = $baseHeaders;
            if ($bearer !== null) $headers[] = "Authorization: Bearer $bearer";
            if ($apiKey !== null) $headers[] = "X-API-Key: $apiKey";
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER=>true,
                CURLOPT_CUSTOMREQUEST=>strtoupper($method),
                CURLOPT_HTTPHEADER=>$headers,
                CURLOPT_TIMEOUT=>10,
                CURLOPT_CONNECTTIMEOUT=>5,
                CURLOPT_SSL_VERIFYPEER=>false,
                CURLOPT_SSL_VERIFYHOST=>0,
            ]);
            if ($data !== null) {
                $payload = is_string($data) ? $data : json_encode($data, JSON_UNESCAPED_UNICODE);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            }
            $body = curl_exec($ch);
            $err = curl_error($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($err) return ['status'=>$code,'body'=>$body,'error'=>$err];
            return ['status'=>$code,'body'=>$body];
        };

        if ($this->authMode() === 'password') {
            $tokRes = $this->getValidToken();
            if (empty($tokRes['status'])) return ['status'=>0,'body'=>'','error'=>$tokRes['msg']??'login failed'];
            $resp = $do($tokRes['access_token'], null);
            if (($resp['status'] ?? 0) == 401) {
                $rej = (string)$tokRes['access_token'];
                $this->clearToken($rej);
                $retry = $this->getValidToken($rej, true);
                if (empty($retry['status'])) return ['status'=>0,'body'=>'','error'=>$retry['msg']??'retry login failed'];
                $resp = $do($retry['access_token'], null);
            }
            return $resp;
        }
        $apiKey = $this->apiKey();
        return $do(null, $apiKey);
    }

    private function headers(): array {
        // برای سازگاری با کد قبلی - استفاده مستقیم از pasarguardRequest توصیه می‌شود
        $t = $this->getToken();
        return $t ? ["Authorization: Bearer $t"] : [];
    }

    public function createUser(string $username, int $dataLimitBytes, int $expireTimestamp, $groupOrInboundIds = null): array {
        if (!$this->authenticate()) return ['success'=>false,'message'=>'احراز هویت پاسارگاد ناموفق','subscription_url'=>'','uuid'=>''];
        // کپی دقیق payload فاکسیما: pasarguardAddUser
        $panelProxies = $this->config['proxies'] ?? null;
        $proxies = $panelProxies ? json_decode($panelProxies, true) : null;
        $data = [
            "proxy_settings" => $proxies,
            "data_limit" => $dataLimitBytes,
            "username" => $username,
            "note" => "",
            "data_limit_reset_strategy" => "no_reset"
        ];
        // group_ids handling
        $inbounds = $groupOrInboundIds;
        if ($inbounds === null && !empty($this->config['inbounds'])) {
            $inbounds = json_decode($this->config['inbounds'], true);
        }
        if ($inbounds !== null && $inbounds !== "null" && $inbounds !== "") {
            if (is_string($inbounds) && strpos($inbounds, ',') !== false) $inbounds = explode(',', $inbounds);
            if (!is_array($inbounds)) $inbounds = [$inbounds];
            $gids = array_values(array_map('intval', (array)$inbounds));
            // مرج با گروه‌های فعلی اگر کاربر وجود دارد (مانند فاکسیما)
            $existing = $this->pasarguardRequest('GET', '/api/user/'.$username);
            $sc = (int)($existing['status'] ?? 0);
            if ($sc === 200) {
                $j = json_decode($existing['body'] ?? '', true);
                $cur = is_array($j) ? ($j['group_ids'] ?? null) : null;
                if (is_array($cur) && !empty($cur)) $gids = array_values(array_unique(array_merge(array_map('intval',$cur), $gids)));
            } elseif ($sc !== 404) {
                // خطای شبکه - بدون group_ids درخواست بده
                unset($gids);
                $data_req = $data;
                $resp = $this->pasarguardRequest('POST', '/api/user', $data_req);
                goto handle_create;
            }
            $data['group_ids'] = $gids;
        }
        // expire / on_hold مانند فاکسیما
        // در bot.pr ستون conecton/on_hold_test نداریم - ساده: expire مستقیم
        $data['expire'] = $expireTimestamp == 0 ? 0 : $expireTimestamp;
        // اگر پنل on_hold فعال باشد، فاکسیما expire=0 و on_hold_expire_duration میگذارد - ما برای سازگاری همان expire را میگذاریم

        $resp = $this->pasarguardRequest('POST', '/api/user', $data);
        handle_create:
        $body = $resp['body'] ?? '';
        $code = (int)($resp['status'] ?? 0);
        $err = $resp['error'] ?? '';
        if ($err) return ['success'=>false,'message'=>$err,'subscription_url'=>'','uuid'=>''];
        if ($code <200 || $code>=300) {
            $j = json_decode($body, true);
            $msg = is_array($j) && isset($j['detail']) ? $j['detail'] : ($j['message'] ?? "HTTP $code");
            return ['success'=>false,'message'=> is_array($msg)?json_encode($msg, JSON_UNESCAPED_UNICODE):$msg,'subscription_url'=>'','uuid'=>''];
        }
        $j = json_decode($body, true);
        $sub = $j['subscription_url'] ?? $j['sub_url'] ?? $j['link'] ?? ($this->baseUrl().'/sub/'.$username);
        return ['success'=>true,'subscription_url'=>$sub,'uuid'=>$j['uuid']??'','message'=>'ok','raw'=>$j];
    }

    public function getUser(string $username): ?array {
        $resp = $this->pasarguardRequest('GET', '/api/user/'.$username);
        $code = (int)($resp['status'] ?? 0);
        if ($code <200 || $code>=300 || !empty($resp['error'])) return null;
        $j = json_decode($resp['body'] ?? '', true);
        if (!is_array($j)) return null;
        return [
            'username'=>$j['username'] ?? $username,
            'data_limit'=>$j['data_limit'] ?? 0,
            'used_traffic'=>$j['used_traffic'] ?? $j['used'] ?? 0,
            'expire'=>$j['expire'] ?? 0,
            'subscription_url'=>$j['subscription_url'] ?? '',
            'status'=>$j['status'] ?? 'active'
        ];
    }

    public function renewUser(string $username, int $addDataBytes, int $addTimeSeconds, bool $resetTraffic = false): array {
        $u = $this->getUser($username);
        if (!$u) return ['success'=>false,'message'=>'کاربر یافت نشد'];
        $newLimit = ($u['data_limit'] ??0) + $addDataBytes;
        $newExpire = ($u['expire'] ?? time()) + $addTimeSeconds;
        // فاکسیما از PUT /api/user/{username} با همین payload استفاده می‌کند
        $resp = $this->pasarguardRequest('PUT', '/api/user/'.$username, ['data_limit'=>$newLimit, 'expire'=>$newExpire]);
        if (($resp['status'] ?? 0) <200 || ($resp['status'] ?? 0)>=300) {
            $resp = $this->pasarguardRequest('POST', '/api/user/'.$username.'/renew', ['data_limit'=>$newLimit, 'expire'=>$newExpire]);
        }
        $code = (int)($resp['status'] ?? 0);
        return ($code>=200 && $code<300) ? ['success'=>true,'message'=>'تمدید شد'] : ['success'=>false,'message'=>$resp['error'] ?? $resp['body']];
    }

    public function deleteUser(string $username): bool {
        $resp = $this->pasarguardRequest('DELETE', '/api/user/'.$username);
        $code = (int)($resp['status'] ?? 0);
        return $code>=200 && $code<300;
    }

    public function toggleUserStatus(string $username, bool $active): bool {
        $resp = $this->pasarguardRequest('PUT', '/api/user/'.$username, ['status'=>$active?'active':'disabled']);
        $code = (int)($resp['status'] ?? 0);
        return $code>=200 && $code<300;
    }

    public function getGroupsOrInbounds(): array {
        $resp = $this->pasarguardRequest('GET', '/api/groups');
        $code = (int)($resp['status'] ?? 0);
        if ($code>=200 && $code<300) {
            $j = json_decode($resp['body'] ?? '', true);
            $groups = $j['groups'] ?? (is_array($j) && array_keys($j)===range(0,count($j)-1) ? $j : []);
            $list=[];
            foreach ((array)$groups as $g) if (is_array($g) && isset($g['id'])) $list[]=['id'=>$g['id'],'name'=>$g['name']??$g['title'],'type'=>'group'];
            if ($list) return $list;
        }
        return [['id'=>'default','name'=>'Default Group','type'=>'group']];
    }

    public function testConnection(): array {
        // دقیقاً مانند pasarguardTestConnection در فاکسیما
        if ($this->authMode() === 'api_key') {
            $apiKey = $this->apiKey();
            if (empty($apiKey)) return ['success'=>false,'message'=>'کلید API پاسارگاد خالی است'];
            $resp = $this->pasarguardRequest('GET', '/api/admin');
            $code = (int)($resp['status'] ?? 0);
            if ($code>=200 && $code<300) return ['success'=>true,'message'=>'اتصال پاسارگاد موفق (API Key)'];
            $j = json_decode($resp['body'] ?? '', true);
            $msg = is_array($j) && isset($j['detail']) ? $j['detail'] : ($resp['error'] ?? "HTTP $code");
            return ['success'=>false,'message'=> is_array($msg)?json_encode($msg, JSON_UNESCAPED_UNICODE):$msg];
        }
        // password mode
        if (trim($this->username())==='' || trim($this->password())==='') return ['success'=>false,'message'=>'نام کاربری یا رمز عبور پاسارگاد خالی است'];
        $tok = $this->getValidToken();
        if (empty($tok['status'])) return ['success'=>false,'message'=>$tok['msg'] ?? 'ورود ناموفق'];
        $resp = $this->pasarguardRequest('GET', '/api/admin');
        $code = (int)($resp['status'] ?? 0);
        if ($code>=200 && $code<300) return ['success'=>true,'message'=>'اتصال پاسارگاد موفق'];
        $j = json_decode($resp['body'] ?? '', true);
        $msg = is_array($j) && isset($j['detail']) ? $j['detail'] : ($resp['error'] ?? "HTTP $code");
        return ['success'=>false,'message'=> is_array($msg)?json_encode($msg, JSON_UNESCAPED_UNICODE):$msg];
    }
}
