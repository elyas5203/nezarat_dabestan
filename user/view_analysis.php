<?php
session_start();
require_once "../includes/db.php";
require_once "../includes/functions.php";
require_once "../includes/jdf.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../index.php");
    exit;
}

$user_id = $_SESSION['id'];
$action = $_GET['action'] ?? 'select_class'; // 'select_class', 'view_summary', 'view_details'
$class_id = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;

// --- Data Fetching ---
$user_classes = [];
$class_name = '';
$submissions = [];
$score_trend = [];
$performance_score = 0;
$score_change = 0;

if ($action === 'select_class') {
    $user_classes_result = mysqli_query($link, "SELECT c.id, c.class_name FROM classes c JOIN class_teachers ct ON c.id = ct.class_id WHERE ct.teacher_id = $user_id ORDER BY c.class_name");
    $user_classes = mysqli_fetch_all($user_classes_result, MYSQLI_ASSOC);
}

if ($class_id > 0) {
    // Security Check: Make sure user is a teacher of this class
    $check_sql = "SELECT c.class_name FROM classes c JOIN class_teachers ct ON c.id = ct.class_id WHERE ct.teacher_id = $user_id AND c.id = $class_id";
    $check_result = mysqli_query($link, $check_sql);
    if(mysqli_num_rows($check_result) == 0) die("دسترسی غیرمجاز.");
    $class_name = mysqli_fetch_assoc($check_result)['class_name'];

    // Fetch all submissions for this class by this user
    $query = "
        SELECT fsub.id as submission_id, fsub.submitted_at, ff.field_label, fsd.field_value
        FROM form_submission_data fsd
        JOIN form_submissions fsub ON fsd.submission_id = fsub.id
        JOIN form_fields ff ON fsd.field_id = ff.id
        WHERE fsub.class_id = $class_id AND fsub.user_id = $user_id
        ORDER BY fsub.submitted_at ASC
    ";
    $result = mysqli_query($link, $query);
    $all_submissions_data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $all_submissions_data[$row['submission_id']]['date'] = $row['submitted_at'];
        $all_submissions_data[$row['submission_id']]['data'][$row['field_label']] = $row['field_value'];
    }

    // Scoring Logic
    $scores = [];
    foreach ($all_submissions_data as $sub_id => $sub) {
        $score = 0;
        if(($sub['data']['مدرسین قبل از جلسه هماهنگی داشته اند؟'] ?? '') === 'بله') $score += 2;
        if(($sub['data']['مدرسین قبل از جلسه توسل داشته اند'] ?? '') === 'بله') $score += 2;
        if(($sub['data']['با غائبین بدون اطلاع تماس گرفته شده'] ?? '') === 'بله') $score += 1;
        // Add more scoring rules here...
        $scores[date('Y-m-d', strtotime($sub['date']))] = $score;
    }
    $score_trend = $scores;

    if (count($scores) > 0) {
        $performance_score = end($scores);
        if (count($scores) > 1) {
            $prev_score = prev($scores);
            $score_change = $performance_score - $prev_score;
        }
    }
}


require_once "../includes/header.php";
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="page-content">

    <?php if ($action === 'select_class'): ?>
    <div class="container">
        <div class="text-center" style="max-width: 500px; margin: 50px auto;">
            <h2>تحلیل عملکرد کلاس‌ها</h2>
            <p class="text-muted">لطفا کلاسی را که می‌خواهید تحلیل آن را مشاهده کنید، انتخاب نمایید.</p>
            <form method="get">
                <input type="hidden" name="action" value="view_summary">
                <div class="form-group">
                    <select name="class_id" class="form-select form-select-lg">
                        <?php foreach($user_classes as $class): ?>
                            <option value="<?php echo $class['id']; ?>"><?php echo htmlspecialchars($class['class_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary btn-lg mt-3">مشاهده تحلیل</button>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($action === 'view_summary' && $class_id > 0): ?>
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>تحلیل تجمیعی کلاس: <?php echo htmlspecialchars($class_name); ?></h2>
            <div>
                <a href="?action=view_details&class_id=<?php echo $class_id; ?>" class="btn btn-outline-secondary">مشاهده ریز جزئیات فرم‌ها</a>
                <a href="?action=select_class" class="btn btn-light">تغییر کلاس</a>
            </div>
        </div>

        <!-- Performance Score -->
        <div class="row">
            <div class="col-lg-4 mb-4">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <h5 class="card-title">آخرین نمره عملکرد</h5>
                        <p class="display-4 fw-bold"><?php echo $performance_score; ?></p>
                        <?php if ($score_change > 0): ?>
                            <p class="text-success"><i data-feather="arrow-up"></i> <?php echo $score_change; ?> امتیاز بهتر از جلسه قبل</p>
                        <?php elseif ($score_change < 0): ?>
                            <p class="text-danger"><i data-feather="arrow-down"></i> <?php echo abs($score_change); ?> امتیاز کمتر از جلسه قبل</p>
                        <?php else: ?>
                            <p class="text-muted">بدون تغییر نسبت به جلسه قبل</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-lg-8 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">روند نمره عملکرد در طول زمان</h5>
                        <canvas id="scoreTrendChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($action === 'view_details' && $class_id > 0): ?>
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>ریز جزئیات فرم‌های کلاس: <?php echo htmlspecialchars($class_name); ?></h2>
            <a href="?action=view_summary&class_id=<?php echo $class_id; ?>" class="btn btn-primary">بازگشت به تحلیل تجمیعی</a>
        </div>
        <div class="card">
            <div class="card-body">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>تاریخ ثبت</th>
                            <th>مشاهده</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_reverse($all_submissions_data) as $sub_id => $sub): ?>
                        <tr>
                            <td><?php echo jdf('Y/m/d H:i', strtotime($sub['date'])); ?></td>
                            <td><a href="../admin/view_submission_details.php?id=<?php echo $sub_id; ?>" class="btn btn-sm btn-info" target="_blank">مشاهده فرم</a></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    <?php if ($action === 'view_summary' && !empty($score_trend)): ?>
    new Chart(document.getElementById('scoreTrendChart'), {
        type: 'line',
        data: {
            labels: <?php echo json_encode(array_map(function($d) { return jdf('Y/m/d', strtotime($d)); }, array_keys($score_trend))); ?>,
            datasets: [{
                label: 'نمره عملکرد',
                data: <?php echo json_encode(array_values($score_trend)); ?>,
                borderColor: 'rgba(78, 115, 223, 1)',
                backgroundColor: 'rgba(78, 115, 223, 0.1)',
                fill: true,
                tension: 0.2
            }]
        },
        options: {
            responsive: true,
            scales: { y: { beginAtZero: true } }
        }
    });
    <?php endif; ?>
});
</script>

<?php
mysqli_close($link);
require_once "../includes/footer.php";
?>
