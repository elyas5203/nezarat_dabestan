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
$submission_id = $_GET['id'] ?? null;

if (!$submission_id) {
    header("location: view_all_assessments.php");
    exit;
}

// Fetch the specific submission
$stmt = mysqli_prepare($link, "SELECT sa.*, u.first_name, u.last_name, c.class_name
                               FROM self_assessments sa
                               JOIN users u ON sa.user_id = u.id
                               JOIN classes c ON sa.class_id = c.id
                               WHERE sa.id = ?");
mysqli_stmt_bind_param($stmt, "i", $submission_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$submission = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$submission) {
    die("Submission not found.");
}

$form_data = json_decode($submission['form_data'], true);

// A simple mapping from field names to more readable Persian labels
$label_map = [
    'class_type' => 'نوع کلاس',
    'extra_session_type' => 'نوع فوق برنامه',
    'coordination_exists' => 'هماهنگی قبل از جلسه',
    'coordination_time' => 'زمان هماهنگی',
    'tavassol_exists' => 'توسل قبل از جلسه',
    'teacher1_status' => 'وضعیت حضور مدرس اول',
    'teacher2_status' => 'وضعیت حضور مدرس دوم',
    'teacher3_status' => 'وضعیت حضور مدرس سوم',
    'absent_count' => 'تعداد غایبین',
    'present_count' => 'تعداد حاضرین',
    'absent_contacted' => 'تماس با غائبین',
    'booklet_story_type' => 'نوع جزوه/داستان',
    'booklet_time' => 'زمان جزوه',
    'booklet_performer' => 'اجرای جزوه',
    'mahnameh_volume' => 'جلد ماهنامه',
    'mahnameh_lesson' => 'درس ماهنامه',
    'bazmandeh_lesson' => 'درس آخرین بازمانده',
    'story_title' => 'عنوان داستان',
    'yadehazrat_type' => 'نوع یادحضرت',
    'yadehazrat_time' => 'زمان یادحضرت',
    'yadehazrat_performer' => 'اجرای یادحضرت',
    'yadehazrat_title' => 'عنوان یادحضرت',
    'game_type' => 'نوع بازی',
    'game_time' => 'زمان بازی',
    'game_performer' => 'اجرای بازی',
    'game_title' => 'عنوان بازی',
    'other_content' => 'محتوای دیگر',
    'other_content_text' => 'شرح محتوای دیگر',
    'creativity_exists' => 'خلاقیت در ارائه',
    'description' => 'توضیحات'
];


require_once "../includes/header.php";
?>
<style>
    .details-container { background: #fff; padding: 30px; border-radius: 8px; box-shadow: var(--shadow-lg); }
    .details-header { border-bottom: 2px solid var(--primary-color); padding-bottom: 15px; margin-bottom: 25px; }
    .details-header h2 { margin: 0; }
    .details-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-bottom: 20px; }
    .detail-item { background-color: #f9f9f9; padding: 15px; border-radius: 5px; border-left: 4px solid var(--secondary-color); word-wrap: break-word; }
    .detail-item strong { display: block; margin-bottom: 8px; color: #333; }
    .detail-item span { color: #555; }
</style>

<div class="page-content">
    <div class="details-container">
        <div class="details-header">
            <h2>جزئیات فرم خوداظهاری</h2>
            <p>کلاس: <strong><?php echo htmlspecialchars($submission['class_name']); ?></strong> | مدرس: <strong><?php echo htmlspecialchars($submission['first_name'] . ' ' . $submission['last_name']); ?></strong></p>
        </div>

        <div class="details-grid">
            <div class="detail-item">
                <strong>تاریخ جلسه:</strong>
                <span><?php echo $submission['meeting_date'] ? jdf("l, j F Y", strtotime($submission['meeting_date'])) : '_'; ?></span>
            </div>
            <div class="detail-item">
                <strong>نوع جلسه:</strong>
                <span><?php echo htmlspecialchars($submission['class_type']); ?></span>
            </div>
            <div class="detail-item">
                <strong>تاریخ ثبت فرم:</strong>
                <span><?php echo jdf("Y/m/d H:i", strtotime($submission['created_at'])); ?></span>
            </div>
        </div>

        <hr>

        <div class="form-data-section">
            <h4>اطلاعات ثبت شده در فرم:</h4>
            <div class="details-grid">
                <?php foreach ($form_data as $key => $value):
                    if (empty($value) || in_array($key, ['class_id', 'submit_self_assessment', 'meeting_date'])) continue;
                    $label = $label_map[$key] ?? ucwords(str_replace('_', ' ', $key));
                ?>
                    <div class="detail-item">
                        <strong><?php echo htmlspecialchars($label); ?>:</strong>
                        <span><?php echo nl2br(htmlspecialchars(is_array($value) ? implode(', ', $value) : $value)); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
     <div class="form-group" style="margin-top: 20px;">
        <a href="view_all_assessments.php?class_id=<?php echo $submission['class_id']; ?>" class="btn btn-secondary">بازگشت به لیست</a>
    </div>
</div>

<?php
require_once "../includes/footer.php";
?>
