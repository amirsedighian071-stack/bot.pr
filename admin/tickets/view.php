<?php
// پردازش قبل از خروجی برای جلوگیری از headers already sent
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../src/Core/Database.php';
require_once __DIR__ . '/../../src/Core/Config.php';
require_once __DIR__ . '/../../src/Core/Helper.php';
require_once __DIR__ . '/../../src/Core/Auth.php';
use Core\Database;
use Core\Helper;
use Core\Telegram;
use Core\Auth;

Auth::startSession();
if (!Auth::check()) Helper::redirect(Helper::appUrl('admin/login.php'));

$id=(int)($_GET['id']??0);
$ticket=Database::fetchOne("SELECT t.*, u.username, u.first_name FROM tickets t LEFT JOIN users u ON u.telegram_id=t.telegram_id WHERE t.id=?",[$id]);
if(!$ticket){
    $pageTitle='گفتگو تیکت';
    require_once __DIR__ . '/../layout/header.php';
    echo "<div class='alert alert-danger'>تیکت یافت نشد</div>";
    require_once __DIR__ . '/../layout/footer.php';
    exit;
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $msg=trim($_POST['message']??'');
    $file=null; $fileType=null;
    if(isset($_FILES['file']) && $_FILES['file']['error']===0){
        $ext=pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
        $name=time().'_admin_'.bin2hex(random_bytes(4)).'.'.$ext;
        $dest=__DIR__ . '/../../public/uploads/tickets/'.$name;
        @mkdir(dirname($dest),0755,true);
        if(move_uploaded_file($_FILES['file']['tmp_name'],$dest)){
            $file='uploads/tickets/'.$name;
            $fileType=$_FILES['file']['type'];
        }
    }
    if($msg || $file){
        Database::query("INSERT INTO ticket_messages (ticket_id,sender_type,sender_id,message,file_path,file_type) VALUES (?,?,?,?,?,?)",[$id,'admin', (string)($_SESSION['admin_id']??'admin'), $msg, $file, $fileType]);
        Database::query("UPDATE tickets SET status='answered', updated_at=NOW() WHERE id=?",[$id]);
        try{
            $tg=new Telegram();
            $text='<i class="bi bi-chat-dots"></i> پاسخ پشتیبانی (تیکت #' . $id . '):' . "\n\n" . $msg;
            if($file){
                $fullUrl=Helper::appUrl('public/'.$file);
                if(str_contains($fileType??'','image')) $tg->sendPhoto((int)$ticket['telegram_id'], $fullUrl, $text);
                else $tg->sendMessage((int)$ticket['telegram_id'], $text . "\n\n" . '<i class="bi bi-paperclip"></i> فایل: ' . $fullUrl);
            } else $tg->sendMessage((int)$ticket['telegram_id'], $text);
        } catch(\Throwable $e){}
        $_SESSION['flash_success']='پاسخ ارسال شد';
        Helper::redirect(Helper::appUrl('admin/tickets/view.php?id='.$id));
    } else {
        $_SESSION['flash_error']='پیام خالی است';
        Helper::redirect(Helper::appUrl('admin/tickets/view.php?id='.$id));
    }
}

$pageTitle='گفتگو تیکت';
require_once __DIR__ . '/../layout/header.php';

$messages=Database::fetchAll("SELECT * FROM ticket_messages WHERE ticket_id=? ORDER BY id ASC",[$id]);
Database::query("UPDATE ticket_messages SET is_read=1 WHERE ticket_id=? AND sender_type='user'",[$id]);
?>
<div class="card">
  <div class="card-header d-flex justify-content-between"><span>تیکت #<?= $id ?> - <?= Helper::escapeHtml($ticket['subject']) ?> | کاربر: <?= $ticket['telegram_id'] ?> (@<?= $ticket['username']??'' ?>)</span><a href="index.php" class="btn btn-sm btn-light">بازگشت</a></div>
  <div class="card-body">
    <div class="chat-box mb-3" id="chatBox">
      <?php foreach($messages as $m): ?>
        <div class="msg <?= $m['sender_type']==='user'?'admin':'user' ?>" style="<?= $m['sender_type']==='user'?'background:#fff;border:1px solid #e2e8f0;color:#1e293b;margin-left:auto':'background:#6366f1;color:#fff;margin-right:auto' ?>">
          <div><?= nl2br(Helper::escapeHtml($m['message']??'')) ?></div>
          <?php if($m['file_path']): ?>
            <div class="mt-2">
              <?php if(str_contains($m['file_type']??'','image')): ?><img src="<?= Helper::appUrl('public/'.$m['file_path']) ?>" style="max-width:200px;border-radius:8px;cursor:pointer" onclick="window.open(this.src)"><?php else: ?><a href="<?= Helper::appUrl('public/'.$m['file_path']) ?>" target="_blank" class="btn btn-sm btn-light"><i class="bi bi-paperclip"></i> دانلود فایل</a><?php endif; ?>
            </div>
          <?php endif; ?>
          <div class="meta small opacity-75"><?= $m['sender_type']==='user'?'کاربر':'ادمین' ?> - <?= $m['created_at'] ?></div>
        </div>
      <?php endforeach; if(empty($messages)): ?><div class="text-center text-muted py-5">پیامی نیست</div><?php endif; ?>
    </div>
    <form method="post" enctype="multipart/form-data" class="row g-2">
      <div class="col-12"><textarea name="message" rows="3" class="form-control" placeholder="پاسخ خود را بنویسید..."></textarea></div>
      <div class="col-md-6"><input type="file" name="file" class="form-control"></div>
      <div class="col-md-6 text-end"><button class="btn btn-primary px-4"><i class="bi bi-send"></i> ارسال پاسخ (به تلگرام کاربر)</button></div>
    </form>
  </div>
</div>
<script>document.getElementById('chatBox').scrollTop=document.getElementById('chatBox').scrollHeight;</script>
<?php require_once __DIR__ . '/../layout/footer.php'; ?>
