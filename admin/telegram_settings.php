<?php
session_start();
require_once "../includes/db.php";
require_once "../includes/functions.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !$_SESSION["is_admin"]) {
    header("location: ../index.php");
    exit;
}

// For simplicity, we store the token in a file. In a real app, use a more secure method.
$token_file = '../config/telegram_token.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_token'])) {
    $token = trim($_POST['bot_token']);
    file_put_contents($token_file, "<?php define('TELEGRAM_BOT_TOKEN', '" . $token . "');");
    $success_msg = "توکن ربات تلگرام با موفقیت ذخیره شد.";
}

$current_token = '';
if (file_exists($token_file)) {
    require_once $token_file;
    if (defined('TELEGRAM_BOT_TOKEN')) {
        $current_token = TELEGRAM_BOT_TOKEN;
    }
}

require_once "../includes/header.php";
?>

<div class="page-content">
    <h2>تنظیمات ربات تلگرام</h2>
    <p>در این بخش می‌توانید توکن ربات تلگرام خود را برای ارسال اعلان‌ها وارد کنید.</p>

    <?php
    if(!empty($success_msg)){ echo '<div class="alert alert-success">' . $success_msg . '</div>'; }
    ?>

    <form method="POST">
        <div class="form-group">
            <label for="bot_token">توکن ربات تلگرام</label>
            <input type="text" name="bot_token" id="bot_token" class="form-control" value="<?php echo htmlspecialchars($current_token); ?>" placeholder="123456:ABC-DEF1234ghIkl-zyx57W2v1u123ew11">
            <small class="form-text text-muted">توکن را از BotFather در تلگرام دریافت کنید.</small>
        </div>
        <button type="submit" name="save_token" class="btn btn-primary">ذخیره توکن</button>
    </form>
</div>

<?php
mysqli_close($link);
require_once "../includes/footer.php";
?>
