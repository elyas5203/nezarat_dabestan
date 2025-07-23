<?php
session_start();
require_once "../includes/db_singleton.php";
require_once "../includes/functions.php";
require_once "../includes/access_control.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !has_permission('manage_users')) {
    header("location: ../index.php");
    exit;
}

$link = get_db_connection();
$users = mysqli_query($link, "SELECT id, username, first_name, last_name, is_admin FROM users ORDER BY username");

require_once "../includes/header.php";
?>

<div class="page-content">
    <h2>مدیریت کاربران</h2>
    <a href="create_user.php" class="btn btn-primary" style="margin-bottom: 20px;">ایجاد کاربر جدید</a>

    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>نام کاربری</th>
                    <th>نام</th>
                    <th>نام خانوادگی</th>
                    <th>نقش</th>
                    <th>عملیات</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($user = mysqli_fetch_assoc($users)): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                        <td><?php echo htmlspecialchars($user['first_name']); ?></td>
                        <td><?php echo htmlspecialchars($user['last_name']); ?></td>
                        <td><?php echo $user['is_admin'] ? 'ادمین' : 'کاربر'; ?></td>
                        <td>
                            <a href="edit_user.php?id=<?php echo $user['id']; ?>" class="btn btn-secondary btn-sm">ویرایش</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
require_once "../includes/footer.php";
?>
