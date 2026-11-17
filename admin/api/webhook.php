<?php
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../src/Core/Database.php';
require_once __DIR__ . '/../../src/Core/Config.php';
require_once __DIR__ . '/../../src/Core/Helper.php';
require_once __DIR__ . '/../../src/Core/Telegram.php';
use Core\Config;
use Core\Helper;
use Core\Telegram;
session_start();
if (!isset($_SESSION['admin_id'])) { http_response_code(403); exit('forbidden'); }

if ($_POST['action']==='delete') {
    $token=Config::setting('bot_token','');
    if($token){ $tg=new Telegram($token); $tg->deleteWebhook(true); }
    $_SESSION['flash_success']='وب‌هوک حذف شد';
    Helper::redirect(Helper::appUrl('admin/settings/bot.php'));
}
