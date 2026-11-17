<?php
$pageTitle='لاگ‌ها';
require_once __DIR__ . '/../layout/header.php';
use Core\Helper;
$logs=['webhook.log','payment.log','panels.log','callback.log','delivery.log','cron.log','error.log','bot.log','telegram.log','app.log'];
$selected=$_GET['file']??'error.log';
if(!in_array($selected,$logs)) $selected='error.log';
$path=__DIR__ . '/../../logs/'.$selected;
$content='';
if(file_exists($path)) $content=file_get_contents($path);
$content= $content ? implode("\n", array_slice(explode("\n",$content), -300)) : 'لاگی نیست';
?>
<div class="card">
  <div class="card-header d-flex flex-wrap gap-2">لاگ سیستم <?php foreach($logs as $l): ?><a href="?file=<?= $l ?>" class="btn btn-sm <?= $selected===$l?'btn-primary':'btn-outline-secondary' ?>"><?= $l ?></a><?php endforeach; ?></div>
  <div class="card-body"><pre style="max-height:60vh;overflow:auto;background:#0f172a;color:#e2e8f0;padding:16px;border-radius:12px;direction:ltr;text-align:left;font-size:.8rem"><?= Helper::escapeHtml($content) ?></pre></div>
</div>
<?php require_once __DIR__ . '/../layout/footer.php'; ?>
