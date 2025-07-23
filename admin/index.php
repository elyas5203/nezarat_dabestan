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
$stats = [
    'users' => mysqli_fetch_assoc(mysqli_query($link, "SELECT COUNT(id) as count FROM users"))['count'],
    'classes' => mysqli_fetch_assoc(mysqli_query($link, "SELECT COUNT(id) as count FROM classes WHERE status = 'active'"))['count'],
    'open_tickets' => mysqli_fetch_assoc(mysqli_query($link, "SELECT COUNT(id) as count FROM tickets WHERE status != 'closed'"))['count'],
    'pending_tasks' => mysqli_fetch_assoc(mysqli_query($link, "SELECT COUNT(id) as count FROM tasks WHERE status = 'pending'"))['count']
];

// Fetch last 5 distinct activities
// By using UNION, we can get different types of activities.
// We order by date and use a subquery to group similar activities if needed, but for now, simple UNION is fine.
$recent_activities_query = "
    (SELECT 'ticket' as type, t.id, t.title as activity_title, t.created_at, u.username
     FROM tickets t JOIN users u ON t.user_id = u.id
     ORDER BY t.created_at DESC LIMIT 10)
    UNION
    (SELECT 'assessment' as type, sa.id, f.form_name as activity_title, sa.submitted_at as created_at, u.username
     FROM form_submissions sa
     JOIN forms f ON sa.form_id = f.id
     JOIN users u ON sa.user_id = u.id
     ORDER BY sa.submitted_at DESC LIMIT 10)
    UNION
    (SELECT 'login' as type, u.id, 'ورود به سیستم' as activity_title, u.last_login_at as created_at, u.username
     FROM users u
     WHERE u.last_login_at IS NOT NULL
     ORDER BY u.last_login_at DESC LIMIT 10)
    ORDER BY created_at DESC
    LIMIT 5
";
$activity_result = mysqli_query($link, $recent_activities_query);
$recent_activities = mysqli_fetch_all($activity_result, MYSQLI_ASSOC);


require_once "../includes/header.php";
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
    .activity-log .meta { font-size: 0.8rem; color: #6c757d; }
    .activity-log .activity-item a { text-decoration: none; color: var(--text-color); }
    .activity-log .activity-item a:hover { color: var(--primary-color); }
</style>

<div class="page-content">
    <h2>داشبورد مدیریت</h2>
    <p>سلام <b><?php echo htmlspecialchars($_SESSION["username"]); ?></b>، به پنل مدیریت خوش آمدید.</p>

    <!-- Stat cards -->
    <div class="row">
        <div class="col-md-3">
            <div class="card text-white bg-primary mb-3">
                <div class="card-body">
                    <h5 class="card-title">کاربران</h5>
                    <p class="card-text fs-4"><?php echo $stats['users']; ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-info mb-3">
                <div class="card-body">
                    <h5 class="card-title">کلاس‌های فعال</h5>
                    <p class="card-text fs-4"><?php echo $stats['classes']; ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-warning mb-3">
                <div class="card-body">
                    <h5 class="card-title">تیکت‌های باز</h5>
                    <p class="card-text fs-4"><?php echo $stats['open_tickets']; ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-danger mb-3">
                <div class="card-body">
                    <h5 class="card-title">وظایف در انتظار</h5>
                    <p class="card-text fs-4"><?php echo $stats['pending_tasks']; ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Access -->
    <div class="my-4">
        <a href="create_user.php" class="btn btn-primary"><i data-feather="plus"></i> افزودن کاربر</a>
        <a href="manage_classes.php" class="btn btn-secondary"><i data-feather="plus"></i> افزودن کلاس</a>
        <a href="manage_tasks.php" class="btn btn-info text-white"><i data-feather="plus"></i> افزودن وظیفه</a>
    </div>

    <!-- Recent Activities -->
    <div class="card">
        <div class="card-header">
            <h3>آخرین فعالیت‌ها</h3>
        </div>
        <div class="card-body activity-log">
            <?php if(empty($recent_activities)): ?>
                <p>فعالیت جدیدی ثبت نشده است.</p>
            <?php else: ?>
                <ul class="list-group list-group-flush">
                <?php foreach($recent_activities as $activity):
                    $icon = 'alert-circle';
                    $link = '#';
                    $activity_text = '';

                    switch ($activity['type']) {
                        case 'ticket':
                            $icon = 'message-square';
                            $link = "../user/view_ticket.php?id=" . $activity['id'];
                            $activity_text = "تیکت جدید: " . htmlspecialchars($activity['activity_title']);
                            break;
                        case 'assessment':
                            $icon = 'file-text';
                            $link = "view_submission_details.php?id=" . $activity['id'];
                            $activity_text = "فرم ثبت شد: " . htmlspecialchars($activity['activity_title']);
                            break;
                        case 'login':
                            $icon = 'log-in';
                            $link = "edit_user.php?id=" . $activity['id'];
                            $activity_text = "ورود به سیستم";
                            break;
                    }
                ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center activity-item">
                        <div>
                            <i data-feather="<?php echo $icon; ?>" class="me-2"></i>
                            <a href="<?php echo $link; ?>"><?php echo $activity_text; ?></a>
                            <span class="meta d-block">توسط <?php echo htmlspecialchars($activity['username']); ?></span>
                        </div>
                        <span class="meta"><?php echo time_ago($activity['created_at']); ?></span>
                    </li>
                <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            <div class="mt-3">
                <a href="view_all_activities.php" class="btn btn-outline-secondary">مشاهده تمام فعالیت‌ها</a>
            </div>
        </div>
    </div>
</div>

<?php
require_once "../includes/footer.php";
?>
