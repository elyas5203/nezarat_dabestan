<?php
session_start();
require_once "../includes/db_singleton.php";
require_once "../includes/functions.php";
require_once "../includes/jdf.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../index.php");
    exit;
}

// Check if user has permission to view their own analysis.
// This could be a specific permission or a default for all teachers.
// For now, we'll assume all logged-in users can see their own analysis.

$link = get_db_connection();
$user_id = $_SESSION["id"];

// Filters
$class_filter = isset($_GET['class_id']) && !empty($_GET['class_id']) ? "AND sa.class_id = " . intval($_GET['class_id']) : "";
$date_from_filter = isset($_GET['date_from']) && !empty($_GET['date_from']) ? "AND sa.meeting_date >= '" . mysqli_real_escape_string($link, $_GET['date_from']) . "'" : "";
$date_to_filter = isset($_GET['date_to']) && !empty($_GET['date_to']) ? "AND sa.meeting_date <= '" . mysqli_real_escape_string($link, $_GET['date_to']) . "'" : "";

// Fetch data for filters
$classes_query = "SELECT c.id, c.class_name FROM classes c JOIN class_teachers ct ON c.id = ct.class_id WHERE ct.teacher_id = $user_id ORDER BY c.class_name";
$classes = mysqli_query($link, $classes_query);


// Main Query - Only for the logged-in user
$main_query = "
    SELECT
        sa.id, sa.score, sa.meeting_date,
        c.class_name
    FROM self_assessments sa
    JOIN classes c ON sa.class_id = c.id
    WHERE sa.user_id = $user_id $class_filter $date_from_filter $date_to_filter
    ORDER BY sa.meeting_date ASC
";
$submissions_result = mysqli_query($link, $main_query);

// Analytics Data
$scores_by_date = [];
while($row = mysqli_fetch_assoc($submissions_result)) {
    $date = date('Y-m-d', strtotime($row['meeting_date']));
    if (!isset($scores_by_date[$date])) {
        $scores_by_date[$date] = [];
    }
    $scores_by_date[$date][] = $row['score'];
}

// --- Trend Analysis Functions (copied from admin file) ---
function calculate_moving_average(array $data, int $window): array {
    $result = [];
    $count = count($data);
    for ($i = 0; $i < $count; $i++) {
        $slice = array_slice($data, max(0, $i - $window + 1), min($window, $i + 1));
        $result[] = array_sum($slice) / count($slice);
    }
    return $result;
}

function calculate_linear_regression(array $x, array $y): array {
    $n = count($x);
    if ($n < 2) return ['slope' => 0, 'intercept' => 0, 'y_start' => 0, 'y_end' => 0];

    $sum_x = array_sum($x);
    $sum_y = array_sum($y);
    $sum_xy = 0;
    $sum_x2 = 0;

    for ($i = 0; $i < $n; $i++) {
        $sum_xy += ($x[$i] * $y[$i]);
        $sum_x2 += ($x[$i] * $x[$i]);
    }

    $slope = ($n * $sum_xy - $sum_x * $sum_y) / ($n * $sum_x2 - $sum_x * $sum_x);
    $intercept = ($sum_y - $slope * $sum_x) / $n;

    $y_start = $slope * $x[0] + $intercept;
    $y_end = $slope * end($x) + $intercept;

    return ['slope' => $slope, 'intercept' => $intercept, 'y_start' => $y_start, 'y_end' => $y_end];
}


// Prepare chart data
$chart_labels_date = array_keys($scores_by_date);
$chart_data_date_raw = array_map(function($scores) { return array_sum($scores) / count($scores); }, array_values($scores_by_date));

$numeric_indices = range(0, count($chart_data_date_raw) - 1);
$moving_average_data = !empty($chart_data_date_raw) ? calculate_moving_average($chart_data_date_raw, 5) : [];
$regression_data = count($numeric_indices) > 1 ? calculate_linear_regression($numeric_indices, $chart_data_date_raw) : ['slope' => 0, 'y_start' => 0, 'y_end' => 0];

$trend_description = "نامشخص";
if (count($numeric_indices) > 1) {
    if ($regression_data['slope'] > 0.1) {
        $trend_description = "<span class='text-success'>روند صعودی</span>";
    } elseif ($regression_data['slope'] < -0.1) {
        $trend_description = "<span class='text-danger'>روند نزولی</span>";
    } else {
        $trend_description = "<span class='text-warning'>روند ثابت</span>";
    }
}


require_once "../includes/header.php";
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns/dist/chartjs-adapter-date-fns.bundle.min.js"></script>

<div class="page-content">
    <h2>تحلیل عملکرد شما</h2>

    <div class="card mb-4">
        <div class="card-header">فیلترها</div>
        <div class="card-body">
            <form method="get" class="row g-3">
                <div class="col-md-4">
                    <label for="class_id" class="form-label">کلاس</label>
                    <select name="class_id" id="class_id" class="form-select">
                        <option value="">همه کلاس‌های من</option>
                        <?php mysqli_data_seek($classes, 0); while($class = mysqli_fetch_assoc($classes)): ?>
                            <option value="<?php echo $class['id']; ?>" <?php if(isset($_GET['class_id']) && $_GET['class_id'] == $class['id']) echo 'selected';?>><?php echo htmlspecialchars($class['class_name']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="date_from" class="form-label">از تاریخ</label>
                    <input type="text" name="date_from" id="date_from" class="form-control" value="<?php echo $_GET['date_from'] ?? ''; ?>" placeholder="YYYY-MM-DD">
                </div>
                <div class="col-md-3">
                    <label for="date_to" class="form-label">تا تاریخ</label>
                    <input type="text" name="date_to" id="date_to" class="form-control" value="<?php echo $_GET['date_to'] ?? ''; ?>" placeholder="YYYY-MM-DD">
                </div>
                <div class="col-md-2 align-self-end">
                    <button type="submit" class="btn btn-primary w-100">اعمال فیلتر</button>
                </div>
            </form>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-9">
            <div class="card">
                <div class="card-header">نمودار روند امتیازات شما</div>
                <div class="card-body">
                    <?php if (count($chart_labels_date) > 1): ?>
                        <canvas id="userScoreTrendChart"></canvas>
                    <?php else: ?>
                        <p class="text-center">داده کافی برای نمایش نمودار وجود ندارد. لطفاً حداقل دو خوداظهاری ثبت کنید.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-lg-3">
            <div class="card">
                <div class="card-header">تحلیل روند شما</div>
                <div class="card-body">
                     <?php if (count($chart_labels_date) > 1): ?>
                        <p><strong>وضعیت کلی:</strong> <?php echo $trend_description; ?></p>
                        <p><strong>شیب خط روند:</strong> <?php echo number_format($regression_data['slope'], 3); ?></p>
                        <small class="text-muted">این تحلیل بر اساس عملکرد شما در بازه زمانی و کلاس انتخاب شده است.</small>
                     <?php else: ?>
                        <p class="text-center">داده کافی برای تحلیل وجود ندارد.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

</div>

<?php if (count($chart_labels_date) > 1): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const ctx = document.getElementById('userScoreTrendChart').getContext('2d');

    const rawData = <?php echo json_encode(array_values($chart_data_date_raw)); ?>;
    const labels = <?php echo json_encode($chart_labels_date); ?>;
    const movingAverageData = <?php echo json_encode($moving_average_data); ?>;
    const regressionData = <?php echo json_encode($regression_data); ?>;

    const regressionLineData = [
        { x: labels[0], y: regressionData.y_start },
        { x: labels[labels.length - 1], y: regressionData.y_end }
    ];

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'میانگین امتیاز شما',
                    data: rawData,
                    borderColor: 'rgba(33, 150, 243, 0.7)',
                    backgroundColor: 'rgba(33, 150, 243, 0.1)',
                    fill: true,
                    tension: 0.4
                },
                {
                    label: 'میانگین متحرک (5 جلسه)',
                    data: movingAverageData,
                    borderColor: 'rgba(76, 175, 80, 1)',
                    backgroundColor: 'transparent',
                    borderWidth: 2,
                    tension: 0.3
                },
                {
                    label: 'خط روند',
                    data: regressionLineData,
                    borderColor: 'rgba(255, 87, 34, 1)',
                    borderWidth: 2,
                    borderDash: [5, 5],
                    fill: false,
                    type: 'line',
                    tension: 0
                }
            ]
        },
        options: {
            scales: {
                x: {
                    type: 'time',
                    time: {
                        unit: 'day',
                        tooltipFormat: 'yyyy-MM-dd'
                    },
                    title: {
                        display: true,
                        text: 'تاریخ'
                    }
                },
                y: {
                    title: {
                        display: true,
                        text: 'میانگین امتیاز'
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        title: function(context) {
                            return new Date(context[0].parsed.x).toLocaleDateString('fa-IR');
                        }
                    }
                }
            }
        }
    });
});
</script>
<?php endif; ?>

<?php
require_once "../includes/footer.php";
?>
