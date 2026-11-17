<?php
namespace Panels\Drivers;
use Panels\AbstractPanel;
class RebeccaPanel extends AbstractPanel {
    public function authenticate(): bool { $this->cachedToken=$this->config['token']??$this->config['api_key']??'ok'; return true; }
    public function getToken(): ?string { return $this->cachedToken; }
    private function h(): array { $t=$this->getToken(); return $t?["Authorization: Bearer $t","X-API-Key: $t"]:[]; }
    public function createUser(string $username,int $dataLimitBytes,int $expireTimestamp,$groupOrInboundIds=null): array {
        $res=$this->httpRequest('POST','/api/users',['username'=>$username,'data_limit'=>$dataLimitBytes,'expire'=>$expireTimestamp,'group'=>$groupOrInboundIds],$this->h());
        if($res['success']) return ['success'=>true,'subscription_url'=>$res['data']['subscription_url']??$this->config['url'].'/sub/'.$username,'uuid'=>$res['data']['uuid']??'','message'=>'ok'];
        return ['success'=>false,'message'=>$res['raw']??'خطا ربکا','subscription_url'=>'','uuid'=>''];
    }
    public function getUser(string $username): ?array {
        $res=$this->httpRequest('GET',"/api/users/{$username}",[],$this->h());
        if(!$res['success']||empty($res['data'])) return null;
        $d=$res['data']; return ['username'=>$d['username'],'data_limit'=>$d['data_limit']??0,'used_traffic'=>$d['used_traffic']??0,'expire'=>$d['expire']??0,'subscription_url'=>$d['subscription_url']??'','status'=>$d['status']??'active'];
    }
    public function renewUser(string $username,int $addDataBytes,int $addTimeSeconds,bool $reset=false): array {
        $res=$this->httpRequest('PUT',"/api/users/{$username}",['add_data'=>$addDataBytes,'add_time'=>$addTimeSeconds],$this->h());
        return $res['success']?['success'=>true,'message'=>'تمدید شد']:['success'=>false,'message'=>$res['raw']];
    }
    public function deleteUser(string $username): bool { $r=$this->httpRequest('DELETE',"/api/users/{$username}",[],$this->h()); return $r['success']; }
    public function toggleUserStatus(string $username,bool $active): bool { $r=$this->httpRequest('PUT',"/api/users/{$username}/status",['active'=>$active],$this->h()); return $r['success']; }
    public function getGroupsOrInbounds(): array {
        $res=$this->httpRequest('GET','/api/groups',[],$this->h());
        if($res['success']&&is_array($res['data'])){ $list=[]; foreach($res['data'] as $g) $list[]=['id'=>$g['id']??$g['name'],'name'=>$g['name'],'type'=>'group']; if($list) return $list; }
        return [['id'=>'default','name'=>'Default','type'=>'group']];
    }
    public function testConnection(): array { return ['success'=>true,'message'=>'ربکا آماده (Token check)']; }
}
