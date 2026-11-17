<?php
$pageTitle='تنظیمات عمومی و پیام‌ها';
require_once __DIR__ . '/../layout/header.php';
use Core\Database;
use Core\Config;
use Core\Helper;

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $fields = ['fee_amount','trial_volume_mb','trial_days','trial_daily_limit','token_per_invite','gift_5gb_tokens','gift_10gb_tokens','gift_cash_tokens','support_channel_link','education_link','app_download_link','faq_text','welcome_text','payment_before_text','payment_caption','referral_banner','bot_username'];
    foreach($fields as $k){
        if(isset($_POST[$k])) Config::setSetting($k, trim($_POST[$k]));
    }
    // متون داینامیک
    foreach($_POST['bot_texts']??[] as $key=>$val){
        Database::query("INSERT INTO bot_texts (text_key, text_value) VALUES (?,?) ON DUPLICATE KEY UPDATE text_value=VALUES(text_value)", [$key, $val]);
    }
    // منو
    if(isset($_POST['menus'])){
        Database::query("DELETE FROM bot_menus");
        foreach($_POST['menus'] as $m){
            if(empty($m['label'])||empty($m['callback_data'])) continue;
            Database::query("INSERT INTO bot_menus (row_num,col_num,label,callback_data,emoji,is_active) VALUES (?,?,?,?,?,?)", [$m['row_num'], $m['col_num'], $m['label'], $m['callback_data'], $m['emoji']??'', 1]);
        }
    }
    $_SESSION['flash_success']='ذخیره شد';
    Helper::redirect(Helper::appUrl('admin/settings/general.php'));
}

$settings = [];
foreach(Database::fetchAll("SELECT setting_key,setting_value FROM settings") as $r) $settings[$r['setting_key']]=$r['setting_value'];
$botTexts = [];
foreach(Database::fetchAll("SELECT text_key,text_value FROM bot_texts") as $r) $botTexts[$r['text_key']]=$r['text_value'];
$menus = Database::fetchAll("SELECT * FROM bot_menus ORDER BY row_num,col_num");
?>
<form method="post">
<div class="row g-3">
  <div class="col-lg-6">
    <div class="card">
      <div class="card-header">⚙️ تنظیمات مالی و توکن</div>
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
      <div class="card-header">🔗 لینک‌ها</div>
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
      <div class="card-header">📝 متن‌های ربات</div>
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
      <div class="card-header">🧩 منوساز شیشه‌ای (Inline Keyboard)</div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-sm" id="menuTable">
            <thead><tr><th>ردیف</th><th>ستون</th><th>ایموجی</th><th>عنوان</th><th>callback_data</th><th></th></tr></thead>
            <tbody>
              <?php foreach($menus as $m): ?>
              <tr>
                <td><input name="menus[][row_num]" value="<?= $m['row_num'] ?>" class="form-control form-control-sm" style="width:60px"></td>
                <td><input name="menus[][col_num]" value="<?= $m['col_num'] ?>" class="form-control form-control-sm" style="width:60px"></td>
                <td><input name="menus[][emoji]" value="<?= $m['emoji'] ?>" class="form-control form-control-sm" style="width:70px"></td>
                <td><input name="menus[][label]" value="<?= $m['label'] ?>" class="form-control form-control-sm"></td>
                <td><input name="menus[][callback_data]" value="<?= $m['callback_data'] ?>" class="form-control form-control-sm"></td>
                <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove()">حذف</button></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <button type="button" class="btn btn-sm btn-outline-primary" onclick="addMenuRow()">+ افزودن دکمه</button>
        <div class="inline-key-preview mt-3"><b>پیش‌نمایش:</b> <span class="kbd-btn">🛒 خرید اشتراک</span> <span class="kbd-btn">🎁 تست</span> ...</div>
      </div>
    </div>
  </div>
</div>
<div class="mt-3"><button class="btn btn-primary px-4">💾 ذخیره همه</button></div>
</form>
<script>
function addMenuRow(){
  const tbody=document.querySelector('#menuTable tbody');
  const tr=document.createElement('tr');
  tr.innerHTML=`<td><input name="menus[][row_num]" value="1" class="form-control form-control-sm" style="width:60px"></td><td><input name="menus[][col_num]" value="1" class="form-control form-control-sm" style="width:60px"></td><td><input name="menus[][emoji]" class="form-control form-control-sm" style="width:70px"></td><td><input name="menus[][label]" class="form-control form-control-sm"></td><td><input name="menus[][callback_data]" class="form-control form-control-sm"></td><td><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove()">حذف</button></td>`;
  tbody.appendChild(tr);
}
</script>
<?php require_once __DIR__ . '/../layout/footer.php'; ?>
