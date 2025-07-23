<?php
session_start();
require_once "../includes/db_singleton.php";
require_once "../includes/functions.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !$_SESSION["is_admin"]) {
    header("location: ../index.php");
    exit;
}

$link = get_db_connection();

// Fetch all dynamic forms
$forms_result = mysqli_query($link, "SELECT id, form_name, updated_at FROM dynamic_forms ORDER BY form_name");

require_once "../includes/header.php";
?>

<div class="page-content">
    <div class="page-header">
        <h2>مدیریت فرم‌های پویا</h2>
        <a href="design_form.php" class="btn btn-success">ایجاد فرم جدید</a>
    </div>

    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>نام فرم</th>
                    <th>آخرین بروزرسانی</th>
                    <th>عملیات</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($form = mysqli_fetch_assoc($forms_result)): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($form['form_name']); ?></td>
                        <td><?php echo jdf("Y/m/d H:i", strtotime($form['updated_at'])); ?></td>
                        <td>
<a href="edit_form.php?id=<?php echo $form['id']; ?>" class="btn btn-primary btn-sm">ویرایش/طراحی</a>
<a href="../user/fill_form.php?form_id=<?php echo $form['id']; ?>" class="btn btn-info btn-sm" target="_blank">پیش‌نمایش</a>
                            <a href="delete_form.php?form_id=<?php echo $form['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('آیا از حذف این فرم مطمئن هستید؟');">حذف</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
mysqli_close($link);
require_once "../includes/footer.php";
?>
