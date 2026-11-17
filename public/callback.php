<?php
// کال‌بک آبان‌گیت - تایید خودکار
declare(strict_types=1);
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../src/Core/Database.php';
require_once __DIR__ . '/../src/Core/Config.php';
require_once __DIR__ . '/../src/Core/Helper.php';
require_once __DIR__ . '/../src/Core/Cache.php';
require_once __DIR__ . '/../src/Core/Telegram.php';
require_once __DIR__ . '/../src/Panels/PanelInterface.php';
require_once __DIR__ . '/../src/Panels/PanelCapabilities.php';
require_once __DIR__ . '/../src/Panels/AbstractPanel.php';
require_once __DIR__ . '/../src/Panels/PanelFactory.php';
require_once __DIR__ . '/../src/Panels/UnifiedPanelManager.php';
require_once __DIR__ . '/../src/Panels/Drivers/PasarGuardPanel.php';
require_once __DIR__ . '/../src/Panels/Drivers/MarzbanPanel.php';
require_once __DIR__ . '/../src/Panels/Drivers/MarzneshinPanel.php';
require_once __DIR__ . '/../src/Panels/Drivers/XuiPanel.php';
require_once __DIR__ . '/../src/Panels/Drivers/RebeccaPanel.php';
require_once __DIR__ . '/../src/Panels/Drivers/RemnawavePanel.php';
require_once __DIR__ . '/../src/Panels/Drivers/GuardPanel.php';
require_once __DIR__ . '/../src/Panels/Drivers/WGDashboardPanel.php';
require_once __DIR__ . '/../src/Panels/Drivers/ManualPanel.php';
require_once __DIR__ . '/../src/Payment/AbanGateway.php';
require_once __DIR__ . '/../src/Services/OrderService.php';
require_once __DIR__ . '/../src/Services/TokenService.php';

spl_autoload_register(function($class){
    $file = __DIR__ . '/../src/' . str_replace('\\','/',$class) . '.php';
    if (file_exists($file)) require_once $file;
});

use Core\Helper;
use Core\Database;
use Payment\AbanGateway;
use Services\OrderService;

// دریافت پارامترها (GET + POST + JSON)
$input = file_get_contents('php://input');
$payload = [];
if ($input) {
    $j = json_decode($input, true);
    if (is_array($j)) $payload = $j;
}
$payload = array_merge($payload, $_GET, $_POST);

$orderId = $payload['order_id'] ?? $_GET['order_id'] ?? null;
Helper::log("Callback hit: ".json_encode($payload, JSON_UNESCAPED_UNICODE), 'callback.log');

if (!$orderId) {
    http_response_code(400);
    echo "order_id required";
    exit;
}

// Idempotency + Verify
$order = Database::fetchOne("SELECT * FROM orders WHERE order_id_unique=?", [$orderId]);
if (!$order) {
    http_response_code(404);
    echo "Order not found";
    exit;
}

// ذخیره کال‌بک
Database::query("INSERT INTO abangateway_logs (order_id_unique, status, callback_json) VALUES (?,?,?)", [$orderId, $payload['status']??'callback', json_encode($payload, JSON_UNESCAPED_UNICODE)]);
Database::query("UPDATE abangateway_logs SET callback_json=? WHERE order_id_unique=? ORDER BY id DESC LIMIT 1", [json_encode($payload, JSON_UNESCAPED_UNICODE), $orderId]);

// اگر قبلاً کامل شده
if ($order['status']==='completed') {
    echo "Already completed (idempotent)";
    exit;
}

// تایید از API آبان
$aban = new AbanGateway();
$verify = $aban->verifyInvoice($orderId, $order['abangateway_invoice_id'] ?? $payload['invoice_id'] ?? null);

if (($verify['paid'] ?? false) || ($verify['success'] ?? false) || strtolower($payload['status']??'')==='paid') {
    Database::query("UPDATE orders SET status='paid' WHERE order_id_unique=?", [$orderId]);
    $delivery = OrderService::deliverOrder($orderId);
    Helper::log("Delivery result for $orderId: ".json_encode($delivery, JSON_UNESCAPED_UNICODE), 'delivery.log');

    // اطلاع به کاربر در تلگرام
    try {
        $tg = new Core\Telegram();
        $subUrl = $delivery['subscription_url'] ?? $delivery['delivery']['subscription_url'] ?? '';
        $msg = "✅ پرداخت شما تأیید شد!\n\n🆔 سفارش: $orderId\n";
        if ($subUrl) $msg .= "🔗 لینک اتصال:\n<code>$subUrl</code>\n\nاز بخش «اشتراک من» می‌توانید مجدد دریافت کنید.";
        else $msg .= "⚠️ تحویل با خطا مواجه شد: ".($delivery['message']??'') . "\nپشتیبانی بررسی خواهد کرد.";
        $tg->sendMessage((int)$order['telegram_id'], $msg);
        if ($subUrl) {
            $qr = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=".urlencode($subUrl);
            $tg->sendPhoto((int)$order['telegram_id'], $qr, "📷 QR Code");
        }
    } catch (Throwable $e) {
        Helper::log("Notify failed: ".$e->getMessage(), 'error.log');
    }

    // پاسخ به آبان‌گیت
    header('Content-Type: application/json');
    echo json_encode(['ok'=>true, 'message'=>'verified and delivered']);
    exit;
} else {
    Helper::log("Verify failed for $orderId: ".json_encode($verify, JSON_UNESCAPED_UNICODE), 'payment.log');
    header('Content-Type: application/json');
    echo json_encode(['ok'=>false, 'message'=>'not paid yet', 'verify'=>$verify]);
    exit;
}
