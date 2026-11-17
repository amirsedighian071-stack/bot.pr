<?php
$pageTitle='داشبورد';
require_once __DIR__ . '/layout/header.php';
use Core\Database;
use Core\Helper;

// stats
try {
  $stats = [
    'users' => Database::fetchOne("SELECT COUNT(*) as c FROM users")['c'] ?? 0,
    'orders' => Database::fetchOne("SELECT COUNT(*) as c FROM orders")['c'] ?? 0,
    'paid' => Database::fetchOne("SELECT COUNT(*) as c FROM orders WHERE status='completed'")['c'] ?? 0,
    'revenue' => Database::fetchOne("SELECT COALESCE(SUM(final_price),0) as s FROM orders WHERE status='completed'")['s'] ?? 0,
    'subs' => Database::fetchOne("SELECT COUNT(*) as c FROM subscriptions WHERE status='active'")['c'] ?? 0,
    'tickets' => Database::fetchOne("SELECT COUNT(*) as c FROM tickets WHERE status='open'")['c'] ?? 0,
    'panels' => Database::fetchOne("SELECT COUNT(*) as c FROM panels WHERE is_active=1")['c'] ?? 0,
  ];
  $recentOrders = Database::fetchAll("SELECT o.*, u.username FROM orders o LEFT JOIN users u ON u.id=o.user_id ORDER BY o.id DESC LIMIT 5");
  $recentTickets = Database::fetchAll("SELECT t.*, u.username FROM tickets t LEFT JOIN users u ON u.telegram_id=t.telegram_id ORDER BY t.updated_at DESC LIMIT 5");
} catch(Exception $e){ $stats=['users'=>0,'orders'=>0,'paid'=>0,'revenue'=>0,'subs'=>0,'tickets'=>0,'panels'=>0]; $recentOrders=[]; $recentTickets=[]; }
?>
<div class="row g-3 mb-4">
  <div class="col-6 col-lg-3"><div class="stat-card" style="background:linear-gradient(135deg,#6366f1,#4f46e5)"><div class="small opacity-75">کاربران</div><div class="h3 fw-bold"><?= number_format($stats['users']) ?></div><i class="bi bi-people position-absolute" style="left:16px;top:16px;font-size:28px;opacity:.3"></i></div></div>
  <div class="col-6 col-lg-3"><div class="stat-card" style="background:linear-gradient(135deg,#10b981,#059669)"><div class="small opacity-75">درآمد کل (تومان)</div><div class="h5 fw-bold"><?= number_format($stats['revenue']) ?></div><i class="bi bi-currency-dollar position-absolute" style="left:16px;top:16px;font-size:28px;opacity:.3"></i></div></div>
  <div class="col-6 col-lg-3"><div class="stat-card" style="background:linear-gradient(135deg,#f59e0b,#d97706)"><div class="small opacity-75">سفارش موفق</div><div class="h3 fw-bold"><?= number_format($stats['paid']) ?> / <?= number_format($stats['orders']) ?></div><i class="bi bi-receipt position-absolute" style="left:16px;top:16px;font-size:28px;opacity:.3"></i></div></div>
  <div class="col-6 col-lg-3"><div class="stat-card" style="background:linear-gradient(135deg,#ef4444,#dc2626)"><div class="small opacity-75">تیکت باز</div><div class="h3 fw-bold"><?= number_format($stats['tickets']) ?></div><i class="bi bi-chat-dots position-absolute" style="left:16px;top:16px;font-size:28px;opacity:.3"></i></div></div>
</div>

<div class="row g-3">
  <div class="col-lg-7">
    <div class="card">
      <div class="card-header d-flex justify-content-between"><span>آخرین سفارش‌ها</span><a href="<?= Helper::appUrl('admin/orders/index.php') ?>" class="small">همه</a></div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 small">
          <thead class="table-light"><tr><th>سفارش</th><th>کاربر</th><th>پلن</th><th>مبلغ</th><th>وضعیت</th></tr></thead>
          <tbody>
            <?php foreach($recentOrders as $o): ?>
            <tr><td><code class="small"><?= $o['order_id_unique'] ?></code></td><td><?= $o['telegram_id'] ?></td><td><?= $o['username_config'] ?> (<?= $o['volume_gb'] ?>GB)</td><td><?= number_format($o['final_price']) ?></td><td><span class="badge bg-<?= $o['status']==='completed'?'success':($o['status']==='pending'?'warning':'danger') ?>"><?= $o['status'] ?></span></td></tr>
            <?php endforeach; if(empty($recentOrders)): ?><tr><td colspan="5" class="text-center text-muted py-3">سفارشی نیست</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <div class="col-lg-5">
    <div class="card">
      <div class="card-header d-flex justify-content-between"><span>تیکت‌های باز</span><a href="<?= Helper::appUrl('admin/tickets/index.php') ?>" class="small">همه</a></div>
      <div class="list-group list-group-flush">
        <?php foreach($recentTickets as $t): ?>
        <a href="<?= Helper::appUrl('admin/tickets/view.php?id='.$t['id']) ?>" class="list-group-item list-group-item-action d-flex justify-content-between">
          <span><i class="bi bi-chat"></i> <?= Helper::escapeHtml($t['subject']) ?> <small class="text-muted">- <?= $t['telegram_id'] ?></small></span>
          <span class="badge bg-<?= $t['status']==='open'?'danger':'success' ?>"><?= $t['status'] ?></span>
        </a>
        <?php endforeach; if(empty($recentTickets)): ?><div class="p-3 text-center text-muted small">تیکتی نیست</div><?php endif; ?>
      </div>
    </div>
    <div class="card mt-3">
      <div class="card-body">
        <h6 class="fw-bold"><i class="bi bi-lightning"></i> دسترسی سریع</h6>
        <div class="d-flex flex-wrap gap-2 mt-2">
          <a href="<?= Helper::appUrl('admin/settings/bot.php') ?>" class="btn btn-sm btn-outline-primary">ست وب‌هوک</a>
          <a href="<?= Helper::appUrl('admin/integrations/panels.php') ?>" class="btn btn-sm btn-outline-success">افزودن پنل</a>
          <a href="<?= Helper::appUrl('admin/plans/index.php') ?>" class="btn btn-sm btn-outline-warning">پلن جدید</a>
          <a href="<?= Helper::appUrl('public/cron.php?secret='.(\Core\Config::setting('cron_secret',''))) ?>" target="_blank" class="btn btn-sm btn-outline-secondary">اجرای کرون</a>
        </div>
        <div class="small text-muted mt-3">آدرس وب‌هوک: <code><?= Helper::appUrl('public/webhook.php') ?></code> <button class="btn btn-sm btn-light" onclick="copyText('<?= Helper::appUrl('public/webhook.php') ?>')">کپی</button></div>
        <div class="small text-muted">کال‌بک آبان: <code><?= Helper::appUrl('public/callback.php') ?></code></div>
      </div>
    </div>
  </div>
</div>

<div class="card mt-3">
  <div class="card-header">نمودار فروش (7 روز اخیر - نمونه)</div>
  <div class="card-body"><canvas id="salesChart" height="80"></canvas></div>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx=document.getElementById('salesChart');
if(ctx){
  new Chart(ctx,{type:'line',data:{labels:['6 روز قبل','5','4','3','2','دیروز','امروز'],datasets:[{label:'فروش (تومان)',data:[120000,190000,150000,220000,180000,250000,<?= $stats['revenue']%300000 ?>],borderColor:'#6366f1',backgroundColor:'rgba(99,102,241,.1)',fill:true,tension:.4}]},options:{responsive:true,plugins:{legend:{display:false}}}});
}
</script>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
