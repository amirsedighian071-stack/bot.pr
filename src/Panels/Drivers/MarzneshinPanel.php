<?php
namespace Panels\Drivers;
use Panels\AbstractPanel;
use Core\Cache;

class MarzneshinPanel extends AbstractPanel {
    public function authenticate(): bool {
        if ($this->authenticating) return false;
        $this->authenticating = true;
        try {
            $cached = Cache::get($this->cacheKey('token'));
            if ($cached) { $this->cachedToken=$cached; return true; }
            if ($this->cachedToken) return true;
            $res = $this->httpRequest('POST', '/api/admins/token', [
                'username'=>$this->config['username'],
                'password'=>$this->config['password']
            ], [], 0, false);
            if (!$res['success'] || empty($res['data']['access_token'])) {
                $url = rtrim($this->config['url'],'/').'/api/admins/token';
                $ch=curl_init($url);
                curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true, CURLOPT_POST=>true, CURLOPT_POSTFIELDS=>http_build_query(['username'=>$this->config['username'],'password'=>$this->config['password']]), CURLOPT_HTTPHEADER=>['Content-Type: application/x-www-form-urlencoded'], CURLOPT_TIMEOUT=>10, CURLOPT_SSL_VERIFYPEER=>false]);
                $raw=curl_exec($ch); curl_close($ch);
                $j=json_decode($raw,true);
                if(empty($j['access_token'])) return false;
                $res['data']=$j;
            }
            $this->cachedToken=$res['data']['access_token'];
            Cache::set($this->cacheKey('token'), $this->cachedToken, 3600*12);
            return true;
        } finally {
            $this->authenticating = false;
        }
    }
    public function getToken(): ?string {
        if($this->cachedToken) return $this->cachedToken;
        $cached = Cache::get($this->cacheKey('token'));
        if ($cached) { $this->cachedToken=$cached; return $cached; }
        if ($this->authenticating) return null;
        $this->authenticate();
        return $this->cachedToken;
    }
    private function h(): array { $t=$this->getToken(); return $t?["Authorization: Bearer $t"]:[]; }

    public function createUser(string $username, int $dataLimitBytes, int $expireTimestamp, $groupOrInboundIds = null): array {
        if (!$this->authenticate()) return ['success'=>false,'message'=>'احراز هویت مرزنشین ناموفق','subscription_url'=>'','uuid'=>''];
        $payload=['username'=>$username,'data_limit'=>$dataLimitBytes,'expire'=>$expireTimestamp,'service_ids'=> $groupOrInboundIds ? (is_array($groupOrInboundIds)?$groupOrInboundIds:[$groupOrInboundIds]) : []];
        $res=$this->httpRequest('POST','/api/users',$payload,$this->h());
        if(!$res['success']) return ['success'=>false,'message'=>$res['raw'],'subscription_url'=>'','uuid'=>''];
        $d=$res['data'];
        return ['success'=>true,'subscription_url'=>$d['subscription_url']??$this->config['url'].'/sub/'.$username,'uuid'=>$d['uuid']??'','message'=>'ok'];
    }
    public function getUser(string $username): ?array {
        if (!$this->authenticate()) return null;
        $res=$this->httpRequest('GET',"/api/users/{$username}",[],$this->h());
        if(!$res['success']||empty($res['data'])) return null;
        $d=$res['data'];
        return ['username'=>$d['username'],'data_limit'=>$d['data_limit']??0,'used_traffic'=>$d['used_traffic']??0,'expire'=>$d['expire']??0,'subscription_url'=>$d['subscription_url']??'','status'=>$d['status']??'active'];
    }
    public function renewUser(string $username, int $addDataBytes, int $addTimeSeconds, bool $resetTraffic=false): array {
        $u=$this->getUser($username);
        if(!$u) return ['success'=>false,'message'=>'کاربر یافت نشد'];
        $newLimit=($u['data_limit']??0)+$addDataBytes;
        $newExpire=($u['expire']??time())+$addTimeSeconds;
        $res=$this->httpRequest('PUT',"/api/users/{$username}",['data_limit'=>$newLimit,'expire'=>$newExpire],$this->h());
        return $res['success']?['success'=>true,'message'=>'تمدید شد']:['success'=>false,'message'=>$res['raw']];
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
    public function testConnection(): array { return $this->authenticate()?['success'=>true,'message'=>'مرزنشین متصل شد']:['success'=>false,'message'=>'خطا مرزنشین']; }
}
