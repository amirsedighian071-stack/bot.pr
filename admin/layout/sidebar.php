<div class="sidebar d-flex flex-column" id="sidebar" style="width:270px">
  <div class="brand"><i class="bi bi-shield-lock-fill fs-4 text-primary"></i> VPN Bot <small class="opacity-75 fw-normal">Faoxima Style</small></div>
  <nav class="nav flex-column py-2 flex-grow-1" style="overflow-y:auto">
    <a class="nav-link <?= str_contains($_SERVER['REQUEST_URI'],'/admin/index.php')?'active':'' ?>" href="<?= \Core\Helper::appUrl('admin/index.php') ?>"><i class="bi bi-speedometer2"></i> داشبورد</a>
    <div class="small text-uppercase opacity-50 px-3 mt-3">ربات</div>
    <a class="nav-link" href="<?= \Core\Helper::appUrl('admin/settings/bot.php') ?>"><i class="bi bi-robot"></i> تنظیمات ربات & وب‌هوک</a>
    <a class="nav-link" href="<?= \Core\Helper::appUrl('admin/settings/general.php') ?>"><i class="bi bi-gear"></i> تنظیمات عمومی & پیام‌ها</a>
    <a class="nav-link" href="<?= \Core\Helper::appUrl('admin/broadcast/index.php') ?>"><i class="bi bi-broadcast"></i> پیام همگانی</a>

    <div class="small text-uppercase opacity-50 px-3 mt-3">اتصالات</div>
    <a class="nav-link" href="<?= \Core\Helper::appUrl('admin/integrations/abangateway.php') ?>"><i class="bi bi-credit-card"></i> آبان‌گیت</a>
    <a class="nav-link" href="<?= \Core\Helper::appUrl('admin/integrations/panels.php') ?>"><i class="bi bi-hdd-stack"></i> پنل‌های VPN</a>
    <a class="nav-link" href="<?= \Core\Helper::appUrl('admin/groups/index.php') ?>"><i class="bi bi-collection"></i> گروه‌های کانفیگ</a>

    <div class="small text-uppercase opacity-50 px-3 mt-3">فروش</div>
    <a class="nav-link" href="<?= \Core\Helper::appUrl('admin/services/index.php') ?>"><i class="bi bi-layers"></i> سرویس‌ها</a>
    <a class="nav-link" href="<?= \Core\Helper::appUrl('admin/plans/index.php') ?>"><i class="bi bi-box-seam"></i> پلن‌ها</a>
    <a class="nav-link" href="<?= \Core\Helper::appUrl('admin/discounts/index.php') ?>"><i class="bi bi-ticket-perforated"></i> کد تخفیف</a>
    <a class="nav-link" href="<?= \Core\Helper::appUrl('admin/orders/index.php') ?>"><i class="bi bi-receipt"></i> سفارش‌ها</a>
    <a class="nav-link" href="<?= \Core\Helper::appUrl('admin/subscriptions/index.php') ?>"><i class="bi bi-person-badge"></i> اشتراک‌ها</a>
    <a class="nav-link" href="<?= \Core\Helper::appUrl('admin/warehouse/index.php') ?>"><i class="bi bi-archive"></i> انبار دستی</a>
    <a class="nav-link" href="<?= \Core\Helper::appUrl('admin/volumes/index.php') ?>"><i class="bi bi-hdd"></i> حجم‌ها</a>

    <div class="small text-uppercase opacity-50 px-3 mt-3">کاربران & پشتیبانی</div>
    <a class="nav-link" href="<?= \Core\Helper::appUrl('admin/users/index.php') ?>"><i class="bi bi-people"></i> کاربران</a>
    <a class="nav-link" href="<?= \Core\Helper::appUrl('admin/tickets/index.php') ?>"><i class="bi bi-chat-dots"></i> تیکت‌ها</a>
    <a class="nav-link" href="<?= \Core\Helper::appUrl('admin/tickets/gifts.php') ?>"><i class="bi bi-gift"></i> هدیه نقدی & توکن</a>

    <div class="small text-uppercase opacity-50 px-3 mt-3">سیستم</div>
    <a class="nav-link" href="<?= \Core\Helper::appUrl('admin/reports/index.php') ?>"><i class="bi bi-graph-up"></i> گزارش مالی</a>
    <a class="nav-link" href="<?= \Core\Helper::appUrl('admin/backups/index.php') ?>"><i class="bi bi-cloud-arrow-down"></i> بکاپ</a>
    <a class="nav-link" href="<?= \Core\Helper::appUrl('admin/api/logs.php') ?>"><i class="bi bi-journal-text"></i> لاگ‌ها</a>
  </nav>
  <div class="p-3 border-top border-secondary small opacity-75">نسخه 1.0.0 | PHP 8.2+ | <span class="text-success">● آنلاین</span></div>
</div>
