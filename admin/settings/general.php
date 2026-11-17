<?php
// پردازش قبل از خروجی برای جلوگیری از headers already sent و صفحه سفید
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../src/Core/Database.php';
require_once __DIR__ . '/../../src/Core/Config.php';
require_once __DIR__ . '/../../src/Core/Helper.php';
require_once __DIR__ . '/../../src/Core/Auth.php';
use Core\Database;
use Core\Config;
use Core\Helper;
use Core\Auth;
Auth::startSession();
if (!Auth::check()) Helper::redirect(Helper::appUrl('admin/login.php'));

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $fields = ['fee_amount','trial_volume_mb','trial_days','trial_daily_limit','token_per_invite','gift_5gb_tokens','gift_10gb_tokens','gift_cash_tokens','support_channel_link','education_link','app_download_link','faq_text','welcome_text','payment_before_text','payment_caption','referral_banner','bot_username'];
    foreach($fields as $k){
        if(isset($_POST[$k])) Config::setSetting($k, trim($_POST[$k]));
    }
    foreach($_POST['bot_texts']??[] as $key=>$val){
        Database::query("INSERT INTO bot_texts (text_key, text_value) VALUES (?,?) ON DUPLICATE KEY UPDATE text_value=VALUES(text_value)", [$key, $val]);
    }
    // منو - پشتیبانی از هر دو ساختار flat و indexed
    if(isset($_POST['menus']) && is_array($_POST['menus'])){
        $menusRaw = $_POST['menus'];
        $menus = [];
        // اگر ساختار flat باشد (هر عنصر فقط یک کلید دارد)، بازسازی کن
        $isFlat = false;
        foreach($menusRaw as $m){
            if(is_array($m) && count($m)===1 && isset($m['row_num']) || isset($m['col_num']) || isset($m['label'])){
                // بررسی flat: اگر هر $m فقط یک کلید دارد
                if(count($m)===1) $isFlat=true;
            }
        }
        if($isFlat){
            // بازسازی از flat: هر 5 فیلد یک ردیف
            $flatVals = [];
            foreach($menusRaw as $m){
                foreach($m as $k=>$v) $flatVals[] = [$k=>$v];
            }
            // روش ساده: از POST خام بازخوانی با regex - جایگزین با دسته‌بندی 5 تایی
            $menus = [];
            $keys = ['row_num','col_num','emoji','label','callback_data'];
            $idx=0;
            $current=[];
            foreach($_POST['menus'] as $entry){
                foreach($entry as $k=>$v){
                    $current[$k]=$v;
                    if(count($current)===5){
                        $menus[]=$current;
                        $current=[];
                    }
                }
            }
            if($current) $menus[]=$current;
        } else {
            $menus = $menusRaw;
        }
        // پاکسازی و ذخیره
        Database::query("DELETE FROM bot_menus");
        foreach($menus as $m){
            if(!is_array($m)) continue;
            if(empty($m['label'])||empty($m['callback_data'])) continue;
            $rn = (int)($m['row_num']??0);
            $cn = (int)($m['col_num']??0);
            if($rn<1 || $rn>5) continue;
            if($cn<1 || $cn>2) $cn=1;
            Database::query("INSERT INTO bot_menus (row_num,col_num,label,callback_data,emoji,is_active) VALUES (?,?,?,?,?,?)", [$rn, $cn, trim($m['label']), trim($m['callback_data']), trim($m['emoji']??''), 1]);
        }
        // اگر منو خالی شد، پیش‌فرض را بازگردان
        $cnt = Database::fetchOne("SELECT COUNT(*) as c FROM bot_menus")['c'] ?? 0;
        if($cnt==0){
            $defaults = [
                [1,1,'خرید اشتراک','buy',''],
                [2,1,'تست رایگان','trial',''],
                [3,1,'حساب کاربری','account',''],
                [3,2,'اشتراک من','my_subs',''],
                [4,1,'پشتیبانی','support',''],
                [4,2,'آموزش','education',''],
                [5,1,'زیرمجموعه‌گیری','referral',''],
            ];
            foreach($defaults as $d) Database::query("INSERT INTO bot_menus (row_num,col_num,label,callback_data,emoji,is_active) VALUES (?,?,?,?,?,?)", [$d[0],$d[1],$d[2],$d[3],$d[4],1]);
        }
    }
    $_SESSION['flash_success']='تنظیمات با موفقیت ذخیره شد';
    Helper::redirect(Helper::appUrl('admin/settings/general.php'));
}

$pageTitle='تنظیمات عمومی و پیام‌ها';
require_once __DIR__ . '/../layout/header.php';

$settings = [];
foreach(Database::fetchAll("SELECT setting_key,setting_value FROM settings") as $r) $settings[$r['setting_key']]=$r['setting_value'];
$botTexts = [];
foreach(Database::fetchAll("SELECT text_key,text_value FROM bot_texts") as $r) $botTexts[$r['text_key']]=$r['text_value'];
$menus = Database::fetchAll("SELECT * FROM bot_menus ORDER BY row_num,col_num");
?>

?>
<form method="post">
<div class="row g-3">
  <div class="col-lg-6">
    <div class="card">
      <div class="card-header"><i class="bi bi-gear-fill"></i> تنظیمات مالی و توکن</div>
      <div class="card-body row g-3">
        <div class="col-6"><label class="form-label">کارمزد (تومان)</label><input name="fee_amount" class="form-control" value="<?= $settings['fee_amount']??3000 ?>"></div>
        <div class="col-6"><label class="form-label">حجم تست (MB)</label><input name="trial_volume_mb" class="form-control" value="<?= $settings['trial_volume_mb']??100 ?>"></div>
        <div class="col-6"><label class="form-label">روز تست</label><input name="trial_days" class="form-control" value="<?= $settings['trial_days']??1 ?>"></div>
        <div class="col-6"><label class="form-label">سقف تست روزانه</label><input name="trial_daily_limit" class="form-control" value="<?= $settings['trial_daily_limit']??0 ?>" placeholder="0=نامحدود"></div>
        <div class="col-6"><label class="form-label">توکن هر دعوت</label><input name="token_per_invite" class="form-control" value="<?= $settings['token_per_invite']??1 ?>"></div>
        <div class="col-4"><label class="form-label">5GB توکن</label><input name="gift_5gb_tokens" class="form-control" value="<?= $settings['gift_5gb_tokens']??4 ?>"></div>
        <div class="col-4"><label class="form-label">10GB توکن</label><input name="gift_10gb_tokens" class="form-control" value="<?= $settings['gift_10gb_tokens']??7 ?>"></div>
        <div class="col-4"><label class="form-label">نقدی توکن</label><input name="gift_cash_tokens" class="form-control" value="<?= $settings['gift_cash_tokens']??13 ?>"></div>
      </div>
    </div>
    <div class="card mt-3">
      <div class="card-header"><i class="bi bi-link-45deg"></i> لینک‌ها</div>
      <div class="card-body row g-3">
        <div class="col-12"><label class="form-label">لینک کانال پشتیبانی</label><input name="support_channel_link" class="form-control" value="<?= $settings['support_channel_link']??'' ?>"></div>
        <div class="col-12"><label class="form-label">لینک آموزش</label><input name="education_link" class="form-control" value="<?= $settings['education_link']??'' ?>"></div>
        <div class="col-12"><label class="form-label">لینک دانلود اپ</label><input name="app_download_link" class="form-control" value="<?= $settings['app_download_link']??'' ?>"></div>
        <div class="col-12"><label class="form-label">بنر دعوت (URL عکس)</label><input name="referral_banner" class="form-control" value="<?= $settings['referral_banner']??'' ?>"></div>
        <div class="col-12"><label class="form-label">یوزرنیم ربات (بدون @)</label><input name="bot_username" class="form-control" value="<?= $settings['bot_username']??'' ?>"></div>
      </div>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="card">
      <div class="card-header"><i class="bi bi-pencil-square"></i> متن‌های ربات</div>
      <div class="card-body">
        <div class="mb-2"><label class="form-label">متن خوش‌آمدگویی (متغیر: {first_name},{user_id},{tokens})</label><textarea name="bot_texts[welcome]" rows="3" class="form-control"><?= Helper::escapeHtml($botTexts['welcome']??'') ?></textarea></div>
        <div class="mb-2"><label class="form-label">متن قبل پرداخت</label><input name="payment_before_text" class="form-control" value="<?= Helper::escapeHtml($settings['payment_before_text']??'') ?>"></div>
        <div class="mb-2"><label class="form-label">کپشن پرداخت</label><input name="payment_caption" class="form-control" value="<?= Helper::escapeHtml($settings['payment_caption']??'') ?>"></div>
        <div class="mb-2"><label class="form-label">سوالات متداول</label><textarea name="faq_text" rows="3" class="form-control"><?= Helper::escapeHtml($settings['faq_text']??'') ?></textarea></div>
        <div class="mb-2"><label class="form-label">متن تست موفق</label><textarea name="bot_texts[trial_success]" rows="2" class="form-control"><?= Helper::escapeHtml($botTexts['trial_success']??'') ?></textarea></div>
        <div class="mb-2"><label class="form-label">متن دعوت</label><textarea name="bot_texts[referral_text]" rows="2" class="form-control"><?= Helper::escapeHtml($botTexts['referral_text']??'') ?></textarea></div>
        <div class="small text-muted">متغیرهای پویا: {user_id} {username} {volume} {plan} {price} {expire_date} {invite_link}</div>
      </div>
    </div>
  </div>
  <div class="col-12">
    <div class="card">
      <div class="card-header"><i class="bi bi-puzzle"></i> منوساز شیشه‌ای (Inline Keyboard)</div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-sm" id="menuTable">
            <thead><tr><th>ردیف</th><th>ستون</th><th>ایموجی</th><th>عنوان</th><th>callback_data</th><th></th></tr></thead>
            <tbody>
              <?php foreach($menus as $idx=>$m): ?>
              <tr>
                <td><input name="menus[<?= $idx ?>][row_num]" value="<?= $m['row_num'] ?>" class="form-control form-control-sm" style="width:60px"></td>
                <td><input name="menus[<?= $idx ?>][col_num]" value="<?= $m['col_num'] ?>" class="form-control form-control-sm" style="width:60px"></td>
                <td><input name="menus[<?= $idx ?>][emoji]" value="<?= $m['emoji'] ?>" class="form-control form-control-sm" style="width:70px"></td>
                <td><input name="menus[<?= $idx ?>][label]" value="<?= $m['label'] ?>" class="form-control form-control-sm"></td>
                <td><input name="menus[<?= $idx ?>][callback_data]" value="<?= $m['callback_data'] ?>" class="form-control form-control-sm"></td>
                <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove()">حذف</button></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <button type="button" class="btn btn-sm btn-outline-primary" onclick="addMenuRow()">+ افزودن دکمه</button>
        <div class="inline-key-preview mt-3"><b>پیش‌نمایش:</b> <span class="kbd-btn"><i class="bi bi-cart3"></i> خرید اشتراک</span> <span class="kbd-btn"><i class="bi bi-gift"></i> تست</span> ...</div>
      </div>
    </div>
  </div>
</div>
<div class="mt-3"><button class="btn btn-primary px-4"><i class="bi bi-floppy"></i> ذخیره همه</button></div>
</form>
<script>
function addMenuRow(){
  const tbody=document.querySelector('#menuTable tbody');
  const tr=document.createElement('tr');
  const idx = document.querySelectorAll('#menuTable tbody tr').length;
  tr.innerHTML=`<td><input name="menus[${idx}][row_num]" value="1" class="form-control form-control-sm" style="width:60px"></td><td><input name="menus[${idx}][col_num]" value="1" class="form-control form-control-sm" style="width:60px"></td><td><input name="menus[${idx}][emoji]" class="form-control form-control-sm" style="width:70px"></td><td><input name="menus[${idx}][label]" class="form-control form-control-sm"></td><td><input name="menus[${idx}][callback_data]" class="form-control form-control-sm"></td><td><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove()">حذف</button></td>`;
  tbody.appendChild(tr);
}
</script>

<?php include __DIR__ . '/../layout/creator_card.php'; ?>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
