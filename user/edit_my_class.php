<?php
session_start();
require_once "../includes/db_singleton.php";
require_once "../includes/functions.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../index.php");
    exit;
}

if (!isset($_GET['class_id']) || empty($_GET['class_id'])) {
    header("location: my_classes.php");
    exit;
}

$link = get_db_connection();
$class_id = $_GET['class_id'];
$user_id = $_SESSION['id'];
$err = $success_msg = "";

// Security Check: Ensure the user is a teacher of this class
if (!is_teacher_of_class($link, $user_id, $class_id)) {
    // Or redirect to an error page
    die("دسترسی غیرمجاز.");
}

// --- Handle Add Student ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_student'])) {
    $student_name = trim($_POST['student_name']);
    if (!empty($student_name)) {
        $sql_add = "INSERT INTO class_students (class_id, student_name, added_by_user_id) VALUES (?, ?, ?)";
        if ($stmt_add = mysqli_prepare($link, $sql_add)) {
            mysqli_stmt_bind_param($stmt_add, "isi", $class_id, $student_name, $user_id);
            if (mysqli_stmt_execute($stmt_add)) {
                $success_msg = "متربی با موفقیت اضافه شد.";
                // Send notification to admins/recruitment managers
                $class_name_q = mysqli_fetch_assoc(mysqli_query($link, "SELECT class_name, region_id FROM classes WHERE id = $class_id"));
                $class_name = $class_name_q['class_name'];
                $region_id = $class_name_q['region_id'];
                $message = "متربی جدید '{$student_name}' توسط مدرس به کلاس '{$class_name}' اضافه شد. لطفاً لیست جذب را بررسی کنید.";
                $link_url = "/dabestan/admin/manage_class_students.php?class_id=" . $class_id;
                // This function needs to be created, it will notify users with 'manage_recruitment' permission
                notify_permission('manage_recruitment', $message, $link_url);
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

    // Fetch student name for notification before deleting
    $student_name_q = mysqli_fetch_assoc(mysqli_query($link, "SELECT student_name FROM class_students WHERE id = $student_id_to_delete AND class_id = $class_id"));
    $student_name = $student_name_q['student_name'] ?? 'ناشناس';

    $sql_delete = "DELETE FROM class_students WHERE id = ? AND class_id = ?";
    if ($stmt_delete = mysqli_prepare($link, $sql_delete)) {
        mysqli_stmt_bind_param($stmt_delete, "ii", $student_id_to_delete, $class_id);
        if (mysqli_stmt_execute($stmt_delete)) {
            $success_msg = "متربی با موفقیت حذف شد.";
             // Send notification
            $class_name = mysqli_fetch_assoc(mysqli_query($link, "SELECT class_name FROM classes WHERE id = $class_id"))['class_name'];
            $message = "متربی '{$student_name}' توسط مدرس از کلاس '{$class_name}' حذف شد.";
            $link_url = "/dabestan/admin/manage_class_students.php?class_id=" . $class_id;
            notify_permission('manage_recruitment', $message, $link_url);
        } else {
            $err = "خطا در حذف متربی.";
        }
        mysqli_stmt_close($stmt_delete);
    }
}


// Fetch class details and students
$class_query = mysqli_query($link, "SELECT * FROM classes WHERE id = $class_id");
$class = mysqli_fetch_assoc($class_query);
$students_query = mysqli_query($link, "SELECT id, student_name FROM class_students WHERE class_id = $class_id ORDER BY student_name ASC");
$students = mysqli_fetch_all($students_query, MYSQLI_ASSOC);


require_once "../includes/header.php";
?>

<div class="page-content">
    <a href="my_classes.php" class="btn btn-secondary" style="margin-bottom: 20px;">&larr; بازگشت به لیست کلاس‌ها</a>
    <h2>ویرایش کلاس: <?php echo htmlspecialchars($class['class_name']); ?></h2>

    <?php
    if(!empty($err)){ echo '<div class="alert alert-danger">' . $err . '</div>'; }
    if(!empty($success_msg)){ echo '<div class="alert alert-success">' . $success_msg . '</div>'; }
    ?>

    <div class="table-container">
        <h3>مدیریت متربیان</h3>

        <!-- Add Student Form -->
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

        <!-- List of current students -->
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
                    <tr><td colspan="2">هنوز متربی‌ای به این کلاس اضافه نشده است.</td></tr>
                <?php else: ?>
                    <?php foreach ($students as $student): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($student['student_name']); ?></td>
                        <td>
                            <a href="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>?class_id=<?php echo $class_id; ?>&action=delete_student&student_id=<?php echo $student['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('آیا از حذف این متربی از کلاس مطمئن هستید؟ این عمل غیرقابل بازگشت است.')">
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
