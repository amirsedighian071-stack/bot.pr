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

if(isset($_GET['approve'])){ Database::query("UPDATE token_gifts SET status='approved' WHERE id=?",[(int)$_GET['approve']]); $_SESSION['flash_success']='تأیید شد'; Helper::redirect(Helper::appUrl('admin/tickets/gifts.php')); }
if(isset($_GET['paid'])){ Database::query("UPDATE token_gifts SET status='paid' WHERE id=?",[(int)$_GET['paid']]); $_SESSION['flash_success']='پرداخت شد ثبت شد'; Helper::redirect(Helper::appUrl('admin/tickets/gifts.php')); }
if(isset($_GET['reject'])){
    $id=(int)$_GET['reject'];
    $g=Database::fetchOne("SELECT * FROM token_gifts WHERE id=?",[$id]);
    if($g){
        $u=Database::fetchOne("SELECT * FROM users WHERE telegram_id=?",[$g['telegram_id']]);
        if($u){ Database::query("UPDATE users SET tokens=tokens+? WHERE telegram_id=?",[$g['tokens_cost'],$g['telegram_id']]); Database::query("INSERT INTO token_transactions (user_id,telegram_id,amount,type,reason,balance_after) VALUES (?,?,?,?,?,?)",[$u['id'],$g['telegram_id'],$g['tokens_cost'],'redeem','بازگشت توکن هدیه رد شده', $u['tokens']+$g['tokens_cost']]); }
        Database::query("UPDATE token_gifts SET status='rejected' WHERE id=?",[$id]);
        $_SESSION['flash_success']='رد شد و توکن بازگشت';
    }
    Helper::redirect(Helper::appUrl('admin/tickets/gifts.php'));
}
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['gift_action'])){
    // placeholder for future POST handling
}

$pageTitle='درخواست‌های هدیه نقدی و توکن';
require_once __DIR__ . '/../layout/header.php';
$gifts=Database::fetchAll("SELECT * FROM token_gifts ORDER BY id DESC LIMIT 100");
$tx=Database::fetchAll("SELECT * FROM token_transactions ORDER BY id DESC LIMIT 50");
?>
<div class="card mb-3">
  <div class="card-header"><i class="bi bi-cash-coin"></i> درخواست‌های هدیه نقدی (13 توکن = 45,000 تومان)</div>
  <div class="table-responsive"><table class="table small mb-0"><thead class="table-light"><tr><th>#</th><th>کاربر</th><th>نوع</th><th>توکن</th><th>کارت</th><th>وضعیت</th><th>تاریخ</th><th>عملیات</th></tr></thead><tbody>
  <?php foreach($gifts as $g): ?><tr><td><?= $g['id'] ?></td><td><?= $g['telegram_id'] ?></td><td><?= $g['gift_type'] ?></td><td><?= $g['tokens_cost'] ?></td><td><code><?= $g['card_number']??'-' ?></code><br><small><?= Helper::escapeHtml($g['card_holder']??'') ?></small></td><td><span class="badge bg-<?= $g['status']==='paid'?'success':($g['status']==='pending'?'warning':'secondary') ?>"><?= $g['status'] ?></span></td><td class="small"><?= $g['created_at'] ?></td><td><?php if($g['status']==='pending'): ?><a href="?approve=<?= $g['id'] ?>" class="btn btn-sm btn-success">تأیید</a> <a href="?paid=<?= $g['id'] ?>" class="btn btn-sm btn-primary">پرداخت شد</a> <a href="?reject=<?= $g['id'] ?>" class="btn btn-sm btn-danger" data-confirm="رد و بازگشت توکن؟">رد</a><?php endif; ?></td></tr><?php endforeach; if(empty($gifts)): ?><tr><td colspan="8" class="text-center text-muted py-3">درخواستی نیست</td></tr><?php endif; ?>
  </tbody></table></div>
</div>
<div class="card">
  <div class="card-header"><i class="bi bi-coin"></i> تراکنش‌های توکن (50 اخیر)</div>
  <div class="table-responsive"><table class="table small mb-0"><thead class="table-light"><tr><th>کاربر</th><th>مقدار</th><th>نوع</th><th>دلیل</th><th>موجودی بعد</th><th>زمان</th></tr></thead><tbody>
  <?php foreach($tx as $t): ?><tr><td><?= $t['telegram_id'] ?></td><td class="<?= $t['amount']>0?'text-success':'text-danger' ?>"><?= $t['amount']>0?'+':'' ?><?= $t['amount'] ?></td><td><?= $t['type'] ?></td><td class="small"><?= Helper::escapeHtml($t['reason']??'') ?></td><td><?= $t['balance_after'] ?></td><td class="small"><?= $t['created_at'] ?></td></tr><?php endforeach; ?>
  </tbody></table></div>
</div>
<?php require_once __DIR__ . '/../layout/footer.php'; ?>
