<div class="sidebar d-flex flex-column" id="sidebar">
  <div class="brand">
    <div class="brand-icon"><i class="bi bi-shield-lock-fill"></i></div>
    <div>
      <div class="brand-title">پنل هوشمند</div>
      <div class="brand-sub">فروش و مدیریت VPN</div>
    </div>
  </div>
  <nav class="nav flex-column py-2 flex-grow-1 accordion-nav" style="overflow-y:auto" id="sidebarAccordion">
    <a class="nav-link <?= str_contains($_SERVER['REQUEST_URI'],'/admin/index.php')?'active':'' ?>" href="<?= \Core\Helper::appUrl('admin/index.php') ?>"><i class="bi bi-speedometer2"></i> داشبورد</a>

    <!-- ربات -->
    <div class="accordion-item">
      <button class="accordion-header" data-bs-toggle="collapse" data-bs-target="#collapseRobot" aria-expanded="true">
        <span><i class="bi bi-robot me-2"></i> ربات</span><i class="bi bi-chevron-down accordion-arrow"></i>
      </button>
      <div id="collapseRobot" class="accordion-collapse collapse show" data-bs-parent="#sidebarAccordion">
        <div class="accordion-body">
          <a class="nav-link <?= str_contains($_SERVER['REQUEST_URI'],'/admin/settings/bot.php')?'active':'' ?>" href="<?= \Core\Helper::appUrl('admin/settings/bot.php') ?>"><i class="bi bi-broadcast"></i> تنظیم ربات & وب‌هوک</a>
          <a class="nav-link <?= str_contains($_SERVER['REQUEST_URI'],'/admin/settings/general.php')?'active':'' ?>" href="<?= \Core\Helper::appUrl('admin/settings/general.php') ?>"><i class="bi bi-sliders"></i> تنظیمات عمومی & پیام‌ها</a>
          <a class="nav-link <?= str_contains($_SERVER['REQUEST_URI'],'/admin/broadcast')?'active':'' ?>" href="<?= \Core\Helper::appUrl('admin/broadcast/index.php') ?>"><i class="bi bi-megaphone"></i> پیام همگانی</a>
        </div>
      </div>
    </div>

    <!-- اتصالات -->
    <div class="accordion-item">
      <button class="accordion-header" data-bs-toggle="collapse" data-bs-target="#collapseConn" aria-expanded="false">
        <span><i class="bi bi-hdd-stack me-2"></i> اتصالات</span><i class="bi bi-chevron-down accordion-arrow"></i>
      </button>
      <div id="collapseConn" class="accordion-collapse collapse" data-bs-parent="#sidebarAccordion">
        <div class="accordion-body">
          <a class="nav-link" href="<?= \Core\Helper::appUrl('admin/integrations/abangateway.php') ?>"><i class="bi bi-credit-card-2-front"></i> آبان‌گیت</a>
          <a class="nav-link" href="<?= \Core\Helper::appUrl('admin/integrations/panels.php') ?>"><i class="bi bi-hdd-stack"></i> پنل‌های VPN</a>
          <a class="nav-link" href="<?= \Core\Helper::appUrl('admin/groups/index.php') ?>"><i class="bi bi-collection"></i> گروه‌های کانفیگ</a>
        </div>
      </div>
    </div>

    <!-- فروش -->
    <div class="accordion-item">
      <button class="accordion-header" data-bs-toggle="collapse" data-bs-target="#collapseSell" aria-expanded="false">
        <span><i class="bi bi-bag me-2"></i> فروش</span><i class="bi bi-chevron-down accordion-arrow"></i>
      </button>
      <div id="collapseSell" class="accordion-collapse collapse" data-bs-parent="#sidebarAccordion">
        <div class="accordion-body">
          <a class="nav-link" href="<?= \Core\Helper::appUrl('admin/services/index.php') ?>"><i class="bi bi-layers"></i> سرویس‌ها</a>
          <a class="nav-link" href="<?= \Core\Helper::appUrl('admin/plans/index.php') ?>"><i class="bi bi-box-seam"></i> پلن‌ها</a>
          <a class="nav-link" href="<?= \Core\Helper::appUrl('admin/discounts/index.php') ?>"><i class="bi bi-ticket-perforated"></i> کد تخفیف</a>
          <a class="nav-link" href="<?= \Core\Helper::appUrl('admin/orders/index.php') ?>"><i class="bi bi-receipt"></i> سفارش‌ها</a>
          <a class="nav-link" href="<?= \Core\Helper::appUrl('admin/subscriptions/index.php') ?>"><i class="bi bi-person-badge"></i> اشتراک‌ها</a>
          <a class="nav-link" href="<?= \Core\Helper::appUrl('admin/warehouse/index.php') ?>"><i class="bi bi-archive"></i> انبار دستی</a>
          <a class="nav-link" href="<?= \Core\Helper::appUrl('admin/volumes/index.php') ?>"><i class="bi bi-hdd"></i> حجم‌ها</a>
        </div>
      </div>
    </div>

    <!-- کاربران و پشتیبانی -->
    <div class="accordion-item">
      <button class="accordion-header" data-bs-toggle="collapse" data-bs-target="#collapseUsers" aria-expanded="false">
        <span><i class="bi bi-people me-2"></i> کاربران & پشتیبانی</span><i class="bi bi-chevron-down accordion-arrow"></i>
      </button>
      <div id="collapseUsers" class="accordion-collapse collapse" data-bs-parent="#sidebarAccordion">
        <div class="accordion-body">
          <a class="nav-link" href="<?= \Core\Helper::appUrl('admin/users/index.php') ?>"><i class="bi bi-people"></i> کاربران</a>
          <a class="nav-link" href="<?= \Core\Helper::appUrl('admin/tickets/index.php') ?>"><i class="bi bi-chat-dots"></i> تیکت‌ها</a>
          <a class="nav-link" href="<?= \Core\Helper::appUrl('admin/tickets/gifts.php') ?>"><i class="bi bi-gift"></i> هدیه نقدی & توکن</a>
        </div>
      </div>
    </div>

    <!-- سیستم -->
    <div class="accordion-item">
      <button class="accordion-header" data-bs-toggle="collapse" data-bs-target="#collapseSys" aria-expanded="false">
        <span><i class="bi bi-gear me-2"></i> سیستم</span><i class="bi bi-chevron-down accordion-arrow"></i>
      </button>
      <div id="collapseSys" class="accordion-collapse collapse" data-bs-parent="#sidebarAccordion">
        <div class="accordion-body">
          <a class="nav-link" href="<?= \Core\Helper::appUrl('admin/reports/index.php') ?>"><i class="bi bi-graph-up"></i> گزارش مالی</a>
          <a class="nav-link" href="<?= \Core\Helper::appUrl('admin/backups/index.php') ?>"><i class="bi bi-cloud-arrow-down"></i> بکاپ</a>
          <a class="nav-link" href="<?= \Core\Helper::appUrl('admin/api/logs.php') ?>"><i class="bi bi-journal-text"></i> لاگ‌ها</a>
        </div>
      </div>
    </div>
  </nav>
  <div class="sidebar-footer">
    <div class="small"><i class="bi bi-cpu me-1"></i> نسخه 1.0.0 | PHP 8.2+</div>
    <div class="small mt-1"><span class="pulse-dot"></span> آنلاین - Dark Neon</div>
  </div>
</div>
