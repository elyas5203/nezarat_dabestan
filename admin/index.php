<?php
session_start();
require_once "../includes/db.php";
require_once "../includes/functions.php";
require_once "../includes/jdf.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !$_SESSION["is_admin"]) {
    header("location: ../user/index.php");
    exit;
}

// --- Data Fetching for Admin Widgets ---
$stats_query = "
    SELECT
        (SELECT COUNT(id) FROM users) as total_users,
        (SELECT COUNT(id) FROM classes WHERE status = 'active') as active_classes,
        (SELECT COUNT(id) FROM tickets WHERE status != 'closed') as open_tickets,
        (SELECT COUNT(id) FROM tasks WHERE status = 'pending') as pending_tasks
";
$stats = mysqli_fetch_assoc(mysqli_query($link, $stats_query));

// --- Data for Weekly Activity Chart ---
$chart_data = ['labels' => [], 'tickets' => [], 'submissions' => []];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $chart_data['labels'][] = jdf('l', strtotime($date)); // Day of the week

    $sql_tickets = "SELECT COUNT(id) as count FROM tickets WHERE DATE(created_at) = '$date'";
    $chart_data['tickets'][] = mysqli_fetch_assoc(mysqli_query($link, $sql_tickets))['count'];

    $sql_submissions = "SELECT COUNT(id) as count FROM form_submissions WHERE DATE(submitted_at) = '$date'";
    $chart_data['submissions'][] = mysqli_fetch_assoc(mysqli_query($link, $sql_submissions))['count'];
}

// --- Fetch Recent Activities ---
$recent_activities_query = "
    (SELECT 'ticket' as type, t.id, t.title as title, t.created_at, u.username FROM tickets t JOIN users u ON t.user_id = u.id)
    UNION ALL
    (SELECT 'submission' as type, fs.id, f.form_name as title, fs.submitted_at as created_at, u.username FROM form_submissions fs JOIN users u ON fs.user_id = u.id JOIN forms f ON fs.form_id = f.id)
    UNION ALL
    (SELECT 'login' as type, u.id, 'ورود به سیستم' as title, u.last_login_at as created_at, u.username FROM users u WHERE u.last_login_at IS NOT NULL)
    ORDER BY created_at DESC
    LIMIT 5
";
$activities_result = mysqli_query($link, $recent_activities_query);


require_once "../includes/header.php";
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
    .page-content { background-color: #f4f7fc; }
    .stat-card {
        background: #fff;
        border: none;
        border-radius: 12px;
        box-shadow: 0 6px 20px rgba(0,0,0,0.07);
        padding: 25px;
        display: flex;
        align-items: center;
        gap: 20px;
        transition: transform 0.2s;
    }
    .stat-card:hover { transform: translateY(-5px); }
    .stat-card .icon {
        font-size: 2rem;
        padding: 20px;
        border-radius: 50%;
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .stat-card .info .number { font-size: 2.2rem; font-weight: 700; }
    .stat-card .info .label { color: #6c757d; font-size: 0.9rem; }
    .chart-container { background: #fff; padding: 25px; border-radius: 12px; box-shadow: 0 6px 20px rgba(0,0,0,0.07); }
    .quick-access .btn {
        border-radius: 8px;
        padding: 10px 20px;
        font-weight: 500;
        transition: all 0.3s;
    }
    .activity-log .list-group-item { border-right: 3px solid transparent; transition: all 0.2s; }
    .activity-log .list-group-item:hover { background-color: #f8f9fa; border-right-color: var(--primary-color); }
</style>

<div class="page-content">
    <div class="container-fluid">
        <div class="row mb-4">
            <div class="col">
                <h3>داشبورد مدیریت</h3>
                <p class="text-muted">خوش آمدید, <?php echo htmlspecialchars($_SESSION["username"]); ?>!</p>
            </div>
        </div>

        <!-- Stat Cards -->
        <div class="row">
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="stat-card">
                    <div class="icon" style="background-color: #4e73df;"><i data-feather="users"></i></div>
                    <div class="info">
                        <div class="number"><?php echo $stats['total_users']; ?></div>
                        <div class="label">تعداد کاربران</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="stat-card">
                    <div class="icon" style="background-color: #1cc88a;"><i data-feather="book-open"></i></div>
                    <div class="info">
                        <div class="number"><?php echo $stats['active_classes']; ?></div>
                        <div class="label">کلاس‌های فعال</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="stat-card">
                    <div class="icon" style="background-color: #f6c23e;"><i data-feather="inbox"></i></div>
                    <div class="info">
                        <div class="number"><?php echo $stats['open_tickets']; ?></div>
                        <div class="label">تیکت‌های باز</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="stat-card">
                    <div class="icon" style="background-color: #e74a3b;"><i data-feather="clock"></i></div>
                    <div class="info">
                        <div class="number"><?php echo $stats['pending_tasks']; ?></div>
                        <div class="label">وظایف در انتظار</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts and Activities -->
        <div class="row">
            <!-- Weekly Activity Chart -->
            <div class="col-lg-8 mb-4">
                <div class="chart-container">
                    <h5 class="mb-3">فعالیت‌های هفته اخیر</h5>
                    <canvas id="weeklyActivityChart"></canvas>
                </div>
            </div>

            <!-- Recent Activities -->
            <div class="col-lg-4 mb-4">
                <div class="chart-container activity-log">
                    <h5 class="mb-3">آخرین رویدادها</h5>
                    <ul class="list-group list-group-flush">
                        <?php while($activity = mysqli_fetch_assoc($activities_result)):
                            $icon = 'alert-circle'; $link = '#'; $title = $activity['title'];
                            switch($activity['type']) {
                                case 'ticket': $icon = 'message-square'; $link = "../user/view_ticket.php?id={$activity['id']}"; break;
                                case 'submission': $icon = 'file-text'; $link = "view_submission_details.php?id={$activity['id']}"; break;
                                case 'login': $icon = 'log-in'; $link = "edit_user.php?id={$activity['id']}"; break;
                            }
                        ?>
                        <a href="<?php echo $link; ?>" class="list-group-item list-group-item-action">
                            <div class="d-flex w-100 justify-content-start align-items-center">
                                <i data-feather="<?php echo $icon; ?>" class="me-3"></i>
                                <div>
                                    <h6 class="mb-1"><?php echo htmlspecialchars($title); ?></h6>
                                    <small class="text-muted"><?php echo htmlspecialchars($activity['username']) . ' - ' . time_ago($activity['created_at']); ?></small>
                                </div>
                            </div>
                        </a>
                        <?php endwhile; ?>
                    </ul>
                     <a href="view_all_activities.php" class="btn btn-outline-primary btn-sm mt-3">مشاهده همه</a>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('weeklyActivityChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($chart_data['labels']); ?>,
            datasets: [{
                label: 'تیکت‌ها',
                data: <?php echo json_encode($chart_data['tickets']); ?>,
                backgroundColor: 'rgba(78, 115, 223, 0.7)',
                borderColor: 'rgba(78, 115, 223, 1)',
                borderWidth: 1
            }, {
                label: 'فرم‌های ارسالی',
                data: <?php echo json_encode($chart_data['submissions']); ?>,
                backgroundColor: 'rgba(28, 200, 138, 0.7)',
                borderColor: 'rgba(28, 200, 138, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1 } }
            },
            plugins: {
                legend: { position: 'top' }
            }
        }
    });
});
</script>

<?php
require_once "../includes/footer.php";
?>
