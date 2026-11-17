<?php
namespace Panels;
use Core\Database;
use Core\Helper;

class UnifiedPanelManager {
    // ساخت کانفیگ از پلن: پلن -> config_group -> panel -> driver
    public static function createFromPlan(int $planId, string $username, float $volumeGb, int $durationDays): array {
        $plan = Database::fetchOne("SELECT * FROM plans WHERE id=?", [$planId]);
        if (!$plan) return ['success'=>false,'message'=>'پلن یافت نشد'];
        $group = Database::fetchOne("SELECT * FROM config_groups WHERE id=?", [$plan['config_group_id']]);
        if (!$group) return ['success'=>false,'message'=>'گروه کانفیگ یافت نشد'];
        $panelRow = Database::fetchOne("SELECT * FROM panels WHERE id=?", [$group['panel_id']]);
        if (!$panelRow) return ['success'=>false,'message'=>'پنل یافت نشد'];
        
        $driver = PanelFactory::create($panelRow);
        $bytes = Helper::gbToBytes((float)$volumeGb);
        $expire = time() + ($durationDays * 86400);
        $inboundIds = $group['inbound_ids'] ?: $group['group_identifier'];
        // اگر manual باشد، inboundIds = group id
        if ($panelRow['type'] === 'manual') $inboundIds = $group['id'];
        
        $res = $driver->createUser($username, $bytes, $expire, $inboundIds);
        if ($res['success']) {
            Helper::log("Created user $username on panel {$panelRow['name']} via group {$group['name']}", 'panels.log');
        }
        $res['panel_id'] = $panelRow['id'];
        $res['config_group_id'] = $group['id'];
        return $res;
    }

    public static function renewSubscription(int $subscriptionId, float $addVolumeGb, int $addDays): array {
        $sub = Database::fetchOne("SELECT * FROM subscriptions WHERE id=?", [$subscriptionId]);
        if (!$sub) return ['success'=>false,'message'=>'اشتراک یافت نشد'];
        $panelRow = Database::fetchOne("SELECT * FROM panels WHERE id=?", [$sub['panel_id']]);
        if (!$panelRow) return ['success'=>false,'message'=>'پنل یافت نشد'];
        $driver = PanelFactory::create($panelRow);
        $bytes = Helper::gbToBytes($addVolumeGb);
        $seconds = $addDays * 86400;
        $renewMethod = $panelRow['renew_method'] ?? 'append';
        $reset = ($renewMethod === 'reset');
        return $driver->renewUser($sub['username'], $bytes, $seconds, $reset);
    }

    public static function getUserInfo(int $subscriptionId): ?array {
        $sub = Database::fetchOne("SELECT * FROM subscriptions WHERE id=?", [$subscriptionId]);
        if (!$sub) return null;
        $driver = PanelFactory::createById((int)$sub['panel_id']);
        if (!$driver) return null;
        return $driver->getUser($sub['username']);
    }

    public static function syncPanelGroups(int $panelId): array {
        $panelRow = Database::fetchOne("SELECT * FROM panels WHERE id=?", [$panelId]);
        if (!$panelRow) return ['success'=>false,'message'=>'پنل یافت نشد'];
        $driver = PanelFactory::create($panelRow);
        $groups = $driver->getGroupsOrInbounds();
        Database::query("UPDATE panels SET last_sync_at=NOW(), last_error=NULL, capabilities_json=? WHERE id=?", [json_encode($groups, JSON_UNESCAPED_UNICODE), $panelId]);
        return ['success'=>true,'groups'=>$groups,'message'=>'همگام‌سازی موفق: '.count($groups).' مورد'];
    }

    public static function testPanel(int $panelId): array {
        $panelRow = Database::fetchOne("SELECT * FROM panels WHERE id=?", [$panelId]);
        if (!$panelRow) return ['success'=>false,'message'=>'پنل یافت نشد'];
        $driver = PanelFactory::create($panelRow);
        $res = $driver->testConnection();
        Database::query("UPDATE panels SET last_error=? WHERE id=?", [$res['success']?NULL:$res['message'], $panelId]);
        return $res;
    }
}
