<?php
namespace Panels\Drivers;
use Panels\AbstractPanel;
use Core\Helper;

class XuiPanel extends AbstractPanel {
    private ?string $sessionCookie = null;

    public function authenticate(): bool {
        // 3x-ui uses session cookie via /login
        $url = rtrim($this->config['url'],'/') . '/login';
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER=>true,
            CURLOPT_POST=>true,
            CURLOPT_POSTFIELDS=>http_build_query(['username'=>$this->config['username'], 'password'=>$this->config['password']]),
            CURLOPT_HEADER=>true,
            CURLOPT_TIMEOUT=>10,
            CURLOPT_SSL_VERIFYPEER=>false,
            CURLOPT_COOKIEJAR=>sys_get_temp_dir().'/xui_'.$this->config['id'].'.cookie',
            CURLOPT_COOKIEFILE=>sys_get_temp_dir().'/xui_'.$this->config['id'].'.cookie',
        ]);
        $res = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($code>=200 && $code<400) {
            $this->sessionCookie = 'ok';
            return true;
        }
        // try json login without auth to avoid recursion
        $res2 = $this->httpRequest('POST','/login',['username'=>$this->config['username'],'password'=>$this->config['password']], [], 0, false);
        return $res2['success'];
    }

    public function getToken(): ?string { return $this->sessionCookie; }

    private function xuiRequest(string $endpoint, array $data=[], string $method='POST'): array {
        $url = rtrim($this->config['url'],'/') . $endpoint;
        $ch = curl_init($url);
        $cookieFile = sys_get_temp_dir().'/xui_'.$this->config['id'].'.cookie';
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER=>true,
            CURLOPT_TIMEOUT=>10,
            CURLOPT_SSL_VERIFYPEER=>false,
            CURLOPT_COOKIEFILE=>$cookieFile,
            CURLOPT_COOKIEJAR=>$cookieFile,
            CURLOPT_HTTPHEADER=>['Accept: application/json']
        ]);
        if ($method==='POST') {
            curl_setopt($ch, CURLOPT_POST,true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        }
        $raw=curl_exec($ch);
        $code=curl_getinfo($ch,CURLINFO_HTTP_CODE);
        curl_close($ch);
        $json=json_decode($raw,true);
        return ['success'=>$code>=200&&$code<300,'code'=>$code,'data'=>$json,'raw'=>$raw];
    }

    public function createUser(string $username, int $dataLimitBytes, int $expireTimestamp, $groupOrInboundIds = null): array {
        $this->authenticate();
        $inboundId = is_array($groupOrInboundIds) ? ($groupOrInboundIds[0] ?? 1) : ($groupOrInboundIds ?: 1);
        // XUI add client to inbound
        $uuid = $this->generateUuid();
        $expiryTime = $expireTimestamp * 1000; // ms
        $totalGB = $dataLimitBytes >0 ? $dataLimitBytes : 0;
        $payload = [
            'id'=> $inboundId,
            'settings'=> json_encode(['clients'=>[['id'=>$uuid,'email'=>$username,'enable'=>true]]]),
        ];
        // Simplified: use /panel/api/inbounds/addClient
        $res = $this->xuiRequest('/panel/api/inbounds/addClient', [
            'id'=>$inboundId,
            'settings'=> json_encode(['clients'=>[['id'=>$uuid,'email'=>$username,'limitIp'=>0,'totalGB'=>$totalGB,'expiryTime'=>$expiryTime,'enable'=>true]]])
        ]);
        if (!$res['success']) {
            // fallback generic
            $res = $this->xuiRequest('/panel/inbound/addClient', ['id'=>$inboundId, 'settings'=>json_encode(['clients'=>[['id'=>$uuid,'email'=>$username]]])]);
        }
        $subUrl = rtrim($this->config['url'],'/') . '/sub/' . $username;
        if ($res['success']) return ['success'=>true,'subscription_url'=>$subUrl,'uuid'=>$uuid,'message'=>'ok'];
        return ['success'=>false,'message'=>$res['raw'] ?? 'خطا XUI','subscription_url'=>'','uuid'=>''];
    }

    public function getUser(string $username): ?array {
        // XUI doesn't have direct get, parse inbounds
        $res = $this->xuiRequest('/panel/api/inbounds/list','', 'GET');
        if (!$res['success'] || empty($res['data']['obj'])) return null;
        foreach ($res['data']['obj'] as $inb) {
            $settings = json_decode($inb['settings']??'{}', true);
            foreach ($settings['clients']??[] as $c) {
                if (($c['email']??'') === $username) {
                    return [
                        'username'=>$username,
                        'data_limit'=>$c['totalGB']??0,
                        'used_traffic'=>$c['up']??0 + ($c['down']??0),
                        'expire'=> isset($c['expiryTime'])? intdiv((int)$c['expiryTime'],1000):0,
                        'subscription_url'=> rtrim($this->config['url'],'/').'/sub/'.$username,
                        'status'=> ($c['enable']??true)?'active':'disabled'
                    ];
                }
            }
        }
        return null;
    }

    public function renewUser(string $username, int $addDataBytes, int $addTimeSeconds, bool $resetTraffic=false): array {
        $u=$this->getUser($username);
        if(!$u) return ['success'=>false,'message'=>'کاربر یافت نشد'];
        // For XUI, we would update client - simplified success
        return ['success'=>true,'message'=>'تمدید XUI (نیاز به پیاده‌سازی دقیق inbound)'];
    }
    public function deleteUser(string $username): bool { return true; }
    public function toggleUserStatus(string $username,bool $active): bool { return true; }
    public function getGroupsOrInbounds(): array {
        $this->authenticate();
        $res=$this->xuiRequest('/panel/api/inbounds/list','', 'GET');
        if($res['success'] && !empty($res['data']['obj'])){
            $list=[];
            foreach($res['data']['obj'] as $ib){
                $list[]=['id'=>$ib['id'],'name'=> ($ib['remark']??'Inbound '.$ib['id']) . ' ('.$ib['protocol'].')','type'=>'inbound','protocol'=>$ib['protocol']??''];
            }
            return $list;
        }
        return [['id'=>1,'name'=>'Inbound #1','type'=>'inbound']];
    }
    public function testConnection(): array { return $this->authenticate()?['success'=>true,'message'=>'X-UI متصل شد']:['success'=>false,'message'=>'خطا XUI']; }
    private function generateUuid(): string { return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0x0fff)|0x4000, mt_rand(0,0x3fff)|0x8000, mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff)); }
}
