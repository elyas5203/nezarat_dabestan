<?php
session_start();
require_once "../includes/db_singleton.php";
require_once "../includes/functions.php";
require_once "../includes/access_control.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !has_permission('manage_classes')) {
    header("location: ../index.php");
    exit;
}

if (!isset($_GET['class_id']) || empty($_GET['class_id'])) {
    header("location: manage_classes.php");
    exit;
}

$link = get_db_connection();
$class_id = $_GET['class_id'];
$admin_id = $_SESSION['id'];
$err = $success_msg = "";

// Fetch class details
$class_query = mysqli_query($link, "SELECT class_name, region_id FROM classes WHERE id = $class_id");
if(mysqli_num_rows($class_query) == 0){
    header("location: manage_classes.php");
    exit;
}
$class = mysqli_fetch_assoc($class_query);
$region_id = $class['region_id'];
$class_name = $class['class_name'];

// --- Handle Add Student ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_student'])) {
    $student_name = trim($_POST['student_name']);
    if (!empty($student_name)) {
        $sql_add = "INSERT INTO class_students (class_id, student_name, added_by_user_id) VALUES (?, ?, ?)";
        if ($stmt_add = mysqli_prepare($link, $sql_add)) {
            mysqli_stmt_bind_param($stmt_add, "isi", $class_id, $student_name, $admin_id);
            if (mysqli_stmt_execute($stmt_add)) {
                $success_msg = "متربی با موفقیت اضافه شد.";
                // Notify teachers of this class
                $message = "متربی جدید '{$student_name}' توسط مدیر به کلاس شما ('{$class_name}') اضافه شد.";
                $link_url = "/dabestan/user/my_classes.php";
                notify_class_teachers($class_id, $message, $link_url);
            } else {
                $err = "خطا در افزودن متربی.";
            }
            mysqli_stmt_close($stmt_add);
        }
    } else {
        $err = "نام متربی نمی‌تواند خالی باشد.";
    }
}

// --- Handle Delete Student ---
if (isset($_GET['action']) && $_GET['action'] === 'delete_student' && isset($_GET['student_id'])) {
    $student_id_to_delete = $_GET['student_id'];
    $student_name_q = mysqli_fetch_assoc(mysqli_query($link, "SELECT student_name FROM class_students WHERE id = $student_id_to_delete"));
    $student_name = $student_name_q['student_name'] ?? 'ناشناس';

    $sql_delete = "DELETE FROM class_students WHERE id = ?";
    if ($stmt_delete = mysqli_prepare($link, $sql_delete)) {
        mysqli_stmt_bind_param($stmt_delete, "i", $student_id_to_delete);
        if (mysqli_stmt_execute($stmt_delete)) {
            $success_msg = "متربی با موفقیت حذف شد.";
            // Notify teachers
            $message = "متربی '{$student_name}' توسط مدیر از کلاس شما ('{$class_name}') حذف شد.";
            $link_url = "/dabestan/user/my_classes.php";
            notify_class_teachers($class_id, $message, $link_url);
        } else {
            $err = "خطا در حذف متربی.";
        }
        mysqli_stmt_close($stmt_delete);
    }
}

// Fetch current students in the class
$students_query = mysqli_query($link, "SELECT id, student_name FROM class_students WHERE class_id = $class_id ORDER BY student_name ASC");
$students = mysqli_fetch_all($students_query, MYSQLI_ASSOC);

require_once "../includes/header.php";
?>

<div class="page-content">
    <a href="manage_classes.php" class="btn btn-secondary" style="margin-bottom: 20px;">&larr; بازگشت به لیست کلاس‌ها</a>
    <h2>مدیریت متربیان کلاس: <?php echo htmlspecialchars($class_name); ?></h2>

    <?php
    if(!empty($err)){ echo '<div class="alert alert-danger">' . $err . '</div>'; }
    if(!empty($success_msg)){ echo '<div class="alert alert-success">' . $success_msg . '</div>'; }
    ?>

    <div class="table-container">
        <div class="form-container" style="margin-bottom: 30px; background-color: #f8f9fa; padding: 20px; border-radius: 8px;">
             <h4>افزودن متربی جدید</h4>
             <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>?class_id=<?php echo $class_id; ?>" method="post">
                 <div class="form-group">
                     <label for="student_name">نام و نام خانوادگی متربی:</label>
                     <input type="text" name="student_name" class="form-control" required>
                 </div>
                 <button type="submit" name="add_student" class="btn btn-success">افزودن به کلاس</button>
            </form>
        </div>

        <h4>لیست متربیان فعلی (<?php echo count($students); ?> نفر)</h4>
        <table class="table">
            <thead>
                <tr>
                    <th>نام متربی</th>
                    <th style="width: 100px;">عملیات</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($students)): ?>
                    <tr><td colspan="2">هنوز متربی‌ای در این کلاس ثبت نشده است.</td></tr>
                <?php else: ?>
                    <?php foreach ($students as $student): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($student['student_name']); ?></td>
                        <td>
                            <a href="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>?class_id=<?php echo $class_id; ?>&action=delete_student&student_id=<?php echo $student['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('آیا از حذف این متربی از کلاس مطمئن هستید؟')">
                                حذف
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
require_once "../includes/footer.php";
?>
