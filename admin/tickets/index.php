<?php
$pageTitle='تیکت‌ها';
require_once __DIR__ . '/../layout/header.php';
use Core\Database;
use Core\Helper;
if(isset($_GET['close'])){ Database::query("UPDATE tickets SET status='closed' WHERE id=?",[(int)$_GET['close']]); Helper::redirect(Helper::appUrl('admin/tickets/index.php')); }
if(isset($_GET['open'])){ Database::query("UPDATE tickets SET status='open' WHERE id=?",[(int)$_GET['open']]); Helper::redirect(Helper::appUrl('admin/tickets/index.php')); }
$status=$_GET['status']??'';
$where=$status ? "WHERE t.status='$status'":'';
$tickets=Database::fetchAll("SELECT t.*, u.username, u.first_name FROM tickets t LEFT JOIN users u ON u.telegram_id=t.telegram_id $where ORDER BY t.updated_at DESC LIMIT 100");
?>
<div class="card">
  <div class="card-header d-flex gap-2">
    <a href="?" class="btn btn-sm <?= !$status?'btn-primary':'btn-outline-primary' ?>">همه</a>
    <a href="?status=open" class="btn btn-sm <?= $status==='open'?'btn-danger':'btn-outline-danger' ?>">باز</a>
    <a href="?status=answered" class="btn btn-sm <?= $status==='answered'?'btn-success':'btn-outline-success' ?>">پاسخ داده</a>
    <a href="?status=closed" class="btn btn-sm <?= $status==='closed'?'btn-secondary':'btn-outline-secondary' ?>">بسته</a>
  </div>
  <div class="table-responsive"><table class="table table-hover small mb-0"><thead class="table-light"><tr><th>#</th><th>کاربر</th><th>موضوع</th><th>وضعیت</th><th>بروزرسانی</th><th>عملیات</th></tr></thead><tbody>
  <?php foreach($tickets as $t): ?><tr><td><?= $t['id'] ?></td><td><?= $t['telegram_id'] ?> <small class="text-muted">@<?= $t['username']??'' ?></small></td><td><?= Helper::escapeHtml($t['subject']) ?></td><td><span class="badge bg-<?= $t['status']==='open'?'danger':($t['status']==='closed'?'secondary':'success') ?>"><?= $t['status'] ?></span></td><td class="small"><?= $t['updated_at'] ?></td><td><a href="view.php?id=<?= $t['id'] ?>" class="btn btn-sm btn-primary">مشاهده چت</a> <?php if($t['status']!=='closed'): ?><a href="?close=<?= $t['id'] ?>" class="btn btn-sm btn-outline-secondary">بستن</a><?php else: ?><a href="?open=<?= $t['id'] ?>" class="btn btn-sm btn-outline-success">بازکردن</a><?php endif; ?></td></tr><?php endforeach; if(empty($tickets)): ?><tr><td colspan="6" class="text-center text-muted py-3">تیکتی نیست</td></tr><?php endif; ?>
  </tbody></table></div>
</div>
<?php require_once __DIR__ . '/../layout/footer.php'; ?>
