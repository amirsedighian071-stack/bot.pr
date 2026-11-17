<?php
$pageTitle='درگاه آبان‌گیت';
require_once __DIR__ . '/../layout/header.php';
use Core\Config;
use Core\Database;
use Core\Helper;

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $token = trim($_POST['abangateway_token']??'');
    $cb = trim($_POST['abangateway_callback_url']??'');
    Config::setSetting('abangateway_token',$token);
    Config::setSetting('abangateway_callback_url',$cb);
    $_SESSION['flash_success']='ذخیره شد';
    Helper::redirect(Helper::appUrl('admin/integrations/abangateway.php'));
}

// تست اتصال
$testResult=null;
if (isset($_GET['test'])) {
    require_once __DIR__ . '/../../src/Payment/AbanGateway.php';
    $ag = new Payment\AbanGateway();
    $testResult = $ag->testConnection();
}
$token = Config::setting('abangateway_token','');
$cb = Config::setting('abangateway_callback_url', Helper::appUrl('public/callback.php'));
$logs = Database::fetchAll("SELECT * FROM abangateway_logs ORDER BY id DESC LIMIT 20");
?>
<div class="row g-3">
  <div class="col-lg-5">
    <div class="card">
      <div class="card-header"><i class="bi bi-credit-card-2-front"></i> تنظیمات آبان‌گیت (abangateway.ir)</div>
      <div class="card-body">
        <form method="post">
          <div class="mb-3"><label class="form-label">ABAN_TOKEN (API Key)</label><input name="abangateway_token" class="form-control" value="<?= Helper::escapeHtml($token) ?>" placeholder="Bearer ..."></div>
          <div class="mb-3"><label class="form-label">Callback URL</label>
            <div class="input-group"><input name="abangateway_callback_url" class="form-control" value="<?= Helper::escapeHtml($cb) ?>"><button type="button" class="btn btn-outline-secondary" onclick="copyText(this.previousElementSibling.value)">کپی</button></div>
            <div class="form-text">مثال: <?= Helper::appUrl('public/callback.php?order_id=ORD-...') ?></div>
          </div>
          <button class="btn btn-primary">ذخیره</button>
          <a href="?test=1" class="btn btn-outline-success"><i class="bi bi-search"></i> تست اتصال به API</a>
        </form>
        <?php if($testResult): ?>
          <div class="alert mt-3 <?= $testResult['success']?'alert-success':'alert-danger' ?> py-2"><?= $testResult['message'] ?></div>
        <?php endif; ?>
        <div class="alert alert-info py-2 small mt-3">
          <b>نکته فنی:</b> مبلغ به ریال ارسال می‌شود (تومان ×10) همراه order_id یکتا و callback_url. تأیید هم از Callback و هم Verify انجام می‌شود تا تراکنش تکراری پردازش نشود.
        </div>
      </div>
    </div>
  </div>
  <div class="col-lg-7">
    <div class="card">
      <div class="card-header"><i class="bi bi-file-text"></i> لاگ تراکنش‌های آبان‌گیت</div>
      <div class="table-responsive">
        <table class="table table-sm table-hover mb-0 small">
          <thead class="table-light"><tr><th>سفارش</th><th>مبلغ ریال/تومان</th><th>وضعیت</th><th>زمان</th></tr></thead>
          <tbody>
            <?php foreach($logs as $l): ?>
            <tr><td><code><?= $l['order_id_unique'] ?></code></td><td><?= number_format($l['amount_rial']??0) ?> / <?= number_format($l['amount_toman']??0) ?></td><td><span class="badge bg-secondary"><?= $l['status'] ?></span></td><td><?= $l['created_at'] ?></td></tr>
            <?php endforeach; if(empty($logs)): ?><tr><td colspan="4" class="text-center text-muted py-3">لاگی نیست</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../layout/footer.php'; ?>
