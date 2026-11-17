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
    $name=trim($_POST['name']??'');
    $desc=trim($_POST['description']??'');
    $active=isset($_POST['is_active'])?1:0;
    $order=(int)($_POST['sort_order']??0);
    if($id) Database::query("UPDATE services SET name=?, description=?, is_active=?, sort_order=? WHERE id=?",[$name,$desc,$active,$order,$id]);
    else Database::query("INSERT INTO services (name,description,is_active,sort_order) VALUES (?,?,?,?)",[$name,$desc,$active,$order]);
    $_SESSION['flash_success']='ذخیره شد'; Helper::redirect(Helper::appUrl('admin/services/index.php'));
}
if(isset($_GET['delete'])){ Database::query("DELETE FROM services WHERE id=?",[(int)$_GET['delete']]); $_SESSION['flash_success']='حذف شد'; Helper::redirect(Helper::appUrl('admin/services/index.php')); }

$pageTitle='سرویس‌ها';
require_once __DIR__ . '/../layout/header.php';
$services=Database::fetchAll("SELECT * FROM services ORDER BY sort_order, id DESC");
$edit=null; if(isset($_GET['edit'])) $edit=Database::fetchOne("SELECT * FROM services WHERE id=?",[(int)$_GET['edit']]);
?>
<div class="card mb-3"><div class="card-header"><?= $edit?'ویرایش':'افزودن سرویس' ?></div>
<div class="card-body"><form method="post" class="row g-3">
<input type="hidden" name="id" value="<?= $edit['id']??0 ?>">
<div class="col-md-4"><label class="form-label">نام سرویس</label><input name="name" required class="form-control" value="<?= Helper::escapeHtml($edit['name']??'') ?>"></div>
<div class="col-md-4"><label class="form-label">ترتیب</label><input name="sort_order" type="number" class="form-control" value="<?= $edit['sort_order']??0 ?>"></div>
<div class="col-md-4 d-flex align-items-end"><div class="form-check"><input type="checkbox" name="is_active" class="form-check-input" id="ac2" <?= ($edit['is_active']??1)?'checked':'' ?>><label for="ac2" class="form-check-label">فعال</label></div></div>
<div class="col-12"><label class="form-label">توضیحات</label><textarea name="description" rows="2" class="form-control"><?= Helper::escapeHtml($edit['description']??'') ?></textarea></div>
<div class="col-12"><button class="btn btn-primary"><?= $edit?'ویرایش':'افزودن' ?></button></div>
</form></div></div>
<div class="card"><div class="table-responsive"><table class="table small mb-0"><thead class="table-light"><tr><th>#</th><th>نام</th><th>وضعیت</th><th>عملیات</th></tr></thead><tbody>
<?php foreach($services as $s): ?><tr><td><?= $s['id'] ?></td><td><?= Helper::escapeHtml($s['name']) ?></td><td><?= $s['is_active']?'<span class="badge bg-success">فعال</span>':'<span class="badge bg-secondary">غیرفعال</span>' ?></td><td><a href="?edit=<?= $s['id'] ?>" class="btn btn-sm btn-warning">ویرایش</a> <a href="?delete=<?= $s['id'] ?>" class="btn btn-sm btn-danger" data-confirm="حذف؟">حذف</a></td></tr><?php endforeach; ?>
</tbody></table></div></div>
<?php require_once __DIR__ . '/../layout/footer.php'; ?>
