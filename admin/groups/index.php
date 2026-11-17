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
    // inbound_ids ممکن است از select یا input دستی بیاید
    $inbound=trim($_POST['inbound_ids']??'');
    if($inbound === '' && isset($_POST['inbound_ids_select'])) $inbound = trim($_POST['inbound_ids_select']);
    // اگر چندتایی انتخاب شده (select multiple)
    if(isset($_POST['inbound_ids_select_multi']) && is_array($_POST['inbound_ids_select_multi'])){
        $inbound = implode(',', array_filter(array_map('trim', $_POST['inbound_ids_select_multi'])));
    }
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

$groups=Database::fetchAll("SELECT g.*, p.name as panel_name, p.type, p.capabilities_json FROM config_groups g LEFT JOIN panels p ON p.id=g.panel_id ORDER BY g.id DESC");
$panels=Database::fetchAll("SELECT * FROM panels WHERE is_active=1");
$edit=null; if(isset($_GET['edit'])) $edit=Database::fetchOne("SELECT * FROM config_groups WHERE id=?", [(int)$_GET['edit']]);

// نقشه پنل => گروه‌های همگام‌شده
$panelGroupsMap = [];
foreach($panels as $pn){
    $caps = $pn['capabilities_json'] ?? null;
    if($caps){
        $dec = is_string($caps) ? json_decode($caps, true) : $caps;
        if(is_array($dec) && !empty($dec)) $panelGroupsMap[$pn['id']] = $dec;
    }
}
?>
<div class="card mb-3">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span><?= $edit?'ویرایش گروه':'افزودن گروه کانفیگ' ?></span>
    <a href="<?= Helper::appUrl('admin/integrations/panels.php') ?>" class="btn btn-sm btn-outline-primary">همگام‌سازی گروه‌ها از پنل</a>
  </div>
  <div class="card-body">
    <div class="alert alert-warning small">
      <b>نکته Outbound Center / PasarGuard:</b> برای اینکه لینک ساب کاربر خالی (AVANET 0) نماند، هر گروه کانفیگ باید دقیقاً به یک <b>گروه پنل</b> (مثل <code>Ava test</code>، <code>Ava max</code>) وصل باشد.<br>
      تصویر پنل شما: <code>ویرایش کاربر → گروه‌ها → Ava test ✔️</code> — همین گروه را اینجا انتخاب کنید تا ربات هنگام ساخت کاربر <code>group_ids=[ID گروه]</code> را به API پنل بفرستد.
      اگر گروه‌ها اینجا خالی است، ابتدا در <code>پنل‌ها → همگام‌سازی گروه‌ها</code> را بزنید.
    </div>
    <form method="post" class="row g-3" id="groupForm">
      <input type="hidden" name="id" value="<?= $edit['id']??0 ?>">
      <div class="col-md-4"><label class="form-label">نام گروه (دلخواه برای نمایش در ربات)</label><input name="name" class="form-control" required value="<?= Helper::escapeHtml($edit['name']??'') ?>" placeholder="مثلاً Ava test — تست"></div>
      <div class="col-md-4"><label class="form-label">پنل</label><select name="panel_id" id="panel_id" class="form-select" required><option value="">انتخاب</option><?php foreach($panels as $p): ?><option value="<?= $p['id'] ?>" <?= ($edit['panel_id']??'')==$p['id']?'selected':'' ?>><?= Helper::escapeHtml($p['name']) ?> (<?= $p['type'] ?>)</option><?php endforeach; ?></select></div>
      <div class="col-md-4"><label class="form-label">گروه پنل (Inbound/Group IDs)</label>
        <select id="inbound_select" class="form-select" style="display:none"></select>
        <input name="inbound_ids" id="inbound_input" class="form-control" value="<?= Helper::escapeHtml($edit['inbound_ids']??'') ?>" placeholder="مثلاً 5 یا 1,2">
        <small class="text-muted d-block mt-1">اگر پنل همگام شده باشد لیست گروه‌ها ظاهر می‌شود؛ در غیر این صورت شناسه عددی گروه را دستی وارد کنید (در Outbound Center هر گروه مثل Ava test یک ID دارد).</small>
        <div id="groupHelp" class="small text-info mt-1" style="display:none"></div>
      </div>
      <div class="col-md-6"><label class="form-label">شناسه گروه در پنل (اختیاری — نام گروه)</label><input name="group_identifier" class="form-control" value="<?= Helper::escapeHtml($edit['group_identifier']??'') ?>" placeholder="مثلاً Ava test"></div>
      <div class="col-md-3 d-flex align-items-end"><div class="form-check"><input type="checkbox" name="is_active" class="form-check-input" id="ac" <?= ($edit['is_active']??1)?'checked':'' ?>><label for="ac" class="form-check-label">فعال</label></div></div>
      <div class="col-12"><button class="btn btn-primary"><?= $edit?'ویرایش':'افزودن' ?></button> <?php if($edit): ?><a href="index.php" class="btn btn-light">انصراف</a><?php endif; ?></div>
    </form>
  </div>
</div>

<script>
const panelGroups = <?= json_encode($panelGroupsMap, JSON_UNESCAPED_UNICODE) ?>;
const panelSel = document.getElementById('panel_id');
const inboundInput = document.getElementById('inbound_input');
const inboundSelect = document.getElementById('inbound_select');
const help = document.getElementById('groupHelp');
function refreshGroups(){
  const pid = panelSel.value;
  const groups = panelGroups[pid] || [];
  if(groups.length){
    inboundSelect.style.display='';
    inboundInput.style.display='none';
    inboundInput.name='inbound_ids_hidden';
    inboundSelect.name='inbound_ids';
    inboundSelect.innerHTML='';
    const optEmpty = document.createElement('option');
    optEmpty.value=''; optEmpty.textContent='— انتخاب گروه پنل —'; inboundSelect.appendChild(optEmpty);
    groups.forEach(g=>{
      const opt=document.createElement('option');
      opt.value = String(g.id);
      opt.textContent = (g.name||'Group')+' ('+g.id+')';
      if(inboundInput.value && String(inboundInput.value).split(',').map(s=>s.trim()).includes(String(g.id))) opt.selected=true;
      inboundSelect.appendChild(opt);
    });
    // اگر مقدار فعلی دستی باشد و در لیست نیست، آن را هم اضافه کن
    if(inboundInput.value && ![...inboundSelect.options].some(o=>o.value===inboundInput.value.trim())){
      const opt=document.createElement('option');
      opt.value=inboundInput.value.trim(); opt.textContent='دستی: '+inboundInput.value.trim(); opt.selected=true;
      inboundSelect.appendChild(opt);
    }
    help.style.display='block';
    help.textContent='گروه‌های همگام‌شده از پنل ('+groups.length+' مورد). برای Outbound Center گروه Ava test / Ava max را انتخاب کنید.';
  } else {
    inboundSelect.style.display='none';
    inboundInput.style.display='';
    inboundInput.name='inbound_ids';
    inboundSelect.name='inbound_ids_select';
    help.style.display='block';
    help.textContent='هنوز گروهی برای این پنل همگام نشده. در پنل‌ها → همگام‌سازی بزنید یا شناسه را دستی وارد کنید.';
  }
}
panelSel.addEventListener('change', refreshGroups);
refreshGroups();
// هنگام ارسال فرم، اگر select نمایش است مقدارش را به input هم منتقل کن
document.getElementById('groupForm').addEventListener('submit', function(){
  if(inboundSelect.style.display !== 'none'){
    inboundInput.value = inboundSelect.value;
  }
});
</script>

<div class="card">
  <div class="table-responsive">
    <table class="table table-hover small mb-0"><thead><tr><th>#</th><th>نام گروه</th><th>پنل</th><th>Group ID (inbound)</th><th>وضعیت</th><th>عملیات</th></tr></thead>
    <tbody>
      <?php foreach($groups as $g): ?><tr><td><?= $g['id'] ?></td><td><?= Helper::escapeHtml($g['name']) ?></td><td><?= Helper::escapeHtml($g['panel_name']) ?> <small class="text-muted"><?= $g['type'] ?></small></td><td><code><?= $g['inbound_ids'] ?: $g['group_identifier'] ?: '-' ?></code><?php if(empty($g['inbound_ids']) && empty($g['group_identifier'])): ?> <span class="badge bg-danger">خالی!</span><?php endif; ?></td><td><?= $g['is_active']?'<span class="badge bg-success">فعال</span>':'<span class="badge bg-secondary">غیرفعال</span>' ?></td><td><a href="?edit=<?= $g['id'] ?>" class="btn btn-sm btn-outline-warning">ویرایش</a> <a href="?delete=<?= $g['id'] ?>" class="btn btn-sm btn-outline-danger" data-confirm="حذف گروه؟">حذف</a></td></tr><?php endforeach; if(empty($groups)): ?><tr><td colspan="6" class="text-center text-muted py-3">گروهی نیست</td></tr><?php endif; ?>
    </tbody></table>
  </div>
</div>
<?php require_once __DIR__ . '/../layout/footer.php'; ?>
