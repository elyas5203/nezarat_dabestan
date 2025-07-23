<?php
session_start();
require_once "includes/db_singleton.php";
$link = get_db_connection(); // Get connection

$username = $password = "";
$err = "";

// if user is already logged in, redirect to appropriate dashboard
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true){
    if($_SESSION["is_admin"]){
        header("location: admin/index.php");
    } else {
        header("location: user/index.php");
    }
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (empty(trim($_POST["username"])) || empty(trim($_POST["password"]))) {
        $err = "لطفا نام کاربری و رمز عبور را وارد کنید.";
    } else {
        $username = trim($_POST["username"]);
        $password = trim($_POST["password"]);
    }

    if (empty($err)) {
        $sql = "SELECT id, username, password, is_admin FROM users WHERE username = ?";

        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $param_username);
            $param_username = $username;

            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_store_result($stmt);

                if (mysqli_stmt_num_rows($stmt) == 1) {
                    mysqli_stmt_bind_result($stmt, $id, $username, $hashed_password, $is_admin);
                    if (mysqli_stmt_fetch($stmt)) {
                        if (password_verify($password, $hashed_password)) {
                            // Update last login timestamp
                            $update_sql = "UPDATE users SET last_login_at = NOW() WHERE id = ?";
                            if($update_stmt = mysqli_prepare($link, $update_sql)){
                                mysqli_stmt_bind_param($update_stmt, "i", $id);
                                mysqli_stmt_execute($update_stmt);
                                mysqli_stmt_close($update_stmt);
                            }

                            // Set session variables
                            $_SESSION["loggedin"] = true;
                            $_SESSION["id"] = $id;
                            $_SESSION["username"] = $username;
                            $_SESSION["is_admin"] = $is_admin;

                            // Create a login notification
                            $notification_message = "شما با موفقیت وارد سیستم شدید.";
                            $insert_notification_sql = "INSERT INTO notifications (user_id, message, type, link) VALUES (?, ?, 'login', '/user/view_all_notifications.php')";
                            if($notif_stmt = mysqli_prepare($link, $insert_notification_sql)){
                                mysqli_stmt_bind_param($notif_stmt, "is", $id, $notification_message);
                                mysqli_stmt_execute($notif_stmt);
                                mysqli_stmt_close($notif_stmt);
                            }

                            // Redirect user
                            if($is_admin){
                                header("location: admin/index.php");
                            } else {
                                header("location: user/index.php");
                            }
                            exit; // Exit after redirect
                        } else {
                            $err = "نام کاربری یا رمز عبور اشتباه است.";
                        }
                    }
                } else {
                    $err = "نام کاربری یا رمز عبور اشتباه است.";
                }
            } else {
                $err = "خطایی رخ داد. لطفا بعدا تلاش کنید.";
            }
            mysqli_stmt_close($stmt);
        }
    }
}
// mysqli_close($link); // Singleton handles connection closing
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ورود به سامانه دبستان</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-page">
    <div class="login-wrapper">
        <h2>ورود به سامانه</h2>
        <p>لطفا اطلاعات خود را برای ورود وارد کنید.</p>
        <?php
        if(!empty($err)){
            echo '<div class="alert alert-danger">' . $err . '</div>';
        }
        ?>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label>نام کاربری</label>
                <input type="text" name="username" class="form-control" value="<?php echo $username; ?>">
            </div>
            <div class="form-group">
                <label for="password">رمز عبور</label>
                <div class="password-wrapper" style="position: relative;">
                    <input type="password" id="password" name="password" class="form-control">
                    <span class="toggle-password" onclick="togglePasswordVisibility()" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); cursor: pointer;">
                        <i data-feather="eye"></i>
                    </span>
                </div>
            </div>
            <div class="form-group">
                <input type="submit" class="btn btn-primary" value="ورود">
            </div>
        </form>
    </div>
    <script src="https://unpkg.com/feather-icons"></script>
    <script>
        feather.replace();
        function togglePasswordVisibility() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.querySelector('.toggle-password i');
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.setAttribute('data-feather', 'eye-off');
            } else {
                passwordInput.type = 'password';
                toggleIcon.setAttribute('data-feather', 'eye');
            }
            feather.replace();
        }
    </script>
</body>
</html>
?>
