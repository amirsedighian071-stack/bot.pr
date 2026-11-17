<?php
$pageTitle='تنظیمات ربات و وب‌هوک';
require_once __DIR__ . '/../layout/header.php';
use Core\Database;
use Core\Config;
use Core\Helper;
use Core\Telegram;

$botToken = Config::setting('bot_token','');
$adminId = Config::setting('admin_telegram_id','');
$webhookUrl = Config::setting('webhook_url', Helper::appUrl('public/webhook.php'));

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $token = trim($_POST['bot_token']??'');
    $admin = trim($_POST['admin_telegram_id']??'');
    // ولیدیشن ساده
    if(empty($token) || empty($admin)){
        $_SESSION['flash_error']='لطفا توکن ربات و آیدی ادمین را کامل وارد کنید';
        Helper::redirect(Helper::appUrl('admin/settings/bot.php'));
    }
    // ذخیره
    Config::setSetting('bot_token',$token);
    Config::setSetting('admin_telegram_id',$admin);
    // Secret خودکار (اگر نبود بساز)
    $secret = Config::setting('webhook_secret','');
    if(empty($secret)) $secret = bin2hex(random_bytes(16));
    Config::setSetting('webhook_secret',$secret);
    // آدرس وب‌هوک به صورت خودکار از دامنه فعلی تشخیص داده می‌شود
    // تشخیص پروتکل + هاست + مسیر
    $autoWebhook = Helper::appUrl('public/webhook.php');
    // اگر تنظیمات app.url خالی باشد، از هدر فعلی تشخیص بده
    if(strpos($autoWebhook,'yourdomain')!==false){
        $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=='off') ? 'https' : 'https';
        $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '';
        $autoWebhook = $proto . '://' . $host . dirname(dirname($_SERVER['SCRIPT_NAME'])) . '/public/webhook.php';
        $autoWebhook = str_replace('/admin/settings','',$autoWebhook);
        $autoWebhook = preg_replace('#(?<!:)//+#','/',$autoWebhook);
        $autoWebhook = str_replace(':/','://',$autoWebhook);
    }
    Config::setSetting('webhook_url',$autoWebhook);
    $webhookUrl = $autoWebhook;

    // ارسال خودکار setWebhook به تلگرام
    try{
        $tg = new Telegram($token);
        $res = $tg->setWebhook($webhookUrl, $secret);
        if($res && ($res['ok']??false)){
            // تست getMe برای ذخیره یوزرنیم
            try{ $me = $tg->request('getMe'); if($me && ($me['ok']??false)) Config::setSetting('bot_username',$me['result']['username']??''); }catch(\Throwable $e){}
            $_SESSION['flash_success']='وب‌هوک با موفقیت تنظیم شد';
            $_SESSION['flash_modal']='success';
            $_SESSION['flash_modal_msg']='وب‌هوک با موفقیت به آدرس <br><code>'.$webhookUrl.'</code><br> متصل شد. اکنون به ربات /start بدهید.';
        } else {
            $_SESSION['flash_error']='خطا در تنظیم وب‌هوک: '.htmlspecialchars(json_encode($res, JSON_UNESCAPED_UNICODE));
            $_SESSION['flash_modal']='error';
        }
    }catch(\Throwable $e){
        $_SESSION['flash_error']='خطای اتصال به تلگرام: '.$e->getMessage();
        $_SESSION['flash_modal']='error';
    }
    Helper::redirect(Helper::appUrl('admin/settings/bot.php'));
}

$botToken = Config::setting('bot_token','');
$adminId = Config::setting('admin_telegram_id','');
$secret = Config::setting('webhook_secret','');
$webhookUrl = Config::setting('webhook_url', Helper::appUrl('public/webhook.php'));
$masked = $botToken ? substr($botToken,0,6).'******'.substr($botToken,-6) : '';

$webhookInfo=null;
if($botToken){
    try{ $tg=new Telegram($botToken); $webhookInfo=$tg->getWebhookInfo(); }catch(\Throwable $e){}
}
$info = $webhookInfo['result'] ?? null;
$autoDetected = Helper::appUrl('public/webhook.php');
?>
<div class="row g-3">
  <div class="col-lg-6">
    <div class="card glass-card neon-border">
      <div class="card-header d-flex align-items-center gap-2"><i class="bi bi-robot text-primary"></i> تنظیم خودکار وب‌هوک</div>
      <div class="card-body">
        <div class="alert alert-dark border border-primary border-opacity-25 small" style="background:rgba(139,92,246,0.08)">
          <i class="bi bi-lightning-charge text-warning me-1"></i> فقط توکن و آیدی را وارد کنید، سیستم به صورت خودکار پروتکل، دامنه و مسیر را تشخیص داده و وب‌هوک را ست می‌کند.
        </div>
        <form method="post" id="webhookForm">
          <div class="mb-3">
            <label class="form-label fw-bold"><i class="bi bi-key me-1"></i> توکن ربات <span class="text-danger">*</span></label>
            <input name="bot_token" class="form-control form-control-lg" placeholder="123456789:ABCdefGHIjklMNOpqrsTUVwxyz" value="<?= Helper::escapeHtml($botToken) ?>" required>
            <div class="form-text">از @BotFather دریافت کنید — فعلی: <code><?= $masked ?: 'ثبت نشده' ?></code></div>
          </div>
          <div class="mb-4">
            <label class="form-label fw-bold"><i class="bi bi-person-badge me-1"></i> آیدی عددی ادمین اصلی <span class="text-danger">*</span></label>
            <input name="admin_telegram_id" class="form-control form-control-lg" placeholder="123456789" value="<?= Helper::escapeHtml($adminId) ?>" required>
            <div class="form-text">از @userinfobot بگیرید</div>
          </div>
          <div class="mb-3 p-3 rounded-3" style="background:rgba(24,24,27,0.6);border:1px solid rgba(139,92,246,0.2)">
            <div class="small text-muted mb-1"><i class="bi bi-link-45deg me-1"></i> آدرس تشخیص خودکار وب‌هوک:</div>
            <code class="small" style="word-break:break-all" id="autoWebhookPreview"><?= Helper::escapeHtml($autoDetected) ?></code>
            <div class="small text-muted mt-2"><i class="bi bi-shield-lock me-1"></i> Secret خودکار: <code><?= $secret ? substr($secret,0,8).'******' : 'در زمان ذخیره ساخته می‌شود' ?></code></div>
          </div>
          <button type="submit" class="btn btn-primary w-100 py-3 fw-bold" style="background:linear-gradient(135deg,#7c3aed,#8b5cf6);border:none;box-shadow:0 0 20px rgba(139,92,246,0.45)">
            <i class="bi bi-rocket-takeoff me-2"></i> تنظیم خودکار وب‌هوک
          </button>
          <div class="text-center small text-muted mt-2">با کلیک، درخواست <code>setWebhook</code> به صورت خودکار به تلگرام ارسال می‌شود</div>
        </form>
      </div>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="card glass-card neon-border">
      <div class="card-header d-flex justify-content-between align-items-center"><span><i class="bi bi-activity me-1 text-success"></i> وضعیت وب‌هوک</span><a href="?check=1" class="btn btn-sm btn-outline-light"><i class="bi bi-arrow-clockwise me-1"></i> بروزرسانی</a></div>
      <div class="card-body small">
        <?php if($info): ?>
          <div class="mb-2 d-flex justify-content-between"><span><i class="bi bi-link-45deg me-1"></i> URL:</span> <code style="word-break:break-all"><?= Helper::escapeHtml($info['url'] ?? '-') ?></code></div>
          <div class="mb-2 d-flex justify-content-between"><span><i class="bi bi-shield-check me-1"></i> گواهی اختصاصی:</span> <span><?= ($info['has_custom_certificate']?'بله':'خیر') ?></span></div>
          <div class="mb-2 d-flex justify-content-between"><span><i class="bi bi-envelope me-1"></i> پیام‌های در صف:</span> <b><?= $info['pending_update_count'] ?? 0 ?></b></div>
          <div class="mb-2"><i class="bi bi-exclamation-triangle me-1 text-warning"></i> آخرین خطا: <span class="text-muted"><?= Helper::escapeHtml($info['last_error_message'] ?? 'ندارد') ?></span></div>
          <div class="mb-2"><i class="bi bi-calendar3 me-1"></i> تاریخ خطا: <span><?= isset($info['last_error_date'])?date('Y-m-d H:i',$info['last_error_date']):'-' ?></span></div>
          <div class="d-flex gap-2 mt-3">
            <button type="button" class="btn btn-sm btn-outline-danger flex-fill" data-custom-confirm="آیا از حذف وب‌هوک اطمینان دارید؟" data-confirm-action="deleteWebhook"><i class="bi bi-trash me-1"></i> حذف وب‌هوک</button>
            <button type="button" class="btn btn-sm btn-outline-light flex-fill" onclick="copyText('<?= Helper::escapeHtml($webhookUrl) ?>')"><i class="bi bi-clipboard me-1"></i> کپی آدرس</button>
          </div>
          <form method="post" action="<?= Helper::appUrl('admin/api/webhook.php') ?>" id="deleteWebhookForm" class="d-none">
            <input type="hidden" name="action" value="delete">
          </form>
        <?php else: ?>
          <div class="text-center py-4">
            <i class="bi bi-broadcast text-muted" style="font-size:32px"></i>
            <p class="text-muted mt-2 small">اطلاعات وب‌هوک در دسترس نیست.<br>توکن و آیدی را وارد و دکمه تنظیم خودکار را بزنید.</p>
          </div>
        <?php endif; ?>
        <hr style="border-color:rgba(139,92,246,0.15)">
        <div class="small">
          <b><i class="bi bi-info-circle me-1"></i> راهنما:</b>
          <ol class="mt-2 ps-3">
            <li>توکن و آیدی ادمین را وارد کنید.</li>
            <li>دکمه تنظیم خودکار را بزنید.</li>
            <li>به ربات <code>/start</code> بدهید.</li>
          </ol>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../layout/creator_card.php'; ?>
<?php require_once __DIR__ . '/../layout/footer.php'; ?>
<script>
// نمایش مودال اختصاصی به جای alert برای نتیجه وب‌هوک
document.addEventListener('DOMContentLoaded',()=>{
  <?php if(!empty($_SESSION['flash_modal'])): ?>
    setTimeout(()=>{
      const type='<?= $_SESSION['flash_modal'] ?>';
      const msg=`<?= addslashes($_SESSION['flash_modal_msg'] ?? $_SESSION['flash_success'] ?? $_SESSION['flash_error'] ?? '') ?>`;
      if(type==='success') showModal('موفق','<div class="text-success"><i class="bi bi-check-circle-fill fs-3"></i><div class="mt-2">'+msg+'</div></div>','success');
      else showModal('خطا',msg,'error');
    },400);
    <?php unset($_SESSION['flash_modal'],$_SESSION['flash_modal_msg']); ?>
  <?php endif; ?>
  // confirm حذف
  document.querySelector('[data-confirm-action="deleteWebhook"]')?.addEventListener('click',()=>{
    showConfirm('حذف وب‌هوک','آیا از حذف وب‌هوک اطمینان دارید؟ این عمل اتصال ربات را قطع می‌کند.',()=>{
      document.getElementById('deleteWebhookForm').submit();
    });
  });
});
</script>
