<?php
// نصب اولیه
ini_set('display_errors',1); error_reporting(E_ALL);
$step = $_GET['step']??1;
$configPath = __DIR__ . '/../config/config.php';
$hasConfig = file_exists($configPath);

if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['db_host'])) {
    $cfg = [
        'db'=>[
            'host'=>trim($_POST['db_host']),
            'name'=>trim($_POST['db_name']),
            'user'=>trim($_POST['db_user']),
            'pass'=>trim($_POST['db_pass']),
            'charset'=>'utf8mb4',
            'port'=> (int)($_POST['db_port']??3306),
        ],
        'app'=>[
            'url'=>rtrim(trim($_POST['app_url']),'/'),
            'debug'=>false,
            'timezone'=>'Asia/Tehran',
        ],
        'security'=>[
            'session_name'=>'VPNBOTSESSID',
            'csrf_token_name'=>'_csrf',
            'upload_max_size'=>20*1024*1024,
        ],
        'telegram'=>['timeout'=>10,'retry'=>2],
    ];
    // تست اتصال
    try{
        $dsn="mysql:host={$cfg['db']['host']};port={$cfg['db']['port']};charset={$cfg['db']['charset']}";
        $pdo=new PDO($dsn,$cfg['db']['user'],$cfg['db']['pass'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$cfg['db']['name']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `{$cfg['db']['name']}`");
        // import schema - split by ; to avoid multi-statement issues
        $sql=file_get_contents(__DIR__ . '/../database/schema.sql');
        $sql = str_replace('SET FOREIGN_KEY_CHECKS=0;', '', $sql);
        $stmts = array_filter(array_map('trim', explode(';', $sql)));
        foreach($stmts as $stmt){
            if($stmt==='') continue;
            try{ $pdo->exec($stmt); } catch(Exception $e){ /* log but continue */ }
        }
        $pdo->exec("SET FOREIGN_KEY_CHECKS=1");
        // create admin
        $adminUser=trim($_POST['admin_user']??'admin');
        $adminPass=trim($_POST['admin_pass']??'admin123');
        $hash=password_hash($adminPass,PASSWORD_DEFAULT);
        $pdo->exec("INSERT INTO admins (username,password_hash,role,display_name) VALUES ('$adminUser','$hash','super_admin','مدیر کل') ON DUPLICATE KEY UPDATE password_hash='$hash'");
        // save config
        file_put_contents($configPath, "<?php\nreturn ".var_export($cfg,true).";\n");
        header("Location: index.php?step=2&success=1");
        exit;
    }catch(Exception $e){
        $error=$e->getMessage();
    }
}
?>
<!doctype html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>نصب VPN Bot</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;700&display=swap" rel="stylesheet">
<style>body{font-family:Vazirmatn;background:#f1f5f9} .wrap{max-width:700px;margin:40px auto}</style>
</head>
<body>
<div class="wrap">
  <div class="card shadow">
    <div class="card-header bg-primary text-white fw-bold">🚀 نصب ربات VPN - Faoxima Optimized</div>
    <div class="card-body">
      <?php if(isset($_GET['success'])): ?>
        <div class="alert alert-success">✅ نصب با موفقیت انجام شد!<br>فایل config/config.php ساخته شد.<br><b>حتما پوشه install را حذف کنید!</b><br><a href="../admin/login.php" class="btn btn-primary mt-2">ورود به پنل مدیریت</a> <a href="../public/webhook.php?debug=1" class="btn btn-outline-secondary mt-2">تست وب‌هوک</a></div>
      <?php else: ?>
        <?php if(!empty($error)): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <?php if($hasConfig): ?><div class="alert alert-warning">⚠️ فایل config.php از قبل وجود دارد. در صورت نصب مجدد حذف خواهد شد.</div><?php endif; ?>
        <form method="post" class="row g-3">
          <h6 class="fw-bold">🔧 تنظیمات دیتابیس MySQL</h6>
          <div class="col-md-6"><label class="form-label">هاست</label><input name="db_host" class="form-control" value="localhost" required></div>
          <div class="col-md-3"><label class="form-label">پورت</label><input name="db_port" class="form-control" value="3306"></div>
          <div class="col-md-3"><label class="form-label">نام دیتابیس</label><input name="db_name" class="form-control" required placeholder="vpn_bot"></div>
          <div class="col-md-6"><label class="form-label">یوزر دیتابیس</label><input name="db_user" class="form-control" required></div>
          <div class="col-md-6"><label class="form-label">رمز دیتابیس</label><input name="db_pass" class="form-control"></div>
          <h6 class="fw-bold mt-3">🌐 تنظیمات اپ</h6>
          <div class="col-12"><label class="form-label">آدرس سایت (https)</label><input name="app_url" class="form-control" required placeholder="https://yourdomain.com" value="<?= (isset($_SERVER['HTTPS'])?'https':'http').'://'.($_SERVER['HTTP_HOST']??'yourdomain.com') ?>"></div>
          <h6 class="fw-bold mt-3">👤 ادمین پنل</h6>
          <div class="col-md-6"><label class="form-label">نام کاربری ادمین</label><input name="admin_user" class="form-control" value="admin" required></div>
          <div class="col-md-6"><label class="form-label">رمز ادمین</label><input name="admin_pass" class="form-control" value="admin123" required></div>
          <div class="col-12"><button class="btn btn-primary w-100 py-2 fw-bold">نصب و ساخت جداول</button></div>
        </form>
        <div class="small text-muted mt-3">پیش‌نیاز: PHP 8.2+, MySQL 5.7+, ext-pdo, ext-curl, ext-json, ext-mbstring. هاست ابری با HTTPS.</div>
      <?php endif; ?>
    </div>
  </div>
  <div class="text-center small text-muted mt-3">نیاز به راهنما؟ README.md را ببینید.</div>
</div>
</body>
</html>
