<?php
$pageTitle='گزارش مالی';
require_once __DIR__ . '/../layout/header.php';
use Core\Database;
$revenue = Database::fetchOne("SELECT COALESCE(SUM(final_price),0) as s, COALESCE(SUM(discount_amount),0) as d FROM orders WHERE status='completed'") ;
$today = Database::fetchOne("SELECT COALESCE(SUM(final_price),0) as s FROM orders WHERE status='completed' AND DATE(paid_at)=CURDATE()")['s'] ?? 0;
$week = Database::fetchOne("SELECT COALESCE(SUM(final_price),0) as s FROM orders WHERE status='completed' AND paid_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")['s'] ?? 0;
$month = Database::fetchOne("SELECT COALESCE(SUM(final_price),0) as s FROM orders WHERE status='completed' AND paid_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")['s'] ?? 0;
$byPlan = Database::fetchAll("SELECT p.name, COUNT(*) as cnt, SUM(o.final_price) as sum FROM orders o LEFT JOIN plans p ON p.id=o.plan_id WHERE o.status='completed' GROUP BY p.name ORDER BY sum DESC");
?>
<div class="row g-3 mb-3">
  <div class="col-6 col-lg-3"><div class="card p-3 text-center"><div class="small text-muted">درآمد کل</div><div class="h5 fw-bold text-success"><?= number_format($revenue['s']??0) ?> ت</div><small class="text-muted">تخفیف: <?= number_format($revenue['d']??0) ?></small></div></div>
  <div class="col-6 col-lg-3"><div class="card p-3 text-center"><div class="small text-muted">امروز</div><div class="h5 fw-bold"><?= number_format($today) ?> ت</div></div></div>
  <div class="col-6 col-lg-3"><div class="card p-3 text-center"><div class="small text-muted">7 روز اخیر</div><div class="h5 fw-bold"><?= number_format($week) ?> ت</div></div></div>
  <div class="col-6 col-lg-3"><div class="card p-3 text-center"><div class="small text-muted">30 روز اخیر</div><div class="h5 fw-bold"><?= number_format($month) ?> ت</div></div></div>
</div>
<div class="card">
  <div class="card-header">فروش به تفکیک پلن</div>
  <div class="table-responsive"><table class="table small mb-0"><thead class="table-light"><tr><th>پلن</th><th>تعداد فروش</th><th>مجموع</th></tr></thead><tbody>
  <?php foreach($byPlan as $r): ?><tr><td><?= \Core\Helper::escapeHtml($r['name']??'نامشخص') ?></td><td><?= $r['cnt'] ?></td><td><?= number_format($r['sum']) ?> ت</td></tr><?php endforeach; if(empty($byPlan)): ?><tr><td colspan="3" class="text-center text-muted py-3">داده‌ای نیست</td></tr><?php endif; ?>
  </tbody></table></div>
</div>
<canvas id="chart" height="80" class="mt-3"></canvas>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
new Chart(document.getElementById('chart'),{type:'bar',data:{labels:[<?php foreach($byPlan as $r) echo "'".addslashes($r['name'])."',"; ?>],datasets:[{label:'فروش',data:[<?php foreach($byPlan as $r) echo $r['sum'].","; ?>],backgroundColor:'#6366f1'}]},options:{responsive:true}});
</script>
<?php require_once __DIR__ . '/../layout/footer.php'; ?>
