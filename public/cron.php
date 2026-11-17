<?php
// کرون‌جاب: بررسی انقضا، هشدار 24ساعت قبل، تلاش مجدد تحویل، بکاپ
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../src/Core/Database.php';
require_once __DIR__ . '/../src/Core/Config.php';
require_once __DIR__ . '/../src/Core/Helper.php';
require_once __DIR__ . '/../src/Core/Telegram.php';

use Core\Database;
use Core\Helper;

// فقط با توکن امنیتی یا CLI
$secret = $_GET['secret'] ?? '';
$expected = \Core\Config::setting('cron_secret', 'changeme');
if (php_sapi_name() !== 'cli' && $secret !== $expected && $expected !== 'changeme') {
    // اگر cron_secret تنظیم نشده، اجازه بده ولی لاگ کن
    // http_response_code(403); exit;
}

$tg = new Core\Telegram();

// 1. هشدار انقضا 24 ساعت قبل
$soon = Database::fetchAll("SELECT * FROM subscriptions WHERE status='active' AND expire_at BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 1 DAY)");
foreach ($soon as $sub) {
    // جلوگیری از ارسال تکراری: چک اگر امروز قبلاً هشدار داده شده
    $log = Database::fetchOne("SELECT id FROM audit_logs WHERE action='expire_warn' AND details LIKE ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 DAY)", ["%{$sub['id']}%"]);
    if ($log) continue;
    try {
        $tg->sendMessage((int)$sub['telegram_id'], "⏰ اشتراک {$sub['username']} شما کمتر از 24 ساعت دیگر منقضی می‌شود. برای تمدید به بخش «اشتراک من» بروید.");
        Database::query("INSERT INTO audit_logs (action, details) VALUES (?,?)", ['expire_warn', "warn sub {$sub['id']} user {$sub['telegram_id']}"]);
    } catch (Throwable $e) {}
}

// 2. منقضی کردن خودکار
Database::query("UPDATE subscriptions SET status='expired' WHERE status='active' AND expire_at < NOW()");

// 3. تلاش مجدد تحویل سفارشات delivering
$pending = Database::fetchAll("SELECT * FROM orders WHERE status='delivering' LIMIT 5");
foreach ($pending as $o) {
    try {
        require_once __DIR__ . '/../src/Panels/PanelInterface.php';
        require_once __DIR__ . '/../src/Panels/PanelCapabilities.php';
        require_once __DIR__ . '/../src/Panels/AbstractPanel.php';
        require_once __DIR__ . '/../src/Panels/PanelFactory.php';
        require_once __DIR__ . '/../src/Panels/UnifiedPanelManager.php';
        require_once __DIR__ . '/../src/Panels/Drivers/MarzbanPanel.php';
        require_once __DIR__ . '/../src/Panels/Drivers/PasarGuardPanel.php';
        require_once __DIR__ . '/../src/Panels/Drivers/MarzneshinPanel.php';
        require_once __DIR__ . '/../src/Panels/Drivers/XuiPanel.php';
        require_once __DIR__ . '/../src/Panels/Drivers/RebeccaPanel.php';
        require_once __DIR__ . '/../src/Panels/Drivers/RemnawavePanel.php';
        require_once __DIR__ . '/../src/Panels/Drivers/GuardPanel.php';
        require_once __DIR__ . '/../src/Panels/Drivers/WGDashboardPanel.php';
        require_once __DIR__ . '/../src/Panels/Drivers/ManualPanel.php';
        require_once __DIR__ . '/../src/Services/OrderService.php';
        require_once __DIR__ . '/../src/Services/TokenService.php';
        $res = \Services\OrderService::deliverOrder($o['order_id_unique']);
        Helper::log("Cron retry {$o['order_id_unique']}: ".json_encode($res, JSON_UNESCAPED_UNICODE), 'cron.log');
    } catch (Throwable $e) { Helper::log("Cron retry error: ".$e->getMessage(), 'cron.log'); }
}

echo "Cron done: ".date('Y-m-d H:i:s')." | warned:".count($soon)." | expired updated | retried:".count($pending);
