<?php
session_start();
require_once "../includes/db_singleton.php";
require_once "../includes/functions.php";
require_once "../includes/jdf.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !$_SESSION["is_admin"]) {
    header("location: ../index.php");
    exit;
}

$link = get_db_connection();
$selected_class_id = $_GET['class_id'] ?? null;
$assessments = [];
$analytics_data = null;

if ($selected_class_id) {
    // Fetch assessments for the selected class
    $stmt = mysqli_prepare($link, "SELECT sa.*, u.first_name, u.last_name FROM self_assessments sa JOIN users u ON sa.user_id = u.id WHERE sa.class_id = ? ORDER BY sa.meeting_date DESC");
    mysqli_stmt_bind_param($stmt, "i", $selected_class_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $row['form_data'] = json_decode($row['form_data'], true);
        $assessments[] = $row;
    }
    mysqli_stmt_close($stmt);

    // --- Analytics Calculation ---
    if (!empty($assessments)) {
        $total_score = 0;
        $scores_over_time = [];

        foreach ($assessments as &$assessment) {
            $score = 0;
            $data = $assessment['form_data'];

            // Scoring logic
            if ($assessment['class_type'] === 'عادی') $score += 2;
            if ($assessment['class_type'] === 'تشکیل نشده') $score -= 5;

            if (isset($data['coordination_exists']) && $data['coordination_exists'] === 'بله') $score += 1;
            if (isset($data['tavassol_exists']) && $data['tavassol_exists'] === 'بله') $score += 1;

            if (isset($data['teacher1_status']) && $data['teacher1_status'] === 'راس ساعت') $score += 1;
            if (isset($data['teacher1_status']) && $data['teacher1_status'] === 'غیبت') $score -= 2;
            if (isset($data['teacher2_status']) && $data['teacher2_status'] === 'راس ساعت') $score += 1;
            if (isset($data['teacher2_status']) && $data['teacher2_status'] === 'غیبت') $score -= 2;

            if (isset($data['booklet_story_type']) && $data['booklet_story_type'] !== 'عدم اجرا') $score += 2;
            if (isset($data['yadehazrat_type']) && $data['yadehazrat_type'] !== 'عدم اجرا') $score += 2;

            if (isset($data['creativity_exists']) && $data['creativity_exists'] === 'بله') $score += 3;

            $assessment['score'] = $score;
            $total_score += $score;

            // For the chart (in reverse order for correct timeline)
            $scores_over_time[to_persian_date($assessment['meeting_date'])] = $score;
        }
        unset($assessment); // Unset reference

        $analytics_data = [
            'total_submissions' => count($assessments),
            'class_type_distribution' => array_count_values(array_column($assessments, 'class_type')),
            'average_score' => round($total_score / count($assessments), 2),
            'scores_over_time' => array_reverse($scores_over_time) // Reverse to show timeline correctly
        ];
    }
}

require_once "../includes/header.php";
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
    .analytics-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    .analytics-card {
        background: #fff;
        padding: 20px;
        border-radius: 8px;
        box-shadow: var(--shadow-md);
    }
    .analytics-card h4 {
        margin-top: 0;
        border-bottom: 1px solid #eee;
        padding-bottom: 10px;
    }
</style>

<div class="page-content">
    <h2>تحلیل و مشاهده فرم‌های خوداظهاری</h2>

    <div class="form-container" style="margin-bottom: 20px;">
        <form method="get" action="">
            <div class="form-group">
                <label for="class_id">برای مشاهده گزارش‌ها، یک کلاس را انتخاب کنید:</label>
                <select name="class_id" id="class_id" class="form-control" onchange="this.form.submit()">
                    <option value="">-- انتخاب کلاس --</option>
                    <?php
                    $classes_query = mysqli_query($link, "SELECT id, class_name FROM classes WHERE status = 'active' ORDER BY class_name");
                    while ($class_item = mysqli_fetch_assoc($classes_query)) {
                        $selected = ($selected_class_id == $class_item['id']) ? 'selected' : '';
                        echo "<option value='{$class_item['id']}' {$selected}>" . htmlspecialchars($class_item['class_name']) . "</option>";
                    }
                    ?>
                </select>
            </div>
        </form>
    </div>

    <?php if ($selected_class_id && empty($assessments)): ?>
        <div class="alert alert-info">هنوز هیچ فرم خوداظهاری برای این کلاس ثبت نشده است.</div>
    <?php endif; ?>

    <?php if ($selected_class_id && !empty($assessments)): ?>
        <!-- Analytics Section -->
        <div class="analytics-grid">
            <div class="analytics-card">
                <h4>تعداد فرم‌های ثبت شده</h4>
                <p style="font-size: 2rem; font-weight: bold; text-align: center;"><?php echo $analytics_data['total_submissions']; ?></p>
            </div>
             <div class="analytics-card">
                <h4>میانگین امتیاز جلسات</h4>
                <p style="font-size: 2rem; font-weight: bold; text-align: center; color: <?php echo ($analytics_data['average_score'] >= 0) ? 'var(--success-color)' : 'var(--danger-color)'; ?>;">
                    <?php echo $analytics_data['average_score']; ?>
                </p>
            </div>
            <div class="analytics-card" style="grid-column: 1 / -1;">
                <h4>روند امتیازات در طول زمان</h4>
                <canvas id="scoreTrendChart"></canvas>
            </div>
            <div class="analytics-card">
                <h4>پراکندگی نوع جلسات</h4>
                <canvas id="classTypeChart"></canvas>
            </div>
        </div>

        <!-- Submissions Table -->
        <div class="table-container">
            <h3>لیست فرم‌های ثبت شده</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>مدرس</th>
                        <th>تاریخ جلسه</th>
                        <th>نوع جلسه</th>
                        <th>امتیاز</th>
                        <th>تاریخ ثبت</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($assessments as $assessment): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($assessment['first_name'] . ' ' . $assessment['last_name']); ?></td>
                            <td><?php echo htmlspecialchars(to_persian_date($assessment['meeting_date'])); ?></td>
                            <td><?php echo htmlspecialchars($assessment['class_type']); ?></td>
                            <td style="font-weight: bold; color: <?php echo ($assessment['score'] >= 0) ? 'green' : 'red'; ?>;"><?php echo $assessment['score']; ?></td>
                            <td><?php echo htmlspecialchars(to_persian_date($assessment['created_at'])); ?></td>
                            <td>
                                <a href="view_submission_details.php?id=<?php echo $assessment['id']; ?>" class="btn btn-primary btn-sm">مشاهده جزئیات</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php if ($analytics_data): ?>
    // Chart for Class Type Distribution
    const pieCtx = document.getElementById('classTypeChart').getContext('2d');
    new Chart(pieCtx, {
        type: 'pie',
        data: {
            labels: <?php echo json_encode(array_keys($analytics_data['class_type_distribution'])); ?>,
            datasets: [{
                label: 'تعداد جلسات',
                data: <?php echo json_encode(array_values($analytics_data['class_type_distribution'])); ?>,
                backgroundColor: ['rgba(54, 162, 235, 0.7)', 'rgba(255, 206, 86, 0.7)', 'rgba(255, 99, 132, 0.7)'],
            }]
        },
        options: { responsive: true, plugins: { legend: { position: 'top' } } }
    });

    // Chart for Score Trend
    const lineCtx = document.getElementById('scoreTrendChart').getContext('2d');
    new Chart(lineCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode(array_keys($analytics_data['scores_over_time'])); ?>,
            datasets: [{
                label: 'امتیاز جلسه',
                data: <?php echo json_encode(array_values($analytics_data['scores_over_time'])); ?>,
                fill: false,
                borderColor: 'rgb(75, 192, 192)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            scales: { y: { beginAtZero: false } }
        }
    });
    <?php endif; ?>
});
</script>

<?php
require_once "../includes/footer.php";
?>
