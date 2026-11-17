<?php
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../src/Core/Database.php';
require_once __DIR__ . '/../../src/Core/Config.php';
require_once __DIR__ . '/../../src/Core/Helper.php';
require_once __DIR__ . '/../../src/Core/Auth.php';
require_once __DIR__ . '/../../src/Core/Telegram.php';
use Core\Database;
use Core\Helper;
use Core\Telegram;
use Core\Auth;
Auth::startSession();
if (!Auth::check()) Helper::redirect(Helper::appUrl('admin/login.php'));

if($_SERVER['REQUEST_METHOD']==='POST'){
    $msg=trim($_POST['message']??'');
    $filter=$_POST['target_filter']??'all';
    $media=null;
    if(isset($_FILES['media']) && $_FILES['media']['error']===0){
        $ext=pathinfo($_FILES['media']['name'], PATHINFO_EXTENSION);
        $name=time().'_broadcast_'.bin2hex(random_bytes(4)).'.'.$ext;
        $dest=__DIR__ . '/../../public/uploads/'.$name;
        if(move_uploaded_file($_FILES['media']['tmp_name'],$dest)) $media='uploads/'.$name;
    }
    if($msg){
        Database::query("INSERT INTO broadcasts (admin_id,message,media_path,target_filter,status) VALUES (?,?,?,?,?)", [$_SESSION['admin_id']??0,$msg,$media,$filter,'sending']);
        $bid=(int)Database::lastInsertId();
        $users=[];
        if($filter==='all') $users=Database::fetchAll("SELECT telegram_id FROM users WHERE is_blocked=0");
        elseif($filter==='buyers') $users=Database::fetchAll("SELECT DISTINCT telegram_id FROM orders WHERE status='completed'");
        elseif($filter==='no_sub') $users=Database::fetchAll("SELECT telegram_id FROM users WHERE telegram_id NOT IN (SELECT telegram_id FROM subscriptions WHERE status='active')");
        $tg=new Telegram();
        $sent=0;
        foreach($users as $u){
            try{
                if($media && str_contains(mime_content_type(__DIR__.'/../../public/'.$media)??'','image')) $tg->sendPhoto((int)$u['telegram_id'], Helper::appUrl('public/'.$media), $msg);
                else $tg->sendMessage((int)$u['telegram_id'], $msg);
                $sent++; usleep(200000);
            }catch(\Throwable $e){}
        }
        Database::query("UPDATE broadcasts SET status='completed', sent_count=?, total_count=? WHERE id=?",[$sent,count($users),$bid]);
        $_SESSION['flash_success']="پیام به $sent از ".count($users)." کاربر ارسال شد";
        Helper::redirect(Helper::appUrl('admin/broadcast/index.php'));
    }
}

$pageTitle='پیام همگانی';
require_once __DIR__ . '/../layout/header.php';

$broadcasts=Database::fetchAll("SELECT * FROM broadcasts ORDER BY id DESC LIMIT 20");
?>
<div class="card mb-3">
  <div class="card-header"><i class="bi bi-megaphone"></i> ارسال پیام همگانی</div>
  <div class="card-body">
    <form method="post" enctype="multipart/form-data" class="row g-3">
      <div class="col-12"><label class="form-label">متن پیام (پشتیبانی HTML)</label><textarea name="message" rows="4" class="form-control" required placeholder="سلام ..."></textarea></div>
      <div class="col-md-4"><label class="form-label">فیلتر گیرندگان</label><select name="target_filter" class="form-select"><option value="all">همه کاربران</option><option value="buyers">فقط خریداران</option><option value="no_sub">بدون اشتراک</option></select></div>
      <div class="col-md-4"><label class="form-label">عکس/ویدیو (اختیاری)</label><input type="file" name="media" class="form-control"></div>
      <div class="col-md-4 d-flex align-items-end"><button class="btn btn-primary w-100"><i class="bi bi-rocket-takeoff"></i> ارسال همگانی</button></div>
    </form>
    <div class="alert alert-warning py-2 small mt-3">برای جلوگیری از اسپم تلگرام، ارسال با تأخیر 0.2 ثانیه انجام می‌شود.</div>
  </div>
</div>
<div class="card">
  <div class="card-header">تاریخچه ارسال</div>
  <div class="table-responsive"><table class="table small mb-0"><thead><tr><th>#</th><th>پیام</th><th>فیلتر</th><th>وضعیت</th><th>ارسال</th><th>تاریخ</th></tr></thead><tbody>
  <?php foreach($broadcasts as $b): ?><tr><td><?= $b['id'] ?></td><td class="small" style="max-width:300px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= Helper::escapeHtml($b['message']) ?></td><td><?= $b['target_filter'] ?></td><td><span class="badge bg-<?= $b['status']==='completed'?'success':'warning' ?>"><?= $b['status'] ?></span></td><td><?= $b['sent_count'] ?>/<?= $b['total_count'] ?></td><td class="small"><?= $b['created_at'] ?></td></tr><?php endforeach; if(empty($broadcasts)): ?><tr><td colspan="6" class="text-center text-muted py-3">موردی نیست</td></tr><?php endif; ?>
  </tbody></table></div>
</div>
<?php require_once __DIR__ . '/../layout/footer.php'; ?>
