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
    'inventory_items' => mysqli_fetch_assoc(mysqli_query($link, "SELECT SUM(quantity) as count FROM inventory_items"))['count'] ?? 0,
];

// Data for Class Performance Chart
$class_performance_data = [];
$sql_class_perf = "SELECT c.class_name, AVG(sa.score) as avg_score
                   FROM self_assessments sa
                   JOIN classes c ON sa.class_id = c.id
                   GROUP BY sa.class_id
                   ORDER BY avg_score DESC";
$class_perf_result = mysqli_query($link, $sql_class_perf);
while($row = mysqli_fetch_assoc($class_perf_result)){
    $class_performance_data['labels'][] = $row['class_name'];
    $class_performance_data['scores'][] = round($row['avg_score'], 2);
}

// Data for Financial Summary Chart
$financial_chart_data = ['labels' => [], 'debits' => [], 'credits' => []];
$sql_financial = "SELECT
                    SUM(CASE WHEN transaction_type = 'debit' THEN amount ELSE 0 END) as total_debit,
                    SUM(CASE WHEN transaction_type = 'credit' THEN amount ELSE 0 END) as total_credit
                  FROM booklet_transactions";
$financial_result = mysqli_fetch_assoc(mysqli_query($link, $sql_financial));
$financial_chart_data['labels'] = ['کل بدهی', 'کل پرداختی'];
$financial_chart_data['data'] = [$financial_result['total_debit'] ?? 0, $financial_result['total_credit'] ?? 0];


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
            <div class="icon" style="color: #6610f2; background-color: #e0cffc;"><i data-feather="users"></i></div>
            <div class="info"><div class="number"><?php echo $stats['users']; ?></div><div class="label">تعداد کل کاربران</div></div>
        </div>
        <div class="stat-card">
            <div class="icon" style="color: #007bff; background-color: #cce5ff;"><i data-feather="book-open"></i></div>
            <div class="info"><div class="number"><?php echo $stats['classes']; ?></div><div class="label">کلاس‌های فعال</div></div>
        </div>
        <div class="stat-card">
            <div class="icon" style="color: #ffc107; background-color: #fff3cd;"><i data-feather="message-square"></i></div>
            <div class="info"><div class="number"><?php echo $stats['open_tickets']; ?></div><div class="label">تیکت‌های باز</div></div>
        </div>
        <div class="stat-card">
            <div class="icon" style="color: #198754; background-color: #d1e7dd;"><i data-feather="archive"></i></div>
            <div class="info"><div class="number"><?php echo $stats['inventory_items']; ?></div><div class="label">اقلام در انبار</div></div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-md-6">
            <div class="chart-container">
                <h3>عملکرد کلاس‌ها (میانگین امتیاز خوداظهاری)</h3>
                <canvas id="classPerformanceChart"></canvas>
            </div>
        </div>
        <div class="col-md-6">
            <div class="chart-container">
                <h3>خلاصه وضعیت مالی</h3>
                <canvas id="financialSummaryChart"></canvas>
            </div>
        </div>
    </div>

    <div class="activity-log mt-4">
        <h3>آخرین رویدادها</h3>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>کاربر</th>
                    <th>رویداد</th>
                    <th>جزئیات</th>
                    <th>زمان</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $sql_events = "SELECT e.*, u.username
                               FROM events e
                               LEFT JOIN users u ON e.user_id = u.id
                               ORDER BY e.created_at DESC
                               LIMIT 5";
                $events_result = mysqli_query($link, $sql_events);
                if ($events_result && mysqli_num_rows($events_result) > 0):
                    while ($event = mysqli_fetch_assoc($events_result)):
                ?>
                        <tr>
                            <td><?php echo htmlspecialchars($event['username'] ?? 'سیستم'); ?></td>
                            <td><?php echo htmlspecialchars($event['action']); ?></td>
                            <td><?php echo htmlspecialchars($event['details']); ?></td>
                            <td><?php echo time_ago($event['created_at']); ?></td>
                        </tr>
                <?php
                    endwhile;
                else:
                ?>
                    <tr>
                        <td colspan="4" class="text-center">هیچ رویدادی برای نمایش وجود ندارد.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
require_once "../includes/footer.php";
?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Class Performance Chart
    const classPerfCtx = document.getElementById('classPerformanceChart').getContext('2d');
    new Chart(classPerfCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($class_performance_data['labels'] ?? []); ?>,
            datasets: [{
                label: 'میانگین امتیاز',
                data: <?php echo json_encode($class_performance_data['scores'] ?? []); ?>,
                backgroundColor: 'rgba(54, 162, 235, 0.6)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }]
        },
        options: { responsive: true, scales: { y: { beginAtZero: true } } }
    });

    // Financial Summary Chart
    const financialCtx = document.getElementById('financialSummaryChart').getContext('2d');
    new Chart(financialCtx, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode($financial_chart_data['labels'] ?? []); ?>,
            datasets: [{
                data: <?php echo json_encode($financial_chart_data['data'] ?? []); ?>,
                backgroundColor: ['rgba(255, 99, 132, 0.7)', 'rgba(75, 192, 192, 0.7)'],
            }]
        },
        options: { responsive: true, legend: { position: 'top' } }
    });
});
</script>
