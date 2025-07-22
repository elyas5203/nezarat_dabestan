<?php
session_start();
require_once "../includes/db_singleton.php";
$link = get_db_connection(); // Get connection
require_once "../includes/functions.php";
require_once "../includes/access_control.php";
require_once "../includes/jdf.php";


if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !$_SESSION["is_admin"]) {
    header("location: ../user/index.php");
    exit;
}

// --- Data Fetching for Admin Widgets ---
$stats = [
    'users' => mysqli_fetch_assoc(mysqli_query($link, "SELECT COUNT(id) as count FROM users"))['count'],
    'classes' => mysqli_fetch_assoc(mysqli_query($link, "SELECT COUNT(id) as count FROM classes WHERE status = 'active'"))['count'],
    'open_tickets' => mysqli_fetch_assoc(mysqli_query($link, "SELECT COUNT(id) as count FROM tickets WHERE status != 'closed'"))['count'],
    'pending_tasks' => mysqli_fetch_assoc(mysqli_query($link, "SELECT COUNT(id) as count FROM tasks WHERE status = 'pending'"))['count']
];

// Fetch last 5 activities
$recent_activities = [];
$sql_activity = "(SELECT user_id, CONCAT('تیکت جدید: ', title) as activity, created_at, 'ticket' as type, id FROM tickets ORDER BY created_at DESC LIMIT 5)
                UNION
                 (SELECT user_id, CONCAT('فرم خوداظهاری جدید برای کلاس ', c.class_name) as activity, sa.created_at, 'assessment' as type, sa.id FROM self_assessments sa JOIN classes c ON sa.class_id = c.id ORDER BY sa.created_at DESC LIMIT 5)
                 ORDER BY created_at DESC LIMIT 5";
$activity_query = mysqli_query($link, $sql_activity);
while($row = mysqli_fetch_assoc($activity_query)){
    $user_info = mysqli_fetch_assoc(mysqli_query($link, "SELECT username FROM users WHERE id = {$row['user_id']}"));
    $row['username'] = $user_info['username'] ?? 'کاربر حذف شده';
    $recent_activities[] = $row;
}


require_once "../includes/header.php";
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
?>
<style>
    .dashboard-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; }
    .stat-card { background: #fff; border-radius: 8px; padding: 20px; box-shadow: var(--shadow-md); display: flex; align-items: center; gap: 20px; }
    .stat-card .icon { font-size: 2.5rem; color: var(--primary-color); background-color: var(--primary-color-light); padding: 15px; border-radius: 50%; }
    .stat-card .info .number { font-size: 2rem; font-weight: bold; }
    .stat-card .info .label { color: #6c757d; }
    .quick-access { display: flex; gap: 10px; margin: 20px 0; flex-wrap: wrap; }
    .quick-access .btn { flex-grow: 1; }
    .activity-log { background: #fff; border-radius: 8px; padding: 20px; box-shadow: var(--shadow-md); margin-top: 20px;}
    .activity-log h3 { margin-top: 0; border-bottom: 1px solid #eee; padding-bottom: 10px;}
    .activity-item { display: flex; align-items: center; gap: 15px; padding: 10px 0; border-bottom: 1px solid #f0f0f0; }
    .activity-item:last-child { border-bottom: none; }
    .activity-icon { padding: 10px; border-radius: 50%; display: inline-flex; }
    .activity-content a { text-decoration: none; color: var(--text-color); font-weight: 500; }
    .activity-content a:hover { color: var(--primary-color); }
    .activity-item .meta { font-size: 0.85rem; color: #6c757d; }
</style>

<div class="page-content">
    <h2>داشبورد مدیریت</h2>
    <p>سلام <b><?php echo htmlspecialchars($_SESSION["username"]); ?></b>، به پنل مدیریت خوش آمدید.</p>

    <div class="dashboard-grid">
    <div class="stat-card">
        <div class="icon" style="color: #6610f2; background-color: #e0cffc;">
            <i data-feather="users"></i>
        </div>
        <div class="info">
            <div class="number"><?php echo $stats['users']; ?></div>
            <div class="label">تعداد کل کاربران</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="icon" style="color: #007bff; background-color: #cce5ff;">
            <i data-feather="book-open"></i>
        </div>
        <div class="info">
            <div class="number"><?php echo $stats['classes']; ?></div>
            <div class="label">کلاس‌های فعال</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="icon" style="color: #ffc107; background-color: #fff3cd;">
            <i data-feather="message-square"></i>
        </div>
        <div class="info">
            <div class="number"><?php echo $stats['open_tickets']; ?></div>
            <div class="label">تیکت‌های باز</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="icon" style="color: #dc3545; background-color: #f8d7da;">
            <i data-feather="clock"></i>
        </div>
        <div class="info">
            <div class="number"><?php echo $stats['pending_tasks']; ?></div>
            <div class="label">وظایف در انتظار</div>
        </div>
    </div>
</div>

    <div class="quick-access">
        <a href="manage_users.php?action=add" class="btn btn-primary"><i data-feather="plus"></i> افزودن کاربر</a>
        <a href="manage_classes.php?action=add" class="btn btn-secondary"><i data-feather="plus"></i> افزودن کلاس</a>
        <a href="manage_tasks.php?action=add" class="btn btn-info"><i data-feather="plus"></i> افزودن وظیفه</a>
    </div>

<div class="activity-log chart-container" style="margin-top: 20px;">
        <h3>آمار هفتگی</h3>
        <canvas id="weeklyActivityChart"></canvas>
    </div>

    <div class="activity-log">
    <h3>آخرین فعالیت‌ها</h3>
    <?php if(empty($recent_activities)): ?>
        <p>فعالیت جدیدی ثبت نشده است.</p>
    <?php else: ?>
        <?php foreach($recent_activities as $activity):
$activity_link = '#';
            $icon = 'alert-circle';
            if ($activity['type'] === 'ticket') {
                $activity_link = "../user/view_ticket.php?id=" . $activity['id'];
                $icon = 'message-square';
            } elseif ($activity['type'] === 'assessment') {
                $activity_link = "view_submission_details.php?id=" . $activity['id'];
                $icon = 'file-text';
            }
        ?>
            <div class="activity-item">
                <div class="activity-icon" style="background-color: var(--secondary-color-light); color: var(--secondary-color);">
                    <i data-feather="<?php echo $icon; ?>"></i>
                </div>
                <div class="activity-content">
                    <a href="<?php echo $activity_link; ?>"><?php echo htmlspecialchars($activity['activity']); ?></a>
                    <span class="meta">توسط <?php echo htmlspecialchars($activity['username']); ?> در <?php echo to_persian_date($activity['created_at']); ?></span>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
</div>

<?php
// --- Data Fetching for Chart ---
$chart_data = [
    'labels' => [],
    'tickets' => [],
    'tasks' => []
];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $jalali_date = to_persian_date($date, 'Y/m/d');
    $chart_data['labels'][] = $jalali_date;

    $sql_tickets = "SELECT COUNT(id) as count FROM tickets WHERE DATE(created_at) = '$date'";
    $chart_data['tickets'][] = mysqli_fetch_assoc(mysqli_query($link, $sql_tickets))['count'];

    $sql_tasks = "SELECT COUNT(id) as count FROM tasks WHERE DATE(created_at) = '$date'";
    $chart_data['tasks'][] = mysqli_fetch_assoc(mysqli_query($link, $sql_tasks))['count'];
}

require_once "../includes/footer.php";
?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('weeklyActivityChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($chart_data['labels']); ?>,
            datasets: [{
                label: 'تیکت‌های جدید',
                data: <?php echo json_encode($chart_data['tickets']); ?>,
                borderColor: 'rgba(255, 193, 7, 1)',
                backgroundColor: 'rgba(255, 193, 7, 0.2)',
                fill: true,
                tension: 0.4
            }, {
                label: 'وظایف جدید',
                data: <?php echo json_encode($chart_data['tasks']); ?>,
                borderColor: 'rgba(220, 53, 69, 1)',
                backgroundColor: 'rgba(220, 53, 69, 0.2)',
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
});
</script>
