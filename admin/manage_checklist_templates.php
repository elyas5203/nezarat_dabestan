<?php
session_start();
require_once "../includes/db.php";
require_once "../includes/functions.php";

if (!isset($_SESSION["loggedin"]) || !$_SESSION["loggedin"] || !is_admin()) {
    header("location: ../index.php");
    exit;
}

// Handle Add Template POST Request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_template'])) {
    $template_name = trim($_POST['template_name']);
    $description = trim($_POST['description']);

    if (!empty($template_name)) {
        $sql = "INSERT INTO checklist_templates (template_name, description, created_by) VALUES (?, ?, ?)";
        $stmt = mysqli_prepare($link, $sql);
        mysqli_stmt_bind_param($stmt, "ssi", $template_name, $description, $_SESSION['id']);
        mysqli_stmt_execute($stmt);
    }
}

// Fetch all templates
$templates = [];
$sql = "SELECT * FROM checklist_templates ORDER BY template_name ASC";
$result = mysqli_query($link, $sql);
if ($result) {
    $templates = mysqli_fetch_all($result, MYSQLI_ASSOC);
}


require_once "../includes/header.php";
?>

<div class="page-content">
    <h2>مدیریت قالب‌های چک‌لیست</h2>
    <p>در این بخش می‌توانید قالب‌های چک‌لیست برای انواع جلسات (مانند جلسه اولیا، ضمن خدمت و...) را تعریف کنید.</p>

    <div class="form-container card mb-4">
        <div class="card-header"><h3>ایجاد قالب جدید</h3></div>
        <div class="card-body">
            <form action="" method="post">
                <div class="form-group">
                    <label for="template_name">نام قالب (مثال: چک‌لیست جلسه اولیا)</label>
                    <input type="text" name="template_name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="description">توضیحات</label>
                    <textarea name="description" class="form-control"></textarea>
                </div>
                <button type="submit" name="add_template" class="btn btn-primary">ایجاد قالب</button>
            </form>
        </div>
    </div>

    <div class="table-container card">
        <div class="card-header"><h3>قالب‌های موجود</h3></div>
        <div class="card-body">
            <table class="table">
                <thead>
                    <tr>
                        <th>نام قالب</th>
                        <th>توضیحات</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($templates)): ?>
                        <tr><td colspan="3" class="text-center">هیچ قالبی یافت نشد.</td></tr>
                    <?php else: ?>
                        <?php foreach ($templates as $template): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($template['template_name']); ?></td>
                                <td><?php echo htmlspecialchars($template['description']); ?></td>
                                <td>
                                    <a href="edit_checklist_template.php?id=<?php echo $template['id']; ?>" class="btn btn-primary btn-sm">ویرایش آیتم‌ها</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
mysqli_close($link);
require_once "../includes/footer.php";
?>
