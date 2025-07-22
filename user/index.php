<?php
session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../index.php");
    exit;
}
require_once "../includes/db_singleton.php";
require_once "../includes/functions.php";

$link = get_db_connection();
$user_id = $_SESSION['id'];
$username = $_SESSION['username'];

// Fetch user's classes
$classes = [];
$class_query = "SELECT c.id, c.class_name FROM classes c JOIN class_teachers ct ON c.id = ct.class_id WHERE ct.teacher_id = ? AND c.status = 'active'";
$stmt = mysqli_prepare($link, $class_query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($result)) {
    $classes[] = $row;
}
mysqli_stmt_close($stmt);

// Fetch recent tasks
$tasks = [];
$task_query = "SELECT t.id, t.title, t.deadline, t.status FROM tasks t JOIN task_assignments ta ON t.id = ta.task_id WHERE ta.assigned_to_user_id = ? ORDER BY t.created_at DESC LIMIT 5";
$stmt = mysqli_prepare($link, $task_query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($result)) {
    $tasks[] = $row;
}
mysqli_stmt_close($stmt);

// Fetch financial status
$financial_status_query = "SELECT
    (SELECT SUM(amount) FROM booklet_transactions WHERE user_id = ? AND transaction_type = 'debit') as total_debit,
    (SELECT SUM(amount) FROM booklet_transactions WHERE user_id = ? AND transaction_type = 'credit') as total_credit";
$stmt = mysqli_prepare($link, $financial_status_query);
mysqli_stmt_bind_param($stmt, "ii", $user_id, $user_id);
mysqli_stmt_execute($stmt);
$financial_result = mysqli_stmt_get_result($stmt);
$financial_status = mysqli_fetch_assoc($financial_result);
$balance = ($financial_status['total_credit'] ?? 0) - ($financial_status['total_debit'] ?? 0);
mysqli_stmt_close($stmt);


require_once "../includes/header.php";
?>

<style>
    .dashboard-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 25px;
    }
    .dashboard-card {
        background-color: #fff;
        border-radius: 10px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        padding: 25px;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .dashboard-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 6px 16px rgba(0,0,0,0.12);
    }
    .card-header {
        display: flex;
        align-items: center;
        gap: 15px;
        border-bottom: 1px solid #f0f0f0;
        padding-bottom: 15px;
        margin-bottom: 20px;
    }
    .card-header i {
        color: var(--primary-color);
    }
    .card-header h3 {
        margin: 0;
        font-size: 1.2rem;
    }
    .card-content ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    .card-content li {
        padding: 10px 0;
        border-bottom: 1px solid #f5f5f5;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .card-content li:last-child {
        border-bottom: none;
    }
    .card-content .status {
        padding: 4px 8px;
        border-radius: 15px;
        font-size: 0.8rem;
        font-weight: 500;
    }
    .status-completed { background-color: #d4edda; color: #155724; }
    .status-pending { background-color: #fff3cd; color: #856404; }
    .status-overdue { background-color: #f8d7da; color: #721c24; }
    .welcome-banner {
        background: linear-gradient(135deg, var(--primary-color), #5f79d6);
        color: white;
        padding: 30px;
        border-radius: 10px;
        margin-bottom: 30px;
    }
    .welcome-banner h2 { margin-top: 0; }
</style>

<div class="page-content">
    <div class="welcome-banner">
        <h2>خوش آمدید, <?php echo htmlspecialchars($username); ?>!</h2>
        <p>اینجا داشبورد شماست. می‌توانید به سرعت به بخش‌های مختلف دسترسی پیدا کنید.</p>
    </div>

    <div class="dashboard-grid">
        <!-- My Classes Card -->
        <div class="dashboard-card">
            <div class="card-header">
                <i data-feather="book-open"></i>
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
                                <a href="self_assessment_form.php?class_id=<?php echo $class['id']; ?>" class="btn btn-sm btn-outline-primary">فرم خوداظهاری</a>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>
        </div>

        <!-- My Tasks Card -->
        <div class="dashboard-card">
            <div class="card-header">
                <i data-feather="check-square"></i>
                <h3>آخرین وظایف من</h3>
            </div>
            <div class="card-content">
                <ul>
                    <?php if (empty($tasks)): ?>
                        <li>وظیفه‌ای برای شما ثبت نشده است.</li>
                    <?php else: ?>
                        <?php foreach ($tasks as $task):
                            $status_class = 'status-pending';
                            $status_text = 'در حال انجام';
                            if ($task['status'] == 'completed') {
                                $status_class = 'status-completed';
                                $status_text = 'تکمیل شده';
                            } elseif (strtotime($task['deadline']) < time()) {
                                $status_class = 'status-overdue';
                                $status_text = 'معوق';
                            }
                        ?>
                            <li>
                                <a href="view_task.php?id=<?php echo $task['id']; ?>"><?php echo htmlspecialchars($task['title']); ?></a>
                                <span class="status <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
                 <div style="margin-top: 15px; text-align: left;">
                    <a href="my_tasks.php" class="btn btn-sm btn-secondary">مشاهده همه وظایف</a>
                </div>
            </div>
        </div>

        <!-- Financial Status Card -->
        <div class="dashboard-card">
            <div class="card-header">
                <i data-feather="dollar-sign"></i>
                <h3>وضعیت مالی</h3>
            </div>
            <div class="card-content">
                <p>موجودی حساب شما: <strong><?php echo number_format($balance, 2); ?> تومان</strong></p>
                <a href="my_financial_status.php" class="btn btn-sm btn-info">مشاهده جزئیات</a>
            </div>
        </div>
    </div>
</div>

<?php
mysqli_close($link);
require_once "../includes/footer.php";
?>
