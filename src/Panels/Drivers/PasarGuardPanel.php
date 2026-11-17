<?php
namespace Panels\Drivers;
use Panels\AbstractPanel;
use Core\Cache;

class PasarGuardPanel extends AbstractPanel {
    public function authenticate(): bool {
        // PasarGuard uses token/API key directly
        if (!empty($this->config['token'])) {
            $this->cachedToken = $this->config['token'];
            return true;
        }
        // Try login to get token
        $res = $this->httpRequest('POST', '/api/auth/login', [
            'username'=>$this->config['username'],
            'password'=>$this->config['password']
        ]);
        if (!$res['success'] || empty($res['data']['token'])) return false;
        $this->cachedToken = $res['data']['token'];
        Cache::set($this->cacheKey('token'), $this->cachedToken, 3600*6);
        return true;
    }

    public function getToken(): ?string {
        if ($this->cachedToken) return $this->cachedToken;
        $cached = Cache::get($this->cacheKey('token'));
        if ($cached) { $this->cachedToken=$cached; return $cached; }
        $this->authenticate();
        return $this->cachedToken;
    }

    private function headers(): array {
        $t = $this->getToken();
        return $t ? ["Authorization: Bearer $t"] : [];
    }

    public function createUser(string $username, int $dataLimitBytes, int $expireTimestamp, $groupOrInboundIds = null): array {
        $this->authenticate();
        $payload = [
            'username'=>$username,
            'data_limit'=>$dataLimitBytes,
            'expire'=> $expireTimestamp,
            'group_id'=> $groupOrInboundIds ?: null,
            'status'=>'active'
        ];
        // Try common PasarGuard endpoints
        $endpoints = ['/api/users', '/api/user', '/api/clients'];
        foreach ($endpoints as $ep) {
            $res = $this->httpRequest('POST', $ep, $payload, $this->headers());
            if ($res['success']) {
                $d = $res['data'];
                $sub = $d['subscription_url'] ?? $d['sub_url'] ?? $d['link'] ?? ($this->config['url'].'/sub/'.$username);
                return ['success'=>true, 'subscription_url'=>$sub, 'uuid'=>$d['uuid'] ?? '', 'message'=>'ok'];
            }
        }
        return ['success'=>false, 'message'=> 'خطا پاسارگاد: ' . ($res['raw'] ?? 'unknown'), 'subscription_url'=>'','uuid'=>''];
    }

    public function getUser(string $username): ?array {
        $this->authenticate();
        foreach (['/api/users/'.$username, '/api/user/'.$username] as $ep) {
            $res = $this->httpRequest('GET', $ep, [], $this->headers());
            if ($res['success'] && !empty($res['data'])) {
                $d = $res['data'];
                return [
                    'username'=>$d['username'] ?? $username,
                    'data_limit'=>$d['data_limit'] ?? 0,
                    'used_traffic'=>$d['used_traffic'] ?? $d['used'] ?? 0,
                    'expire'=>$d['expire'] ?? 0,
                    'subscription_url'=>$d['subscription_url'] ?? '',
                    'status'=>$d['status'] ?? 'active'
                ];
            }
        }
        return null;
    }

    public function renewUser(string $username, int $addDataBytes, int $addTimeSeconds, bool $resetTraffic = false): array {
        $u = $this->getUser($username);
        if (!$u) return ['success'=>false,'message'=>'کاربر یافت نشد'];
        $newLimit = ($u['data_limit'] ??0) + $addDataBytes;
        $newExpire = ($u['expire'] ?? time()) + $addTimeSeconds;
        $res = $this->httpRequest('PUT', "/api/users/{$username}", ['data_limit'=>$newLimit, 'expire'=>$newExpire], $this->headers());
        if (!$res['success']) $res = $this->httpRequest('POST', "/api/users/{$username}/renew", ['data_limit'=>$newLimit, 'expire'=>$newExpire], $this->headers());
        return $res['success'] ? ['success'=>true,'message'=>'تمدید شد'] : ['success'=>false,'message'=>$res['raw']];
    }

    public function deleteUser(string $username): bool {
        $this->authenticate();
        $res = $this->httpRequest('DELETE', "/api/users/{$username}", [], $this->headers());
        return $res['success'];
    }

    public function toggleUserStatus(string $username, bool $active): bool {
        $res = $this->httpRequest('PUT', "/api/users/{$username}", ['status'=>$active?'active':'disabled'], $this->headers());
        return $res['success'];
    }

    public function getGroupsOrInbounds(): array {
        $this->authenticate();
        foreach (['/api/groups','/api/services','/api/inbounds'] as $ep) {
            $res = $this->httpRequest('GET', $ep, [], $this->headers());
            if ($res['success'] && is_array($res['data'])) {
                $list = [];
                $data = $res['data']['data'] ?? $res['data'];
                foreach ($data as $g) {
                    $list[] = ['id'=>$g['id']??$g['name'],'name'=>$g['name']??$g['title'],'type'=>'group'];
                }
                if ($list) return $list;
            }
        }
        return [['id'=>'default','name'=>'Default Group','type'=>'group']];
    }

    public function testConnection(): array {
        return $this->authenticate() ? ['success'=>true,'message'=>'اتصال پاسارگاد موفق'] : ['success'=>false,'message'=>'احراز هویت پاسارگاد ناموفق'];
    }
}
