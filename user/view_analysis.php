<?php
session_start();
require_once "../includes/db.php";
require_once "../includes/functions.php";
require_once "../includes/jdf.php";

// Check if user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../index.php");
    exit;
}

$user_id = $_SESSION['id'];

// --- Filter Handling ---
$class_filter_sql = "";
$compare_class_filter_sql = "";
$date_filter_sql = "";

// Get user's classes for the filter dropdown
$user_classes_result = mysqli_query($link, "SELECT c.id, c.class_name FROM classes c JOIN class_teachers ct ON c.id = ct.class_id WHERE ct.teacher_id = $user_id ORDER BY c.class_name");
$user_classes = mysqli_fetch_all($user_classes_result, MYSQLI_ASSOC);

$selected_class_id = 0;
if (!empty($_GET['class_id'])) {
    $selected_class_id = intval($_GET['class_id']);
    // Security check: ensure the user is a teacher of the selected class
    $is_teacher = false;
    foreach($user_classes as $class) {
        if ($class['id'] == $selected_class_id) {
            $is_teacher = true;
            break;
        }
    }
    if ($is_teacher) {
        $class_filter_sql = " AND fsub.class_id = $selected_class_id";
    } else {
        // If not a teacher, redirect or show an error
        die("شما به این کلاس دسترسی ندارید.");
    }
} elseif (!empty($user_classes)) {
    // Default to the first class if none selected
    $selected_class_id = $user_classes[0]['id'];
    $class_filter_sql = " AND fsub.class_id = $selected_class_id";
}


if (!empty($_GET['date_from'])) {
    $date_filter_sql .= " AND fsub.submitted_at >= '" . mysqli_real_escape_string($link, $_GET['date_from']) . "'";
}
if (!empty($_GET['date_to'])) {
    $date_filter_sql .= " AND fsub.submitted_at <= '" . mysqli_real_escape_string($link, $_GET['date_to']) . " 23:59:59'";
}

// --- Main Data Query ---
$query = "
    SELECT
        fsub.submitted_at,
        fsd.field_label,
        fsd.field_value
    FROM form_submission_data fsd
    JOIN form_submissions fsub ON fsd.submission_id = fsub.id
    JOIN forms f ON fsub.form_id = f.id
    WHERE f.form_name LIKE '%خوداظهاری%'
    AND fsub.user_id = $user_id
    $class_filter_sql
    $date_filter_sql
    ORDER BY fsub.submitted_at ASC
";

$result = mysqli_query($link, $query);

// --- Data Processing for Charts ---
$submissions = [];
while ($row = mysqli_fetch_assoc($result)) {
    $date = date('Y-m-d', strtotime($row['submitted_at']));
    $submissions[$date][$row['field_label']] = $row['field_value'];
}

// Example: Trend chart for "زمان جزوه"
$booklet_time_trend = [];
$booklet_time_map = [
    'کمتر از 15 دقیقه' => 10,
    'بین 15 تا 30 دقیقه' => 22,
    'بیش از 30 دقیقه' => 35,
    'عدم اجرا' => 0
];

// Example: Score trend
$score_trend = [];

foreach ($submissions as $date => $data) {
    // Booklet time trend
    $booklet_time_val = $data['زمان جزوه'] ?? 'عدم اجرا';
    $booklet_time_trend[$date] = $booklet_time_map[$booklet_time_val] ?? 0;

    // Score trend simulation
    $score = 0;
    if(($data['مدرسین قبل از جلسه هماهنگی داشته اند؟'] ?? '') === 'بله') $score += 2;
    if(($data['مدرسین قبل از جلسه توسل داشته اند'] ?? '') === 'بله') $score += 2;
    if(($data['با غائبین بدون اطلاع تماس گرفته شده'] ?? '') === 'بله') $score += 1;
    $score_trend[$date] = $score;
}

$trend_labels = json_encode(array_map(function($d) { return jdf('Y/m/d', strtotime($d)); }, array_keys($score_trend)));
$score_trend_data = json_encode(array_values($score_trend));
$booklet_time_data = json_encode(array_values($booklet_time_trend));


require_once "../includes/header.php";
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
    .chart-container { padding: 20px; background: #fff; border-radius: 8px; box-shadow: var(--shadow-md); margin-bottom: 20px; }
</style>

<div class="page-content">
    <h2>تحلیل عملکرد کلاس‌ها</h2>
    <p>در این بخش می‌توانید روند عملکرد خود را در کلاس‌های مختلف مشاهده و مقایسه کنید.</p>

    <!-- Filter Form -->
    <div class="card mb-4">
        <div class="card-header">انتخاب کلاس و بازه زمانی</div>
        <div class="card-body">
            <form method="get" class="row g-3">
                <div class="col-md-4">
                    <label for="class_id">نمایش تحلیل برای کلاس:</label>
                    <select name="class_id" id="class_id" class="form-select" onchange="this.form.submit()">
                        <?php foreach($user_classes as $class): ?>
                            <option value="<?php echo $class['id']; ?>" <?php if($selected_class_id == $class['id']) echo 'selected';?>>
                                <?php echo htmlspecialchars($class['class_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                 <div class="col-md-3">
                    <label for="date_from">از تاریخ</label>
                    <input type="date" name="date_from" id="date_from" class="form-control" value="<?php echo $_GET['date_from'] ?? ''; ?>">
                </div>
                <div class="col-md-3">
                    <label for="date_to">تا تاریخ</label>
                    <input type="date" name="date_to" id="date_to" class="form-control" value="<?php echo $_GET['date_to'] ?? ''; ?>">
                </div>
                <div class="col-md-2 align-self-end">
                    <button type="submit" class="btn btn-primary">نمایش</button>
                </div>
            </form>
        </div>
    </div>

    <?php if (empty($submissions)): ?>
        <div class="alert alert-warning">هیچ فرم خوداظهاری برای کلاس و بازه زمانی انتخاب شده یافت نشد.</div>
    <?php else: ?>
    <!-- Charts -->
    <div class="row">
        <div class="col-lg-12">
            <div class="chart-container">
                <h3>روند امتیاز و زمان مطالعه جزوه</h3>
                <canvas id="scoreTrendChart"></canvas>
            </div>
        </div>
    </div>
    <?php endif; ?>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    <?php if (!empty($submissions)): ?>
    new Chart(document.getElementById('scoreTrendChart'), {
        type: 'line',
        data: {
            labels: <?php echo $trend_labels; ?>,
            datasets: [
                {
                    label: 'امتیاز عملکرد (شبیه‌سازی شده)',
                    data: <?php echo $score_trend_data; ?>,
                    borderColor: 'rgba(0, 123, 255, 1)',
                    yAxisID: 'y_score',
                },
                {
                    label: 'زمان مطالعه جزوه (دقیقه)',
                    data: <?php echo $booklet_time_data; ?>,
                    borderColor: 'rgba(255, 193, 7, 1)',
                    yAxisID: 'y_time',
                }
            ]
        },
        options: {
            responsive: true,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            scales: {
                y_score: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    title: { display: true, text: 'امتیاز' }
                },
                y_time: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    title: { display: true, text: 'دقیقه' },
                    grid: { drawOnChartArea: false } // only want the grid lines for one axis to show up
                }
            }
        }
    });
    <?php endif; ?>
});
</script>

<?php
mysqli_close($link);
require_once "../includes/footer.php";
?>
