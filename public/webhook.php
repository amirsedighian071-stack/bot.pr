<?php
// وب‌هوک تلگرام - نقطه ورود ربات
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
require_once __DIR__ . '/../src/Bot/Keyboards.php';
require_once __DIR__ . '/../src/Bot/BotHandler.php';

// Autoload fallback
spl_autoload_register(function($class){
    $prefix = '';
    $base = __DIR__ . '/../src/';
    $file = $base . str_replace('\\','/',$class) . '.php';
    if (file_exists($file)) require_once $file;
});

use Core\Config;
use Core\Helper;

// بررسی Secret Token
$secret = Config::setting('webhook_secret','');
if ($secret) {
    $headerSecret = $_SERVER['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN'] ?? '';
    if ($headerSecret !== $secret) {
        // لاگ ولی اجازه بده اگر هدر خالی بود و از تلگرام نیست؟ سخت‌گیر: 403
        // اما برای تست محلی اجازه می‌دهیم اگر پارامتر debug باشد
        if (!isset($_GET['debug'])) {
            http_response_code(403);
            echo "Forbidden: invalid secret";
            exit;
        }
    }
}

// دریافت آپدیت
$input = file_get_contents('php://input');
if (!$input) {
    // تست health
    echo "Bot webhook is active. ".date('Y-m-d H:i:s');
    exit;
}
$update = json_decode($input, true);
if (!$update) {
    http_response_code(400);
    echo "Invalid JSON";
    exit;
}

// لاگ آپدیت برای دیباگ (سبک)
Helper::log("Update: ". $input, 'webhook.log');

// پردازش سریع - پاسخ 200 فوری برای تلگرام سپس پردازش
// در هاست ابری با fastcgi_finish_request
if (function_exists('fastcgi_finish_request')) {
    // هدر کوتاه
    header('Content-Type: application/json');
    echo json_encode(['ok'=>true]);
    fastcgi_finish_request();
} else {
    // بدون fastcgi، پردازش سینک
}

// اجرا
try {
    // اطمینان از وجود فایل config.php
    if (!file_exists(CONFIG_PATH.'/config.php')) {
        Helper::log("config.php missing", 'error.log');
    }
    $handler = new Bot\BotHandler($update);
    $handler->handle();
} catch (Throwable $e) {
    Helper::log("Webhook error: ".$e->getMessage()." ".$e->getFile().":".$e->getLine(), 'error.log');
}

// اگر fastcgi استفاده نشد، پاسخ
if (!function_exists('fastcgi_finish_request')) {
    header('Content-Type: application/json');
    echo json_encode(['ok'=>true]);
}
