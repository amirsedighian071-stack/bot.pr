<?php
namespace Panels\Drivers;
use Panels\AbstractPanel;

/**
 * کپی دقیق remnawave.php + RemnawaveManager.php فاکسیما
 * - api_key / token via Authorization: Bearer
 * - squads به جای inbounds
 */
class RemnawavePanel extends AbstractPanel {
    private function baseUrl(): string { return rtrim($this->config['url'] ?? '', '/'); }
    private function token(): ?string {
        $t=$this->config['token'] ?? $this->config['api_key'] ?? null;
        $t=is_string($t)?trim($t):$t;
        return $t!==''?$t:null;
    }
    public function authenticate(): bool {
        $t=$this->token();
        if(empty($t)) return false;
        $this->cachedToken=$t;
        return true;
    }
    public function getToken(): ?string {
        if($this->cachedToken) return $this->cachedToken;
        $t=$this->token();
        if($t){ $this->cachedToken=$t; return $t; }
        return null;
    }
    private function h(): array { $t=$this->getToken(); return $t?["Authorization: Bearer $t"]:[]; }

    // تست دقیق مانند فاکسیما - GET /api/squads یا /api/users
    public function testConnection(): array {
        $t=$this->token();
        if(empty($t)) return ['success'=>false,'message'=>'توکن رمناویو خالی است'];
        $base=$this->baseUrl();
        if(empty($base)) return ['success'=>false,'message'=>'آدرس رمناویو خالی است'];
        // فاکسیما از RemnawaveManager->getSquads یا getUsers استفاده می‌کند - ما GET /api/squads را تست می‌کنیم
        $res=$this->httpRequest('GET','/api/squads',[],$this->h());
        if($res['success']) return ['success'=>true,'message'=>'اتصال رمناویو موفق'];
        // fallback به /api/users
        $res2=$this->httpRequest('GET','/api/users',[],$this->h());
        if($res2['success']) return ['success'=>true,'message'=>'اتصال رمناویو موفق'];
        $code=(int)($res['code']??0);
        $msg=$res['error']??$res['raw']??"HTTP $code";
        if($code==401) $msg='احراز هویت رمناویو ناموفق (401)';
        return ['success'=>false,'message'=>$msg];
    }

    public function createUser(string $username,int $dataLimitBytes,int $expireTimestamp,$groupOrInboundIds=null): array {
        if(!$this->authenticate()) return ['success'=>false,'message'=>'توکن رمناویو ناموفق','subscription_url'=>'','uuid'=>''];
        // فاکسیما: remnawave_adduser -> squadUuids
        $squad = $groupOrInboundIds;
        if(is_array($squad)) $squad = implode(',', $squad);
        $squad = trim((string)($squad ?? $this->config['inbounds'] ?? ''));
        if($squad==='') $squad = trim((string)($this->config['remna_squad'] ?? ''));
        $squads = array_values(array_filter(array_map('trim', explode(',', $squad)), fn($v)=>preg_match('/^[0-9a-fA-F]{8}-/', $v)));
        if(empty($squads)) return ['success'=>false,'message'=>'شناسه Squad رمناویو تنظیم نشده','subscription_url'=>'','uuid'=>''];
        $payload=[
            'username'=>$username,
            'trafficLimitBytes'=>(int)$dataLimitBytes,
            'expireAt'=> gmdate('Y-m-d\TH:i:s\Z', $expireTimestamp>0 ? $expireTimestamp : time()+365*10*86400),
            'status'=>'ACTIVE',
            'activeInternalSquads'=>$squads
        ];
        $res=$this->httpRequest('POST','/api/users',$payload,$this->h());
        if($res['success']){
            $d=$res['data'];
            $raw=$d['subscriptionUrl']??$this->baseUrl().'/sub/'.$username;
            return ['success'=>true,'subscription_url'=>\Core\Helper::normalizeSubscriptionUrl((string)$raw,$this->baseUrl()),'uuid'=>$d['id']??'','message'=>'ok'];
        }
        return ['success'=>false,'message'=>$res['raw']??$res['error']??'خطا رمناویو','subscription_url'=>'','uuid'=>''];
    }
    public function getUser(string $username): ?array {
        if(!$this->authenticate()) return null;
        // فاکسیما از remnawave_users جدول + RemnawaveManager->getUserById استفاده می‌کند - ما مستقیم GET /api/users/{username}
        $res=$this->httpRequest('GET',"/api/users/{$username}",[],$this->h());
        if(!$res['success']||empty($res['data'])) return null;
        $d=$res['data'];
        $raw=$d['subscriptionUrl']??'';
        return ['username'=>$d['username'],'data_limit'=>$d['trafficLimit']??0,'used_traffic'=>$d['trafficUsed']??0,'expire'=>strtotime($d['expireAt']??'now'),'subscription_url'=>$raw!==''?\Core\Helper::normalizeSubscriptionUrl((string)$raw,$this->baseUrl()):'','status'=>$d['status']??'active'];
    }
    public function renewUser(string $username,int $addDataBytes,int $addTimeSeconds,bool $r=false): array {
        if(!$this->authenticate()) return ['success'=>false,'message'=>'auth failed'];
        $res=$this->httpRequest('PATCH',"/api/users/{$username}",['addTraffic'=>$addDataBytes,'addTime'=>$addTimeSeconds],$this->h());
        return $res['success']?['success'=>true,'message'=>'تمدید شد']:['success'=>false,'message'=>$res['raw']??$res['error']];
    }
    public function deleteUser(string $username): bool {
        if(!$this->authenticate()) return false;
        $r=$this->httpRequest('DELETE',"/api/users/{$username}",[],$this->h());
        return $r['success'];
    }
    public function toggleUserStatus(string $username,bool $active): bool {
        if(!$this->authenticate()) return false;
        $ep=$active?"/api/users/{$username}/enable":"/api/users/{$username}/disable";
        $r=$this->httpRequest('POST',$ep,[],$this->h());
        if(!$r['success']) $r=$this->httpRequest('PATCH',"/api/users/{$username}",['active'=>$active],$this->h());
        return $r['success'];
    }
    public function getGroupsOrInbounds(): array {
        if(!$this->authenticate()) return [['id'=>'default','name'=>'Default Squad','type'=>'squad']];
        $res=$this->httpRequest('GET','/api/squads',[],$this->h());
        if($res['success']&&is_array($res['data'])){
            $data=$res['data']['squads']??$res['data'];
            $list=[];
            foreach((array)$data as $s) if(is_array($s)&&isset($s['uuid'])) $list[]=['id'=>$s['uuid'],'name'=>($s['name']??$s['uuid']).' ('.($s['info']['membersCount']??0).' عضو)','type'=>'squad'];
            if($list) return $list;
        }
        return [['id'=>'default','name'=>'Default Squad','type'=>'squad']];
    }
}
