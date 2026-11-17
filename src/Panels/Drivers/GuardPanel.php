<?php
namespace Panels\Drivers;
use Panels\AbstractPanel;

/**
 * کپی دقیق guard.php فاکسیما - Guard uses X-API-Key or Bearer via core
 */
class GuardPanel extends AbstractPanel {
    private function baseUrl(): string {
        // فاکسیما GUARD_CORE_BASE_URL = https://core.erfjab.com اگر url خالی باشد
        $u = trim($this->config['url'] ?? '');
        if ($u === '') return 'https://core.erfjab.com';
        return rtrim($u, '/');
    }
    private function apiKey(): ?string {
        $k = $this->config['api_key'] ?? $this->config['token'] ?? null;
        $k = is_string($k) ? trim($k) : null;
        return $k !== '' ? $k : null;
    }
    public function authenticate(): bool {
        $k=$this->apiKey();
        if(empty($k)) return false;
        $this->cachedToken=$k;
        return true;
    }
    public function getToken(): ?string {
        $k=$this->apiKey();
        if($k){ $this->cachedToken=$k; return $k; }
        return $this->cachedToken;
    }
    private function h(): array {
        $k=$this->apiKey();
        return $k?["Authorization: Bearer $k", "X-API-Key: $k"]:[];
    }
    public function createUser(string $username,int $dataLimitBytes,int $expireTimestamp,$g=null): array {
        if(!$this->authenticate()) return ['success'=>false,'message'=>'کلید Guard خالی است','subscription_url'=>'','uuid'=>''];
        $res=$this->httpRequest('POST','/api/clients',['username'=>$username,'limit'=>$dataLimitBytes,'expire'=>$expireTimestamp],$this->h());
        if($res['success']) {
            $raw=$res['data']['url']??$this->baseUrl().'/sub/'.$username;
            return ['success'=>true,'subscription_url'=>\Core\Helper::normalizeSubscriptionUrl((string)$raw,$this->baseUrl()),'uuid'=>$res['data']['uuid']??'','message'=>'ok'];
        }
        // فاکسیما Guard از /api/v1/users نیز استفاده می‌کند
        $res2=$this->httpRequest('POST','/api/v1/users',['username'=>$username,'data_limit'=>$dataLimitBytes,'expire'=>$expireTimestamp],$this->h());
        if($res2['success']) {
            $raw=$res2['data']['subscription_url']??$this->baseUrl().'/sub/'.$username;
            return ['success'=>true,'subscription_url'=>\Core\Helper::normalizeSubscriptionUrl((string)$raw,$this->baseUrl()),'uuid'=>$res2['data']['uuid']??'','message'=>'ok'];
        }
        return ['success'=>false,'message'=>$res['raw']??$res['error']??'خطا گارد','subscription_url'=>'','uuid'=>''];
    }
    public function getUser(string $username): ?array {
        if(!$this->authenticate()) return null;
        $res=$this->httpRequest('GET',"/api/clients/{$username}",[],$this->h());
        if(!$res['success']||empty($res['data'])) {
            $res=$this->httpRequest('GET',"/api/v1/users/{$username}",[],$this->h());
            if(!$res['success']||empty($res['data'])) return null;
        }
        $d=$res['data']; return ['username'=>$d['username'],'data_limit'=>$d['limit']??$d['data_limit']??0,'used_traffic'=>$d['used']??0,'expire'=>$d['expire']??0,'subscription_url'=>$d['url']??$d['subscription_url']??'','status'=>'active'];
    }
    public function renewUser(string $a,int $b,int $c,bool $r=false): array {
        $res=$this->httpRequest('PUT',"/api/clients/{$a}",['add_limit'=>$b,'add_time'=>$c],$this->h());
        if(!$res['success']) $res=$this->httpRequest('PUT',"/api/v1/users/{$a}",['data_limit'=>$b,'expire'=>time()+$c],$this->h());
        return $res['success']?['success'=>true,'message'=>'ok']:['success'=>false,'message'=>$res['raw']??$res['error']];
    }
    public function deleteUser(string $u): bool {
        $r=$this->httpRequest('DELETE',"/api/clients/{$u}",[],$this->h());
        if(!$r['success']) $r=$this->httpRequest('DELETE',"/api/v1/users/{$u}",[],$this->h());
        return $r['success'];
    }
    public function toggleUserStatus(string $u,bool $a): bool {
        $r=$this->httpRequest('POST',"/api/clients/{$u}/toggle",['active'=>$a],$this->h());
        if(!$r['success']) $r=$this->httpRequest('PATCH',"/api/v1/users/{$u}",['status'=>$a?'active':'disabled'],$this->h());
        return $r['success'];
    }
    public function getGroupsOrInbounds(): array { return [['id'=>'default','name'=>'Guard Default','type'=>'group']]; }
    public function testConnection(): array {
        // دقیقاً مانند guardTestConnection فاکسیما - GET /api/admins/current
        $base=$this->baseUrl(); $key=$this->apiKey();
        if(empty($key)) return ['success'=>false,'message'=>'کلید Guard خالی است'];
        $res=$this->httpRequest('GET','/api/admins/current',[],$this->h());
        $code=(int)($res['code']??0);
        if($res['success']) return ['success'=>true,'message'=>'اتصال گارد موفق'];
        $j=json_decode($res['raw']??'',true);
        $msg=is_array($j)&&isset($j['message'])?$j['message']:($j['detail']??$res['error']??"HTTP $code");
        if($code==401) $msg='احراز هویت گارد ناموفق (401)';
        return ['success'=>false,'message'=> is_array($msg)?json_encode($msg,JSON_UNESCAPED_UNICODE):$msg];
    }
}
