<?php
/**
 * AbanGateway callback — کپی دقیق فاکسیما آپدیت‌شده (payment/abangateway.php)
 * - دو caller: مرورگر کاربر (برگشت) و سرور آبان‌گیت (push)
 * - هر دو فقط order را نام می‌برند، سپس سرور به سرور با کلید اتصال از آبان‌گیت می‌پرسد آیا paid است
 * - تسویه از طریق OrderService::deliverOrder به صورت atomic (idempotent)
 */
declare(strict_types=1);
use Core\Config;
use Core\Database;
use Core\Helper;

// بافر و لود
if (ob_get_level() === 0) ob_start();
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../src/Core/Database.php';
require_once __DIR__ . '/../src/Core/Config.php';
require_once __DIR__ . '/../src/Core/Helper.php';
require_once __DIR__ . '/../src/Core/Cache.php';
require_once __DIR__ . '/../src/Core/Telegram.php';
require_once __DIR__ . '/../src/Payment/AbanGateway.php';
require_once __DIR__ . '/../src/Services/OrderService.php';

use Payment\AbanGateway;

function abangateway_callback_log(string $type, string $message, array $context = []): void {
    Helper::log("[abangateway] $type: $message ".json_encode($context, JSON_UNESCAPED_UNICODE), 'payment.log');
}

// پارامتر order: مرورگر ?order=... ، سرور ?order_id=...
$order = '';
foreach (['order','order_id'] as $f) {
    if (isset($_REQUEST[$f]) && is_string($_REQUEST[$f])) { $order = $_REQUEST[$f]; break; }
}
$order = substr(preg_replace('/[^A-Za-z0-9_\-]/','',$order),0,64);
$wantsPage = stripos((string)($_SERVER['HTTP_ACCEPT'] ?? ''), 'text/html') !== false;

$state = 'missing';
$payment = null;
if ($order !== '') {
    try {
        $row = Database::fetchOne("SELECT * FROM orders WHERE order_id_unique=? LIMIT 1", [$order]);
        if (is_array($row) && ($row['payment_gateway'] ?? '') === 'abangateway') {
            $payment = $row;
            $state = in_array($row['status'] ?? '', ['paid','completed']) ? 'paid' : 'waiting';
        } else if (is_array($row)) {
            // سفارش وجود دارد اما متد پرداخت متفاوت - همچنان waiting
            $payment = $row;
            $state = in_array($row['status'] ?? '', ['paid','completed']) ? 'paid' : 'waiting';
        }
    } catch (Throwable $e) {
        error_log('abangateway callback select failed: '.$e->getMessage());
    }
}

if ($state === 'waiting' && $payment) {
    $authority = trim((string)($payment['abangateway_invoice_id'] ?? ''));
    if (strpos($authority, 'abn_') !== 0) $authority = '';
    $billed = (int)($payment['final_price'] ?? $payment['price'] ?? 0);
    // اگر authority خالی بود، سعی از لاگ
    if ($authority === '') {
        try {
            $log = Database::fetchOne("SELECT abangateway_invoice_id FROM abangateway_logs WHERE order_id_unique=? AND abangateway_invoice_id LIKE 'abn_%' ORDER BY id DESC LIMIT 1", [$order]);
            if ($log && !empty($log['abangateway_invoice_id'])) $authority = trim($log['abangateway_invoice_id']);
        } catch (Throwable $e) {}
    }
    if ($authority !== '') {
        $verify = AbanGateway::abangatewayVerifyPayment($authority, $order, $billed);
        if (!empty($verify['paid'])) {
            try {
                // تلاش delivery اتمیک
                $delivered = \Services\OrderService::deliverOrder($order);
                if (!empty($delivered['success'])) {
                    $state = 'paid';
                    Database::query("UPDATE orders SET status='completed', paid_at=NOW() WHERE order_id_unique=?", [$order]);
                    // لاگ
                    abangateway_callback_log('ABANGATEWAY_PAID', 'verified and delivered', ['order_id'=>$order, 'authority'=>$authority]);
                    // اطلاع تلگرام
                    try {
                        $tg = new \Core\Telegram();
                        $subUrl = $delivered['subscription_url'] ?? $delivered['delivery']['subscription_url'] ?? '';
                        $tgId = (int)($payment['telegram_id'] ?? 0);
                        if ($tgId) {
                            $msg = "✅ پرداخت شما تأیید شد!\n\n🆔 سفارش: $order\n";
                            if ($subUrl) $msg .= "🔗 لینک اتصال:\n<code>$subUrl</code>\n\nاز بخش «اشتراک من» می‌توانید مجدد دریافت کنید.";
                            else $msg .= "⚠️ تحویل انجام شد اما لینک یافت نشد، پشتیبانی بررسی می‌کند.";
                            $tg->sendMessage($tgId, $msg);
                            if ($subUrl) {
                                $qr = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=".urlencode($subUrl);
                                $tg->sendPhoto($tgId, $qr, "📷 QR Code");
                            }
                        }
                    } catch (Throwable $e) {
                        Helper::log("Notify failed: ".$e->getMessage(), 'error.log');
                    }
                } else {
                    // اگر قبلاً توسط درخواست دیگر settle شده
                    if (Database::fetchOne("SELECT status FROM orders WHERE order_id_unique=?", [$order])['status'] ?? '' === 'completed') {
                        $state = 'paid';
                    }
                }
            } catch (Throwable $e) {
                abangateway_callback_log('ABANGATEWAY_CONFIRM_FAILED', $e->getMessage(), ['order_id'=>$order]);
                if (!$wantsPage) {
                    http_response_code(500);
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['status'=>'delivery failed'], JSON_UNESCAPED_UNICODE);
                    exit;
                }
            }
        } elseif (in_array((string)($verify['message'] ?? ''), ['order mismatch','amount mismatch'], true)) {
            abangateway_callback_log('ABANGATEWAY_VERIFY_MISMATCH', (string)$verify['message'], ['order_id'=>$order,'billed_toman'=>$billed,'reported_rial'=> (int)($verify['amount_rial']??0)]);
        }
    }
}

if (!$wantsPage) {
    http_response_code($state === 'missing' ? 404 : 200);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status'=> $state === 'missing' ? 'order not found' : $state], JSON_UNESCAPED_UNICODE);
    exit;
}

// HTML page برای مرورگر - دقیقا مانند فاکسیما
$botUsername = '';
$botUsernameRaw = Config::setting('bot_username','');
if (is_string($botUsernameRaw) && trim($botUsernameRaw)!=='') $botUsername = ltrim(trim($botUsernameRaw),'@');
if ($botUsername==='' ) {
    try {
        $row = Database::fetchOne("SELECT setting_value FROM settings WHERE setting_key='bot_username' LIMIT 1");
        if ($row && !empty($row['setting_value'])) $botUsername = ltrim(trim($row['setting_value']),'@');
    } catch (Throwable $e) {}
}

$miniappName = 'miniapp';
try {
    $row = Database::fetchOne("SELECT setting_value FROM settings WHERE setting_key='miniapp_short_name' LIMIT 1");
    if ($row && !empty($row['setting_value'])) {
        $miniappName = preg_replace('/[^A-Za-z0-9_]/','', (string)$row['setting_value']);
        if ($miniappName==='') $miniappName='miniapp';
    }
} catch (Throwable $e) {}

$isMiniapp = is_array($payment) && (string)($payment['source'] ?? '') === 'miniapp';
$tgUrl = '';
if ($botUsername !== '') {
    $tgUrl = 'https://t.me/'.$botUsername;
    if ($isMiniapp) {
        $startParam = substr('abangatewaypaid_'.$order,0,64);
        $tgUrl .= '/'.$miniappName.'?startapp='.rawurlencode($startParam);
    }
}
$tries = isset($_GET['n']) ? max(0, min(999, (int)$_GET['n'])) : 0;
$refreshUrl = '';
if ($state === 'waiting' && $tries < 120) {
    $refreshUrl = '?order='.rawurlencode($order).'&n='.($tries+1);
}
if ($state === 'paid') {
    $emoji='✅'; $titleFa='پرداخت موفق بود'; $hintFa='واریز شما تایید شد. موجودی کیف پول یا سفارش شما خودکار به روز شده و از طریق ربات به شما خبر داده میشود.'; $isFail=false;
} elseif ($state === 'waiting') {
    $emoji='⏳'; $titleFa='در انتظار تایید پرداخت'; $hintFa='اگر کارت به کارت را انجام داده اید، تایید آن معمولا چند ثانیه تا چند دقیقه طول میکشد و خودکار انجام میشود. میتوانید این صفحه را ببندید؛ نتیجه در ربات به شما خبر داده میشود.'; $isFail=false;
} else {
    $emoji='❌'; $titleFa='سفارش پیدا نشد'; $hintFa='این سفارش مربوط به این درگاه نیست یا شناسه آن معتبر نیست. میتوانید به ربات برگردید و دوباره تلاش کنید.'; $isFail=true;
}
?><!doctype html>
<html dir="rtl" lang="fa">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<meta name="robots" content="noindex" />
<?php if ($refreshUrl !== ''): ?>
<meta http-equiv="refresh" content="10;url=<?php echo htmlspecialchars($refreshUrl, ENT_QUOTES); ?>" />
<?php endif; ?>
<title><?php echo htmlspecialchars($titleFa, ENT_QUOTES); ?></title>
<style>
:root{--bg:#0a0907;--surface:#14110d;--border:#2b2620;--text:#f5f5f5;--muted:#9a9388;--accent:#3b82f6;--accent-bright:#60a5fa;--green:#6fce6f;--red:#e57373;}
*{box-sizing:border-box;}html,body{margin:0;padding:0;background:var(--bg);color:var(--text);font-family:Tahoma,system-ui,"Segoe UI",sans-serif;}
body{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px;}
.card{width:100%;max-width:460px;background:var(--surface);border:1px solid var(--border);border-radius:18px;padding:32px 24px 24px;text-align:center;box-shadow:0 20px 60px rgba(0,0,0,0.5);}
.icon-wrap{width:96px;height:96px;margin:0 auto 16px;border-radius:50%;display:grid;place-items:center;background:<?php echo $isFail?'rgba(229,115,115,0.10)':'rgba(111,206,111,0.10)'; ?>;border:2px solid <?php echo $isFail?'rgba(229,115,115,0.35)':'rgba(111,206,111,0.35)'; ?>;font-size:56px;line-height:1;}
h1{margin:0 0 8px;font-size:22px;color:<?php echo $isFail?'var(--red)':'var(--green)'; ?>;}
.muted{color:var(--muted);font-size:14px;line-height:1.9;margin:0 0 18px;}
.kv{display:flex;justify-content:space-between;background:rgba(255,255,255,0.03);border:1px dashed var(--border);border-radius:10px;padding:10px 14px;margin:0 0 10px;font-size:13px;}
.kv .lbl{color:var(--muted);} .kv .val{font-family:ui-monospace,"JetBrains Mono",monospace;color:var(--accent-bright);word-break:break-all;}
.btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;width:100%;padding:14px 18px;border-radius:12px;border:none;font-size:16px;font-weight:700;cursor:pointer;text-decoration:none;transition:transform 0.08s ease,background 0.15s ease;margin-top:12px;}
.btn-primary{background:linear-gradient(135deg,var(--accent),var(--accent-bright));color:#0b1220;}
.btn-primary:hover{background:var(--accent-bright);} .btn-primary:active{transform:scale(0.99);}
.btn-ghost{background:transparent;color:var(--muted);border:1px solid var(--border);font-weight:500;font-size:13px;padding:10px 14px;}
.btn-ghost:hover{color:var(--text);border-color:#444;}
.brand-bar{margin-top:18px;color:var(--muted);font-size:11px;font-family:ui-monospace,monospace;}
</style>
</head>
<body>
<div class="card">
    <div class="icon-wrap"><?php echo $emoji; ?></div>
    <h1><?php echo htmlspecialchars($titleFa, ENT_QUOTES); ?></h1>
    <p class="muted"><?php echo htmlspecialchars($hintFa, ENT_QUOTES); ?></p>
    <div class="kv"><span class="lbl">کد فاکتور</span><span class="val"><?php echo htmlspecialchars($order!==''?$order:'unknown', ENT_QUOTES); ?></span></div>
    <?php if ($tgUrl !== ''): ?>
    <a class="btn btn-primary" href="<?php echo htmlspecialchars($tgUrl, ENT_QUOTES); ?>">🤖 بازکردن تلگرام و بازگشت به ربات</a>
    <?php else: ?>
    <p class="muted">آدرس ربات روی سرور تنظیم نشده است. لطفا از تلگرام وارد ربات شوید.</p>
    <?php endif; ?>
    <button class="btn btn-ghost" type="button" onclick="try{window.close();}catch(e){}">بستن این پنجره</button>
    <div class="brand-bar">پرداخت کارت به کارت با آبان گیت وی</div>
</div>
</body>
</html>
