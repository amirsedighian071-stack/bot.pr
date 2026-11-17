<?php
// ریدایرکت به پنل یا نصب
if (file_exists(__DIR__ . '/config/config.php')) {
    $cfg = require __DIR__ . '/config/config.php';
    // چک اگر DB کانفیگ خالی باشد برو نصب
    if (empty($cfg['db']['name']) || $cfg['db']['name']==='vpn_bot' && !file_exists(__DIR__ . '/install/done.lock')) {
        // اگر install هنوز انجام نشده ولی config مثال است، به install بفرست
        // برای سادگی: اگر جدول admins وجود ندارد، نصب
        try {
            $pdo = new PDO("mysql:host={$cfg['db']['host']};dbname={$cfg['db']['name']};charset=utf8mb4", $cfg['db']['user'], $cfg['db']['pass']);
            $q=$pdo->query("SHOW TABLES LIKE 'admins'");
            if($q->rowCount()==0) throw new Exception("no tables");
            header("Location: admin/login.php");
            exit;
        } catch(Exception $e){
            header("Location: install/index.php");
            exit;
        }
    }
    header("Location: admin/login.php");
} else {
    header("Location: install/index.php");
}
exit;
