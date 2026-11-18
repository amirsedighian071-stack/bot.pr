<?php
// Entry point for React admin - serves SPA fallback
// Visit: /admin/react.php  or  /public/admin-react/
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../src/Core/Auth.php';
use Core\Auth; use Core\Helper;
Auth::startSession();
if (!Auth::check()) Helper::redirect(Helper::appUrl('admin/login.php'));

// Serve built frontend
$dist = __DIR__ . '/../frontend/dist/index.html';
if (file_exists($dist)) {
    readfile($dist);
    exit;
}
// Fallback: redirect to public
Helper::redirect(Helper::appUrl('public/admin-react/'));
