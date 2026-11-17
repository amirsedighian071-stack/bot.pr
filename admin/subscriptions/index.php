<?php
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../src/Core/Database.php';
require_once __DIR__ . '/../../src/Core/Config.php';
require_once __DIR__ . '/../../src/Core/Helper.php';
require_once __DIR__ . '/../../src/Core/Auth.php';
use Core\Database;
use Core\Helper;
use Core\Auth;
Auth::startSession();
if (!Auth::check()) Helper::redirect(Helper::appUrl('admin/login.php'));

if(isset($_GET['delete'])){ Database::query("DELETE FROM subscriptions WHERE id=?",[(int)$_GET['delete']]); $_SESSION['flash_success']='حذف شد'; Helper::redirect(Helper::appUrl('admin/subscriptions/index.php')); }
if(isset($_GET['toggle'])){
    $id=(int)$_GET['toggle'];
    $s=Database::fetchOne("SELECT status FROM subscriptions WHERE id=?",[$id]);
    if($s){
        $new=$s['status']==='active'?'disabled':'active';
        Database::query("UPDATE subscriptions SET status=? WHERE id=?",[$new,$id]);
        $_SESSION['flash_success']='وضعیت تغییر کرد';
    }
    Helper::redirect(Helper::appUrl('admin/subscriptions/index.php'));
}

$pageTitle='اشتراک‌ها';
require_once __DIR__ . '/../layout/header.php';
$q=$_GET['q']??'';
$where=$q ? "WHERE s.username LIKE '%$q%' OR s.telegram_id LIKE '%$q%'" : '';
$subs=Database::fetchAll("SELECT s.*, p.name as panel_name FROM subscriptions s LEFT JOIN panels p ON p.id=s.panel_id $where ORDER BY s.id DESC LIMIT 100");
?>
<div class="card">
  <div class="card-header d-flex justify-content-between"><span>اشتراک‌ها (100 اخیر)</span><form class="d-flex gap-2"><input name="q" value="<?= Helper::escapeHtml($q) ?>" placeholder="یوزرنیم یا آیدی" class="form-control form-control-sm"><button class="btn btn-sm btn-secondary">جستجو</button></form></div>
  <div class="table-responsive"><table class="table small mb-0 table-hover"><thead class="table-light"><tr><th>#</th><th>کاربر</th><th>یوزرنیم</th><th>پنل</th><th>حجم</th><th>انقضا</th><th>وضعیت</th><th>لینک</th><th>عملیات</th></tr></thead><tbody>
  <?php foreach($subs as $s): ?><tr><td><?= $s['id'] ?></td><td><?= $s['telegram_id'] ?></td><td class="fw-bold"><?= Helper::escapeHtml($s['username']) ?></td><td><?= Helper::escapeHtml($s['panel_name']??'') ?></td><td><?= round($s['data_limit_bytes']/1073741824,2) ?> GB</td><td class="small"><?= $s['expire_at'] ?></td><td><span class="badge bg-<?= $s['status']==='active'?'success':($s['status']==='expired'?'warning':'secondary') ?>"><?= $s['status'] ?></span></td><td><button class="btn btn-sm btn-outline-secondary" onclick="copyText('<?= Helper::escapeHtml($s['subscription_url']) ?>')">کپی</button> <button class="btn btn-sm btn-outline-primary" onclick="previewQR('<?= urlencode($s['subscription_url']) ?>')">QR</button></td><td><a href="?toggle=<?= $s['id'] ?>" class="btn btn-sm btn-outline-warning">تغییر وضعیت</a> <a href="?delete=<?= $s['id'] ?>" class="btn btn-sm btn-outline-danger" data-confirm="حذف؟">حذف</a></td></tr><?php endforeach; if(empty($subs)): ?><tr><td colspan="9" class="text-center text-muted py-3">اشتراکی نیست</td></tr><?php endif; ?>
  </tbody></table></div>
</div>
<?php require_once __DIR__ . '/../layout/footer.php'; ?>
