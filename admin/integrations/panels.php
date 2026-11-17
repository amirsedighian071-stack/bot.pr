<?php
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../src/Core/Database.php';
require_once __DIR__ . '/../../src/Core/Config.php';
require_once __DIR__ . '/../../src/Core/Helper.php';
require_once __DIR__ . '/../../src/Core/Auth.php';
use Core\Database;
use Core\Helper;
use Core\Config;
use Core\Auth;
Auth::startSession();
if (!Auth::check()) Helper::redirect(Helper::appUrl('admin/login.php'));

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $id = (int)($_POST['id']??0);
    $name = trim($_POST['name']??'');
    $type = $_POST['type']??'marzban';
    $url = rtrim(trim($_POST['url']??''),'/');
    $username = trim($_POST['username']??'');
    $password = trim($_POST['password']??'');
    $token = trim($_POST['token']??'');
    $timeout = (int)($_POST['timeout']??10);
    $is_active = isset($_POST['is_active'])?1:0;
    $renew = $_POST['renew_method']??'append';
    if($id){
        Database::query("UPDATE panels SET name=?, type=?, url=?, username=?, password=?, token=?, timeout=?, is_active=?, renew_method=? WHERE id=?",[$name,$type,$url,$username,$password,$token,$timeout,$is_active,$renew,$id]);
        Database::query("INSERT INTO audit_logs (admin_id, action, details) VALUES (?,?,?)", [$_SESSION['admin_id']??0,'edit_panel',"ویرایش پنل $name"]);
    } else {
        Database::query("INSERT INTO panels (name,type,url,username,password,token,timeout,is_active,renew_method) VALUES (?,?,?,?,?,?,?,?,?)",[$name,$type,$url,$username,$password,$token,$timeout,$is_active,$renew]);
        Database::query("INSERT INTO audit_logs (admin_id, action, details) VALUES (?,?,?)", [$_SESSION['admin_id']??0,'add_panel',"افزودن پنل $name"]);
    }
    $_SESSION['flash_success']='ذخیره شد';
    Helper::redirect(Helper::appUrl('admin/integrations/panels.php'));
}
if (isset($_GET['delete'])) {
    Database::query("DELETE FROM panels WHERE id=?", [(int)$_GET['delete']]);
    $_SESSION['flash_success']='حذف شد';
    Helper::redirect(Helper::appUrl('admin/integrations/panels.php'));
}
if (isset($_GET['test'])) {
    require_once __DIR__ . '/../../src/Panels/PanelInterface.php';
    require_once __DIR__ . '/../../src/Panels/PanelCapabilities.php';
    require_once __DIR__ . '/../../src/Panels/AbstractPanel.php';
    require_once __DIR__ . '/../../src/Panels/PanelFactory.php';
    require_once __DIR__ . '/../../src/Panels/Drivers/PasarGuardPanel.php';
    require_once __DIR__ . '/../../src/Panels/Drivers/MarzbanPanel.php';
    require_once __DIR__ . '/../../src/Panels/Drivers/MarzneshinPanel.php';
    require_once __DIR__ . '/../../src/Panels/Drivers/XuiPanel.php';
    require_once __DIR__ . '/../../src/Panels/Drivers/RebeccaPanel.php';
    require_once __DIR__ . '/../../src/Panels/Drivers/RemnawavePanel.php';
    require_once __DIR__ . '/../../src/Panels/Drivers/GuardPanel.php';
    require_once __DIR__ . '/../../src/Panels/Drivers/WGDashboardPanel.php';
    require_once __DIR__ . '/../../src/Panels/Drivers/ManualPanel.php';
    require_once __DIR__ . '/../../src/Panels/UnifiedPanelManager.php';
    $res = \Panels\UnifiedPanelManager::testPanel((int)$_GET['test']);
    $_SESSION['flash_'.($res['success']?'success':'error')] = $res['message'];
    Helper::redirect(Helper::appUrl('admin/integrations/panels.php'));
}
if (isset($_GET['sync'])) {
    require_once __DIR__ . '/../../src/Panels/PanelInterface.php';
    require_once __DIR__ . '/../../src/Panels/PanelCapabilities.php';
    require_once __DIR__ . '/../../src/Panels/AbstractPanel.php';
    require_once __DIR__ . '/../../src/Panels/PanelFactory.php';
    require_once __DIR__ . '/../../src/Panels/Drivers/PasarGuardPanel.php';
    require_once __DIR__ . '/../../src/Panels/Drivers/MarzbanPanel.php';
    require_once __DIR__ . '/../../src/Panels/Drivers/MarzneshinPanel.php';
    require_once __DIR__ . '/../../src/Panels/Drivers/XuiPanel.php';
    require_once __DIR__ . '/../../src/Panels/Drivers/RebeccaPanel.php';
    require_once __DIR__ . '/../../src/Panels/Drivers/RemnawavePanel.php';
    require_once __DIR__ . '/../../src/Panels/Drivers/GuardPanel.php';
    require_once __DIR__ . '/../../src/Panels/Drivers/WGDashboardPanel.php';
    require_once __DIR__ . '/../../src/Panels/Drivers/ManualPanel.php';
    require_once __DIR__ . '/../../src/Panels/UnifiedPanelManager.php';
    $res = \Panels\UnifiedPanelManager::syncPanelGroups((int)$_GET['sync']);
    $_SESSION['flash_success'] = $res['message'];
    Helper::redirect(Helper::appUrl('admin/integrations/panels.php'));
}

$pageTitle='پنل‌های VPN';
require_once __DIR__ . '/../layout/header.php';

$panels = Database::fetchAll("SELECT * FROM panels ORDER BY id DESC");
$edit = null;
if (isset($_GET['edit'])) $edit = Database::fetchOne("SELECT * FROM panels WHERE id=?", [(int)$_GET['edit']]);
?>
<div class="card mb-3">
  <div class="card-header d-flex justify-content-between"><span><?= $edit?'<i class="bi bi-pencil"></i> ویرایش پنل':'<i class="bi bi-plus-circle"></i> افزودن پنل جدید' ?></span> <?php if($edit): ?><a href="panels.php" class="btn btn-sm btn-light">جدید</a><?php endif; ?></div>
  <div class="card-body">
    <form method="post" class="row g-3">
      <input type="hidden" name="id" value="<?= $edit['id']??0 ?>">
      <div class="col-md-4"><label class="form-label">نام دلخواه پنل</label><input name="name" class="form-control" required value="<?= Helper::escapeHtml($edit['name']??'') ?>" placeholder="مثلاً مرزبان اصلی"></div>
      <div class="col-md-4"><label class="form-label">نوع پنل</label>
        <select name="type" class="form-select">
          <?php $types=['pasarguard'=>'پاسارگاد','marzban'=>'مرزبان','marzneshin'=>'مرزنشین','xui_single'=>'3x-ui Single','xui_multi'=>'3x-ui Multi','rebecca'=>'ربکا','remnawave'=>'رمناویو','guard'=>'گارد','wgdashboard'=>'وایرگارد','manual'=>'انبار دستی']; foreach($types as $k=>$v): ?>
          <option value="<?= $k ?>" <?= ($edit['type']??'')===$k?'selected':'' ?>><?= $v ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4"><label class="form-label">آدرس URL/Domain</label><input name="url" class="form-control" required value="<?= Helper::escapeHtml($edit['url']??'') ?>" placeholder="https://panel.example.com"></div>
      <div class="col-md-3"><label class="form-label">نام کاربری</label><input name="username" class="form-control" value="<?= Helper::escapeHtml($edit['username']??'') ?>"></div>
      <div class="col-md-3"><label class="form-label">رمز عبور</label><input name="password" class="form-control" value="<?= Helper::escapeHtml($edit['password']??'') ?>"></div>
      <div class="col-md-3"><label class="form-label">توکن/API Key (اختیاری)</label><input name="token" class="form-control" value="<?= Helper::escapeHtml($edit['token']??'') ?>"></div>
      <div class="col-md-1"><label class="form-label">Timeout</label><input name="timeout" type="number" class="form-control" value="<?= $edit['timeout']??10 ?>"></div>
      <div class="col-md-2"><label class="form-label">تمدید</label><select name="renew_method" class="form-select"><option value="append" <?= ($edit['renew_method']??'')==='append'?'selected':'' ?>>تجمیعی</option><option value="reset" <?= ($edit['renew_method']??'')==='reset'?'selected':'' ?>>ریست</option></select></div>
      <div class="col-12"><div class="form-check"><input type="checkbox" name="is_active" class="form-check-input" id="active" <?= ($edit['is_active']??1)?'checked':'' ?>><label for="active" class="form-check-label">فعال</label></div></div>
      <div class="col-12"><button class="btn btn-primary"><?= $edit?'ویرایش':'افزودن' ?></button></div>
    </form>
  </div>
</div>

<div class="card">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0 small">
      <thead class="table-light"><tr><th>#</th><th>نام</th><th>نوع</th><th>آدرس</th><th>وضعیت</th><th>آخرین همگام</th><th>عملیات</th></tr></thead>
      <tbody>
        <?php foreach($panels as $p): ?>
        <tr>
          <td><?= $p['id'] ?></td><td class="fw-bold"><?= Helper::escapeHtml($p['name']) ?></td><td><span class="badge bg-info"><?= $p['type'] ?></span></td><td><small><?= Helper::escapeHtml($p['url']) ?></small></td>
          <td><?= $p['is_active']?'<span class="badge bg-success">فعال</span>':'<span class="badge bg-secondary">غیرفعال</span>' ?> <?= $p['last_error']?'<span class="text-danger small" title="'.$p['last_error'].'">خطا</span>':'' ?></td>
          <td class="small"><?= $p['last_sync_at']??'-' ?></td>
          <td>
            <a href="?test=<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary">تست</a>
            <a href="?sync=<?= $p['id'] ?>" class="btn btn-sm btn-outline-success">همگام‌سازی گروه‌ها</a>
            <a href="?edit=<?= $p['id'] ?>" class="btn btn-sm btn-outline-warning">ویرایش</a>
            <a href="?delete=<?= $p['id'] ?>" class="btn btn-sm btn-outline-danger" data-confirm="حذف پنل؟">حذف</a>
          </td>
        </tr>
        <?php endforeach; if(empty($panels)): ?><tr><td colspan="7" class="text-center py-3 text-muted">پنلی ثبت نشده</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require_once __DIR__ . '/../layout/footer.php'; ?>
