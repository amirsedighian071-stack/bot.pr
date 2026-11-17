<?php
$pageTitle='تنظیمات ربات و وب‌هوک';
require_once __DIR__ . '/../layout/header.php';
use Core\Database;
use Core\Config;
use Core\Helper;
use Core\Telegram;

$msg=''; $err='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $token = trim($_POST['bot_token']??'');
    $adminId = trim($_POST['admin_telegram_id']??'');
    $secret = trim($_POST['webhook_secret']??'');
    if (!$secret) $secret = bin2hex(random_bytes(16));
    // ذخیره
    if ($token) Config::setSetting('bot_token',$token);
    if ($adminId) Config::setSetting('admin_telegram_id',$adminId);
    Config::setSetting('webhook_secret',$secret);
    $webhookUrl = Helper::appUrl('public/webhook.php');
    Config::setSetting('webhook_url',$webhookUrl);
    // اگر دکمه ست وب‌هوک بود
    if (isset($_POST['set_webhook']) && $token) {
        try {
            $tg = new Telegram($token);
            $res = $tg->setWebhook($webhookUrl, $secret);
            if ($res && ($res['ok']??false)) {
                $msg='✅ وب‌هوک با موفقیت ست شد: '.$webhookUrl;
                // تست getMe
                $me = $tg->request('getMe');
                if ($me && ($me['ok']??false)) Config::setSetting('bot_username',$me['result']['username'] ?? '');
            } else $err='خطا ست وب‌هوک: '.json_encode($res, JSON_UNESCAPED_UNICODE);
        } catch(Exception $e){ $err=$e->getMessage(); }
    } else $msg='تنظیمات ذخیره شد';
    if (!$err) $_SESSION['flash_success']=$msg; else $_SESSION['flash_error']=$err;
    Helper::redirect(Helper::appUrl('admin/settings/bot.php'));
}

$botToken = Config::setting('bot_token','');
$adminId = Config::setting('admin_telegram_id','');
$secret = Config::setting('webhook_secret','');
$webhookUrl = Config::setting('webhook_url', Helper::appUrl('public/webhook.php'));
$masked = $botToken ? substr($botToken,0,6).'******'.substr($botToken,-6) : '';

$webhookInfo=null;
if ($botToken) {
    try { $tg=new Telegram($botToken); $webhookInfo=$tg->getWebhookInfo(); } catch(Exception $e){}
}
$info = $webhookInfo['result'] ?? null;
?>
<div class="row g-3">
  <div class="col-lg-7">
    <div class="card">
      <div class="card-header">🤖 تنظیمات ربات تلگرام</div>
      <div class="card-body">
        <form method="post">
          <div class="mb-3"><label class="form-label">توکن ربات (Bot Token)</label>
            <input name="bot_token" class="form-control" placeholder="123456:ABC..." value="<?= Helper::escapeHtml($botToken) ?>">
            <div class="form-text">از BotFather دریافت کنید. فعلی: <code><?= $masked ?></code></div>
          </div>
          <div class="mb-3"><label class="form-label">آیدی عددی ادمین اصلی</label>
            <input name="admin_telegram_id" class="form-control" placeholder="123456789" value="<?= Helper::escapeHtml($adminId) ?>">
            <div class="form-text">از ربات @userinfobot بگیرید</div>
          </div>
          <div class="mb-3"><label class="form-label">Secret Token وب‌هوک</label>
            <div class="input-group">
              <input name="webhook_secret" class="form-control" value="<?= Helper::escapeHtml($secret) ?>">
              <button type="button" class="btn btn-outline-secondary" onclick="this.previousElementSibling.value=Math.random().toString(36).slice(2)+Math.random().toString(36).slice(2)">تولید</button>
            </div>
          </div>
          <div class="mb-3"><label class="form-label">آدرس وب‌هوک</label>
            <div class="input-group">
              <input class="form-control" value="<?= $webhookUrl ?>" readonly>
              <button type="button" class="btn btn-outline-secondary" onclick="copyText('<?= $webhookUrl ?>')">کپی</button>
            </div>
            <div class="form-text">حتماً باید HTTPS باشد و در هاست ابری قابل دسترس</div>
          </div>
          <div class="d-flex gap-2">
            <button class="btn btn-primary" type="submit" name="save">💾 ذخیره</button>
            <button class="btn btn-success" type="submit" name="set_webhook" value="1">🚀 تنظیم خودکار وب‌هوک</button>
          </div>
        </form>
      </div>
    </div>
  </div>
  <div class="col-lg-5">
    <div class="card">
      <div class="card-header d-flex justify-content-between">وضعیت وب‌هوک <a href="?check=1" class="btn btn-sm btn-light">بروزرسانی</a></div>
      <div class="card-body small">
        <?php if($info): ?>
          <div class="mb-2">🔗 URL: <code><?= $info['url'] ?? '-' ?></code></div>
          <div class="mb-2">✅ Has custom certificate: <?= ($info['has_custom_certificate']?'بله':'خیر') ?></div>
          <div class="mb-2">📨 Pending updates: <b><?= $info['pending_update_count'] ?? 0 ?></b></div>
          <div class="mb-2">❗ Last error: <?= $info['last_error_message'] ?? 'ندارد' ?></div>
          <div class="mb-2">📅 Last error date: <?= isset($info['last_error_date'])?date('Y-m-d H:i',$info['last_error_date']):'-' ?></div>
          <form method="post" action="<?= Helper::appUrl('admin/api/webhook.php') ?>" class="mt-3 d-flex gap-2">
            <input type="hidden" name="action" value="delete">
            <button class="btn btn-sm btn-outline-danger" onclick="return confirm('حذف وب‌هوک؟')">حذف وب‌هوک</button>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="copyText('<?= Helper::appUrl('public/webhook.php?debug=1') ?>')">تست health</button>
          </form>
        <?php else: ?>
          <div class="text-muted">اطلاعات وب‌هوک در دسترس نیست. توکن را وارد و «تنظیم خودکار» را بزنید.</div>
        <?php endif; ?>
        <hr>
        <div class="small">
          <b>راهنما:</b>
          <ol class="mt-2">
            <li>توکن و آیدی ادمین را وارد کنید.</li>
            <li>«تنظیم خودکار وب‌هوک» را بزنید.</li>
            <li>به ربات پیام /start بدهید.</li>
          </ol>
          <div class="alert alert-info py-2 small">اگر هاست Cloud است، مطمئن شوید پوشه logs قابل نوشتن است و HTTPS فعال است.</div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../layout/footer.php'; ?>
