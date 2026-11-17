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

if($_SERVER['REQUEST_METHOD']==='POST'){
    $id=(int)($_POST['id']??0);
    $code=trim($_POST['code']??'');
    $type=$_POST['type']??'percent';
    $value=(float)($_POST['value']??0);
    $max=(int)($_POST['max_uses']??0);
    $min=(int)($_POST['min_amount']??0);
    $plan=(int)($_POST['plan_id']??0) ?: null;
    $expire=$_POST['expire_at']??null; if($expire==='') $expire=null;
    $apply=isset($_POST['apply_on_fee'])?1:0;
    $active=isset($_POST['is_active'])?1:0;
    if($id) Database::query("UPDATE discounts SET code=?, type=?, value=?, max_uses=?, min_amount=?, plan_id=?, expire_at=?, apply_on_fee=?, is_active=? WHERE id=?",[$code,$type,$value,$max,$min,$plan,$expire,$apply,$active,$id]);
    else Database::query("INSERT INTO discounts (code,type,value,max_uses,min_amount,plan_id,expire_at,apply_on_fee,is_active) VALUES (?,?,?,?,?,?,?,?,?)",[$code,$type,$value,$max,$min,$plan,$expire,$apply,$active]);
    $_SESSION['flash_success']='ذخیره شد'; Helper::redirect(Helper::appUrl('admin/discounts/index.php'));
}
if(isset($_GET['delete'])){ Database::query("DELETE FROM discounts WHERE id=?",[(int)$_GET['delete']]); $_SESSION['flash_success']='حذف شد'; Helper::redirect(Helper::appUrl('admin/discounts/index.php')); }

$pageTitle='کد تخفیف';
require_once __DIR__ . '/../layout/header.php';

$discounts=Database::fetchAll("SELECT d.*, p.name as plan_name FROM discounts d LEFT JOIN plans p ON p.id=d.plan_id ORDER BY d.id DESC");
$plans=Database::fetchAll("SELECT * FROM plans");
$edit=null; if(isset($_GET['edit'])) $edit=Database::fetchOne("SELECT * FROM discounts WHERE id=?",[(int)$_GET['edit']]);
?>
<div class="card mb-3"><div class="card-header"><?= $edit?'ویرایش':'افزودن کد تخفیف' ?></div>
<div class="card-body"><form method="post" class="row g-3">
<input type="hidden" name="id" value="<?= $edit['id']??0 ?>">
<div class="col-md-2"><label class="form-label">کد</label><input name="code" required class="form-control" value="<?= Helper::escapeHtml($edit['code']??'') ?>" placeholder="WELCOME10"></div>
<div class="col-md-2"><label class="form-label">نوع</label><select name="type" class="form-select"><option value="percent" <?= ($edit['type']??'')==='percent'?'selected':'' ?>>درصدی</option><option value="fixed" <?= ($edit['type']??'')==='fixed'?'selected':'' ?>>مبلغ ثابت</option></select></div>
<div class="col-md-2"><label class="form-label">مقدار</label><input name="value" type="number" step="0.01" class="form-control" value="<?= $edit['value']??'' ?>"></div>
<div class="col-md-2"><label class="form-label">سقف استفاده</label><input name="max_uses" type="number" class="form-control" value="<?= $edit['max_uses']??0 ?>"></div>
<div class="col-md-2"><label class="form-label">حداقل خرید</label><input name="min_amount" type="number" class="form-control" value="<?= $edit['min_amount']??0 ?>"></div>
<div class="col-md-2"><label class="form-label">پلن خاص</label><select name="plan_id" class="form-select"><option value="">همه پلن‌ها</option><?php foreach($plans as $pl): ?><option value="<?= $pl['id'] ?>" <?= ($edit['plan_id']??'')==$pl['id']?'selected':'' ?>><?= Helper::escapeHtml($pl['name']) ?></option><?php endforeach; ?></select></div>
<div class="col-md-3"><label class="form-label">انقضا</label><input name="expire_at" type="datetime-local" class="form-control" value="<?= $edit['expire_at']?date('Y-m-d\TH:i',strtotime($edit['expire_at'])):'' ?>"></div>
<div class="col-md-3 d-flex align-items-end gap-3"><div class="form-check"><input type="checkbox" name="apply_on_fee" class="form-check-input" id="af" <?= ($edit['apply_on_fee']??0)?'checked':'' ?>><label for="af" class="form-check-label">روی کارمزد هم اعمال شود</label></div><div class="form-check"><input type="checkbox" name="is_active" class="form-check-input" id="ac" <?= ($edit['is_active']??1)?'checked':'' ?>><label for="ac" class="form-check-label">فعال</label></div></div>
<div class="col-12"><button class="btn btn-primary"><?= $edit?'ویرایش':'افزودن' ?></button></div>
</form></div></div>
<div class="card"><div class="table-responsive"><table class="table small mb-0"><thead><tr><th>کد</th><th>نوع/مقدار</th><th>استفاده</th><th>انقضا</th><th>پلن</th><th>وضعیت</th><th>عملیات</th></tr></thead><tbody>
<?php foreach($discounts as $d): ?><tr><td><code><?= $d['code'] ?></code></td><td><?= $d['type']==='percent'?$d['value'].'%':number_format($d['value']).' ت' ?></td><td><?= $d['used_count'] ?>/<?= $d['max_uses']?:'∞' ?></td><td class="small"><?= $d['expire_at']?:'-' ?></td><td><?= Helper::escapeHtml($d['plan_name']??'همه') ?></td><td><?= $d['is_active']?'<span class="badge bg-success">فعال</span>':'<span class="badge bg-secondary">غیرفعال</span>' ?></td><td><a href="?edit=<?= $d['id'] ?>" class="btn btn-sm btn-warning">ویرایش</a> <a href="?delete=<?= $d['id'] ?>" class="btn btn-sm btn-danger" data-confirm="حذف؟">حذف</a></td></tr><?php endforeach; if(empty($discounts)): ?><tr><td colspan="7" class="text-center text-muted py-3">موردی نیست</td></tr><?php endif; ?>
</tbody></table></div></div>
<?php require_once __DIR__ . '/../layout/footer.php'; ?>
