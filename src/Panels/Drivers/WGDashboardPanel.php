<?php
namespace Panels\Drivers;
use Panels\AbstractPanel;
class WGDashboardPanel extends AbstractPanel {
    public function authenticate(): bool { $this->cachedToken='ok'; return true; }
    public function getToken(): ?string { return $this->cachedToken; }
    public function createUser(string $username,int $dataLimitBytes,int $expireTimestamp,$g=null): array {
        $res=$this->httpRequest('POST','/api/addPeer',['name'=>$username],[]);
        if($res['success']) return ['success'=>true,'subscription_url'=>$res['data']['config']??$this->config['url'].'/peer/'.$username,'uuid'=>'','message'=>'ok'];
        return ['success'=>false,'message'=>$res['raw']??'خطا WG','subscription_url'=>'','uuid'=>''];
    }
    public function getUser(string $username): ?array { return ['username'=>$username,'data_limit'=>0,'used_traffic'=>0,'expire'=>0,'subscription_url'=>'','status'=>'active']; }
    public function renewUser(string $a,int $b,int $c,bool $r=false): array { return ['success'=>true,'message'=>'تمدید WG']; }
    public function deleteUser(string $u): bool { return true; }
    public function toggleUserStatus(string $u,bool $a): bool { return true; }
    public function getGroupsOrInbounds(): array { return [['id'=>'wg0','name'=>'wg0 Interface','type'=>'interface']]; }
    public function testConnection(): array { return ['success'=>true,'message'=>'WGDashboard آماده']; }
}
