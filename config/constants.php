<?php
// بافر خروجی برای جلوگیری از خطای headers already sent
if (ob_get_level() === 0) ob_start();

// ثابت‌های سیستمی
define('APP_VERSION', '1.0.0');
define('APP_NAME', 'VPN Bot - Faoxima Optimized');
define('TIMEZONE', 'Asia/Tehran');
date_default_timezone_set(TIMEZONE);

// مسیرها
define('ROOT_PATH', dirname(__DIR__));
define('SRC_PATH', ROOT_PATH . '/src');
define('CONFIG_PATH', ROOT_PATH . '/config');
define('PUBLIC_PATH', ROOT_PATH . '/public');
define('ADMIN_PATH', ROOT_PATH . '/admin');
define('UPLOAD_PATH', PUBLIC_PATH . '/uploads');
define('LOG_PATH', ROOT_PATH . '/logs');

// اطمینان از وجود پوشه‌ها
foreach ([UPLOAD_PATH, LOG_PATH, UPLOAD_PATH . '/tickets'] as $dir) {
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
}

// Autoloader خودکار برای رفع Fatal Error Core\Telegram و سایر کلاس‌ها
spl_autoload_register(function($class){
    // نگاشت Namespace به مسیر
    $map = [
        'Core\\' => SRC_PATH . '/Core/',
        'Panels\\' => SRC_PATH . '/Panels/',
        'Payment\\' => SRC_PATH . '/Payment/',
        'Services\\' => SRC_PATH . '/Services/',
        'Bot\\' => SRC_PATH . '/Bot/',
    ];
    foreach($map as $prefix => $base){
        if(str_starts_with($class, $prefix)){
            $rel = substr($class, strlen($prefix));
            $file = $base . str_replace('\\','/', $rel) . '.php';
            // برای Drivers داخل Panels
            if(file_exists($file)){ require_once $file; return; }
        }
    }
    // fallback عمومی
    $file = SRC_PATH . '/' . str_replace('\\','/', $class) . '.php';
    if(file_exists($file)) require_once $file;
});
