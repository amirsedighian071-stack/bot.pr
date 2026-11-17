<?php
$pageTitle='حجم‌های پیش‌فرض';
require_once __DIR__ . '/../layout/header.php';
use Core\Database;
use Core\Helper;
if($_SERVER['REQUEST_METHOD']==='POST'){
    $id=(int)($_POST['id']??0);
    $label=trim($_POST['label']??'');
    $gb=(float)($_POST['gb']??0);
    $order=(int)($_POST['sort_order']??0);
    $active=isset($_POST['is_active'])?1:0;
    if($id) Database::query("UPDATE volumes SET label=?, gb=?, sort_order=?, is_active=? WHERE id=?",[$label,$gb,$order,$active,$id]);
    else Database::query("INSERT INTO volumes (label,gb,sort_order,is_active) VALUES (?,?,?,?)",[$label,$gb,$order,$active]);
    Helper::redirect(Helper::appUrl('admin/volumes/index.php'));
}
if(isset($_GET['delete'])){ Database::query("DELETE FROM volumes WHERE id=?",[(int)$_GET['delete']]); Helper::redirect(Helper::appUrl('admin/volumes/index.php')); }
$volumes=Database::fetchAll("SELECT * FROM volumes ORDER BY sort_order");
$edit=null; if(isset($_GET['edit'])) $edit=Database::fetchOne("SELECT * FROM volumes WHERE id=?",[(int)$_GET['edit']]);
?>
<div class="card mb-3"><div class="card-header"><?= $edit?'ویرایش حجم':'افزودن حجم' ?></div>
<div class="card-body"><form method="post" class="row g-3">
<input type="hidden" name="id" value="<?= $edit['id']??0 ?>">
<div class="col-md-3"><label class="form-label">برچسب</label><input name="label" required class="form-control" value="<?= Helper::escapeHtml($edit['label']??'') ?>" placeholder="50 گیگ"></div>
<div class="col-md-3"><label class="form-label">گیگابایت</label><input name="gb" type="number" step="0.1" required class="form-control" value="<?= $edit['gb']??'' ?>"></div>
<div class="col-md-2"><label class="form-label">ترتیب</label><input name="sort_order" type="number" class="form-control" value="<?= $edit['sort_order']??0 ?>"></div>
<div class="col-md-2 d-flex align-items-end"><div class="form-check"><input type="checkbox" name="is_active" class="form-check-input" id="ac" <?= ($edit['is_active']??1)?'checked':'' ?>><label for="ac" class="form-check-label">فعال</label></div></div>
<div class="col-12"><button class="btn btn-primary"><?= $edit?'ویرایش':'افزودن' ?></button></div>
</form></div></div>
<div class="card"><div class="table-responsive"><table class="table small mb-0"><thead class="table-light"><tr><th>#</th><th>برچسب</th><th>GB</th><th>ترتیب</th><th>وضعیت</th><th>عملیات</th></tr></thead><tbody>
<?php foreach($volumes as $v): ?><tr><td><?= $v['id'] ?></td><td><?= Helper::escapeHtml($v['label']) ?></td><td><?= $v['gb'] ?></td><td><?= $v['sort_order'] ?></td><td><?= $v['is_active']?'<span class="badge bg-success">فعال</span>':'<span class="badge bg-secondary">غیرفعال</span>' ?></td><td><a href="?edit=<?= $v['id'] ?>" class="btn btn-sm btn-warning">ویرایش</a> <a href="?delete=<?= $v['id'] ?>" class="btn btn-sm btn-danger" data-confirm="حذف؟">حذف</a></td></tr><?php endforeach; ?>
</tbody></table></div></div>
<?php require_once __DIR__ . '/../layout/footer.php'; ?>
