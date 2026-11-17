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
    $group_id=(int)($_POST['config_group_id']??0);
    $configs=trim($_POST['configs']??'');
    $lines=preg_split('/\r\n|\n|\r/', $configs);
    $count=0;
    foreach($lines as $line){
        $line=trim($line);
        if(!$line) continue;
        Database::query("INSERT INTO warehouse_configs (config_group_id, config_text) VALUES (?,?)", [$group_id,$line]);
        $count++;
    }
    $_SESSION['flash_success']="$count کانفیگ به انبار اضافه شد";
    Helper::redirect(Helper::appUrl('admin/warehouse/index.php'));
}
if(isset($_GET['delete'])){ Database::query("DELETE FROM warehouse_configs WHERE id=?",[(int)$_GET['delete']]); $_SESSION['flash_success']='حذف شد'; Helper::redirect(Helper::appUrl('admin/warehouse/index.php')); }
if(isset($_GET['clear_used'])){ Database::query("DELETE FROM warehouse_configs WHERE is_used=1"); $_SESSION['flash_success']='موارد استفاده‌شده حذف شد'; Helper::redirect(Helper::appUrl('admin/warehouse/index.php')); }

$pageTitle='انبار کانفیگ دستی';
require_once __DIR__ . '/../layout/header.php';
$groups=Database::fetchAll("SELECT g.*, p.name as panel_name FROM config_groups g LEFT JOIN panels p ON p.id=g.panel_id WHERE p.type='manual' OR g.id IN (SELECT config_group_id FROM warehouse_configs) ORDER BY g.id DESC");
if(empty($groups)) $groups=Database::fetchAll("SELECT g.*, p.name as panel_name FROM config_groups g LEFT JOIN panels p ON p.id=g.panel_id ORDER BY g.id DESC LIMIT 20");
$stocks=Database::fetchAll("SELECT w.*, g.name as group_name FROM warehouse_configs w LEFT JOIN config_groups g ON g.id=w.config_group_id ORDER BY w.id DESC LIMIT 100");
?>
<div class="card mb-3">
  <div class="card-header"><i class="bi bi-box-seam"></i> افزودن انبوه کانفیگ دستی</div>
  <div class="card-body">
    <form method="post" class="row g-3">
      <div class="col-md-4"><label class="form-label">گروه کانفیگ</label><select name="config_group_id" class="form-select" required><option value="">انتخاب</option><?php foreach($groups as $g): ?><option value="<?= $g['id'] ?>"><?= Helper::escapeHtml($g['name']) ?> (<?= Helper::escapeHtml($g['panel_name']??'') ?>)</option><?php endforeach; ?></select></div>
      <div class="col-12"><label class="form-label">لیست کانفیگ‌ها (هر خط یک کانفیگ/لینک)</label><textarea name="configs" rows="6" class="form-control" placeholder="vmess://...&#10;vless://...&#10;trojan://..."></textarea></div>
      <div class="col-12"><button class="btn btn-primary">افزودن به انبار</button></div>
    </form>
  </div>
</div>
<div class="card">
  <div class="card-header d-flex justify-content-between"><span>موجودی انبار (100 اخیر)</span><a href="?clear_used=1" class="btn btn-sm btn-outline-danger" data-confirm="حذف همه استفاده‌شده‌ها؟">حذف استفاده‌شده‌ها</a></div>
  <div class="table-responsive"><table class="table small mb-0"><thead class="table-light"><tr><th>#</th><th>گروه</th><th>کانفیگ</th><th>وضعیت</th><th>عملیات</th></tr></thead><tbody>
  <?php foreach($stocks as $s): ?><tr><td><?= $s['id'] ?></td><td><?= Helper::escapeHtml($s['group_name']??'') ?></td><td><code style="word-break:break-all" class="small"><?= Helper::escapeHtml(mb_substr($s['config_text'],0,80)) ?>...</code></td><td><?= $s['is_used']?'<span class="badge bg-secondary">استفاده شده</span>':'<span class="badge bg-success">موجود</span>' ?></td><td><a href="?delete=<?= $s['id'] ?>" class="btn btn-sm btn-danger" data-confirm="حذف؟">حذف</a></td></tr><?php endforeach; if(empty($stocks)): ?><tr><td colspan="5" class="text-center text-muted py-3">انبار خالی است</td></tr><?php endif; ?>
  </tbody></table></div>
</div>
<?php require_once __DIR__ . '/../layout/footer.php'; ?>
