<?php
namespace Panels\Drivers;
use Panels\AbstractPanel;

/**
 * کپی دقیق rebecca.php فاکسیما
 * - api_key via Authorization: Bearer
 * - test via GET /api/system
 */
class RebeccaPanel extends AbstractPanel {
    private function baseUrl(): string { return rtrim($this->config['url'] ?? $this->config['url_panel'] ?? '', '/'); }
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
        if($this->cachedToken) return $this->cachedToken;
        $k=$this->apiKey();
        if($k) { $this->cachedToken=$k; return $k; }
        return null;
    }
    private function h(): array {
        $k=$this->apiKey();
        return $k?["Authorization: Bearer $k"]:[] ;
    }
    // دقیقاً مانند rebeccaApiRequest فاکسیما
    private function apiRequest(string $method, string $endpoint, $payload=null): array {
        $url=$this->baseUrl().$endpoint;
        $headers=['accept: application/json'];
        if($payload!==null) $headers[]='Content-Type: application/json';
        $headers = array_merge($headers, $this->h());
        $data = $payload!==null && is_array($payload) ? json_encode($payload) : $payload;
        $res=$this->httpRequest($method,$endpoint,$payload??[],$this->h());
        // httpRequest ما JSON encode می‌کند - برای سازگاری همان را استفاده می‌کنیم
        // اما برای تست اصلی، کافی است status code را چک کنیم
        return $res;
    }
    public function createUser(string $username,int $dataLimitBytes,int $expireTimestamp,$groupOrInboundIds=null): array {
        if(!$this->authenticate()) return ['success'=>false,'message'=>'API key ریبکا خالی است','subscription_url'=>'','uuid'=>''];
        // فاکسیما: rebeccaCreateUser -> POST /api/v2/users یا /api/users با service_id
        $serviceId = $groupOrInboundIds;
        if(is_array($serviceId)) $serviceId = $serviceId[0] ?? null;
        if(empty($serviceId) && !empty($this->config['inbounds'])) {
            $dec=json_decode($this->config['inbounds'],true);
            $serviceId=$dec[0] ?? null;
        }
        $payload=[
            'username'=>$username,
            'data_limit'=>$dataLimitBytes,
            'expire'=>$expireTimestamp,
            'service_id'=> $serviceId ? (int)$serviceId : null,
            'note'=>''
        ];
        // سعی endpointهای فاکسیما
        foreach(['/api/v2/users','/api/users'] as $ep){
            $res=$this->httpRequest('POST',$ep,$payload,$this->h());
            if($res['success']) {
                $d=$res['data'];
                $raw=$d['subscription_url']??$d['sub_url']??$this->baseUrl().'/sub/'.$username;
                return ['success'=>true,'subscription_url'=>\Core\Helper::normalizeSubscriptionUrl((string)$raw,$this->baseUrl()),'uuid'=>$d['uuid']??'','message'=>'ok'];
            }
        }
        return ['success'=>false,'message'=>$res['raw']??$res['error']??'خطا ریبکا','subscription_url'=>'','uuid'=>''];
    }
    public function getUser(string $username): ?array {
        if(!$this->authenticate()) return null;
        $res=$this->httpRequest('GET',"/api/users/{$username}",[],$this->h());
        if(!$res['success']||empty($res['data'])) {
            $res=$this->httpRequest('GET',"/api/v2/users/{$username}",[],$this->h());
            if(!$res['success']||empty($res['data'])) return null;
        }
        $d=$res['data'];
        $raw=$d['subscription_url']??$d['sub_url']??'';
        return ['username'=>$d['username'],'data_limit'=>$d['data_limit']??0,'used_traffic'=>$d['used_traffic']??0,'expire'=>$d['expire']??0,'subscription_url'=>$raw!==''?\Core\Helper::normalizeSubscriptionUrl((string)$raw,$this->baseUrl()):'','status'=>$d['status']??'active'];
    }
    public function renewUser(string $username,int $addDataBytes,int $addTimeSeconds,bool $reset=false): array {
        if(!$this->authenticate()) return ['success'=>false,'message'=>'auth failed'];
        $res=$this->httpRequest('PUT',"/api/users/{$username}",['add_data'=>$addDataBytes,'add_time'=>$addTimeSeconds],$this->h());
        if(!$res['success']) $res=$this->httpRequest('PATCH',"/api/users/{$username}",['add_data'=>$addDataBytes,'add_time'=>$addTimeSeconds],$this->h());
        return $res['success']?['success'=>true,'message'=>'تمدید شد']:['success'=>false,'message'=>$res['raw']??$res['error']];
    }
    public function deleteUser(string $username): bool {
        if(!$this->authenticate()) return false;
        $r=$this->httpRequest('DELETE',"/api/users/{$username}",[],$this->h());
        if(!$r['success']) $r=$this->httpRequest('DELETE',"/api/v2/users/{$username}",[],$this->h());
        return $r['success'];
    }
    public function toggleUserStatus(string $username,bool $active): bool {
        if(!$this->authenticate()) return false;
        $r=$this->httpRequest('PUT',"/api/users/{$username}/status",['active'=>$active],$this->h());
        if(!$r['success']) $r=$this->httpRequest('PATCH',"/api/users/{$username}",['status'=>$active?'active':'disabled'],$this->h());
        return $r['success'];
    }
    public function getGroupsOrInbounds(): array {
        if(!$this->authenticate()) return [['id'=>'default','name'=>'Default','type'=>'group']];
        foreach(['/api/v2/services','/api/services'] as $ep){
            $res=$this->httpRequest('GET',$ep,[],$this->h());
            if($res['success']&&is_array($res['data'])){
                $data=$res['data']['services']??$res['data']['items']??$res['data'];
                $list=[];
                foreach((array)$data as $g) if(is_array($g)&&isset($g['id'])) $list[]=['id'=>$g['id'],'name'=>$g['name']??$g['title'],'type'=>'service'];
                if($list) return $list;
            }
        }
        return [['id'=>'default','name'=>'Default','type'=>'service']];
    }
    public function testConnection(): array {
        // دقیقاً مانند rebeccaTestConnection فاکسیما
        $base=$this->baseUrl();
        $key=$this->apiKey();
        if(empty($key)) return ['success'=>false,'message'=>'کلید API ریبکا خالی است'];
        if(empty($base)) return ['success'=>false,'message'=>'آدرس ریبکا خالی است'];
        $res=$this->httpRequest('GET','/api/system',[],$this->h());
        $code=(int)($res['code']??0);
        if($res['success']) return ['success'=>true,'message'=>'اتصال ریبکا موفق'];
        $j=json_decode($res['raw']??'',true);
        $msg=is_array($j)&&isset($j['message'])?$j['message']:($j['detail']??$res['error']??"HTTP $code");
        if($code==401) $msg='احراز هویت ریبکا ناموفق (401)';
        return ['success'=>false,'message'=> is_array($msg)?json_encode($msg,JSON_UNESCAPED_UNICODE):$msg." (HTTP $code)"];
    }
}
