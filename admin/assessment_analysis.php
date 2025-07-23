<?php
session_start();
require_once "../includes/db.php";
require_once "../includes/access_control.php";
require_once "../includes/functions.php";
require_once "../includes/jdf.php";

// Check if user is logged in and has permission
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../index.php");
    exit;
}
require_permission('view_analytics');

// --- Filter Handling ---
$class_filter_sql = "";
$teacher_filter_sql = "";
$region_filter_sql = "";
$date_filter_sql = "";

if (!empty($_GET['class_id'])) {
    $class_filter_sql = " AND sa.class_id = " . intval($_GET['class_id']);
}
if (!empty($_GET['teacher_id'])) {
    $teacher_filter_sql = " AND sa.user_id = " . intval($_GET['teacher_id']);
}
if (!empty($_GET['region_id'])) {
    $region_filter_sql = " AND c.region_id = " . intval($_GET['region_id']);
}
if (!empty($_GET['date_from'])) {
    $date_filter_sql .= " AND fsub.submitted_at >= '" . mysqli_real_escape_string($link, $_GET['date_from']) . "'";
}
if (!empty($_GET['date_to'])) {
    $date_filter_sql .= " AND fsub.submitted_at <= '" . mysqli_real_escape_string($link, $_GET['date_to']) . " 23:59:59'";
}

// --- Data Fetching for Filters ---
$all_classes = mysqli_query($link, "SELECT id, class_name FROM classes ORDER BY class_name");
$all_teachers = mysqli_query($link, "SELECT id, first_name, last_name FROM users WHERE id IN (SELECT DISTINCT teacher_id FROM class_teachers) ORDER BY last_name");
$all_regions = mysqli_query($link, "SELECT id, name FROM regions ORDER BY name");

// --- Main Data Query ---
$query = "
    SELECT
        fsub.id as submission_id,
        fsub.submitted_at,
        fsd.field_label,
        fsd.field_value,
        c.class_name,
        c.id as class_id,
        u.username as teacher_name,
        u.id as teacher_id,
        r.name as region_name,
        r.id as region_id
    FROM form_submission_data fsd
    JOIN form_submissions fsub ON fsd.submission_id = fsub.id
    JOIN forms f ON fsub.form_id = f.id
    JOIN classes c ON fsub.class_id = c.id
    JOIN users u ON fsub.user_id = u.id
    LEFT JOIN regions r ON c.region_id = r.id
    WHERE f.form_name LIKE '%خوداظهاری%' -- Assuming this identifies the self-assessment form
    $class_filter_sql
    $teacher_filter_sql
    $region_filter_sql
    $date_filter_sql
    ORDER BY fsub.submitted_at DESC
";

$result = mysqli_query($link, $query);

// --- Data Processing for Charts ---
$submissions = [];
while ($row = mysqli_fetch_assoc($result)) {
    $submissions[$row['submission_id']]['details']['submitted_at'] = $row['submitted_at'];
    $submissions[$row['submission_id']]['details']['class_name'] = $row['class_name'];
    $submissions[$row['submission_id']]['details']['teacher_name'] = $row['teacher_name'];
    $submissions[$row['submission_id']]['details']['region_name'] = $row['region_name'] ?? 'نامشخص';
    $submissions[$row['submission_id']]['data'][$row['field_label']] = $row['field_value'];
}

// Example: Chart for "مدرسین قبل از جلسه هماهنگی داشته اند؟"
$coordination_stats = ['بله' => 0, 'خیر' => 0, 'نامشخص' => 0];
$scores_by_class = []; // For class comparison chart
$absentees_by_region = []; // For region analysis

foreach ($submissions as $sub) {
    // Coordination chart data
    $coordination_answer = $sub['data']['مدرسین قبل از جلسه هماهنگی داشته اند؟'] ?? 'نامشخص';
    if(isset($coordination_stats[$coordination_answer])) {
        $coordination_stats[$coordination_answer]++;
    }

    // Class score simulation (replace with actual scoring logic)
    $score = 0;
    if(($sub['data']['مدرسین قبل از جلسه هماهنگی داشته اند؟'] ?? '') === 'بله') $score += 2;
    if(($sub['data']['مدرسین قبل از جلسه توسل داشته اند'] ?? '') === 'بله') $score += 2;
    $score += (int)($sub['data']['تعداد غائبین این جلسه'] ?? 5) <= 2 ? 1 : 0; // Lower is better

    $class_name = $sub['details']['class_name'];
    if (!isset($scores_by_class[$class_name])) {
        $scores_by_class[$class_name] = ['total_score' => 0, 'count' => 0];
    }
    $scores_by_class[$class_name]['total_score'] += $score;
    $scores_by_class[$class_name]['count']++;

    // Region absentee analysis
    $region_name = $sub['details']['region_name'];
    $absent_count = (int)($sub['data']['تعداد غائبین این جلسه'] ?? 0);
    if (!isset($absentees_by_region[$region_name])) {
        $absentees_by_region[$region_name] = ['total_absentees' => 0, 'count' => 0];
    }
    $absentees_by_region[$region_name]['total_absentees'] += $absent_count;
    $absentees_by_region[$region_name]['count']++;
}

// Prepare data for charts
$coordination_chart_labels = json_encode(array_keys($coordination_stats));
$coordination_chart_data = json_encode(array_values($coordination_stats));

$avg_scores_by_class = [];
foreach($scores_by_class as $class => $data) {
    $avg_scores_by_class[$class] = $data['count'] > 0 ? round($data['total_score'] / $data['count'], 2) : 0;
}
arsort($avg_scores_by_class);
$class_comparison_labels = json_encode(array_keys($avg_scores_by_class));
$class_comparison_data = json_encode(array_values($avg_scores_by_class));

$avg_absentees_by_region = [];
foreach($absentees_by_region as $region => $data) {
    $avg_absentees_by_region[$region] = $data['count'] > 0 ? round($data['total_absentees'] / $data['count'], 2) : 0;
}
arsort($avg_absentees_by_region);
$region_absentee_labels = json_encode(array_keys($avg_absentees_by_region));
$region_absentee_data = json_encode(array_values($avg_absentees_by_region));


require_once "../includes/header.php";
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
    .chart-container { padding: 20px; background: #fff; border-radius: 8px; box-shadow: var(--shadow-md); margin-bottom: 20px; }
</style>

<div class="page-content">
    <h2>تحلیل و آنالیز فرم‌های خوداظهاری</h2>
    <p>در این بخش می‌توانید گزارشات و تحلیل‌های دقیقی از داده‌های ثبت‌شده در فرم‌های خوداظهاری استخراج کنید.</p>

    <!-- Filter Form -->
    <div class="card mb-4">
        <div class="card-header">فیلتر کردن نتایج</div>
        <div class="card-body">
            <form method="get" class="row g-3">
                <div class="col-md-3">
                    <label for="class_id">کلاس</label>
                    <select name="class_id" id="class_id" class="form-select">
                        <option value="">همه کلاس‌ها</option>
                        <?php mysqli_data_seek($all_classes, 0); while($class = mysqli_fetch_assoc($all_classes)): ?>
                            <option value="<?php echo $class['id']; ?>" <?php if(isset($_GET['class_id']) && $_GET['class_id'] == $class['id']) echo 'selected';?>><?php echo htmlspecialchars($class['class_name']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="teacher_id">مدرس</label>
                    <select name="teacher_id" id="teacher_id" class="form-select">
                        <option value="">همه مدرسان</option>
                        <?php mysqli_data_seek($all_teachers, 0); while($teacher = mysqli_fetch_assoc($all_teachers)): ?>
                            <option value="<?php echo $teacher['id']; ?>" <?php if(isset($_GET['teacher_id']) && $_GET['teacher_id'] == $teacher['id']) echo 'selected';?>><?php echo htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="region_id">منطقه</label>
                    <select name="region_id" id="region_id" class="form-select">
                        <option value="">همه مناطق</option>
                        <?php mysqli_data_seek($all_regions, 0); while($region = mysqli_fetch_assoc($all_regions)): ?>
                            <option value="<?php echo $region['id']; ?>" <?php if(isset($_GET['region_id']) && $_GET['region_id'] == $region['id']) echo 'selected';?>><?php echo htmlspecialchars($region['name']); ?></option>
                        <?php endwhile; ?>
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
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">اعمال فیلتر</button>
                    <a href="assessment_analysis.php" class="btn btn-secondary">حذف فیلترها</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Charts Row 1 -->
    <div class="row">
        <div class="col-lg-8">
            <div class="chart-container">
                <h3>مقایسه عملکرد کلاس‌ها (بر اساس امتیاز خوداظهاری)</h3>
                <canvas id="classComparisonChart"></canvas>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="chart-container">
                <h3>آمار هماهنگی قبل از جلسه</h3>
                <canvas id="coordinationChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Charts Row 2 -->
    <div class="row mt-4">
        <div class="col-lg-12">
            <div class="chart-container">
                <h3>میانگین غائبین در هر منطقه</h3>
                <canvas id="regionAbsenteesChart"></canvas>
            </div>
        </div>
    </div>

     <!-- Submissions Table -->
    <div class="table-container mt-4">
        <h3>لیست فرم‌های ثبت شده</h3>
        <table class="table">
            <thead>
                <tr>
                    <th>کلاس</th>
                    <th>مدرس</th>
                    <th>تاریخ ثبت</th>
                    <th>عملیات</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($submissions)): ?>
                    <tr><td colspan="4" class="text-center">هیچ فرمی با این مشخصات یافت نشد.</td></tr>
                <?php else:
                    $displayed_submissions = array_slice($submissions, 0, 20, true); // Limit to 20 for display
                    foreach ($displayed_submissions as $id => $sub): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($sub['details']['class_name']); ?></td>
                            <td><?php echo htmlspecialchars($sub['details']['teacher_name']); ?></td>
                            <td><?php echo to_persian_date($sub['details']['submitted_at']); ?></td>
                            <td>
                                <a href="view_submission_details.php?id=<?php echo $id; ?>" class="btn btn-sm btn-info">مشاهده جزئیات</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Coordination Chart (Pie)
    new Chart(document.getElementById('coordinationChart'), {
        type: 'pie',
        data: {
            labels: <?php echo $coordination_chart_labels; ?>,
            datasets: [{
                label: 'وضعیت هماهنگی',
                data: <?php echo $coordination_chart_data; ?>,
                backgroundColor: ['#28a745', '#dc3545', '#ffc107']
            }]
        }
    });

    // Class Comparison Chart (Bar)
    new Chart(document.getElementById('classComparisonChart'), {
        type: 'bar',
        data: {
            labels: <?php echo $class_comparison_labels; ?>,
            datasets: [{
                label: 'میانگین امتیاز',
                data: <?php echo $class_comparison_data; ?>,
                backgroundColor: 'rgba(0, 123, 255, 0.7)'
            }]
        },
        options: {
            indexAxis: 'x',
            scales: { y: { beginAtZero: true } }
        }
    });

    // Region Absentees Chart (Line)
    new Chart(document.getElementById('regionAbsenteesChart'), {
        type: 'line',
        data: {
            labels: <?php echo $region_absentee_labels; ?>,
            datasets: [{
                label: 'میانگین تعداد غائبین',
                data: <?php echo $region_absentee_data; ?>,
                borderColor: 'rgba(217, 83, 79, 1)',
                backgroundColor: 'rgba(217, 83, 79, 0.2)',
                fill: true,
                tension: 0.1
            }]
        }
    });
});
</script>

<?php
mysqli_close($link);
require_once "../includes/footer.php";
?>
