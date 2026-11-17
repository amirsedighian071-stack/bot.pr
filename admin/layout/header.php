<?php
// بافر برای جلوگیری از Cannot modify header information
if (ob_get_level() === 0) ob_start();
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../src/Core/Database.php';
require_once __DIR__ . '/../../src/Core/Config.php';
require_once __DIR__ . '/../../src/Core/Helper.php';
require_once __DIR__ . '/../../src/Core/Auth.php';
use Core\Auth;
use Core\Helper;
use Core\Config;
Auth::startSession();
if (!Auth::check() && basename($_SERVER['PHP_SELF']) !== 'login.php') {
    Helper::redirect(Helper::appUrl('admin/login.php'));
}
$admin = Auth::admin();
$appUrl = Helper::appUrl('');
?>
<!doctype html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>پنل هوشمند فروش و مدیریت VPN<?= isset($pageTitle) ? ' | ' . $pageTitle : '' ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;700;800&display=swap" rel="stylesheet">
<link href="<?= Helper::appUrl('public/assets/css/style.css') ?>" rel="stylesheet">
</head>
<body>
<div class="overlay" id="overlay"></div>
<div class="d-flex">
<?php include __DIR__ . '/sidebar.php'; ?>
<div class="flex-grow-1" style="min-width:0">
<div class="topbar">
  <div class="d-flex align-items-center gap-2">
    <button class="btn btn-outline-secondary d-lg-none" id="sidebarToggle"><i class="bi bi-list"></i></button>
    <h5 class="m-0 fw-bold"><i class="bi bi-shield-check me-1 text-primary"></i>پنل هوشمند فروش و مدیریت VPN<?= isset($pageTitle) && $pageTitle !== 'داشبورد' ? ' <span class="text-muted fw-normal small">| ' . htmlspecialchars($pageTitle) . '</span>' : '' ?></h5>
  </div>
  <div class="d-flex align-items-center gap-3">
    <button id="themeToggle" class="btn btn-sm btn-outline-light" title="تغییر تم" style="width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center"><i class="bi bi-moon-stars" id="themeIcon"></i></button>
    <span class="small text-muted d-none d-md-inline"><?= $admin['username'] ?? '' ?> (<?= $admin['role'] ?? '' ?>)</span>
    <a href="<?= Helper::appUrl('admin/logout.php') ?>" class="btn btn-sm btn-outline-danger"><i class="bi bi-box-arrow-left"></i> خروج</a>
  </div>
</div>
<div class="p-3 p-md-4">
<?php if (!empty($_SESSION['flash_success'])): ?><div class="alert alert-success alert-auto"><i class="bi bi-check-circle"></i> <?= $_SESSION['flash_success']; unset($_SESSION['flash_success']); ?></div><?php endif; ?>
<?php if (!empty($_SESSION['flash_error'])): ?><div class="alert alert-danger alert-auto"><i class="bi bi-exclamation-triangle"></i> <?= $_SESSION['flash_error']; unset($_SESSION['flash_error']); ?></div><?php endif; ?>
