<?php
session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../index.php");
    exit;
}
require_once "../includes/db.php";
require_once "../includes/functions.php";

$user_id = $_SESSION['id'];
$username = $_SESSION['username'];

// Fetch user's classes
$classes = [];
$class_query = "SELECT c.id, c.class_name FROM classes c JOIN class_teachers ct ON c.id = ct.class_id WHERE ct.teacher_id = ? AND c.status = 'active'";
$stmt_classes = mysqli_prepare($link, $class_query);
mysqli_stmt_bind_param($stmt_classes, "i", $user_id);
mysqli_stmt_execute($stmt_classes);
$result_classes = mysqli_stmt_get_result($stmt_classes);
while ($row = mysqli_fetch_assoc($result_classes)) {
    $classes[] = $row;
}
mysqli_stmt_close($stmt_classes);

// Fetch recent tasks
$tasks = [];
$task_query = "SELECT t.id, t.title, t.deadline, t.status FROM tasks t JOIN task_assignments ta ON t.id = ta.task_id WHERE ta.assigned_to_user_id = ? ORDER BY t.created_at DESC LIMIT 5";
$stmt_tasks = mysqli_prepare($link, $task_query);
mysqli_stmt_bind_param($stmt_tasks, "i", $user_id);
mysqli_stmt_execute($stmt_tasks);
$result_tasks = mysqli_stmt_get_result($stmt_tasks);
while ($row = mysqli_fetch_assoc($result_tasks)) {
    $tasks[] = $row;
}
mysqli_stmt_close($stmt_tasks);

// Fetch financial status
$financial_status_query = "SELECT
    (SELECT SUM(amount) FROM booklet_transactions WHERE user_id = ? AND transaction_type = 'debit') as total_debit,
    (SELECT SUM(amount) FROM booklet_transactions WHERE user_id = ? AND transaction_type = 'credit') as total_credit";
$stmt_financial = mysqli_prepare($link, $financial_status_query);
mysqli_stmt_bind_param($stmt_financial, "ii", $user_id, $user_id);
mysqli_stmt_execute($stmt_financial);
$financial_result = mysqli_stmt_get_result($stmt_financial);
$financial_status = mysqli_fetch_assoc($financial_result);
$balance = ($financial_status['total_credit'] ?? 0) - ($financial_status['total_debit'] ?? 0);
mysqli_stmt_close($stmt_financial);

// Fetch a quick analysis stat: Average score from the last 5 submissions
$avg_score = 0;
// Note: This is a simplified analysis. A real scenario might need more complex logic.
$analysis_query = "SELECT AVG(score) as avg_score FROM self_assessments WHERE user_id = ? ORDER BY created_at DESC LIMIT 5";
$stmt_analysis = mysqli_prepare($link, $analysis_query);
mysqli_stmt_bind_param($stmt_analysis, "i", $user_id);
if ($stmt_analysis && mysqli_stmt_execute($stmt_analysis)) {
    $analysis_result = mysqli_stmt_get_result($stmt_analysis);
    $analysis_data = mysqli_fetch_assoc($analysis_result);
    $avg_score = $analysis_data['avg_score'] ?? 0;
}
if($stmt_analysis) mysqli_stmt_close($stmt_analysis);


require_once "../includes/header.php";
?>

<style>
    .dashboard-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
        gap: 25px;
    }
    .dashboard-card {
        background-color: #fff;
        border-radius: 12px;
        box-shadow: 0 6px 20px rgba(0,0,0,0.07);
        padding: 25px;
        transition: transform 0.3s, box-shadow 0.3s;
        display: flex;
        flex-direction: column;
    }
    .dashboard-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    }
    .card-header {
        display: flex;
        align-items: center;
        gap: 15px;
        padding-bottom: 15px;
        margin-bottom: 20px;
        border-bottom: 1px solid #e9ecef;
    }
    .card-header .icon {
        padding: 12px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
    }
    .card-header h3 {
        margin: 0;
        font-size: 1.3rem;
        font-weight: 600;
    }
    .card-content { flex-grow: 1; }
    .card-content ul { list-style: none; padding: 0; margin: 0; }
    .card-content li {
        padding: 12px 5px;
        border-bottom: 1px solid #f5f5f5;
        display: flex;
        justify-content: space-between;
        align-items: center;
        transition: background-color 0.2s;
    }
     .card-content li:hover { background-color: #f8f9fa; }
    .card-content li:last-child { border-bottom: none; }
    .card-footer {
        margin-top: 20px;
        text-align: left;
    }
    .welcome-banner {
        background: linear-gradient(135deg, var(--primary-color), #5f79d6);
        color: white;
        padding: 35px;
        border-radius: 12px;
        margin-bottom: 30px;
        box-shadow: 0 8px 25px rgba(95, 121, 214, 0.3);
    }
    .welcome-banner h2 { margin-top: 0; font-weight: 700; }
</style>

<div class="page-content">
    <div class="welcome-banner">
        <h2>خوش آمدید, <?php echo htmlspecialchars($username); ?>!</h2>
        <p>اینجا داشبورد شماست. به سرعت به بخش‌های کلیدی دسترسی پیدا کنید.</p>
    </div>

    <div class="dashboard-grid">
        <!-- My Classes Card -->
        <div class="dashboard-card">
            <div class="card-header">
                <div class="icon" style="background-color: #17a2b8;"><i data-feather="book-open"></i></div>
                <h3>کلاس‌های من</h3>
            </div>
            <div class="card-content">
                <ul>
                    <?php if (empty($classes)): ?>
                        <li>کلاسی برای شما ثبت نشده است.</li>
                    <?php else: ?>
                        <?php foreach ($classes as $class): ?>
                            <li>
                                <a href="my_classes.php#class-<?php echo $class['id']; ?>"><?php echo htmlspecialchars($class['class_name']); ?></a>
                                <a href="self_assessment_form.php?class_id=<?php echo $class['id']; ?>" class="btn btn-sm btn-outline-info">فرم خوداظهاری</a>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>
            <div class="card-footer">
                <a href="my_classes.php" class="btn btn-sm btn-secondary">مدیریت کلاس‌ها</a>
            </div>
        </div>

        <!-- My Tasks Card -->
        <div class="dashboard-card">
            <div class="card-header">
                <div class="icon" style="background-color: #fd7e14;"><i data-feather="check-square"></i></div>
                <h3>آخرین وظایف من</h3>
            </div>
            <div class="card-content">
                <ul>
                    <?php if (empty($tasks)): ?>
                        <li>وظیفه‌ای برای شما ثبت نشده است.</li>
                    <?php else: ?>
                        <?php foreach ($tasks as $task): ?>
                            <li>
                                <a href="view_task.php?id=<?php echo $task['id']; ?>"><?php echo htmlspecialchars($task['title']); ?></a>
                                <span class="badge bg-light text-dark"><?php echo htmlspecialchars($task['status']); ?></span>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>
            <div class="card-footer">
                <a href="my_tasks.php" class="btn btn-sm btn-secondary">مشاهده همه وظایف</a>
            </div>
        </div>

        <!-- Analysis Card -->
        <div class="dashboard-card">
            <div class="card-header">
                <div class="icon" style="background-color: #28a745;"><i data-feather="bar-chart-2"></i></div>
                <h3>تحلیل عملکرد</h3>
            </div>
            <div class="card-content">
                <p>نگاهی سریع به آخرین تحلیل‌های عملکرد شما.</p>
                <div class="text-center my-3">
                    <h4 class="display-6"><?php echo round($avg_score, 1); ?> / 10</h4>
                    <p class="text-muted">میانگین امتیاز ۵ خوداظهاری اخیر</p>
                </div>
            </div>
            <div class="card-footer">
                <a href="view_analysis.php" class="btn btn-sm btn-success">مشاهده تحلیل کامل</a>
            </div>
        </div>

    </div>
</div>

<?php
mysqli_close($link);
require_once "../includes/footer.php";
?>
