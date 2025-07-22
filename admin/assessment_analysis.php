<?php
session_start();
require_once "../includes/db_singleton.php";
require_once "../includes/functions.php";
require_once "../includes/jdf.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !$_SESSION['is_admin']) {
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
    ORDER BY sa.meeting_date DESC
";
$submissions_result = mysqli_query($link, $main_query);

// Analytics Data
$total_score = 0;
$submission_count = mysqli_num_rows($submissions_result);
$scores_by_date = [];
$scores_by_class = [];

mysqli_data_seek($submissions_result, 0);
while($row = mysqli_fetch_assoc($submissions_result)) {
    $total_score += $row['score'];
    $date = date('Y-m-d', strtotime($row['meeting_date']));
    if (!isset($scores_by_date[$date])) $scores_by_date[$date] = [];
    $scores_by_date[$date][] = $row['score'];

    if (!isset($scores_by_class[$row['class_name']])) $scores_by_class[$row['class_name']] = ['total' => 0, 'count' => 0];
    $scores_by_class[$row['class_name']]['total'] += $row['score'];
    $scores_by_class[$row['class_name']]['count']++;
}

// Prepare chart data
$chart_labels_date = array_keys($scores_by_date);
$chart_data_date = array_map(function($scores) { return array_sum($scores) / count($scores); }, array_values($scores_by_date));

$chart_labels_class = array_keys($scores_by_class);
$chart_data_class = array_map(function($data) { return $data['total'] / $data['count']; }, array_values($scores_by_class));


require_once "../includes/header.php";
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="page-content">
    <h2>تحلیل جامع خوداظهاری‌ها</h2>

    <div class="card mb-4">
        <div class="card-header">فیلترها</div>
        <div class="card-body">
            <form method="get" class="row g-3">
                <div class="col-md-3">
                    <label for="class_id" class="form-label">کلاس</label>
                    <select name="class_id" id="class_id" class="form-select">
                        <option value="">همه</option>
                        <?php while($class = mysqli_fetch_assoc($classes)): ?>
                            <option value="<?php echo $class['id']; ?>" <?php if(isset($_GET['class_id']) && $_GET['class_id'] == $class['id']) echo 'selected';?>><?php echo htmlspecialchars($class['class_name']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="teacher_id" class="form-label">مدرس</label>
                    <select name="teacher_id" id="teacher_id" class="form-select">
                        <option value="">همه</option>
                        <?php while($teacher = mysqli_fetch_assoc($teachers)): ?>
                            <option value="<?php echo $teacher['id']; ?>" <?php if(isset($_GET['teacher_id']) && $_GET['teacher_id'] == $teacher['id']) echo 'selected';?>><?php echo htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="date_from" class="form-label">از تاریخ</label>
                    <input type="date" name="date_from" id="date_from" class="form-control" value="<?php echo $_GET['date_from'] ?? ''; ?>">
                </div>
                <div class="col-md-3">
                    <label for="date_to" class="form-label">تا تاریخ</label>
                    <input type="date" name="date_to" id="date_to" class="form-control" value="<?php echo $_GET['date_to'] ?? ''; ?>">
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">اعمال فیلتر</button>
                </div>
            </form>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header">روند امتیازات در طول زمان</div>
                <div class="card-body">
                    <canvas id="scoreTrendChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
             <div class="card mb-4">
                <div class="card-header">میانگین امتیاز کلاس‌ها</div>
                <div class="card-body">
                    <canvas id="classScoreChart"></canvas>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Score Trend Chart
    new Chart(document.getElementById('scoreTrendChart'), {
        type: 'line',
        data: {
            labels: <?php echo json_encode($chart_labels_date); ?>,
            datasets: [{
                label: 'میانگین امتیاز روزانه',
                data: <?php echo json_encode($chart_data_date); ?>,
                borderColor: 'rgba(106, 90, 249, 1)',
                backgroundColor: 'rgba(106, 90, 249, 0.1)',
                fill: true,
                tension: 0.1
            }]
        }
    });

    // Class Score Chart
    new Chart(document.getElementById('classScoreChart'), {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($chart_labels_class); ?>,
            datasets: [{
                label: 'میانگین امتیاز',
                data: <?php echo json_encode($chart_data_class); ?>,
                backgroundColor: 'rgba(22, 197, 94, 0.7)'
            }]
        },
        options: { indexAxis: 'y' }
    });
});
</script>

<?php
require_once "../includes/footer.php";
?>
