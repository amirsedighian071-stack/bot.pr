<?php
namespace Panels\Drivers;
use Panels\AbstractPanel;
use Core\Cache;

class MarzbanPanel extends AbstractPanel {
    public function authenticate(): bool {
        if ($this->authenticating) return false;
        $this->authenticating = true;
        try {
            $cacheKey = $this->cacheKey('token');
            $cached = Cache::get($cacheKey);
            if ($cached) { $this->cachedToken = $cached; return true; }
            if ($this->cachedToken) return true;

            $res = $this->httpRequest('POST', '/api/admin/token', [
                'username' => $this->config['username'],
                'password' => $this->config['password']
            ], [], 0, false);
            if (!$res['success'] || empty($res['data']['access_token'])) {
                $url = rtrim($this->config['url'],'/') . '/api/admin/token';
                $ch = curl_init($url);
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER=>true,
                    CURLOPT_POST=>true,
                    CURLOPT_POSTFIELDS=>http_build_query(['username'=>$this->config['username'],'password'=>$this->config['password']]),
                    CURLOPT_HTTPHEADER=>['Content-Type: application/x-www-form-urlencoded'],
                    CURLOPT_TIMEOUT=>10, CURLOPT_SSL_VERIFYPEER=>false
                ]);
                $raw = curl_exec($ch); curl_close($ch);
                $json = json_decode($raw, true);
                if (empty($json['access_token'])) return false;
                $res['data'] = $json;
            }
            $token = $res['data']['access_token'];
            $this->cachedToken = $token;
            Cache::set($cacheKey, $token, 3600*12);
            return true;
        } finally {
            $this->authenticating = false;
        }
    }

    public function getToken(): ?string {
        if ($this->cachedToken) return $this->cachedToken;
        $cached = Cache::get($this->cacheKey('token'));
        if ($cached) { $this->cachedToken=$cached; return $cached; }
        if ($this->authenticating) return null;
        $this->authenticate();
        return $this->cachedToken;
    }

    private function authHeaders(): array {
        $t = $this->getToken();
        return $t ? ["Authorization: Bearer $t"] : [];
    }

    public function createUser(string $username, int $dataLimitBytes, int $expireTimestamp, $groupOrInboundIds = null): array {
        if (!$this->authenticate()) return ['success'=>false,'message'=>'احراز هویت مرزبان ناموفق','subscription_url'=>'','uuid'=>''];
        $expire = $expireTimestamp > 0 ? $expireTimestamp : 0;
        $inbounds = [];
        if ($groupOrInboundIds) {
            if (is_string($groupOrInboundIds)) $groupOrInboundIds = explode(',', $groupOrInboundIds);
            $inbounds = ['vmess'=> (array)$groupOrInboundIds, 'vless'=> (array)$groupOrInboundIds, 'trojan'=> (array)$groupOrInboundIds, 'shadowsocks'=> (array)$groupOrInboundIds];
        }
        $payload = [
            'username' => $username,
            'proxies' => ['vmess'=>[], 'vless'=>[], 'trojan'=>[], 'shadowsocks'=>[]],
            'inbounds' => $inbounds ?: ['vmess'=>[], 'vless'=>[], 'trojan'=>[], 'shadowsocks'=>[]],
            'expire' => $expire,
            'data_limit' => $dataLimitBytes,
            'status' => 'active'
        ];
        $res = $this->httpRequest('POST', '/api/user', $payload, $this->authHeaders());
        if (!$res['success']) {
            return ['success'=>false, 'message'=> $res['raw'] ?? 'خطا در ساخت کاربر مرزبان', 'subscription_url'=>'', 'uuid'=>''];
        }
        $data = $res['data'];
        $subUrl = $data['subscription_url'] ?? ($this->config['url'] . '/sub/' . $username);
        return ['success'=>true, 'subscription_url'=>$subUrl, 'uuid'=>$data['uuid'] ?? '', 'message'=>'ok', 'raw'=>$data];
    }

    public function getUser(string $username): ?array {
        if (!$this->authenticate()) return null;
        $res = $this->httpRequest('GET', "/api/user/{$username}", [], $this->authHeaders());
        if (!$res['success'] || empty($res['data'])) return null;
        $d = $res['data'];
        return [
            'username'=>$d['username'],
            'data_limit'=>$d['data_limit'] ?? 0,
            'used_traffic'=>$d['used_traffic'] ?? 0,
            'expire'=>$d['expire'] ?? 0,
            'subscription_url'=>$d['subscription_url'] ?? '',
            'status'=>$d['status'] ?? 'active'
        ];
    }

    public function renewUser(string $username, int $addDataBytes, int $addTimeSeconds, bool $resetTraffic = false): array {
        $u = $this->getUser($username);
        if (!$u) return ['success'=>false, 'message'=>'کاربر یافت نشد'];
        $newLimit = ($u['data_limit'] ?? 0) + $addDataBytes;
        $newExpire = ($u['expire'] ?? time()) + $addTimeSeconds;
        if ($newExpire < time()) $newExpire = time() + $addTimeSeconds;
        $payload = ['data_limit'=>$newLimit, 'expire'=>$newExpire, 'status'=>'active'];
        if ($resetTraffic) $payload['used_traffic'] = 0;
        $res = $this->httpRequest('PUT', "/api/user/{$username}", $payload, $this->authHeaders());
        if (!$res['success']) return ['success'=>false, 'message'=>$res['raw']];
        return ['success'=>true, 'message'=>'تمدید شد'];
    }

    public function deleteUser(string $username): bool {
        if (!$this->authenticate()) return false;
        $res = $this->httpRequest('DELETE', "/api/user/{$username}", [], $this->authHeaders());
        return $res['success'];
    }

    public function toggleUserStatus(string $username, bool $active): bool {
        if (!$this->authenticate()) return false;
        $res = $this->httpRequest('PUT', "/api/user/{$username}", ['status'=>$active?'active':'disabled'], $this->authHeaders());
        return $res['success'];
    }

    public function getGroupsOrInbounds(): array {
        if (!$this->authenticate()) return [];
        $res = $this->httpRequest('GET', '/api/inbounds', [], $this->authHeaders());
        if (!$res['success']) return [];
        $data = $res['data'] ?? [];
        $list = [];
        foreach ($data as $proto => $inbounds) {
            if (is_array($inbounds)) foreach ($inbounds as $ib) {
                $list[] = ['id'=>$ib['tag'] ?? $ib['id'] ?? $proto, 'name'=> ($proto . ' - ' . ($ib['tag'] ?? $ib['remark'] ?? $proto)), 'type'=>'inbound', 'protocol'=>$proto];
            }
        }
        if (empty($list)) $list[] = ['id'=>'all', 'name'=>'All Inbounds', 'type'=>'inbound'];
        return $list;
    }

    public function testConnection(): array {
        if (!$this->authenticate()) return ['success'=>false, 'message'=>'احراز هویت مرزبان ناموفق'];
        $res = $this->httpRequest('GET', '/api/system', [], $this->authHeaders());
        if ($res['success']) return ['success'=>true, 'message'=>'اتصال موفق'];
        return ['success'=>false, 'message'=>'پنل در دسترس نیست: ' . ($res['error'] ?? $res['raw'])];
    }
}
