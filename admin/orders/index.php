<?php
$pageTitle='سفارش‌ها';
require_once __DIR__ . '/../layout/header.php';
use Core\Database;
use Core\Helper;

if(isset($_GET['recheck'])){
    require_once __DIR__ . '/../../src/Payment/AbanGateway.php';
    require_once __DIR__ . '/../../src/Services/OrderService.php';
    require_once __DIR__ . '/../../src/Panels/PanelInterface.php';
    require_once __DIR__ . '/../../src/Panels/PanelCapabilities.php';
    require_once __DIR__ . '/../../src/Panels/AbstractPanel.php';
    require_once __DIR__ . '/../../src/Panels/PanelFactory.php';
    require_once __DIR__ . '/../../src/Panels/UnifiedPanelManager.php';
    require_once __DIR__ . '/../../src/Panels/Drivers/MarzbanPanel.php';
    require_once __DIR__ . '/../../src/Panels/Drivers/PasarGuardPanel.php';
    require_once __DIR__ . '/../../src/Panels/Drivers/MarzneshinPanel.php';
    require_once __DIR__ . '/../../src/Panels/Drivers/XuiPanel.php';
    require_once __DIR__ . '/../../src/Panels/Drivers/RebeccaPanel.php';
    require_once __DIR__ . '/../../src/Panels/Drivers/RemnawavePanel.php';
    require_once __DIR__ . '/../../src/Panels/Drivers/GuardPanel.php';
    require_once __DIR__ . '/../../src/Panels/Drivers/WGDashboardPanel.php';
    require_once __DIR__ . '/../../src/Panels/Drivers/ManualPanel.php';
    $res = \Services\OrderService::verifyAndDeliver($_GET['recheck']);
    $_SESSION['flash_'.($res['success']?'success':'error')] = $res['message'];
    Helper::redirect(Helper::appUrl('admin/orders/index.php'));
}
if(isset($_GET['retry_deliver'])){
    require_once __DIR__ . '/../../src/Services/OrderService.php';
    require_once __DIR__ . '/../../src/Panels/PanelInterface.php';
    require_once __DIR__ . '/../../src/Panels/PanelCapabilities.php';
    require_once __DIR__ . '/../../src/Panels/AbstractPanel.php';
    require_once __DIR__ . '/../../src/Panels/PanelFactory.php';
    require_once __DIR__ . '/../../src/Panels/UnifiedPanelManager.php';
    require_once __DIR__ . '/../../src/Panels/Drivers/MarzbanPanel.php';
    require_once __DIR__ . '/../../src/Panels/Drivers/PasarGuardPanel.php';
    require_once __DIR__ . '/../../src/Panels/Drivers/MarzneshinPanel.php';
    require_once __DIR__ . '/../../src/Panels/Drivers/XuiPanel.php';
    require_once __DIR__ . '/../../src/Panels/Drivers/RebeccaPanel.php';
    require_once __DIR__ . '/../../src/Panels/Drivers/RemnawavePanel.php';
    require_once __DIR__ . '/../../src/Panels/Drivers/GuardPanel.php';
    require_once __DIR__ . '/../../src/Panels/Drivers/WGDashboardPanel.php';
    require_once __DIR__ . '/../../src/Panels/Drivers/ManualPanel.php';
    $res = \Services\OrderService::deliverOrder($_GET['retry_deliver']);
    $_SESSION['flash_'.($res['success']?'success':'error')] = $res['message'];
    Helper::redirect(Helper::appUrl('admin/orders/index.php'));
}

$status = $_GET['status']??'';
$where = $status ? "WHERE o.status='$status'" : '';
$q = $_GET['q']??'';
if($q) $where = ($where ? $where." AND" : "WHERE") . " (o.order_id_unique LIKE '%$q%' OR o.telegram_id LIKE '%$q%')";
$orders = Database::fetchAll("SELECT o.*, p.name as plan_name FROM orders o LEFT JOIN plans p ON p.id=o.plan_id $where ORDER BY o.id DESC LIMIT 100");
?>
<div class="card">
  <div class="card-header d-flex flex-wrap justify-content-between gap-2">
    <div>
      <a href="?" class="btn btn-sm <?= !$status?'btn-primary':'btn-outline-primary' ?>">همه</a>
      <a href="?status=pending" class="btn btn-sm <?= $status==='pending'?'btn-warning':'btn-outline-warning' ?>">در انتظار</a>
      <a href="?status=completed" class="btn btn-sm <?= $status==='completed'?'btn-success':'btn-outline-success' ?>">موفق</a>
      <a href="?status=delivering" class="btn btn-sm <?= $status==='delivering'?'btn-info':'btn-outline-info' ?>">در انتظار تحویل</a>
      <a href="?status=failed" class="btn btn-sm <?= $status==='failed'?'btn-danger':'btn-outline-danger' ?>">ناموفق</a>
    </div>
    <form class="d-flex gap-2"><input name="q" value="<?= Helper::escapeHtml($q) ?>" placeholder="جستجو ORD یا آیدی" class="form-control form-control-sm"><button class="btn btn-sm btn-secondary">جستجو</button></form>
  </div>
  <div class="table-responsive">
    <table class="table table-hover align-middle small mb-0">
      <thead class="table-light"><tr><th>سفارش</th><th>کاربر</th><th>پلن/حجم</th><th>مبلغ نهایی</th><th>وضعیت</th><th>تاریخ</th><th>عملیات</th></tr></thead>
      <tbody>
        <?php foreach($orders as $o): ?>
        <tr>
          <td><code><?= $o['order_id_unique'] ?></code><br><small class="text-muted"><?= $o['username_config'] ?></small></td>
          <td><?= $o['telegram_id'] ?></td>
          <td><?= Helper::escapeHtml($o['plan_name']??'') ?><br><?= $o['volume_gb'] ?>GB / <?= $o['duration_days'] ?>روز</td>
          <td><?= number_format($o['final_price']) ?> ت<br><small class="text-muted">تخفیف: <?= number_format($o['discount_amount']) ?></small></td>
          <td><span class="badge bg-<?= $o['status']==='completed'?'success':($o['status']==='pending'?'warning':($o['status']==='delivering'?'info':'danger')) ?>"><?= $o['status'] ?></span></td>
          <td class="small"><?= $o['created_at'] ?></td>
          <td>
            <a href="?recheck=<?= $o['order_id_unique'] ?>" class="btn btn-sm btn-outline-primary" title="استعلام آبان">استعلام</a>
            <?php if($o['status']!=='completed'): ?><a href="?retry_deliver=<?= $o['order_id_unique'] ?>" class="btn btn-sm btn-outline-success">تحویل مجدد</a><?php endif; ?>
            <?php if($o['invoice_url']): ?><a href="<?= $o['invoice_url'] ?>" target="_blank" class="btn btn-sm btn-outline-secondary">لینک پرداخت</a><?php endif; ?>
          </td>
        </tr>
        <?php endforeach; if(empty($orders)): ?><tr><td colspan="7" class="text-center text-muted py-3">سفارشی نیست</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require_once __DIR__ . '/../layout/footer.php'; ?>
