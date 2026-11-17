<?php
$pageTitle='پلن‌ها';
require_once __DIR__ . '/../layout/header.php';
use Core\Database;
use Core\Helper;
if($_SERVER['REQUEST_METHOD']==='POST'){
    $id=(int)($_POST['id']??0);
    $service_id=(int)($_POST['service_id']??0);
    $group_id=(int)($_POST['config_group_id']??0);
    $name=trim($_POST['name']??'');
    $price=(int)($_POST['price']??0);
    $perGb=(int)($_POST['price_per_gb']??0);
    $days=(int)($_POST['duration_days']??30);
    $vol=(float)($_POST['volume_gb']??0);
    $renew=isset($_POST['is_renewable'])?1:0;
    $active=isset($_POST['is_active'])?1:0;
    $order=(int)($_POST['sort_order']??0);
    if($id) Database::query("UPDATE plans SET service_id=?, config_group_id=?, name=?, price=?, price_per_gb=?, duration_days=?, volume_gb=?, is_renewable=?, is_active=?, sort_order=? WHERE id=?",[$service_id,$group_id,$name,$price,$perGb,$days,$vol,$renew,$active,$order,$id]);
    else Database::query("INSERT INTO plans (service_id,config_group_id,name,price,price_per_gb,duration_days,volume_gb,is_renewable,is_active,sort_order) VALUES (?,?,?,?,?,?,?,?,?,?)",[$service_id,$group_id,$name,$price,$perGb,$days,$vol,$renew,$active,$order]);
    $_SESSION['flash_success']='ذخیره شد'; Helper::redirect(Helper::appUrl('admin/plans/index.php'));
}
if(isset($_GET['delete'])){ Database::query("DELETE FROM plans WHERE id=?",[(int)$_GET['delete']]); Helper::redirect(Helper::appUrl('admin/plans/index.php')); }
$plans=Database::fetchAll("SELECT p.*, s.name as service_name, g.name as group_name FROM plans p LEFT JOIN services s ON s.id=p.service_id LEFT JOIN config_groups g ON g.id=p.config_group_id ORDER BY p.id DESC");
$services=Database::fetchAll("SELECT * FROM services");
$groups=Database::fetchAll("SELECT g.*, p.name as panel_name FROM config_groups g LEFT JOIN panels p ON p.id=g.panel_id");
$edit=null; if(isset($_GET['edit'])) $edit=Database::fetchOne("SELECT * FROM plans WHERE id=?",[(int)$_GET['edit']]);
?>
<div class="card mb-3"><div class="card-header"><?= $edit?'ویرایش پلن':'افزودن پلن' ?></div>
<div class="card-body"><form method="post" class="row g-3">
<input type="hidden" name="id" value="<?= $edit['id']??0 ?>">
<div class="col-md-3"><label class="form-label">سرویس</label><select name="service_id" class="form-select" required><option value="">انتخاب</option><?php foreach($services as $s): ?><option value="<?= $s['id'] ?>" <?= ($edit['service_id']??'')==$s['id']?'selected':'' ?>><?= Helper::escapeHtml($s['name']) ?></option><?php endforeach; ?></select></div>
<div class="col-md-3"><label class="form-label">گروه کانفیگ</label><select name="config_group_id" class="form-select" required><option value="">انتخاب</option><?php foreach($groups as $g): ?><option value="<?= $g['id'] ?>" <?= ($edit['config_group_id']??'')==$g['id']?'selected':'' ?>><?= Helper::escapeHtml($g['name']) ?> (<?= $g['panel_name'] ?>)</option><?php endforeach; ?></select></div>
<div class="col-md-3"><label class="form-label">نام پلن</label><input name="name" required class="form-control" value="<?= Helper::escapeHtml($edit['name']??'') ?>" placeholder="اقتصادی 30 روزه"></div>
<div class="col-md-3"><label class="form-label">ترتیب</label><input name="sort_order" type="number" class="form-control" value="<?= $edit['sort_order']??0 ?>"></div>
<div class="col-md-2"><label class="form-label">قیمت (تومان)</label><input name="price" type="number" class="form-control" value="<?= $edit['price']??0 ?>"></div>
<div class="col-md-2"><label class="form-label">قیمت هر گیگ (تومان)</label><input name="price_per_gb" type="number" class="form-control" value="<?= $edit['price_per_gb']??0 ?>"></div>
<div class="col-md-2"><label class="form-label">مدت (روز)</label><input name="duration_days" type="number" class="form-control" value="<?= $edit['duration_days']??30 ?>"></div>
<div class="col-md-2"><label class="form-label">حجم (GB) 0=دلخواه</label><input name="volume_gb" type="number" step="0.1" class="form-control" value="<?= $edit['volume_gb']??0 ?>"></div>
<div class="col-md-2 d-flex align-items-end gap-3"><div class="form-check"><input type="checkbox" name="is_renewable" class="form-check-input" id="ren" <?= ($edit['is_renewable']??1)?'checked':'' ?>><label for="ren" class="form-check-label">قابل تمدید</label></div><div class="form-check"><input type="checkbox" name="is_active" class="form-check-input" id="ac" <?= ($edit['is_active']??1)?'checked':'' ?>><label for="ac" class="form-check-label">فعال</label></div></div>
<div class="col-12"><button class="btn btn-primary"><?= $edit?'ویرایش':'افزودن' ?></button></div>
</form></div></div>
<div class="card"><div class="table-responsive"><table class="table small mb-0"><thead class="table-light"><tr><th>#</th><th>پلن</th><th>سرویس</th><th>گروه</th><th>قیمت</th><th>هر گیگ</th><th>مدت</th><th>حجم</th><th>وضعیت</th><th>عملیات</th></tr></thead><tbody>
<?php foreach($plans as $p): ?><tr><td><?= $p['id'] ?></td><td class="fw-bold"><?= Helper::escapeHtml($p['name']) ?></td><td><?= Helper::escapeHtml($p['service_name']) ?></td><td><?= Helper::escapeHtml($p['group_name']) ?></td><td><?= number_format($p['price']) ?></td><td><?= number_format($p['price_per_gb']) ?></td><td><?= $p['duration_days'] ?></td><td><?= $p['volume_gb']?:'دلخواه' ?></td><td><?= $p['is_active']?'<span class="badge bg-success">فعال</span>':'<span class="badge bg-secondary">غیرفعال</span>' ?></td><td><a href="?edit=<?= $p['id'] ?>" class="btn btn-sm btn-warning">ویرایش</a> <a href="?delete=<?= $p['id'] ?>" class="btn btn-sm btn-danger" data-confirm="حذف؟">حذف</a></td></tr><?php endforeach; if(empty($plans)): ?><tr><td colspan="10" class="text-center text-muted py-3">پلنی نیست</td></tr><?php endif; ?>
</tbody></table></div></div>
<?php require_once __DIR__ . '/../layout/footer.php'; ?>
