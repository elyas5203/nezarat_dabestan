<?php
session_start();
require_once "../includes/db.php";
require_once "../includes/functions.php";
require_once "../includes/jdf.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !$_SESSION["is_admin"]) {
    header("location: ../user/index.php");
    exit;
}

// Pagination setup
$limit = 25;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page > 1) ? ($page * $limit) - $limit : 0;

// Base Query
$base_query = "
    (SELECT 'ticket' as type, t.id, t.title as activity_title, t.created_at, u.username
     FROM tickets t JOIN users u ON t.user_id = u.id)
    UNION
    (SELECT 'assessment' as type, sa.id, f.form_name as activity_title, sa.submitted_at as created_at, u.username
     FROM form_submissions sa
     JOIN forms f ON sa.form_id = f.id
     JOIN users u ON sa.user_id = u.id)
    UNION
    (SELECT 'login' as type, u.id, 'ورود به سیستم' as activity_title, u.last_login_at as created_at, u.username
     FROM users u
     WHERE u.last_login_at IS NOT NULL)
";

// Get total records for pagination
$total_result = mysqli_query($link, "SELECT COUNT(*) as count FROM ($base_query) as activities");
$total_rows = mysqli_fetch_assoc($total_result)['count'];
$total_pages = ceil($total_rows / $limit);

// Get records for the current page
$paged_query = "$base_query ORDER BY created_at DESC LIMIT $start, $limit";
$activity_result = mysqli_query($link, $paged_query);
$activities = mysqli_fetch_all($activity_result, MYSQLI_ASSOC);

require_once "../includes/header.php";
?>
<style>
    .activity-log .meta { font-size: 0.8rem; color: #6c757d; }
    .activity-log .activity-item a { text-decoration: none; color: var(--text-color); }
    .activity-log .activity-item a:hover { color: var(--primary-color); }
</style>

<div class="page-content">
    <h2>گزارش کامل فعالیت‌های سیستم</h2>
    <p>در این صفحه می‌توانید تمام فعالیت‌های ثبت‌شده در سیستم را مشاهده کنید.</p>

    <div class="card">
        <div class="card-body activity-log">
            <?php if(empty($activities)): ?>
                <p>فعالیتی برای نمایش وجود ندارد.</p>
            <?php else: ?>
                <ul class="list-group list-group-flush">
                <?php foreach($activities as $activity):
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
                        <span class="meta"><?php echo to_persian_date($activity['created_at']); ?></span>
                    </li>
                <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>

    <!-- Pagination -->
    <nav class="mt-4">
        <ul class="pagination justify-content-center">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?php if ($page == $i) echo 'active'; ?>">
                    <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
</div>

<?php
mysqli_close($link);
require_once "../includes/footer.php";
?>
