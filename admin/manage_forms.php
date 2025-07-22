<?php
session_start();
require_once "../includes/db_singleton.php";
require_once "../includes/functions.php";
require_once "../includes/access_control.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !has_permission('manage_forms')) {
    header("location: ../index.php");
    exit;
}

$link = get_db_connection();
$err = $success_msg = "";

// Handle Delete Form Request
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $form_id_to_delete = $_GET['id'];
    // Add CSRF token check here if you have a system for it

    // Deleting a form can have cascading effects. Ensure this is what you want.
    // For example, you might want to delete all submissions for this form first.
    $sql_delete_submissions = "DELETE FROM form_submissions WHERE form_id = ?";
    if($stmt_del_sub = mysqli_prepare($link, $sql_delete_submissions)){
        mysqli_stmt_bind_param($stmt_del_sub, "i", $form_id_to_delete);
        mysqli_stmt_execute($stmt_del_sub);
        mysqli_stmt_close($stmt_del_sub);
    }

    $sql_delete_fields = "DELETE FROM form_fields WHERE form_id = ?";
     if($stmt_del_fields = mysqli_prepare($link, $sql_delete_fields)){
        mysqli_stmt_bind_param($stmt_del_fields, "i", $form_id_to_delete);
        mysqli_stmt_execute($stmt_del_fields);
        mysqli_stmt_close($stmt_del_fields);
    }

    $sql_delete_form = "DELETE FROM forms WHERE id = ?";
    if($stmt_del_form = mysqli_prepare($link, $sql_delete_form)){
        mysqli_stmt_bind_param($stmt_del_form, "i", $form_id_to_delete);
        if(mysqli_stmt_execute($stmt_del_form)){
            $success_msg = "فرم و تمام داده‌های مرتبط با آن با موفقیت حذف شد.";
        } else {
            $err = "خطا در حذف فرم.";
        }
        mysqli_stmt_close($stmt_del_form);
    }
}


// Fetch all forms
$forms = [];
$sql_forms = "SELECT f.id, f.form_name, f.form_description, u.username as created_by, f.created_at, (SELECT COUNT(id) FROM form_fields WHERE form_id = f.id) as field_count FROM forms f JOIN users u ON f.created_by = u.id ORDER BY f.created_at DESC";
$result_forms = mysqli_query($link, $sql_forms);
if($result_forms){
    $forms = mysqli_fetch_all($result_forms, MYSQLI_ASSOC);
}


require_once "../includes/header.php";
?>

<div class="page-content">
    <h2>مدیریت فرم‌ها</h2>

    <?php
    if(!empty($err)){ echo '<div class="alert alert-danger">' . $err . '</div>'; }
    if(!empty($success_msg)){ echo '<div class="alert alert-success">' . $success_msg . '</div>'; }
    ?>

    <div class="form-container" style="margin-bottom: 30px;">
        <h3>ایجاد فرم جدید</h3>
        <form action="create_form.php" method="post">
             <div class="form-group">
                <label for="form_name">نام فرم</label>
                <input type="text" name="form_name" class="form-control" required>
            </div>
             <div class="form-group">
                <label for="form_description">توضیحات فرم</label>
                <textarea name="form_description" class="form-control"></textarea>
            </div>
            <div class="form-group">
                <input type="submit" name="create_form" class="btn btn-primary" value="ایجاد فرم">
            </div>
        </form>
    </div>

    <div class="table-container">
        <h3>لیست فرم‌های موجود</h3>
        <?php if (empty($forms)): ?>
            <p>هیچ فرمی تاکنون ایجاد نشده است.</p>
        <?php else: ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>نام فرم</th>
                        <th>تعداد فیلدها</th>
                        <th>ایجاد شده توسط</th>
                        <th>تاریخ ایجاد</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($forms as $form): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($form['form_name']); ?></td>
                            <td><?php echo $form['field_count']; ?></td>
                            <td><?php echo htmlspecialchars($form['created_by']); ?></td>
                            <td><?php echo to_persian_date($form['created_at']); ?></td>
                            <td>
                                <a href="design_form.php?form_id=<?php echo $form['id']; ?>" class="btn btn-secondary btn-sm">طراحی</a>
                                <a href="manage_forms.php?action=delete&id=<?php echo $form['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('آیا مطمئن هستید؟ حذف فرم تمام فیلدها و پاسخ‌های ثبت شده آن را نیز حذف خواهد کرد.')">حذف</a>
                                <a href="view_submissions.php?form_id=<?php echo $form['id']; ?>" class="btn btn-info btn-sm">مشاهده پاسخ‌ها</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php
require_once "../includes/footer.php";
?>
