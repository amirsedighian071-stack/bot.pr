<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../src/Core/Database.php';
require_once __DIR__ . '/../src/Core/Config.php';
require_once __DIR__ . '/../src/Core/Helper.php';
require_once __DIR__ . '/../src/Core/Auth.php';
\Core\Auth::startSession();
\Core\Auth::logout();
\Core\Helper::redirect(\Core\Helper::appUrl('admin/login.php'));
