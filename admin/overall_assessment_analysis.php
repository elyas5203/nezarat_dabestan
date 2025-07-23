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

// Fetch all submissions
$submissions_query = mysqli_query($link, "SELECT * FROM self_assessments");

$submissions = [];
while($row = mysqli_fetch_assoc($submissions_query)) {
    $submissions[] = $row;
}

// Overall Analysis
$total_submissions = count($submissions);
$total_score = 0;
$class_scores = [];

foreach ($submissions as $submission) {
    $data = json_decode($submission['form_data'], true);
    $score = 0;

    // Scoring logic (same as in view_all_assessments.php)
    if ($submission['class_type'] === 'عادی') $score += 2;
    if ($submission['class_type'] === 'تشکیل نشده') $score -= 5;
    if (isset($data['coordination_exists']) && $data['coordination_exists'] === 'بله') $score += 1;
    if (isset($data['tavassol_exists']) && $data['tavassol_exists'] === 'بله') $score += 1;
    if (isset($data['teacher1_status']) && $data['teacher1_status'] === 'راس ساعت') $score += 1;
    if (isset($data['teacher1_status']) && $data['teacher1_status'] === 'غیبت') $score -= 2;
    if (isset($data['teacher2_status']) && $data['teacher2_status'] === 'راس ساعت') $score += 1;
    if (isset($data['teacher2_status']) && $data['teacher2_status'] === 'غیبت') $score -= 2;
    if (isset($data['booklet_story_type']) && $data['booklet_story_type'] !== 'عدم اجرا') $score += 2;
    if (isset($data['yadehazrat_type']) && $data['yadehazrat_type'] !== 'عدم اجرا') $score += 2;
    if (isset($data['creativity_exists']) && $data['creativity_exists'] === 'بله') $score += 3;

    $total_score += $score;

    if (!isset($class_scores[$submission['class_id']])) {
        $class_scores[$submission['class_id']] = [
            'total_score' => 0,
            'submission_count' => 0
        ];
    }
    $class_scores[$submission['class_id']]['total_score'] += $score;
    $class_scores[$submission['class_id']]['submission_count']++;
}

// Calculate average score for each class
$class_avg_scores = [];
foreach ($class_scores as $class_id => $scores) {
    $class_name_query = mysqli_query($link, "SELECT class_name FROM classes WHERE id = $class_id");
    $class_name = mysqli_fetch_assoc($class_name_query)['class_name'];
    $class_avg_scores[$class_name] = round($scores['total_score'] / $scores['submission_count'], 2);
}

// Sort classes by average score
arsort($class_avg_scores);

require_once "../includes/header.php";
?>

<div class="page-content">
    <h2>تحلیل کلی سیستم خوداظهاری</h2>

    <div class="card">
        <div class="card-header">
            <h3>آمار کلی</h3>
        </div>
        <div class="card-body">
            <p><strong>تعداد کل فرم‌های ثبت شده:</strong> <?php echo $total_submissions; ?></p>
            <p><strong>میانگین امتیاز کل:</strong> <?php echo $total_submissions > 0 ? round($total_score / $total_submissions, 2) : 0; ?></p>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header">
            <h3>رتبه‌بندی کلاس‌ها بر اساس میانگین امتیاز</h3>
        </div>
        <div class="card-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>رتبه</th>
                        <th>نام کلاس</th>
                        <th>میانگین امتیاز</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $rank = 1; ?>
                    <?php foreach ($class_avg_scores as $class_name => $avg_score): ?>
                        <tr>
                            <td><?php echo $rank++; ?></td>
                            <td><?php echo htmlspecialchars($class_name); ?></td>
                            <td><?php echo $avg_score; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
require_once "../includes/footer.php";
?>
