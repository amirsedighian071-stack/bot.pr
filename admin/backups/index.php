<?php
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../src/Core/Database.php';
require_once __DIR__ . '/../../src/Core/Config.php';
require_once __DIR__ . '/../../src/Core/Helper.php';
require_once __DIR__ . '/../../src/Core/Auth.php';
use Core\Database;
use Core\Helper;
use Core\Config;
use Core\Auth;
Auth::startSession();
if (!Auth::check()) Helper::redirect(Helper::appUrl('admin/login.php'));

if(isset($_POST['backup'])){
    $cfg=Config::get('db');
    $mysqldump = trim(shell_exec('which mysqldump')??'');
    $filename='backup_'.date('Y-m-d_H-i-s').'.sql';
    $path=__DIR__ . '/../../public/uploads/'.$filename;
    $success=false;
    if($mysqldump){
        $cmd = sprintf('%s -h %s -P %s -u %s -p%s %s > %s 2>&1', escapeshellarg($mysqldump), escapeshellarg($cfg['host']), escapeshellarg($cfg['port']), escapeshellarg($cfg['user']), escapeshellarg($cfg['pass']), escapeshellarg($cfg['name']), escapeshellarg($path));
        exec($cmd,$out,$code);
        $success = file_exists($path) && filesize($path)> 100;
    }
    if(!$success){
        $tables=Database::fetchAll("SHOW TABLES");
        $sql="-- Backup ".date('Y-m-d H:i:s')."\nSET FOREIGN_KEY_CHECKS=0;\n";
        foreach($tables as $t){
            $table = array_values($t)[0];
            $create=Database::fetchOne("SHOW CREATE TABLE `$table`");
            $sql .= "\nDROP TABLE IF EXISTS `$table`;\n" . ($create['Create Table']??'').";\n";
            $rows=Database::fetchAll("SELECT * FROM `$table`");
            foreach($rows as $row){
                $vals=array_map(fn($v)=> $v===null?'NULL':"'".addslashes($v)."'", array_values($row));
                $cols=array_map(fn($c)=>"`$c`", array_keys($row));
                $sql .= "INSERT INTO `$table` (".implode(',',$cols).") VALUES (".implode(',',$vals).");\n";
            }
        }
        file_put_contents($path,$sql);
        $success=true;
    }
    if($success){
        $_SESSION['flash_success']="بکاپ ساخته شد: $filename (".round(filesize($path)/1024)." KB)";
    } else $_SESSION['flash_error']='خطا ساخت بکاپ';
    Helper::redirect(Helper::appUrl('admin/backups/index.php'));
}

$pageTitle='بکاپ';
require_once __DIR__ . '/../layout/header.php';

$files=glob(__DIR__.'/../../public/uploads/backup_*.sql')?:[];
?>
<div class="card">
  <div class="card-header d-flex justify-content-between"><span>بکاپ دیتابیس</span><form method="post"><button name="backup" value="1" class="btn btn-primary"><i class="bi bi-download"></i> ساخت بکاپ جدید</button></form></div>
  <div class="table-responsive"><table class="table small mb-0"><thead><tr><th>فایل</th><th>حجم</th><th>تاریخ</th><th>دانلود</th></tr></thead><tbody>
  <?php foreach(array_reverse($files) as $f): $name=basename($f); ?><tr><td><code><?= $name ?></code></td><td><?= round(filesize($f)/1024) ?> KB</td><td><?= date('Y-m-d H:i', filemtime($f)) ?></td><td><a href="<?= Helper::appUrl('public/uploads/'.$name) ?>" class="btn btn-sm btn-success">دانلود</a></td></tr><?php endforeach; if(empty($files)): ?><tr><td colspan="4" class="text-center text-muted py-3">بکاپی نیست</td></tr><?php endif; ?>
  </tbody></table></div>
</div>
<div class="alert alert-info small mt-3">برای بکاپ زمان‌بندی شده، کرون‌جاب را به <code>public/cron.php</code> یا این صفحه اضافه کنید.</div>
<?php require_once __DIR__ . '/../layout/footer.php'; ?>
