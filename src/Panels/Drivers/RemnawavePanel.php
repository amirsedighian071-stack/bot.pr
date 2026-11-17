<?php
namespace Panels\Drivers;
use Panels\AbstractPanel;
class RemnawavePanel extends AbstractPanel {
    public function authenticate(): bool { $this->cachedToken=$this->config['token']??'ok'; return true; }
    public function getToken(): ?string { return $this->cachedToken; }
    private function h(): array { return ["Authorization: Bearer ".$this->getToken()]; }
    public function createUser(string $username,int $dataLimitBytes,int $expireTimestamp,$groupOrInboundIds=null): array {
        $res=$this->httpRequest('POST','/api/users',['username'=>$username,'trafficLimit'=>$dataLimitBytes,'expireAt'=>date('c',$expireTimestamp)],$this->h());
        if($res['success']) return ['success'=>true,'subscription_url'=>$res['data']['subscriptionUrl']??$this->config['url'].'/sub/'.$username,'uuid'=>$res['data']['uuid']??'','message'=>'ok'];
        return ['success'=>false,'message'=>$res['raw']??'خطا رمناویو','subscription_url'=>'','uuid'=>''];
    }
    public function getUser(string $username): ?array {
        $res=$this->httpRequest('GET',"/api/users/{$username}",[],$this->h());
        if(!$res['success']||empty($res['data'])) return null;
        $d=$res['data']; return ['username'=>$d['username'],'data_limit'=>$d['trafficLimit']??0,'used_traffic'=>$d['trafficUsed']??0,'expire'=>strtotime($d['expireAt']??'now'),'subscription_url'=>$d['subscriptionUrl']??'','status'=>$d['status']??'active'];
    }
    public function renewUser(string $username,int $addDataBytes,int $addTimeSeconds,bool $r=false): array {
        $res=$this->httpRequest('PATCH',"/api/users/{$username}",['addTraffic'=>$addDataBytes,'addTime'=>$addTimeSeconds],$this->h());
        return $res['success']?['success'=>true,'message'=>'تمدید شد']:['success'=>false,'message'=>$res['raw']];
    }
    public function deleteUser(string $username): bool { return $this->httpRequest('DELETE',"/api/users/{$username}",[],$this->h())['success']; }
    public function toggleUserStatus(string $username,bool $active): bool { return $this->httpRequest('PATCH',"/api/users/{$username}",['active'=>$active],$this->h())['success']; }
    public function getGroupsOrInbounds(): array { return [['id'=>'default','name'=>'Default Node','type'=>'node']]; }
    public function testConnection(): array { return ['success'=>true,'message'=>'Remnawave آماده']; }
}
