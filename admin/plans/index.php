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

// مهاجرت درجا برای نصب‌های قدیمی — افزودن ستون group_ids به plans اگر وجود ندارد
try { Database::query("SELECT group_ids FROM plans LIMIT 1"); } catch (\Throwable $e) {
    try { Database::query("ALTER TABLE plans ADD COLUMN group_ids VARCHAR(255) NULL COMMENT 'Override panel group_ids for this plan (e.g. Ava test id)'"); } catch (\Throwable $e2) {}
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $id=(int)($_POST['id']??0);
    $service_id=(int)($_POST['service_id']??0);
    $group_id=(int)($_POST['config_group_id']??0);
    $plan_group_ids=trim($_POST['group_ids']??$_POST['group_ids_manual']??'');
    // اگر select چندگزینه‌ای بود
    if($plan_group_ids==='' && isset($_POST['group_ids_disabled']) && !empty($_POST['group_ids_manual'])) $plan_group_ids=trim($_POST['group_ids_manual']);
    $name=trim($_POST['name']??'');
    $price=(int)($_POST['price']??0);
    $perGb=(int)($_POST['price_per_gb']??0);
    $days=(int)($_POST['duration_days']??30);
    $vol=(float)($_POST['volume_gb']??0);
    $renew=isset($_POST['is_renewable'])?1:0;
    $active=isset($_POST['is_active'])?1:0;
    $order=(int)($_POST['sort_order']??0);
    // اعتبارسنجی group_ids: اگر پر شده، باید عدد/لیست اعداد یا JSON باشد؛ خالی = استفاده از config_groups.inbound_ids
    if($plan_group_ids !== ''){
        // نرمال‌سازی: "1, 2" -> "1,2"
        $tmp = $plan_group_ids;
        // اگر JSON آرایه بود، به CSV تبدیل کن
        $dec = json_decode($tmp, true);
        if(is_array($dec)) $plan_group_ids = implode(',', array_map('intval', $dec));
        else $plan_group_ids = implode(',', array_filter(array_map('trim', explode(',', $tmp)), fn($v) => $v !== ''));
    }
    if($id) Database::query("UPDATE plans SET service_id=?, config_group_id=?, group_ids=?, name=?, price=?, price_per_gb=?, duration_days=?, volume_gb=?, is_renewable=?, is_active=?, sort_order=? WHERE id=?",[$service_id,$group_id,$plan_group_ids?:null,$name,$price,$perGb,$days,$vol,$renew,$active,$order,$id]);
    else Database::query("INSERT INTO plans (service_id,config_group_id,group_ids,name,price,price_per_gb,duration_days,volume_gb,is_renewable,is_active,sort_order) VALUES (?,?,?,?,?,?,?,?,?,?,?)",[$service_id,$group_id,$plan_group_ids?:null,$name,$price,$perGb,$days,$vol,$renew,$active,$order]);
    $_SESSION['flash_success']='ذخیره شد'; Helper::redirect(Helper::appUrl('admin/plans/index.php'));
}
if(isset($_GET['delete'])){ Database::query("DELETE FROM plans WHERE id=?",[(int)$_GET['delete']]); $_SESSION['flash_success']='حذف شد'; Helper::redirect(Helper::appUrl('admin/plans/index.php')); }

$pageTitle='پلن‌ها';
require_once __DIR__ . '/../layout/header.php';
$plans=Database::fetchAll("SELECT p.*, s.name as service_name, g.name as group_name, g.inbound_ids as group_inbound, pn.name as panel_name FROM plans p LEFT JOIN services s ON s.id=p.service_id LEFT JOIN config_groups g ON g.id=p.config_group_id LEFT JOIN panels pn ON pn.id=g.panel_id ORDER BY p.id DESC");
$services=Database::fetchAll("SELECT * FROM services");
$groups=Database::fetchAll("SELECT g.*, p.name as panel_name, p.type, p.capabilities_json FROM config_groups g LEFT JOIN panels p ON p.id=g.panel_id");
$panels=Database::fetchAll("SELECT * FROM panels WHERE is_active=1");
// نقشه panel_id => گروه‌های همگام‌شده از capabilities_json (برای انتخاب گروه پنل)
$panelGroupsMap = [];
foreach($panels as $pn){
    $caps = $pn['capabilities_json'] ?? null;
    if($caps){
        $decoded = is_string($caps) ? json_decode($caps, true) : $caps;
        if(is_array($decoded) && !empty($decoded)){
            // فرمت پاسارگاد: [{id, name, type}, ...]
            $panelGroupsMap[$pn['id']] = $decoded;
        }
    }
}
$edit=null; if(isset($_GET['edit'])) $edit=Database::fetchOne("SELECT * FROM plans WHERE id=?",[(int)$_GET['edit']]);
?>
<div class="card mb-3"><div class="card-header"><?= $edit?'ویرایش پلن':'افزودن پلن' ?></div>
<div class="card-body"><form method="post" class="row g-3">
<input type="hidden" name="id" value="<?= $edit['id']??0 ?>">
<div class="col-md-3"><label class="form-label">سرویس</label><select name="service_id" class="form-select" required><option value="">انتخاب</option><?php foreach($services as $s): ?><option value="<?= $s['id'] ?>" <?= ($edit['service_id']??'')==$s['id']?'selected':'' ?>><?= Helper::escapeHtml($s['name']) ?></option><?php endforeach; ?></select></div>
<div class="col-md-3"><label class="form-label">گروه کانفیگ (پنل + Inbound)</label><select name="config_group_id" id="config_group_id" class="form-select" required><option value="">انتخاب</option><?php foreach($groups as $g): ?><option value="<?= $g['id'] ?>" data-panel="<?= $g['panel_id'] ?>" <?= ($edit['config_group_id']??'')==$g['id']?'selected':'' ?>><?= Helper::escapeHtml($g['name']) ?> (<?= Helper::escapeHtml($g['panel_name']) ?> — <?= $g['inbound_ids'] ?: $g['group_identifier'] ?: 'بدون Group ID' ?>)</option><?php endforeach; ?></select><small class="text-muted">هر پلن باید به یک «گروه کانفیگ» وصل باشد. Group ID واقعی پنل (Ava ...) در این گروه ذخیره می‌شود.</small></div>
<div class="col-md-3"><label class="form-label">نام پلن</label><input name="name" required class="form-control" value="<?= Helper::escapeHtml($edit['name']??'') ?>" placeholder="اقتصادی 30 روزه"></div>
<div class="col-md-3"><label class="form-label">ترتیب</label><input name="sort_order" type="number" class="form-control" value="<?= $edit['sort_order']??0 ?>"></div>

<div class="col-md-6"><label class="form-label">گروه پنل (Override — برای Outbound Center / PasarGuard)</label>
<select name="group_ids" id="group_ids" class="form-select">
<option value="">— استفاده از Inbound گروه کانفیگ (پیش‌فرض) —</option>
<?php
// رندر همه گروه‌های همگام‌شده برای انتخاب سریع (فیلتر با JS بر اساس panel)
foreach($panelGroupsMap as $pid => $glist){
    $pname = '';
    foreach($panels as $pp){ if($pp['id']==$pid) $pname=$pp['name']; }
    foreach($glist as $gr){
        $gid = $gr['id'] ?? '';
        $gname = $gr['name'] ?? 'Group '.$gid;
        $sel = (($edit['group_ids']??'') == (string)$gid || ($edit['group_ids']??'') == $gname) ? 'selected' : '';
        echo '<option value="'.Helper::escapeHtml((string)$gid).'" data-panel="'.$pid.'" '.$sel.'>'.Helper::escapeHtml($pname).' — '.Helper::escapeHtml($gname).' ('.$gid.')</option>';
    }
}
?>
</select>
<input type="text" name="group_ids_manual" id="group_ids_manual" class="form-control mt-2" placeholder="یا دستی وارد کنید: مثلاً 5 یا 1,2" value="<?= Helper::escapeHtml($edit['group_ids']??'') ?>" style="display:none">
<small class="text-muted d-block mt-1">اگر پلن باید دقیقاً داخل یک گروه پنل ساخته شود (مثل <b>Ava test</b>، <b>Ava max</b>)، آن را اینجا انتخاب کنید. در این صورت هنگام ساخت کاربر، <code>group_ids=[ انتخاب شما ]</code> به پنل ارسال می‌شود و لینک ساب هیچ‌وقت خالی (AVANET 0) نمی‌ماند. خالی بگذارید تا از <code>inbound_ids</code> گروه کانفیگ استفاده شود.</small>
<div class="mt-1"><a href="<?= Helper::appUrl('admin/integrations/panels.php') ?>" target="_blank" class="small">برای به‌روزرسانی لیست گروه‌ها: پنل‌ها → همگام‌سازی گروه‌ها</a></div>
</div>
<div class="col-md-6">
<div class="alert alert-info small mb-0">
<b>راهنما Outbound Center:</b><br>
در پنل Outbound Center مسیر: <code>ویرایش کاربر → گروه‌ها → Ava test / Ava max</code> هر گروه یک ID عددی دارد (در API <code>/api/groups</code>).<br>
برای اینکه لینک کاربر خالی نباشد، باید هر پلن (و تست رایگان) به یک گروه وصل باشد.
<ul class="mb-0 ps-3">
<li>ابتدا در <a href="<?= Helper::appUrl('admin/groups/index.php') ?>">گروه‌های کانفیگ</a> یک گروه بسازید و در فیلد <code>Inbound/Group IDs</code> شناسه گروه پنل را بگذارید (مثلاً همان ID عددی Ava test).</li>
<li>سپس در همین فرم، <code>گروه پنل</code> را روی همان Ava test تنظیم کنید (یا خالی بگذارید اگر inbound گروه درست است).</li>
<li>در <a href="<?= Helper::appUrl('admin/settings/general.php') ?>">تنظیمات عمومی → تست رایگان</a> گروه پیش‌فرض تست را انتخاب کنید.</li>
</ul>
</div>
</div>

<div class="col-md-2"><label class="form-label">قیمت (تومان)</label><input name="price" type="number" class="form-control" value="<?= $edit['price']??0 ?>"></div>
<div class="col-md-2"><label class="form-label">قیمت هر گیگ (تومان)</label><input name="price_per_gb" type="number" class="form-control" value="<?= $edit['price_per_gb']??0 ?>"></div>
<div class="col-md-2"><label class="form-label">مدت (روز)</label><input name="duration_days" type="number" class="form-control" value="<?= $edit['duration_days']??30 ?>"></div>
<div class="col-md-2"><label class="form-label">حجم (GB) 0=دلخواه</label><input name="volume_gb" type="number" step="0.1" class="form-control" value="<?= $edit['volume_gb']??0 ?>"></div>
<div class="col-md-2 d-flex align-items-end gap-3"><div class="form-check"><input type="checkbox" name="is_renewable" class="form-check-input" id="ren" <?= ($edit['is_renewable']??1)?'checked':'' ?>><label for="ren" class="form-check-label">قابل تمدید</label></div><div class="form-check"><input type="checkbox" name="is_active" class="form-check-input" id="ac" <?= ($edit['is_active']??1)?'checked':'' ?>><label for="ac" class="form-check-label">فعال</label></div></div>
<div class="col-12"><button class="btn btn-primary"><?= $edit?'ویرایش':'افزودن' ?></button></div>
</form></div></div>

<script>
(function(){
  const cfgSel = document.getElementById('config_group_id');
  const grpSel = document.getElementById('group_ids');
  const manual = document.getElementById('group_ids_manual');
  function syncGroupOptions(){
    const panelId = cfgSel.options[cfgSel.selectedIndex]?.getAttribute('data-panel');
    let hasVisible=false;
    for(const opt of grpSel.options){
      const p = opt.getAttribute('data-panel');
      if(!p){ opt.style.display=''; continue; } // گزینه پیش‌فرض
      if(!panelId){ opt.style.display='none'; continue; }
      if(p===panelId){ opt.style.display=''; hasVisible=true; } else opt.style.display='none';
    }
    // اگر هیچ گروه همگام‌شده‌ای برای این پنل نیست، ورودی دستی را نشان بده
    if(!hasVisible && panelId){
      grpSel.style.display='none';
      manual.style.display='';
      manual.name='group_ids';
      grpSel.name='group_ids_disabled';
    } else {
      grpSel.style.display='';
      manual.style.display='none';
      manual.name='group_ids_manual';
      grpSel.name='group_ids';
      // اگر دستی پر شده، آن را به select منتقل کن
      if(manual.value && !grpSel.value) grpSel.value = manual.value.trim();
    }
  }
  cfgSel.addEventListener('change', syncGroupOptions);
  syncGroupOptions();
})();
</script>

<div class="card"><div class="table-responsive"><table class="table small mb-0"><thead class="table-light"><tr><th>#</th><th>پلن</th><th>سرویس</th><th>گروه کانفیگ</th><th>گروه پنل</th><th>قیمت</th><th>مدت</th><th>حجم</th><th>وضعیت</th><th>عملیات</th></tr></thead><tbody>
<?php foreach($plans as $p): ?><tr><td><?= $p['id'] ?></td><td class="fw-bold"><?= Helper::escapeHtml($p['name']) ?></td><td><?= Helper::escapeHtml($p['service_name']) ?></td><td><?= Helper::escapeHtml($p['group_name']) ?> <small class="text-muted">(<?= $p['group_inbound'] ?: '-' ?>)</small></td><td><?php if($p['group_ids']): ?><span class="badge bg-primary"><?= Helper::escapeHtml($p['group_ids']) ?></span><?php else: ?><small class="text-muted">از گروه</small><?php endif; ?></td><td><?= number_format($p['price']) ?></td><td><?= $p['duration_days'] ?></td><td><?= $p['volume_gb']?:'دلخواه' ?></td><td><?= $p['is_active']?'<span class="badge bg-success">فعال</span>':'<span class="badge bg-secondary">غیرفعال</span>' ?></td><td><a href="?edit=<?= $p['id'] ?>" class="btn btn-sm btn-warning">ویرایش</a> <a href="?delete=<?= $p['id'] ?>" class="btn btn-sm btn-danger" data-confirm="حذف؟">حذف</a></td></tr><?php endforeach; if(empty($plans)): ?><tr><td colspan="10" class="text-center text-muted py-3">پلنی نیست</td></tr><?php endif; ?>
</tbody></table></div></div>
<?php require_once __DIR__ . '/../layout/footer.php'; ?>
