<?php
session_start();
require_once "../includes/db_singleton.php";
require_once "../includes/functions.php";
require_once "../includes/jdf.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !is_admin_or_has_permission('view_analysis')) {
    header("location: ../index.php");
    exit;
}

$link = get_db_connection();

// Filters
$class_filter = isset($_GET['class_id']) && !empty($_GET['class_id']) ? "AND sa.class_id = " . intval($_GET['class_id']) : "";
$teacher_filter = isset($_GET['teacher_id']) && !empty($_GET['teacher_id']) ? "AND sa.user_id = " . intval($_GET['teacher_id']) : "";
$date_from_filter = isset($_GET['date_from']) && !empty($_GET['date_from']) ? "AND sa.meeting_date >= '" . mysqli_real_escape_string($link, $_GET['date_from']) . "'" : "";
$date_to_filter = isset($_GET['date_to']) && !empty($_GET['date_to']) ? "AND sa.meeting_date <= '" . mysqli_real_escape_string($link, $_GET['date_to']) . "'" : "";

// Fetch data for filters
$classes = mysqli_query($link, "SELECT id, class_name FROM classes ORDER BY class_name");
$teachers = mysqli_query($link, "SELECT id, first_name, last_name FROM users WHERE id IN (SELECT DISTINCT user_id FROM self_assessments) ORDER BY last_name");

// Main Query
$main_query = "
    SELECT
        sa.id, sa.score, sa.meeting_date,
        c.class_name,
        u.first_name, u.last_name
    FROM self_assessments sa
    JOIN classes c ON sa.class_id = c.id
    JOIN users u ON sa.user_id = u.id
    WHERE 1=1 $class_filter $teacher_filter $date_from_filter $date_to_filter
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

// Prepare chart data
$chart_labels_date = array_keys($scores_by_date);
$chart_data_date_raw = array_map(function($scores) { return array_sum($scores) / count($scores); }, array_values($scores_by_date));

// --- Trend Analysis ---
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
    if ($n == 0) return ['slope' => 0, 'intercept' => 0, 'y_start' => 0, 'y_end' => 0];

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

$numeric_indices = range(0, count($chart_data_date_raw) - 1);
$moving_average_data = !empty($chart_data_date_raw) ? calculate_moving_average($chart_data_date_raw, 5) : [];
$regression_data = !empty($numeric_indices) && !empty($chart_data_date_raw) ? calculate_linear_regression($numeric_indices, $chart_data_date_raw) : ['slope' => 0, 'y_start' => 0, 'y_end' => 0];

$trend_description = "نامشخص";
if ($regression_data['slope'] > 0.1) {
    $trend_description = "<span class='text-success'>روند صعودی</span>";
} elseif ($regression_data['slope'] < -0.1) {
    $trend_description = "<span class='text-danger'>روند نزولی</span>";
} else {
    $trend_description = "<span class='text-warning'>روند ثابت</span>";
}


require_once "../includes/header.php";
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns/dist/chartjs-adapter-date-fns.bundle.min.js"></script>

<div class="page-content">
    <h2>تحلیل پیشرفته خوداظهاری‌ها</h2>

    <div class="card mb-4">
        <div class="card-header">فیلترها</div>
        <div class="card-body">
            <form method="get" class="row g-3">
                <div class="col-md-3">
                    <label for="class_id" class="form-label">کلاس</label>
                    <select name="class_id" id="class_id" class="form-select">
                        <option value="">همه</option>
                        <?php mysqli_data_seek($classes, 0); while($class = mysqli_fetch_assoc($classes)): ?>
                            <option value="<?php echo $class['id']; ?>" <?php if(isset($_GET['class_id']) && $_GET['class_id'] == $class['id']) echo 'selected';?>><?php echo htmlspecialchars($class['class_name']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="teacher_id" class="form-label">مدرس</label>
                    <select name="teacher_id" id="teacher_id" class="form-select">
                        <option value="">همه</option>
                        <?php mysqli_data_seek($teachers, 0); while($teacher = mysqli_fetch_assoc($teachers)): ?>
                            <option value="<?php echo $teacher['id']; ?>" <?php if(isset($_GET['teacher_id']) && $_GET['teacher_id'] == $teacher['id']) echo 'selected';?>><?php echo htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="date_from" class="form-label">از تاریخ</label>
                    <input type="text" name="date_from" id="date_from" class="form-control" value="<?php echo $_GET['date_from'] ?? ''; ?>" placeholder="YYYY-MM-DD">
                </div>
                <div class="col-md-2">
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
                <div class="card-header">نمودار روند امتیازات</div>
                <div class="card-body">
                    <canvas id="advancedScoreTrendChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-3">
            <div class="card">
                <div class="card-header">تحلیل روند</div>
                <div class="card-body">
                    <p><strong>وضعیت کلی:</strong> <?php echo $trend_description; ?></p>
                    <p><strong>شیب خط روند:</strong> <?php echo number_format($regression_data['slope'], 3); ?></p>
                    <small class="text-muted">این تحلیل بر اساس رگرسیون خطی روی تمام داده‌های فیلتر شده است. شیب مثبت نشان‌دهنده روند صعودی و شیب منفی نشان‌دهنده روند نزولی است.</small>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const ctx = document.getElementById('advancedScoreTrendChart').getContext('2d');

    const rawData = <?php echo json_encode(array_values($chart_data_date_raw)); ?>;
    const labels = <?php echo json_encode($chart_labels_date); ?>;
    const movingAverageData = <?php echo json_encode($moving_average_data); ?>;
    const regressionData = <?php echo json_encode($regression_data); ?>;
    const regressionLine = [regressionData.y_start, regressionData.y_end];
    const regressionLabels = [labels[0], labels[labels.length - 1]];


    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'میانگین امتیاز روزانه',
                    data: rawData,
                    borderColor: 'rgba(106, 90, 249, 0.5)',
                    backgroundColor: 'rgba(106, 90, 249, 0.05)',
                    fill: true,
                    tension: 0.4
                },
                {
                    label: 'میانگین متحرک (5 روز)',
                    data: movingAverageData,
                    borderColor: 'rgba(22, 197, 94, 1)',
                    backgroundColor: 'transparent',
                    borderWidth: 2,
                    tension: 0.3
                },
                {
                    label: 'خط روند (رگرسیون خطی)',
                    data: {
                        labels: regressionLabels,
                        datasets: [{
                            data: regressionLine,
                            borderColor: 'rgba(255, 99, 132, 1)',
                            borderWidth: 2,
                            borderDash: [5, 5],
                            fill: false
                        }]
                    },
                    type: 'line',
                    parsing: {
                        xAxisKey: 'label',
                        yAxisKey: 'value'
                    }
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
                            return new Date(context[0].label).toLocaleDateString('fa-IR');
                        }
                    }
                }
            }
        }
    });
});
</script>

<?php
require_once "../includes/footer.php";
?>
