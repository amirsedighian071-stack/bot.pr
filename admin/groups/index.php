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

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $id=(int)($_POST['id']??0);
    $name=trim($_POST['name']??'');
    $panel_id=(int)($_POST['panel_id']??0);
    $inbound=trim($_POST['inbound_ids']??'');
    $identifier=trim($_POST['group_identifier']??'');
    $is_active=isset($_POST['is_active'])?1:0;
    if($id) Database::query("UPDATE config_groups SET name=?, panel_id=?, inbound_ids=?, group_identifier=?, is_active=? WHERE id=?", [$name,$panel_id,$inbound,$identifier,$is_active,$id]);
    else Database::query("INSERT INTO config_groups (name,panel_id,inbound_ids,group_identifier,is_active) VALUES (?,?,?,?,?)", [$name,$panel_id,$inbound,$identifier,$is_active]);
    $_SESSION['flash_success']='ذخیره شد';
    Helper::redirect(Helper::appUrl('admin/groups/index.php'));
}
if(isset($_GET['delete'])){ Database::query("DELETE FROM config_groups WHERE id=?", [(int)$_GET['delete']]); $_SESSION['flash_success']='حذف شد'; Helper::redirect(Helper::appUrl('admin/groups/index.php')); }

$pageTitle='گروه‌های کانفیگ';
require_once __DIR__ . '/../layout/header.php';

$groups=Database::fetchAll("SELECT g.*, p.name as panel_name, p.type FROM config_groups g LEFT JOIN panels p ON p.id=g.panel_id ORDER BY g.id DESC");
$panels=Database::fetchAll("SELECT * FROM panels WHERE is_active=1");
$edit=null; if(isset($_GET['edit'])) $edit=Database::fetchOne("SELECT * FROM config_groups WHERE id=?", [(int)$_GET['edit']]);
?>
<div class="card mb-3">
  <div class="card-header"><?= $edit?'ویرایش گروه':'افزودن گروه کانفیگ' ?></div>
  <div class="card-body">
    <form method="post" class="row g-3">
      <input type="hidden" name="id" value="<?= $edit['id']??0 ?>">
      <div class="col-md-4"><label class="form-label">نام گروه</label><input name="name" class="form-control" required value="<?= Helper::escapeHtml($edit['name']??'') ?>" placeholder="VIP پاسارگاد"></div>
      <div class="col-md-4"><label class="form-label">پنل</label><select name="panel_id" class="form-select" required><option value="">انتخاب</option><?php foreach($panels as $p): ?><option value="<?= $p['id'] ?>" <?= ($edit['panel_id']??'')==$p['id']?'selected':'' ?>><?= Helper::escapeHtml($p['name']) ?> (<?= $p['type'] ?>)</option><?php endforeach; ?></select></div>
      <div class="col-md-4"><label class="form-label">Inbound/Group IDs</label><input name="inbound_ids" class="form-control" value="<?= Helper::escapeHtml($edit['inbound_ids']??'') ?>" placeholder="مثلاً 1,2 یا tag ها"></div>
      <div class="col-md-6"><label class="form-label">شناسه گروه در پنل (اختیاری)</label><input name="group_identifier" class="form-control" value="<?= Helper::escapeHtml($edit['group_identifier']??'') ?>"></div>
      <div class="col-md-3 d-flex align-items-end"><div class="form-check"><input type="checkbox" name="is_active" class="form-check-input" id="ac" <?= ($edit['is_active']??1)?'checked':'' ?>><label for="ac" class="form-check-label">فعال</label></div></div>
      <div class="col-12"><button class="btn btn-primary"><?= $edit?'ویرایش':'افزودن' ?></button> <?php if($edit): ?><a href="index.php" class="btn btn-light">انصراف</a><?php endif; ?></div>
    </form>
  </div>
</div>
<div class="card">
  <div class="table-responsive">
    <table class="table table-hover small mb-0"><thead><tr><th>#</th><th>نام گروه</th><th>پنل</th><th>Inbound</th><th>وضعیت</th><th>عملیات</th></tr></thead>
    <tbody>
      <?php foreach($groups as $g): ?><tr><td><?= $g['id'] ?></td><td><?= Helper::escapeHtml($g['name']) ?></td><td><?= Helper::escapeHtml($g['panel_name']) ?> <small class="text-muted"><?= $g['type'] ?></small></td><td><code><?= $g['inbound_ids'] ?: $g['group_identifier'] ?: '-' ?></code></td><td><?= $g['is_active']?'<span class="badge bg-success">فعال</span>':'<span class="badge bg-secondary">غیرفعال</span>' ?></td><td><a href="?edit=<?= $g['id'] ?>" class="btn btn-sm btn-outline-warning">ویرایش</a> <a href="?delete=<?= $g['id'] ?>" class="btn btn-sm btn-outline-danger" data-confirm="حذف گروه؟">حذف</a></td></tr><?php endforeach; if(empty($groups)): ?><tr><td colspan="6" class="text-center text-muted py-3">گروهی نیست</td></tr><?php endif; ?>
    </tbody></table>
  </div>
</div>
<?php require_once __DIR__ . '/../layout/footer.php'; ?>
