<?php
$pageTitle='کاربران';
require_once __DIR__ . '/../layout/header.php';
use Core\Database;
use Core\Helper;
if(isset($_GET['toggle_block'])){
    $id=(int)$_GET['toggle_block'];
    Database::query("UPDATE users SET is_blocked=1-is_blocked WHERE id=?",[$id]);
    Helper::redirect(Helper::appUrl('admin/users/index.php'));
}
if(isset($_POST['add_token'])){
    $tid=(int)$_POST['telegram_id'];
    $amount=(int)$_POST['amount'];
    $reason=trim($_POST['reason']??'');
    $u=Database::fetchOne("SELECT * FROM users WHERE telegram_id=?",[$tid]);
    if($u){
        $new=(int)$u['tokens']+$amount;
        Database::query("UPDATE users SET tokens=? WHERE telegram_id=?",[$new,$tid]);
        Database::query("INSERT INTO token_transactions (user_id,telegram_id,amount,type,reason,balance_after) VALUES (?,?,?,?,?,?)",[$u['id'],$tid,$amount,'admin_adjust',$reason,$new]);
        $_SESSION['flash_success']='توکن تغییر کرد';
    }
    Helper::redirect(Helper::appUrl('admin/users/index.php'));
}
$q=$_GET['q']??'';
$where=$q ? "WHERE telegram_id LIKE '%$q%' OR username LIKE '%$q%'":'';
$users=Database::fetchAll("SELECT * FROM users $where ORDER BY id DESC LIMIT 100");
?>
<div class="card mb-3">
  <div class="card-header d-flex justify-content-between"><span>کاربران (100 اخیر)</span><form class="d-flex gap-2"><input name="q" value="<?= Helper::escapeHtml($q) ?>" placeholder="آیدی یا یوزرنیم" class="form-control form-control-sm"><button class="btn btn-sm btn-secondary">جستجو</button></form></div>
  <div class="table-responsive"><table class="table table-hover small mb-0"><thead class="table-light"><tr><th>تلگرام ID</th><th>نام</th><th>توکن</th><th>دعوت‌دهنده</th><th>وضعیت</th><th>عضویت</th><th>عملیات</th></tr></thead><tbody>
  <?php foreach($users as $u): ?><tr><td><code><?= $u['telegram_id'] ?></code><br><small>@<?= $u['username']??'-' ?></small></td><td><?= Helper::escapeHtml($u['first_name']??'') ?></td><td><span class="badge bg-warning text-dark"><?= $u['tokens'] ?> <i class="bi bi-coin"></i></span></td><td><?= $u['invited_by']?:'-' ?></td><td><?= $u['is_blocked']?'<span class="badge bg-danger">مسدود</span>':'<span class="badge bg-success">فعال</span>' ?></td><td class="small"><?= $u['created_at'] ?></td><td><a href="?toggle_block=<?= $u['id'] ?>" class="btn btn-sm btn-outline-danger"><?= $u['is_blocked']?'رفع مسدود':'مسدود' ?></a> <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#tokenModal<?= $u['id'] ?>">توکن</button></td></tr>
  <!-- modal -->
  <div class="modal fade" id="tokenModal<?= $u['id'] ?>" tabindex="-1"><div class="modal-dialog"><form method="post" class="modal-content"><div class="modal-header"><h6>تغییر توکن <?= $u['telegram_id'] ?></h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body row g-2"><input type="hidden" name="telegram_id" value="<?= $u['telegram_id'] ?>"><div class="col-6"><label class="form-label">مقدار (+/-)</label><input name="amount" type="number" class="form-control" required></div><div class="col-12"><label class="form-label">دلیل</label><input name="reason" class="form-control"></div></div><div class="modal-footer"><button class="btn btn-primary" name="add_token" value="1">ثبت</button></div></form></div></div>
  <?php endforeach; if(empty($users)): ?><tr><td colspan="7" class="text-center text-muted py-3">کاربری نیست</td></tr><?php endif; ?>
  </tbody></table></div>
</div>
<?php require_once __DIR__ . '/../layout/footer.php'; ?>
