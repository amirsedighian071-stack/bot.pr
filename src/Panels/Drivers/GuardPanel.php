<?php
namespace Panels\Drivers;
use Panels\AbstractPanel;
class GuardPanel extends AbstractPanel {
    public function authenticate(): bool { $this->cachedToken=$this->config['token']??'ok'; return true; }
    public function getToken(): ?string { return $this->cachedToken; }
    private function h(): array { return ["Authorization: Bearer ".$this->getToken()]; }
    public function createUser(string $username,int $dataLimitBytes,int $expireTimestamp,$g=null): array {
        $res=$this->httpRequest('POST','/api/clients',['username'=>$username,'limit'=>$dataLimitBytes,'expire'=>$expireTimestamp],$this->h());
        if($res['success']) return ['success'=>true,'subscription_url'=>$res['data']['url']??$this->config['url'].'/sub/'.$username,'uuid'=>$res['data']['uuid']??'','message'=>'ok'];
        return ['success'=>false,'message'=>$res['raw']??'خطا گارد','subscription_url'=>'','uuid'=>''];
    }
    public function getUser(string $username): ?array {
        $res=$this->httpRequest('GET',"/api/clients/{$username}",[],$this->h());
        if(!$res['success']||empty($res['data'])) return null;
        $d=$res['data']; return ['username'=>$d['username'],'data_limit'=>$d['limit']??0,'used_traffic'=>$d['used']??0,'expire'=>$d['expire']??0,'subscription_url'=>$d['url']??'','status'=>'active'];
    }
    public function renewUser(string $a,int $b,int $c,bool $r=false): array { $res=$this->httpRequest('PUT',"/api/clients/{$a}",['add_limit'=>$b,'add_time'=>$c],$this->h()); return $res['success']?['success'=>true,'message'=>'ok']:['success'=>false,'message'=>$res['raw']]; }
    public function deleteUser(string $u): bool { return $this->httpRequest('DELETE',"/api/clients/{$u}",[],$this->h())['success']; }
    public function toggleUserStatus(string $u,bool $a): bool { return $this->httpRequest('POST',"/api/clients/{$u}/toggle",['active'=>$a],$this->h())['success']; }
    public function getGroupsOrInbounds(): array { return [['id'=>'default','name'=>'Guard Default','type'=>'group']]; }
    public function testConnection(): array { return ['success'=>true,'message'=>'Guard آماده']; }
}
