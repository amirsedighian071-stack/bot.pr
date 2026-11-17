<?php
namespace Panels\Drivers;
use Panels\AbstractPanel;
use Core\Cache;

/**
 * کپی دقیق Marzneshin فاکسیما - /api/admins/token
 */
class MarzneshinPanel extends AbstractPanel {
    private static array $mem = [];
    private function baseUrl(): string { return rtrim($this->config['url'] ?? '', '/'); }
    private function jwtExp(?string $t): ?int {
        if(!$t) return null;
        $p=explode('.',$t);
        if(count($p)!==3) return null;
        $pl=base64_decode(strtr($p[1],'-_','+/').str_repeat('=',(4-strlen($p[1])%4)%4),true);
        $d=$pl!==false?json_decode($pl,true):null;
        return is_array($d)&&isset($d['exp'])&&is_numeric($d['exp'])?(int)$d['exp']:null;
    }
    private function ttl(string $tok,int $def=43200): int {
        $e=$this->jwtExp($tok);
        if($e!==null) $def=min($def,$e-time()-60);
        return $def;
    }
    private function key(): string { return "marzneshin_token:".md5($this->baseUrl().'|'.$this->config['username']); }
    private function cached(?string $rej=null): ?string {
        $k=$this->key();
        $c=Cache::get($k);
        if(is_string($c)&&$c!==''&&$c!==$rej){ if($this->ttl($c,43200)>0) return $c; Cache::delete($k); }
        $m=self::$mem[$k]??null;
        if(is_array($m)&&isset($m['token'])&&$m['expires_at']>time()&&$m['token']!==$rej) return $m['token'];
        return null;
    }
    private function store(string $tok): void {
        $ttl=$this->ttl($tok,43200);
        if($ttl<=0) return;
        $k=$this->key();
        Cache::set($k,$tok,$ttl);
        self::$mem[$k]=['token'=>$tok,'expires_at'=>time()+$ttl];
        $this->cachedToken=$tok;
    }
    private function clear(?string $only=null): void {
        $k=$this->key();
        if($only===null){ Cache::delete($k); unset(self::$mem[$k]); return; }
        $m=self::$mem[$k]??null;
        if(is_array($m)&&($m['token']??null)===$only) unset(self::$mem[$k]);
        $c=Cache::get($k);
        if($c===$only) Cache::delete($k);
    }
    private function login(): array {
        $url=$this->baseUrl().'/api/admins/token';
        $data=['username'=>$this->config['username'],'password'=>$this->config['password']];
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
        if($err) return ['status'=>false,'msg'=>$err];
        $j=json_decode((string)$body,true);
        if($code>=400){
            $msg=is_array($j)&&isset($j['detail'])?$j['detail']:"HTTP $code";
            return ['status'=>false,'msg'=>is_array($msg)?json_encode($msg,JSON_UNESCAPED_UNICODE):$msg];
        }
        if(!is_array($j)||empty($j['access_token'])) return ['status'=>false,'msg'=>'Login بدون access_token'];
        return ['status'=>true,'access_token'=>(string)$j['access_token']];
    }
    private function getValid(?string $rej=null,bool $force=false): ?array {
        $may=!$force||$rej!==null;
        if($may){ $c=$this->cached($rej); if($c) return ['status'=>true,'access_token'=>$c]; }
        if($this->authenticating) return null;
        $this->authenticating=true;
        try{
            if($may){ $c=$this->cached($rej); if($c) return ['status'=>true,'access_token'=>$c]; }
            $r=$this->login();
            if(empty($r['status'])) return $r;
            $this->store($r['access_token']);
            return $r;
        } finally{ $this->authenticating=false; }
    }
    public function authenticate(): bool {
        if($this->cachedToken && $this->ttl($this->cachedToken,43200)>0) return true;
        $c=$this->cached(); if($c){ $this->cachedToken=$c; return true; }
        $r=$this->getValid();
        if(!empty($r['status'])){ $this->cachedToken=$r['access_token']; return true; }
        return false;
    }
    public function getToken(): ?string {
        if($this->cachedToken && $this->ttl($this->cachedToken,43200)>0) return $this->cachedToken;
        $c=$this->cached(); if($c){ $this->cachedToken=$c; return $c; }
        if($this->authenticating) return null;
        $r=$this->getValid();
        return $r['access_token'] ?? $this->cachedToken;
    }
    private function h(): array { $t=$this->getToken(); return $t?["Authorization: Bearer $t"]:[]; }

    public function createUser(string $username, int $dataLimitBytes, int $expireTimestamp, $groupOrInboundIds = null): array {
        if (!$this->authenticate()) return ['success'=>false,'message'=>'احراز هویت مرزنشین ناموفق','subscription_url'=>'','uuid'=>''];
        $payload=['username'=>$username,'data_limit'=>$dataLimitBytes,'expire'=>$expireTimestamp,'service_ids'=> $groupOrInboundIds ? (is_array($groupOrInboundIds)?$groupOrInboundIds:[$groupOrInboundIds]) : []];
        $res=$this->httpRequest('POST','/api/users',$payload,$this->h());
        if(!$res['success']) return ['success'=>false,'message'=>$res['raw']??$res['error'],'subscription_url'=>'','uuid'=>''];
        $d=$res['data'];
        return ['success'=>true,'subscription_url'=>$d['subscription_url']??$this->baseUrl().'/sub/'.$username,'uuid'=>$d['uuid']??'','message'=>'ok'];
    }
    public function getUser(string $username): ?array {
        if (!$this->authenticate()) return null;
        $res=$this->httpRequest('GET',"/api/users/{$username}",[],$this->h());
        if(!$res['success']||empty($res['data'])) return null;
        $d=$res['data'];
        return ['username'=>$d['username'],'data_limit'=>$d['data_limit']??0,'used_traffic'=>$d['used_traffic']??0,'expire'=>$d['expire']??0,'subscription_url'=>$d['subscription_url']??'','status'=>$d['status']??'active'];
    }
    public function renewUser(string $username, int $addDataBytes, int $addTimeSeconds, bool $resetTraffic=false): array {
        $u=$this->getUser($username); if(!$u) return ['success'=>false,'message'=>'کاربر یافت نشد'];
        $newLimit=($u['data_limit']??0)+$addDataBytes;
        $newExpire=($u['expire']??time())+$addTimeSeconds;
        $res=$this->httpRequest('PUT',"/api/users/{$username}",['data_limit'=>$newLimit,'expire'=>$newExpire],$this->h());
        return $res['success']?['success'=>true,'message'=>'تمدید شد']:['success'=>false,'message'=>$res['raw']??$res['error']];
    }
    public function deleteUser(string $username): bool { if (!$this->authenticate()) return false; $r=$this->httpRequest('DELETE',"/api/users/{$username}",[],$this->h()); return $r['success']; }
    public function toggleUserStatus(string $username,bool $active): bool { $r=$this->httpRequest('PUT',"/api/users/{$username}",['status'=>$active?'active':'disabled'],$this->h()); return $r['success']; }
    public function getGroupsOrInbounds(): array {
        if (!$this->authenticate()) return [['id'=>'default','name'=>'Default Service','type'=>'service']];
        $res=$this->httpRequest('GET','/api/services',[],$this->h());
        if($res['success']&&is_array($res['data'])){
            $list=[]; $data=$res['data']['items']??$res['data'];
            foreach($data as $s) $list[]=['id'=>$s['id'],'name'=>$s['name'],'type'=>'service'];
            if($list) return $list;
        }
        return [['id'=>'default','name'=>'Default Service','type'=>'service']];
    }
    public function testConnection(): array {
        if (!$this->authenticate()) {
            $r=$this->getValid();
            $msg=$r['msg']??'ورود ناموفق';
            return ['success'=>false,'message'=>$msg];
        }
        // تست واقعی مانند فاکسیما: GET /api/services
        $res=$this->httpRequest('GET','/api/services',[],$this->h());
        $code=(int)($res['code']??0);
        if($res['success']) return ['success'=>true,'message'=>'مرزنشین متصل شد'];
        if($code==401) return ['success'=>false,'message'=>'احراز هویت ناموفق (401)'];
        return ['success'=>false,'message'=>($res['error']??$res['raw']??"HTTP $code")];
    }
}
