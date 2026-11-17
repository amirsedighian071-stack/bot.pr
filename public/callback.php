<?php
// کال‌بک آبان‌گیت - سازگاری قدیمی: public/callback.php?order_id=...
// فاکسیما جدید از /payment/abangateway.php?order= استفاده می‌کند - این فایل برای سازگاری هر دو را هندل می‌کند
declare(strict_types=1);
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../src/Core/Database.php';
require_once __DIR__ . '/../src/Core/Config.php';
require_once __DIR__ . '/../src/Core/Helper.php';
require_once __DIR__ . '/../src/Payment/AbanGateway.php';

use Core\Helper;
use Payment\AbanGateway;

// دریافت order از هر دو فرمت
$input = file_get_contents('php://input');
$payload = [];
if ($input) { $j = json_decode($input, true); if (is_array($j)) $payload = $j; }
$payload = array_merge($payload, $_GET, $_POST);
$orderId = $payload['order_id'] ?? $payload['order'] ?? null;

// اگر پارامتر فاکسیما (order) بود، به payment/abangateway.php فوروارد کن
if (isset($_REQUEST['order']) && !isset($_REQUEST['order_id'])) {
    $_REQUEST['order_id'] = $_REQUEST['order'];
}

// لاگ و delegate به AbanGateway جدید
Helper::log("Callback legacy hit: ".json_encode($payload, JSON_UNESCAPED_UNICODE), 'callback.log');

// اگر فاکسیما endpoint فعال است، همان منطق را اجرا کن
if (\Payment\AbanGateway::abangatewayEndpoint() !== null) {
    // فوروارد به payment/abangateway.php logic
    $_GET['order'] = $orderId;
    $_REQUEST['order'] = $orderId;
    require __DIR__ . '/../payment/abangateway.php';
    exit;
}

// fallback legacy handling (قدیمی)
if (!$orderId) { http_response_code(400); echo "order_id required"; exit; }
$order = \Core\Database::fetchOne("SELECT * FROM orders WHERE order_id_unique=?", [$orderId]);
if (!$order) { http_response_code(404); echo "Order not found"; exit; }
if ($order['status']==='completed') { echo "Already completed (idempotent)"; exit; }
$aban = new AbanGateway();
$verify = $aban->verifyInvoice($orderId, $order['abangateway_invoice_id'] ?? $payload['invoice_id'] ?? null);
if (($verify['paid'] ?? false) || ($verify['success'] ?? false)) {
    \Core\Database::query("UPDATE orders SET status='paid' WHERE order_id_unique=?", [$orderId]);
    $delivery = \Services\OrderService::deliverOrder($orderId);
    header('Content-Type: application/json');
    echo json_encode(['ok'=>true,'message'=>'verified and delivered']);
    exit;
} else {
    header('Content-Type: application/json');
    echo json_encode(['ok'=>false,'message'=>'not paid yet','verify'=>$verify]);
    exit;
}
