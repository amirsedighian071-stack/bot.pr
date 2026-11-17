<?php
namespace Panels\Drivers;
use Panels\AbstractPanel;
use Core\Database;

class ManualPanel extends AbstractPanel {
    public function authenticate(): bool { return true; }
    public function getToken(): ?string { return 'manual'; }
    public function createUser(string $username,int $dataLimitBytes,int $expireTimestamp,$groupOrInboundIds=null): array {
        // $groupOrInboundIds is config_group_id
        $groupId = $this->config['id']; // panel id for manual? Actually config_group_id passed
        // Try to find warehouse config
        $gid = is_array($groupOrInboundIds)? ($groupOrInboundIds[0]??null) : $groupOrInboundIds;
        if (!$gid) {
            // find any group linked to this panel
            $g = Database::fetchOne("SELECT id FROM config_groups WHERE panel_id=? LIMIT 1", [$this->config['id']]);
            $gid = $g['id'] ?? null;
        }
        if ($gid) {
            $w = Database::fetchOne("SELECT * FROM warehouse_configs WHERE config_group_id=? AND is_used=0 LIMIT 1", [$gid]);
            if ($w) {
                Database::query("UPDATE warehouse_configs SET is_used=1 WHERE id=?", [$w['id']]);
                return ['success'=>true,'subscription_url'=>$w['config_text'],'uuid'=>'','message'=>'تحویل از انبار'];
            }
        }
        // fallback generate fake
        return ['success'=>true,'subscription_url'=>"vmess://manual-{$username}@example.com:443",'uuid'=>'','message'=>'تحویل دستی (انبار خالی - نمونه)'];
    }
    public function getUser(string $username): ?array { return null; }
    public function renewUser(string $a,int $b,int $c,bool $r=false): array { return ['success'=>false,'message'=>'تمدید دستی پشتیبانی نمی‌شود']; }
    public function deleteUser(string $u): bool { return true; }
    public function toggleUserStatus(string $u,bool $a): bool { return true; }
    public function getGroupsOrInbounds(): array { return [['id'=>'warehouse','name'=>'انبار کانفیگ دستی','type'=>'warehouse']]; }
    public function testConnection(): array { return ['success'=>true,'message'=>'انبار دستی فعال']; }
}
