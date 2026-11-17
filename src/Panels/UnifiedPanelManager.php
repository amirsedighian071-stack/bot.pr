<?php
namespace Panels;
use Core\Database;
use Core\Helper;

class UnifiedPanelManager {
    // ساخت کانفیگ از پلن: پلن -> config_group -> panel -> driver
    // نسخه جدید: هر پلن می‌تواند گروه پنل Override داشته باشد (plans.group_ids) تا مستقیماً به گروه واقعی پنل (Ava test / Ava max) وصل شود
    public static function createFromPlan(int $planId, string $username, float $volumeGb, int $durationDays): array {
        $plan = Database::fetchOne("SELECT * FROM plans WHERE id=?", [$planId]);
        if (!$plan) return ['success'=>false,'message'=>'پلن یافت نشد'];
        $group = Database::fetchOne("SELECT * FROM config_groups WHERE id=?", [$plan['config_group_id']]);
        if (!$group) return ['success'=>false,'message'=>'گروه کانفیگ یافت نشد — پلن به گروه وصل نیست'];
        $panelRow = Database::fetchOne("SELECT * FROM panels WHERE id=?", [$group['panel_id']]);
        if (!$panelRow) return ['success'=>false,'message'=>'پنل یافت نشد'];
        
        $driver = PanelFactory::create($panelRow);
        $bytes = Helper::gbToBytes((float)$volumeGb);
        $expire = time() + ($durationDays * 86400);
        // اولویت: group_ids داخل پلن (انتخاب مستقیم Ava test/max در مدیریت پلن) > inbound_ids گروه کانفیگ > group_identifier
        $planGroupIds = trim((string)($plan['group_ids'] ?? ''));
        if ($planGroupIds !== '') {
            $inboundIds = $planGroupIds;
        } else {
            $inboundIds = $group['inbound_ids'] ?: $group['group_identifier'];
        }
        // اگر manual باشد، inboundIds = group id (انبار)
        if ($panelRow['type'] === 'manual') $inboundIds = $group['id'];
        // اعتبارسنجی بحرانی برای Outbound Center / PasarGuard: نباید خالی باشد وگرنه سابسکرایب خالی (AVANET 0) می‌شود
        if (empty($inboundIds)) {
            // سعی کن از capabilities_json اولین گروه پنل را به عنوان fallback برداری (اگر همگام شده)
            $caps = $panelRow['capabilities_json'] ?? null;
            if ($caps) {
                $decoded = is_string($caps) ? json_decode($caps, true) : $caps;
                if (is_array($decoded) && !empty($decoded) && isset($decoded[0]['id'])) {
                    $inboundIds = (string)$decoded[0]['id'];
                    Helper::log("createFromPlan fallback group_ids={$inboundIds} for plan {$planId} panel {$panelRow['name']}", 'panels.log');
                }
            }
        }
        if (empty($inboundIds)) {
            $msg = "گروه پنل برای پلن «{$plan['name']}» تنظیم نشده است. در مدیریت → پلن‌ها، برای این پلن یک «گروه پنل» (مثلاً Ava test) انتخاب کنید یا در گروه‌های کانفیگ فیلد Inbound/Group IDs را پر کنید؛ در غیر این صورت کاربر بدون گروه ساخته می‌شود و لینک ساب خالی (0 کانفیگ) خواهد بود. پنل: {$panelRow['name']} ({$panelRow['type']})";
            Helper::log($msg, 'panels.log');
            return ['success'=>false,'message'=>$msg];
        }
        
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
