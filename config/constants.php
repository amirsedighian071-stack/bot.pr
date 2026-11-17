<?php
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
