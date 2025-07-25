<?php
session_start();
require_once "../includes/db.php";
require_once "../includes/functions.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !is_admin()) {
    header("location: ../index.php");
    exit;
}


// Based on: https://davidwalsh.name/backup-mysql-database-php
function backup_database_tables($host, $user, $pass, $name, $tables = '*') {
    $link = mysqli_connect($host, $user, $pass, $name);
    mysqli_set_charset($link, 'utf8');

    if ($tables == '*') {
        $tables = array();
        $result = mysqli_query($link, 'SHOW TABLES');
        while ($row = mysqli_fetch_row($result)) {
            $tables[] = $row[0];
        }
    } else {
        $tables = is_array($tables) ? $tables : explode(',', $tables);
    }

    $return = '';
    foreach ($tables as $table) {
        $result = mysqli_query($link, 'SELECT * FROM ' . $table);
        $num_fields = mysqli_num_fields($result);

        $return .= 'DROP TABLE IF EXISTS ' . $table . ';';
        $row2 = mysqli_fetch_row(mysqli_query($link, 'SHOW CREATE TABLE ' . $table));
        $return .= "\n\n" . $row2[1] . ";\n\n";

        for ($i = 0; $i < $num_fields; $i++) {
            while ($row = mysqli_fetch_row($result)) {
                $return .= 'INSERT INTO ' . $table . ' VALUES(';
                for ($j = 0; $j < $num_fields; $j++) {
                    $row[$j] = addslashes($row[$j]);
                    $row[$j] = preg_replace("/\n/", "\\n", $row[$j]);
                    if (isset($row[$j])) {
                        $return .= '"' . $row[$j] . '"';
                    } else {
                        $return .= '""';
                    }
                    if ($j < ($num_fields - 1)) {
                        $return .= ',';
                    }
                }
                $return .= ");\n";
            }
        }
        $return .= "\n\n\n";
    }

    // Save the file
    $backup_file_name = 'db-backup-'.time().'-'.date("Y-m-d").'.sql';
    $handle = fopen($backup_file_name, 'w+');
    fwrite($handle, $return);
    fclose($handle);

    // Force download
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename=' . basename($backup_file_name));
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($backup_file_name));
    ob_clean();
    flush();
    readfile($backup_file_name);
    unlink($backup_file_name); // Delete the file from server after download
}


// --- Execute backup ---
if(isset($_POST['backup_now'])){
    backup_database_tables(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
    exit();
}


require_once "../includes/header.php";
?>

<div class="page-content">
    <h2>پشتیبان‌گیری از دیتابیس</h2>
    <p>
        در این بخش می‌توانید یک نسخه کامل از دیتابیس سیستم را به صورت یک فایل SQL دانلود کنید.
        این فایل شامل تمام اطلاعات کاربران، کلاس‌ها، فرم‌ها و سایر داده‌های سیستم است.
        توصیه می‌شود به صورت دوره‌ای (مثلاً هفتگی) یک نسخه پشتیبان تهیه کنید.
    </p>

    <div class="card">
        <div class="card-body">
            <h5 class="card-title">ایجاد نسخه پشتیبان جدید</h5>
            <p class="card-text">برای شروع فرآیند پشتیبان‌گیری و دانلود فایل، روی دکمه زیر کلیک کنید.</p>
            <form method="post">
                <button type="submit" name="backup_now" class="btn btn-primary">شروع پشتیبان‌گیری و دانلود</button>
            </form>
        </div>
    </div>
</div>

<?php
require_once "../includes/footer.php";
?>
