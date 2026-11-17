<?php
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../src/Core/Database.php';
require_once __DIR__ . '/../../src/Core/Config.php';
require_once __DIR__ . '/../../src/Core/Helper.php';
require_once __DIR__ . '/../../src/Core/Auth.php';
use Core\Config;
use Core\Database;
use Core\Helper;
use Core\Auth;
Auth::startSession();
if (!Auth::check()) Helper::redirect(Helper::appUrl('admin/login.php'));

// ذخیره - سازگار با هر دو ساختار فاکسیما و قدیمی
if ($_SERVER['REQUEST_METHOD']==='POST') {
    // فاکسیما: urlabangateway, apiabangateway
    $url = trim($_POST['urlabangateway'] ?? $_POST['abangateway_url'] ?? '');
    $api = trim($_POST['apiabangateway'] ?? $_POST['abangateway_token'] ?? '');
    $status = isset($_POST['statusabangateway']) ? 'onabangateway' : 'offabangateway';
    // اگر فرم قدیمی بود (abangateway_token)
    if ($url === '' && isset($_POST['abangateway_callback_url'])) {
        // نگهداری سازگاری قدیمی
        $cb = trim($_POST['abangateway_callback_url']);
        Config::setSetting('abangateway_callback_url', $cb);
        // سعی استخراج دامنه از callback
        if (preg_match('#^https://([^/]+)#', $cb, $m)) {
            $domain = $m[1];
            Config::setSetting('app_url', 'https://'.$domain);
        }
    }
    if ($url !== '' || $api !== '') {
        if ($url !== '') {
            if (stripos($url, 'https://') !== 0 || !filter_var($url, FILTER_VALIDATE_URL)) {
                $_SESSION['flash_error']='آدرس درگاه باید با https:// شروع شود و معتبر باشد';
                Helper::redirect(Helper::appUrl('admin/integrations/abangateway.php'));
            }
            Config::setSetting('urlabangateway', $url);
            // همچنین برای سازگاری PaySetting
            try { Database::query("INSERT INTO settings (setting_key, setting_value) VALUES ('urlabangateway', ?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)", [$url]); } catch(Throwable $e){}
        }
        if ($api !== '') {
            Config::setSetting('apiabangateway', $api);
            Config::setSetting('abangateway_token', $api); // alias قدیمی
            try { Database::query("INSERT INTO settings (setting_key, setting_value) VALUES ('apiabangateway', ?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)", [$api]); } catch(Throwable $e){}
        }
        Config::setSetting('statusabangateway', $status);
        try { Database::query("INSERT INTO settings (setting_key, setting_value) VALUES ('statusabangateway', ?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)", [$status]); } catch(Throwable $e){}
        $_SESSION['flash_success']='ذخیره شد — ساختار فاکسیما آپدیت‌شده فعال است';
        Helper::redirect(Helper::appUrl('admin/integrations/abangateway.php'));
    }
    // fallback قدیمی
    $token = trim($_POST['abangateway_token'] ?? '');
    $cb = trim($_POST['abangateway_callback_url'] ?? '');
    if ($token !== '') { Config::setSetting('abangateway_token',$token); Config::setSetting('apiabangateway',$token); }
    if ($cb !== '') Config::setSetting('abangateway_callback_url',$cb);
    $_SESSION['flash_success']='ذخیره شد';
    Helper::redirect(Helper::appUrl('admin/integrations/abangateway.php'));
}

$testResult=null;
if (isset($_GET['test'])) {
    require_once __DIR__ . '/../../src/Payment/AbanGateway.php';
    try{
        $ag = new \Payment\AbanGateway();
        $testResult = $ag->testConnection();
        $_SESSION['flash_'.($testResult['success']??false?'success':'error')] = $testResult['message'] ?? json_encode($testResult, JSON_UNESCAPED_UNICODE);
    }catch(Throwable $e){ $testResult=['success'=>false,'message'=>$e->getMessage()]; $_SESSION['flash_error']=$e->getMessage(); }
}

// تنظیمات فعلی - اولویت فاکسیما
$urlabangateway = Config::setting('urlabangateway','');
if ($urlabangateway === '') {
    try { $r=Database::fetchOne("SELECT setting_value FROM settings WHERE setting_key='urlabangateway' LIMIT 1"); if($r) $urlabangateway=$r['setting_value']; } catch(Throwable $e){}
}
$apiabangateway = Config::setting('apiabangateway','');
if ($apiabangateway === '') $apiabangateway = Config::setting('abangateway_token','');
$statusabangateway = Config::setting('statusabangateway','offabangateway');
$domainhosts = \Payment\AbanGateway::getDomainHosts();
if ($domainhosts === '') $domainhosts = parse_url(Config::get('app.url',''), PHP_URL_HOST) ?: ($_SERVER['HTTP_HOST'] ?? 'yourdomain.com');
$callbackUrl = 'https://' . $domainhosts . '/payment/abangateway.php?order=TEST123';
$domainForBot = 'https://' . $domainhosts;

// لاگ
$logs = [];
try { $logs = Database::fetchAll("SELECT * FROM abangateway_logs ORDER BY id DESC LIMIT 20"); } catch(Throwable $e){}
$pageTitle='درگاه آبان‌گیت';
require_once __DIR__ . '/../layout/header.php';
?>
<div class="row g-3">
  <div class="col-lg-5">
    <div class="card">
      <div class="card-header"><i class="bi bi-credit-card-2-front"></i> تنظیمات آبان‌گیت — فاکسیما آپدیت‌شده</div>
      <div class="card-body">
        <div class="alert alert-warning py-2 small">
          <b>راهنمای اتصال به @Abangw_bot:</b><br>
          این آدرس و «کلید اتصال» را ربات آبان گیت وی به شما میدهد: تنظیمات ← اتصال به ربات ها ← اتصال ربات جدید ← <b>فاکسیما آپدیت شده</b>.<br>
          سپس <b>آدرس ربات / دامنه</b> خود را برای ربات بفرستید:
          <code style="direction:ltr;display:inline-block"><?= Helper::escapeHtml($domainForBot) ?></code>
          <button type="button" class="btn btn-sm btn-outline-secondary ms-1" onclick="copyText('<?= Helper::escapeHtml($domainForBot) ?>')">کپی دامنه</button>
        </div>
        <form method="post">
          <div class="mb-3">
            <label class="form-label">🔗 آدرس درگاه آبان گیت وی <span class="text-danger">*</span></label>
            <input name="urlabangateway" class="form-control" value="<?= Helper::escapeHtml($urlabangateway) ?>" placeholder="https://gateway.abangateway.ir/..." dir="ltr">
            <div class="form-text">آدرس فعلی: <?= $urlabangateway ? '<code dir="ltr">'.Helper::escapeHtml($urlabangateway).'</code>' : '<span class="text-danger">ثبت نشده</span>' ?></div>
          </div>
          <div class="mb-3">
            <label class="form-label">🔑 کلید اتصال <span class="text-danger">*</span></label>
            <input name="apiabangateway" class="form-control" value="<?= Helper::escapeHtml($apiabangateway) ?>" placeholder="Bearer ..." dir="ltr">
            <div class="form-text">کلید فعلی: <?= $apiabangateway ? '<code>'.str_repeat('•',8).substr(Helper::escapeHtml($apiabangateway),-4).'</code>' : 'ثبت نشده' ?></div>
          </div>
          <div class="mb-3 form-check">
            <input type="checkbox" name="statusabangateway" class="form-check-input" id="statusAban" <?= $statusabangateway==='onabangateway'?'checked':'' ?>>
            <label for="statusAban" class="form-check-label">فعال سازی درگاه (onabangateway)</label>
          </div>
          <div class="mb-3">
            <label class="form-label">Callback URL (خودکار - فاکسیما)</label>
            <div class="input-group"><input class="form-control" value="<?= Helper::escapeHtml($callbackUrl) ?>" readonly dir="ltr"><button type="button" class="btn btn-outline-secondary" onclick="copyText('<?= Helper::escapeHtml($callbackUrl) ?>')">کپی</button></div>
            <div class="form-text">این آدرس به صورت خودکار از دامنه <code><?= Helper::escapeHtml($domainhosts) ?></code> ساخته می‌شود و به آبان‌گیت ارسال می‌شود. نیازی به تنظیم دستی نیست.</div>
          </div>
          <button class="btn btn-primary"><i class="bi bi-check-lg"></i> ذخیره (ساختار فاکسیما)</button>
          <a href="?test=1" class="btn btn-outline-success"><i class="bi bi-search"></i> تست اتصال (فاکسیما)</a>
        </form>
        <?php if($testResult): ?>
          <div class="alert mt-3 <?= $testResult['success']?'alert-success':'alert-danger' ?> py-2 small" style="word-break:break-all"><?= Helper::escapeHtml($testResult['message']) ?></div>
        <?php endif; ?>
        <div class="alert alert-info py-2 small mt-3">
          <b>ساختار فاکسیما:</b> مبلغ به تومان ارسال می‌شود، آبان‌گیت یک بار تبدیل می‌کند. درخواست‌ها به <code>{endpoint}/create</code> و <code>{endpoint}/verify</code> با هدر <code>Authorization: Bearer {apiKey}</code> و <code>callback_url=https://{domain}/payment/abangateway.php?order={order_id}</code> ارسال می‌شوند. پاسخ‌ها شامل <code>authority=abn_...</code> و <code>payment_link=https://.../pay/...</code> هستند.
        </div>
      </div>
    </div>
  </div>
  <div class="col-lg-7">
    <div class="card">
      <div class="card-header"><i class="bi bi-file-text"></i> لاگ تراکنش‌های آبان‌گیت (فاکسیما)</div>
      <div class="table-responsive">
        <table class="table table-sm table-hover mb-0 small">
          <thead class="table-light"><tr><th>سفارش</th><th>مبلغ تومان</th><th>Authority</th><th>وضعیت</th><th>زمان</th></tr></thead>
          <tbody>
            <?php foreach($logs as $l): ?>
            <tr>
              <td><code><?= Helper::escapeHtml($l['order_id_unique'] ?? '') ?></code></td>
              <td><?= number_format((int)($l['amount_toman'] ?? ($l['amount_rial']/10 ?? 0))) ?></td>
              <td><code style="font-size:11px"><?= Helper::escapeHtml(substr($l['abangateway_invoice_id'] ?? '',0,16)) ?></code></td>
              <td><span class="badge bg-<?= ($l['status']=='paid'||$l['status']=='completed')?'success':'secondary' ?>"><?= Helper::escapeHtml($l['status'] ?? '') ?></span></td>
              <td class="small"><?= Helper::escapeHtml($l['created_at'] ?? '') ?></td>
            </tr>
            <?php endforeach; if(empty($logs)): ?><tr><td colspan="5" class="text-center text-muted py-3">لاگی نیست</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
    <div class="alert alert-light border small mt-3">
      <b>آدرس‌های فعال (فاکسیما):</b><br>
      <code>/payment/abangateway.php?order=ORDER_ID</code> ← اصلی (مرورگر + سرور آبان)<br>
      <code>/public/payment/abangateway.php?order=...</code> ← alias<br>
      <code>/public/callback.php?order_id=...</code> ← سازگاری قدیمی (فوروارد خودکار)
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../layout/footer.php'; ?>
