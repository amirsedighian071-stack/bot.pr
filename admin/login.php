<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../src/Core/Database.php';
require_once __DIR__ . '/../src/Core/Config.php';
require_once __DIR__ . '/../src/Core/Helper.php';
require_once __DIR__ . '/../src/Core/Auth.php';
use Core\Auth;
use Core\Helper;
use Core\Database;

Auth::startSession();
if (Auth::check()) Helper::redirect(Helper::appUrl('admin/index.php'));

// اگر هیچ ادمینی نیست، ادمین پیش‌فرض بساز
try {
    $cnt = Database::fetchOne("SELECT COUNT(*) as c FROM admins")['c'] ?? 0;
    if ($cnt==0) {
        Database::query("INSERT INTO admins (username,password_hash,role,display_name) VALUES (?,?,?,?)", ['admin', password_hash('admin123', PASSWORD_DEFAULT), 'super_admin','مدیر کل']);
    }
} catch(Exception $e){}

$error='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $u = trim($_POST['username']??'');
    $p = $_POST['password']??'';
    if (Auth::login($u,$p)) {
        Helper::redirect(Helper::appUrl('admin/index.php'));
    } else $error='نام کاربری یا رمز اشتباه است';
}
?>
<!doctype html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>ورود مدیریت</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;700&display=swap" rel="stylesheet">
<style>body{font-family:Vazirmatn, Tahoma;background:linear-gradient(135deg,#6366f1,#8b5cf6);min-height:100vh;display:flex;align-items:center;justify-content:center}</style>
</head>
<body>
<div class="card shadow-lg" style="max-width:420px;width:95%;border-radius:20px;overflow:hidden">
  <div class="card-body p-4">
    <div class="text-center mb-4">
      <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center" style="width:60px;height:60px;font-size:28px">🔐</div>
      <h4 class="mt-3 fw-bold">ورود به پنل مدیریت</h4>
      <p class="text-muted small">VPN Bot - Faoxima Optimized</p>
    </div>
    <?php if($error): ?><div class="alert alert-danger py-2 small"><?= $error ?></div><?php endif; ?>
    <?php if(!empty($_SESSION['flash_success'])): ?><div class="alert alert-success py-2 small"><?= $_SESSION['flash_success']; unset($_SESSION['flash_success']); ?></div><?php endif; ?>
    <form method="post">
      <div class="mb-3"><label class="form-label">نام کاربری</label><input name="username" class="form-control" required value="admin"></div>
      <div class="mb-3"><label class="form-label">رمز عبور</label><input name="password" type="password" class="form-control" required value="admin123"></div>
      <button class="btn btn-primary w-100 py-2 fw-bold">ورود</button>
      <div class="small text-muted mt-3 text-center">پیش‌فرض: admin / admin123 — پس از ورود تغییر دهید</div>
    </form>
  </div>
</div>
</body>
</html>
