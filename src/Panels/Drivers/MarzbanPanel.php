<?php
namespace Panels\Drivers;
use Panels\AbstractPanel;
use Core\Cache;

/**
 * کپی دقیق Marzban.php فاکسیما - توکن via /api/admin/token
 */
class MarzbanPanel extends AbstractPanel {
    private static array $memCache = [];
    private function baseUrl(): string { return rtrim($this->config['url'] ?? '', '/'); }
    private function jwtExp(?string $t): ?int {
        if (!$t) return null;
        $p = explode('.', $t);
        if (count($p)!==3) return null;
        $pl = base64_decode(strtr($p[1], '-_', '+/').str_repeat('=', (4 - strlen($p[1])%4)%4), true);
        $d = $pl!==false ? json_decode($pl,true):null;
        return is_array($d) && isset($d['exp']) && is_numeric($d['exp']) ? (int)$d['exp'] : null;
    }
    private function tokenTtl(string $tok, int $def=3600): int {
        $e = $this->jwtExp($tok);
        if ($e!==null) $def = min($def, $e - time() - 60);
        return $def;
    }
    private function cacheKeyM(): string { return "marzban_token:".md5($this->baseUrl().'|'.$this->config['username']); }

    private function cachedSession(?string $rej=null): ?array {
        $k = $this->cacheKeyM();
        $c = Cache::get($k);
        if (is_array($c) && isset($c['access_token']) && $c['access_token']!==$rej) {
            $ttl = $this->tokenTtl($c['access_token'], 3600);
            if ($ttl>0) return $c;
            Cache::delete($k);
        }
        $m = self::$memCache[$k] ?? null;
        if (is_array($m) && isset($m['access_token']) && $m['access_token']!==$rej && $m['expires_at']>time()) return $m;
        return null;
    }
    private function remember(array $sess): void {
        $k=$this->cacheKeyM();
        $ttl=$this->tokenTtl($sess['access_token']??'',3600);
        if($ttl<=0) return;
        Cache::set($k,$sess,$ttl);
        self::$memCache[$k]=array_merge($sess,['expires_at'=>time()+$ttl]);
    }

    // دقیقاً مانند marzbanLoginLocked فاکسیما
    private function loginLocked(): array {
        $url = $this->baseUrl().'/api/admin/token';
        $data = ['username'=>$this->config['username'],'password'=>$this->config['password']];
        $ch=curl_init($url);
        curl_setopt_array($ch,[
            CURLOPT_RETURNTRANSFER=>true,
            CURLOPT_POST=>true,
            CURLOPT_POSTFIELDS=>http_build_query($data),
            CURLOPT_HTTPHEADER=>['Content-Type: application/x-www-form-urlencoded','accept: application/json'],
            CURLOPT_TIMEOUT=>10, CURLOPT_CONNECTTIMEOUT=>5,
            CURLOPT_SSL_VERIFYPEER=>false, CURLOPT_SSL_VERIFYHOST=>0
        ]);
        $body=curl_exec($ch);
        $err=curl_error($ch);
        $code=curl_getinfo($ch,CURLINFO_HTTP_CODE);
        curl_close($ch);
        if($err) return ['error'=>$err];
        $j=json_decode((string)$body,true);
        if(isset($j['access_token']) && is_string($j['access_token']) && $j['access_token']!==''){
            $sess=['time'=>date('Y/m/d H:i:s'),'access_token'=>$j['access_token']];
            $this->remember($sess);
            return $j;
        }
        if ($code>=400) {
            $msg = is_array($j) && isset($j['detail']) ? $j['detail'] : "HTTP $code";
            return ['error'=> is_array($msg)?json_encode($msg,JSON_UNESCAPED_UNICODE):$msg];
        }
        return ['error'=>'Login response did not include access_token'];
    }

    private function acquireToken(?string $rejected=null): array {
        if ($this->authenticating) return ['error'=>'busy'];
        $this->authenticating=true;
        try {
            $c=$this->cachedSession($rejected);
            if($c) return $c;
            return $this->loginLocked();
        } finally { $this->authenticating=false; }
    }

    public function authenticate(): bool {
        if ($this->cachedToken && $this->tokenTtl($this->cachedToken,3600)>0) return true;
        $c=$this->cachedSession();
        if($c){ $this->cachedToken=$c['access_token']; return true; }
        $res=$this->acquireToken();
        if(!empty($res['access_token'])){ $this->cachedToken=$res['access_token']; return true; }
        return false;
    }
    public function getToken(): ?string {
        if ($this->cachedToken && $this->tokenTtl($this->cachedToken,3600)>0) return $this->cachedToken;
        $c=$this->cachedSession();
        if($c){ $this->cachedToken=$c['access_token']; return $c['access_token']; }
        if($this->authenticating) return null;
        $this->authenticate();
        return $this->cachedToken;
    }
    private function authHeaders(): array {
        $t=$this->getToken();
        return $t?["Authorization: Bearer $t"]:[];
    }
    // مانند marzbanSendAuthorized فاکسیما - 401 retry
    private function sendAuthorized(string $method, string $endpoint, array $data=[], array $headers=[]): array {
        $token=$this->getToken();
        if(!$token) return ['success'=>false,'code'=>0,'error'=>'no token','raw'=>''];
        $res=$this->httpRequest($method,$endpoint,$data,$headers);
        if(($res['code']??0)==401){
            $rej=$token;
            $fresh=$this->acquireToken($rej);
            if(!empty($fresh['access_token']) && $fresh['access_token']!==$rej){
                $this->cachedToken=$fresh['access_token'];
                $headers=$this->authHeaders();
                $res=$this->httpRequest($method,$endpoint,$data,$headers);
            }
        }
        return $res;
    }

    public function createUser(string $username, int $dataLimitBytes, int $expireTimestamp, $groupOrInboundIds = null): array {
        if (!$this->authenticate()) return ['success'=>false,'message'=>'احراز هویت مرزبان ناموفق','subscription_url'=>'','uuid'=>''];
        $inbounds = [];
        if ($groupOrInboundIds) {
            if (is_string($groupOrInboundIds)) $groupOrInboundIds = explode(',', $groupOrInboundIds);
            $inbounds = ['vmess'=>(array)$groupOrInboundIds,'vless'=>(array)$groupOrInboundIds,'trojan'=>(array)$groupOrInboundIds,'shadowsocks'=>(array)$groupOrInboundIds];
        }
        $payload = [
            'username'=>$username,
            'proxies'=>['vmess'=>[],'vless'=>[],'trojan'=>[],'shadowsocks'=>[]],
            'inbounds'=>$inbounds?:['vmess'=>[],'vless'=>[],'trojan'=>[],'shadowsocks'=>[]],
            'expire'=>$expireTimestamp>0?$expireTimestamp:0,
            'data_limit'=> $dataLimitBytes,
            'status'=>'active'
        ];
        $res=$this->httpRequest('POST','/api/user',$payload,$this->authHeaders());
        if(!$res['success']) return ['success'=>false,'message'=>$res['raw']??'خطا مرزبان','subscription_url'=>'','uuid'=>''];
        $d=$res['data'];
        $sub=$d['subscription_url']??($this->baseUrl().'/sub/'.$username);
        return ['success'=>true,'subscription_url'=>$sub,'uuid'=>$d['uuid']??'','message'=>'ok','raw'=>$d];
    }
    public function getUser(string $username): ?array {
        if (!$this->authenticate()) return null;
        $res=$this->httpRequest('GET',"/api/user/{$username}",[],$this->authHeaders());
        if(!$res['success']||empty($res['data'])) return null;
        $d=$res['data'];
        return ['username'=>$d['username'],'data_limit'=>$d['data_limit']??0,'used_traffic'=>$d['used_traffic']??0,'expire'=>$d['expire']??0,'subscription_url'=>$d['subscription_url']??'','status'=>$d['status']??'active'];
    }
    public function renewUser(string $username, int $addDataBytes, int $addTimeSeconds, bool $resetTraffic = false): array {
        $u=$this->getUser($username); if(!$u) return ['success'=>false,'message'=>'کاربر یافت نشد'];
        $newLimit=($u['data_limit']??0)+$addDataBytes;
        $newExpire=($u['expire']??time())+$addTimeSeconds;
        if($newExpire<time()) $newExpire=time()+$addTimeSeconds;
        $payload=['data_limit'=>$newLimit,'expire'=>$newExpire,'status'=>'active'];
        if($resetTraffic) $payload['used_traffic']=0;
        $res=$this->httpRequest('PUT',"/api/user/{$username}",$payload,$this->authHeaders());
        if(!$res['success']) return ['success'=>false,'message'=>$res['raw']];
        return ['success'=>true,'message'=>'تمدید شد'];
    }
    public function deleteUser(string $username): bool {
        if (!$this->authenticate()) return false;
        $res=$this->httpRequest('DELETE',"/api/user/{$username}",[],$this->authHeaders());
        return $res['success'];
    }
    public function toggleUserStatus(string $username, bool $active): bool {
        if (!$this->authenticate()) return false;
        $res=$this->httpRequest('PUT',"/api/user/{$username}",['status'=>$active?'active':'disabled'],$this->authHeaders());
        return $res['success'];
    }
    public function getGroupsOrInbounds(): array {
        if (!$this->authenticate()) return [];
        $res=$this->httpRequest('GET','/api/inbounds',[],$this->authHeaders());
        if(!$res['success']) return [];
        $data=$res['data']??[];
        $list=[];
        foreach($data as $proto=>$inbounds) if(is_array($inbounds)) foreach($inbounds as $ib) $list[]=['id'=>$ib['tag']??$ib['id']??$proto,'name'=>($proto.' - '.($ib['tag']??$ib['remark']??$proto)),'type'=>'inbound','protocol'=>$proto];
        if(empty($list)) $list[]=['id'=>'all','name'=>'All Inbounds','type'=>'inbound'];
        return $list;
    }
    public function testConnection(): array {
        if (!$this->authenticate()) {
            // سعی گرفتن ارور دقیق از login
            $fresh=$this->acquireToken();
            $msg=$fresh['error']??'احراز هویت مرزبان ناموفق';
            return ['success'=>false,'message'=>$msg];
        }
        $res=$this->httpRequest('GET','/api/system',[],$this->authHeaders());
        if($res['success']) return ['success'=>true,'message'=>'اتصال مرزبان موفق'];
        $msg=$res['error']??$res['raw']??'پنل در دسترس نیست';
        // نگاشت HTTP FA مانند فاکسیما
        $code=(int)($res['code']??0);
        if($code==401) $msg='احراز هویت ناموفق؛ نام کاربری/رمز اشتباه';
        elseif($code==404) $msg='آدرس پنل یا مسیر API اشتباه (404)';
        return ['success'=>false,'message'=>$msg." (HTTP $code)"];
    }
}
