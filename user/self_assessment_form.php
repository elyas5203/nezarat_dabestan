<?php
session_start();
require_once "../includes/db_singleton.php";
require_once "../includes/functions.php";
require_once "../includes/jdf.php"; // For Persian date

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../index.php");
    exit;
}

$link = get_db_connection();
$user_id = $_SESSION['id'];

// --- Handle Form Submission ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_self_assessment'])) {
    // Extract data from POST
    $class_id = $_POST['class_id'];
    $meeting_date = !empty($_POST['meeting_date']) ? $_POST['meeting_date'] : null;
    $class_type = $_POST['class_type'] ?? '';

    $form_data = [];
    foreach ($_POST as $key => $value) {
        if (!in_array($key, ['class_id', 'submit_self_assessment'])) {
            $form_data[$key] = is_array($value) ? implode(', ', $value) : $value;
        }
    }

    // Convert form data to JSON to store in a single record
    $form_data_json = json_encode($form_data, JSON_UNESCAPED_UNICODE);

    // Start transaction
    mysqli_begin_transaction($link);
    try {
        // Insert the main form data
        $sql = "INSERT INTO self_assessments (user_id, class_id, meeting_date, class_type, form_data) VALUES (?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($link, $sql);
        mysqli_stmt_bind_param($stmt, "iisss", $user_id, $class_id, $meeting_date, $class_type, $form_data_json);
        mysqli_stmt_execute($stmt);

        // Scoring Logic
        $total_score = 0;
        $section_scores = [];

        // Section: Attendance
        $attendance_score = 0;
        if (isset($_POST['coordination_exists']) && $_POST['coordination_exists'] === 'بله') $attendance_score += 1;
        if (isset($_POST['tavassol_exists']) && $_POST['tavassol_exists'] === 'بله') $attendance_score += 1;
        if (isset($_POST['teacher1_status']) && $_POST['teacher1_status'] === 'راس ساعت') $attendance_score += 1;
        if (isset($_POST['teacher1_status']) && $_POST['teacher1_status'] === 'غیبت') $attendance_score -= 2;
        if (isset($_POST['teacher2_status']) && $_POST['teacher2_status'] === 'راس ساعت') $attendance_score += 1;
        if (isset($_POST['teacher2_status']) && $_POST['teacher2_status'] === 'غیبت') $attendance_score -= 2;
        $section_scores['attendance'] = $attendance_score;
        $total_score += $attendance_score;

        // Section: Content
        $content_score = 0;
        if (isset($_POST['booklet_story_type']) && $_POST['booklet_story_type'] !== 'عدم اجرا') $content_score += 2;
        if (isset($_POST['yadehazrat_type']) && $_POST['yadehazrat_type'] !== 'عدم اجرا') $content_score += 2;
        if (isset($_POST['creativity_exists']) && $_POST['creativity_exists'] === 'بله') $content_score += 3;
        $section_scores['content'] = $content_score;
        $total_score += $content_score;

        // Update total score in self_assessments table
        $update_score_stmt = mysqli_prepare($link, "UPDATE self_assessments SET score = ? WHERE id = ?");
        mysqli_stmt_bind_param($update_score_stmt, "ii", $total_score, $assessment_id);
        mysqli_stmt_execute($update_score_stmt);

        // Insert section scores
        $section_sql = "INSERT INTO assessment_section_scores (assessment_id, section_name, score) VALUES (?, ?, ?)";
        $section_stmt = mysqli_prepare($link, $section_sql);
        foreach ($section_scores as $section_name => $score) {
            mysqli_stmt_bind_param($section_stmt, "isi", $assessment_id, $section_name, $score);
            mysqli_stmt_execute($section_stmt);
        }

        mysqli_commit($link);
        header("location: my_self_assessments.php?success=1");
        exit;
    } catch (Exception $e) {
        mysqli_rollback($link);
        header("location: self_assessment_form.php?class_id={$class_id}&error=" . urlencode($e->getMessage()));
        exit;
    }
}

// --- Fetch Data for Form ---
$selected_class_id = $_GET['class_id'] ?? null;
$class_name = '';
$students = [];
$total_students = 0;

if ($selected_class_id) {
    // Fetch class details
    $stmt = mysqli_prepare($link, "SELECT class_name FROM classes WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $selected_class_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $class_name);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    // Fetch class students
    $stmt_students = mysqli_prepare($link, "SELECT student_name FROM class_students WHERE class_id = ? ORDER BY student_name");
    mysqli_stmt_bind_param($stmt_students, "i", $selected_class_id);
    mysqli_stmt_execute($stmt_students);
    $result_students = mysqli_stmt_get_result($stmt_students);
    while ($row = mysqli_fetch_assoc($result_students)) {
        $students[] = $row['student_name'];
    }
    $total_students = count($students);
    mysqli_stmt_close($stmt_students);
}


require_once "../includes/header.php";
?>

<!-- Add custom CSS for the new form design -->
<link rel="stylesheet" href="../assets/css/persian-datepicker.min.css"/>
<style>
    .form-section {
        display: none;
        padding: 20px;
        border: 1px solid #ddd;
        border-radius: 8px;
        margin-top: 20px;
        animation: fadeIn 0.5s;
    }
    .form-section.active {
        display: block;
    }
    .form-section h3 {
        margin-top: 0;
        border-bottom: 2px solid var(--primary-color);
        padding-bottom: 10px;
        margin-bottom: 20px;
    }
    .radio-group, .checkbox-group {
        display: flex;
        align-items: center;
        gap: 15px;
        margin-bottom: 10px;
    }
    .other-input {
        display: none;
        margin-top: 10px;
    }
    .required-star { color: var(--danger-color); }
    @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
    .student-attendance-list {
        display: flex;
        flex-direction: column;
        gap: 10px;
        max-height: 300px;
        overflow-y: auto;
        padding: 10px;
        border: 1px solid #eee;
        border-radius: 5px;
    }
    .student-attendance-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px;
        background-color: #f9f9f9;
        border-radius: 4px;
    }
</style>

<div class="page-content">

    <?php if (!$selected_class_id): ?>
        <h2>فرم خوداظهاری هفتگی - انتخاب کلاس</h2>
        <div class="form-container">
            <p>لطفاً کلاسی که می‌خواهید برای آن فرم خوداظهاری پر کنید را انتخاب نمایید.</p>
            <form method="get" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <div class="form-group">
                    <label for="class-select">کلاس‌های من:</label>
                    <select name="class_id" id="class-select" class="form-control" onchange="this.form.submit()">
                        <option value="">-- یک کلاس را انتخاب کنید --</option>
                        <?php
                        $classes_query = mysqli_query($link, "SELECT c.id, c.class_name FROM classes c JOIN class_teachers ct ON c.id = ct.class_id WHERE ct.teacher_id = $user_id AND c.status = 'active' ORDER BY c.class_name");
                        while($class_item = mysqli_fetch_assoc($classes_query)) {
                            echo "<option value='{$class_item['id']}'>" . htmlspecialchars($class_item['class_name']) . "</option>";
                        }
                        ?>
                    </select>
                </div>
            </form>
        </div>
    <?php else: ?>
        <h2>فرم خوداظهاری هفتگی برای کلاس: <?php echo htmlspecialchars($class_name); ?></h2>

        <form id="selfAssessmentForm" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="form-container">
            <input type="hidden" name="class_id" value="<?php echo $selected_class_id; ?>">

            <!-- Section 1: Basic Info -->
            <div id="section-basic" class="form-section active">
                <h3>۱. اطلاعات پایه</h3>
                <div class="form-group">
                    <label for="class_type">نوع کلاس برگزار شده را مشخص کنید: <span class="required-star">*</span></label>
                    <select name="class_type" id="class_type" class="form-control" required>
                        <option value="">انتخاب کنید...</option>
                        <option value="عادی">عادی</option>
                        <option value="فوق برنامه">فوق برنامه (جزئیات در بخش توضیحات)</option>
                        <option value="تشکیل نشده">تشکیل نشده (علت در بخش توضیحات)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="meeting_date_pd">تاریخ جلسه: <span class="required-star">*</span></label>
                    <input type="text" id="meeting_date_pd" class="form-control" autocomplete="off" required>
                    <input type="hidden" name="meeting_date" id="meeting_date">
                </div>
                 <div class="form-group" id="extra-session-type-group" style="display: none;">
                    <label for="extra_session_type">نوع فوق برنامه:</label>
                    <input type="text" name="extra_session_type" id="extra_session_type" class="form-control">
                </div>
            </div>

            <!-- Section 2: Attendance -->
            <div id="section-attendance" class="form-section">
                <h3>۲. حضور و غیاب</h3>
                 <div class="form-group">
                    <label>مدرسین قبل از جلسه هماهنگی داشته اند؟ <span class="required-star">*</span></label>
                    <div class="radio-group">
                        <label><input type="radio" name="coordination_exists" value="بله" required> بله</label>
                        <label><input type="radio" name="coordination_exists" value="خیر"> خیر</label>
                    </div>
                </div>
                <div class="form-group">
                    <label for="coordination_time">زمان هماهنگی قبل از جلسه چقدر بوده است؟</label>
                    <select name="coordination_time" id="coordination_time" class="form-control">
                        <option value="نداشتیم">نداشتیم</option>
                        <option value="کمتر از نیم ساعت">کمتر از نیم ساعت</option>
                        <option value="بین نیم تا دو ساعت">بین نیم تا دو ساعت</option>
                        <option value="بیش از دو ساعت">بیش از دو ساعت</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>مدرسین قبل از جلسه توسل داشته اند؟ <span class="required-star">*</span></label>
                    <div class="radio-group">
                        <label><input type="radio" name="tavassol_exists" value="بله" required> بله</label>
                        <label><input type="radio" name="tavassol_exists" value="خیر"> خیر</label>
                    </div>
                </div>
                 <div class="form-group">
                    <label for="teacher1_status">وضعیت حضور مدرس اول؟ <span class="required-star">*</span></label>
                    <select name="teacher1_status" id="teacher1_status" class="form-control" required>
                        <option value="">انتخاب کنید...</option>
                        <option value="راس ساعت">راس ساعت</option>
                        <option value="با تاخیر تا ده دقیقه">با تاخیر تا ده دقیقه</option>
                        <option value="تاخیر بیش از ده دقیقه">تاخیر بیش از ده دقیقه</option>
                        <option value="غیبت">غیبت</option>
                    </select>
                </div>
                 <div class="form-group">
                    <label for="teacher2_status">وضعیت حضور مدرس دوم؟ <span class="required-star">*</span></label>
                     <select name="teacher2_status" id="teacher2_status" class="form-control" required>
                        <option value="">انتخاب کنید...</option>
                        <option value="راس ساعت">راس ساعت</option>
                        <option value="با تاخیر تا ده دقیقه">با تاخیر تا ده دقیقه</option>
                        <option value="تاخیر بیش از ده دقیقه">تاخیر بیش از ده دقیقه</option>
                        <option value="غیبت">غیبت</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="teacher3_status">وضعیت حضور مدرس سوم (در صورت وجود)؟</label>
                     <select name="teacher3_status" id="teacher3_status" class="form-control">
                        <option value="">انتخاب کنید...</option>
                        <option value="راس ساعت">راس ساعت</option>
                        <option value="با تاخیر تا ده دقیقه">با تاخیر تا ده دقیقه</option>
                        <option value="تاخیر بیش از ده دقیقه">تاخیر بیش از ده دقیقه</option>
                        <option value="غیبت">غیبت</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>حضور و غیاب متربیان: <span class="required-star">*</span></label>
                    <div class="student-attendance-list">
                        <?php foreach ($students as $student): ?>
                            <div class="student-attendance-item">
                                <label>
                                    <input type="checkbox" name="present_students[]" value="<?php echo htmlspecialchars($student); ?>" checked>
                                    <?php echo htmlspecialchars($student); ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                 <div class="form-group">
                    <label>با غائبین بدون اطلاع تماس گرفته شد؟ <span class="required-star">*</span></label>
                     <div class="radio-group">
                        <label><input type="radio" name="absent_contacted" value="بله" required> بله</label>
                        <label><input type="radio" name="absent_contacted" value="خیر"> خیر</label>
                        <label><input type="radio" name="absent_contacted" value="غائب بدون اطلاع نداشتیم">غائب بدون اطلاع نداشتیم</label>
                    </div>
                </div>
            </div>

            <!-- Section 3: Content -->
            <div id="section-content" class="form-section">
                <h3>۳. جزوه و داستان</h3>
                <div class="form-group">
                    <label for="booklet_story_type">جزوه و داستان: <span class="required-star">*</span></label>
                    <select name="booklet_story_type" id="booklet_story_type" class="form-control" required>
                        <option value="">انتخاب کنید...</option>
                        <option value="آخرین بازمانده">آخرین بازمانده</option>
                        <option value="ماهنامه">ماهنامه</option>
                        <option value="داستان با هماهنگی">داستان با هماهنگی</option>
                        <option value="داستان بدون هماهنگی">داستان بدون هماهنگی</option>
                        <option value="عدم اجرا">عدم اجرا</option>
                    </select>
                </div>

                <!-- Sub-section for booklet -->
                <div id="subsection-booklet-details" style="display:none;">
                    <div class="form-group">
                        <label for="booklet_time">زمان جزوه: <span class="required-star">*</span></label>
                        <select name="booklet_time" id="booklet_time" class="form-control">
                            <option value="">انتخاب کنید...</option>
                            <option value="بین 15 تا 30 دقیقه">بین 15 تا 30 دقیقه</option>
                            <option value="بیش از 30 دقیقه">بیش از 30 دقیقه</option>
                             <option value="عدم اجرا">عدم اجرا</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="booklet_performer">اجرای جزوه: <span class="required-star">*</span></label>
                        <select name="booklet_performer" id="booklet_performer" class="form-control">
                           <option value="">انتخاب کنید...</option>
                           <option value="مدرس اول">مدرس اول</option>
                           <option value="مدرس دوم">مدرس دوم</option>
                           <option value="مدرس سوم">مدرس سوم</option>
                           <option value="به صورت مشترک">به صورت مشترک</option>
                           <option value="عدم اجرا">عدم اجرا</option>
                        </select>
                    </div>
                </div>

                <!-- Sub-section for Mahnameh -->
                <div id="subsection-mahnameh" style="display: none;">
                    <div class="form-group">
                        <label for="mahnameh_volume">کدام جلد از جزوه ماهنامه را تدریس کردید؟ <span class="required-star">*</span></label>
                        <select name="mahnameh_volume" id="mahnameh_volume" class="form-control">
                            <option value="">انتخاب کنید...</option>
                            <option value="محرم">محرم</option>
                            <option value="صفر">صفر</option>
                            <option value="ربیع الاول">ربیع الاول</option>
                            <option value="ربیع الثانی">ربیع الثانی</option>
                            <option value="جمادی الاول">جمادی الاول</option>
                            <option value="جمادی الثانی">جمادی الثانی</option>
                            <option value="رجب">رجب</option>
                            <option value="شعبان">شعبان</option>
                            <option value="رمضان">رمضان</option>
                            <option value="شوال">شوال</option>
                            <option value="ذی القعده">ذی القعده</option>
                            <option value="ذی الحجه">ذی الحجه</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="mahnameh_lesson">درس چندم جزوه را تدریس کردید؟ <span class="required-star">*</span></label>
                        <select name="mahnameh_lesson" id="mahnameh_lesson" class="form-control">
                            <option value="">انتخاب کنید...</option>
                            <option value="درس اول">درس اول</option>
                            <option value="درس دوم">درس دوم</option>
                            <option value="درس سوم">درس سوم</option>
                            <option value="درس چهارم">درس چهارم</option>
                        </select>
                    </div>
                </div>

                <!-- Sub-section for Bazmandeh -->
                <div id="subsection-bazmandeh" style="display: none;">
                     <div class="form-group">
                        <label for="bazmandeh_lesson">کدام درس از جزوه آخرین بازمانده را تدریس کردید؟ <span class="required-star">*</span></label>
                        <select name="bazmandeh_lesson" id="bazmandeh_lesson" class="form-control">
                             <option value="">انتخاب کنید...</option>
                             <?php for ($i = 1; $i <= 14; $i++): ?>
                                <option value="درس <?php echo $i; ?>">درس <?php echo $i; ?></option>
                             <?php endfor; ?>
                        </select>
                    </div>
                </div>

                <!-- Sub-section for Story -->
                <div id="subsection-story" style="display: none;">
                    <div class="form-group">
                        <label for="story_title">عنوان داستان گفته شده: <span class="required-star">*</span></label>
                        <input type="text" name="story_title" id="story_title" class="form-control">
                    </div>
                </div>
            </div>

            <!-- Section 4: Other Content -->
            <div id="section-other-content" class="form-section">
                <h3>۴. محتوا</h3>
                <div class="form-group">
                    <label for="yadehazrat_type">نوع یادحضرت؟ <span class="required-star">*</span></label>
                    <select name="yadehazrat_type" id="yadehazrat_type" class="form-control" required>
                        <option value="">انتخاب کنید...</option>
                        <option value="طبق چارت">طبق چارت</option>
                        <option value="با هماهنگی">با هماهنگی</option>
                        <option value="بدون هماهنگی">بدون هماهنگی</option>
                        <option value="عدم اجرا">عدم اجرا</option>
                    </select>
                </div>
                 <div class="form-group">
                    <label for="yadehazrat_time">زمان یادحضرت؟ <span class="required-star">*</span></label>
                     <select name="yadehazrat_time" id="yadehazrat_time" class="form-control" required>
                        <option value="">انتخاب کنید...</option>
                        <option value="کمتر از 15 دقیقه">کمتر از 15 دقیقه</option>
                        <option value="بین 15 تا 30 دقیقه">بین 15 تا 30 دقیقه</option>
                        <option value="بیش از 30 دقیقه">بیش از 30 دقیقه</option>
                        <option value="عدم اجرا">عدم اجرا</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="yadehazrat_performer">اجرای یادحضرت؟ <span class="required-star">*</span></label>
                     <select name="yadehazrat_performer" id="yadehazrat_performer" class="form-control" required>
                        <option value="">انتخاب کنید...</option>
                        <option value="مدرس اول">مدرس اول</option>
                        <option value="مدرس دوم">مدرس دوم</option>
                        <option value="مدرس سوم">مدرس سوم</option>
                        <option value="به صورت مشترک">به صورت مشترک</option>
                        <option value="عدم اجرا">عدم اجرا</option>
                    </select>
                </div>
                 <div class="form-group">
                    <label for="yadehazrat_title">عنوان یادحضرت: <span class="required-star">*</span></label>
                    <input type="text" name="yadehazrat_title" id="yadehazrat_title" class="form-control" required>
                </div>

                <hr>

                <div class="form-group">
                    <label for="game_type">نوع بازی؟ <span class="required-star">*</span></label>
                    <select name="game_type" id="game_type" class="form-control" required>
                        <option value="">انتخاب کنید...</option>
                        <option value="کانال بازی">کانال بازی</option>
                        <option value="بازی جدید">بازی جدید</option>
                        <option value="عدم اجرا">عدم اجرا</option>
                    </select>
                </div>
                 <div class="form-group">
                    <label for="game_time">زمان بازی؟ <span class="required-star">*</span></label>
                     <select name="game_time" id="game_time" class="form-control" required>
                        <option value="">انتخاب کنید...</option>
                        <option value="کمتر از 30 دقیقه">کمتر از 30 دقیقه</option>
                        <option value="بین 30 تا 45 دقیقه">بین 30 تا 45 دقیقه</option>
                        <option value="بیش از 45 دقیقه">بیش از 45 دقیقه</option>
                        <option value="عدم اجرا">عدم اجرا</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="game_performer">اجرای بازی؟ <span class="required-star">*</span></label>
                     <select name="game_performer" id="game_performer" class="form-control" required>
                        <option value="">انتخاب کنید...</option>
                        <option value="مدرس اول">مدرس اول</option>
                        <option value="مدرس دوم">مدرس دوم</option>
                        <option value="مدرس سوم">مدرس سوم</option>
                        <option value="به صورت مشترک">به صورت مشترک</option>
                        <option value="عدم اجرا">عدم اجرا</option>
                    </select>
                </div>
                 <div class="form-group">
                    <label for="game_title">عنوان بازی:</label>
                    <input type="text" name="game_title" id="game_title" class="form-control">
                </div>

                <hr>

                <div class="form-group">
                    <label>محتوای دیگر ارائه شده؟</label>
                    <div class="checkbox-group">
                        <label><input type="checkbox" name="other_content[]" value="احکام"> احکام</label>
                        <label><input type="checkbox" name="other_content[]" value="قرآن"> قرآن</label>
                        <label><input type="checkbox" name="other_content[]" value="مناسبت"> مناسبت</label>
                        <label><input type="checkbox" name="other_content[]" value="نداشتیم"> نداشتیم</label>
                        <label><input type="checkbox" id="other_content_checkbox" name="other_content[]" value="سایر"> سایر</label>
                    </div>
                    <input type="text" name="other_content_text" id="other_content_text" class="form-control other-input" placeholder="لطفا نوع محتوای دیگر را بنویسید">
                </div>

                <div class="form-group">
                    <label>در ارائه محتوا خلاقیت داشتید؟ <span class="required-star">*</span></label>
                    <div class="radio-group">
                        <label><input type="radio" name="creativity_exists" value="بله" required> بله (لطفا در بخش توضیحات شرح دهید)</label>
                        <label><input type="radio" name="creativity_exists" value="خیر"> خیر</label>
                    </div>
                </div>
            </div>

            <!-- Section 5: Description -->
            <div id="section-description" class="form-section">
                <h3>۵. توضیحات</h3>
                <div class="form-group">
                    <label for="description">توضیحات:</label>
                    <textarea name="description" id="description" class="form-control" rows="5" placeholder="به عنوان مثال: علت عدم برگزاری، نوع مناسبت، توضیح خلاقیت در ارائه محتوا، اضافه یا کم شدن متربیان و سایر موارد"></textarea>
                </div>
            </div>

            <div class="form-group" style="margin-top: 30px;">
                <input type="submit" name="submit_self_assessment" class="btn btn-primary btn-lg" value="ثبت نهایی فرم">
            </div>
        </form>
    <?php endif; ?>
</div>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script src="../assets/js/persian-date.min.js"></script>
<script src="../assets/js/persian-datepicker.min.js"></script>
<script>
$(document).ready(function() {
    const totalStudents = <?php echo $total_students; ?>;

    // --- Date Picker ---
    $("#meeting_date_pd").pDatepicker({
        format: 'YYYY/MM/DD',
        altField: '#meeting_date',
        altFormat: 'X', // Unix timestamp
        observer: true,
        autoClose: true,
        initialValue: false
    });

    // --- Conditional Logic ---
    const form = $('#selfAssessmentForm');
    const sections = form.find('.form-section');

    function updateFormVisibility() {
        const classType = $('#class_type').val();

        // Hide all optional sections first
        $('#section-attendance, #section-content, #section-other-content').hide();
        $('#extra-session-type-group').hide();

        if (classType === 'عادی') {
            $('#section-attendance, #section-content, #section-other-content').show();
        } else if (classType === 'فوق برنامه') {
            $('#extra-session-type-group').show();
        }

        // The description section is always visible, but might be required
        const descriptionTextarea = $('#description');
        if (classType === 'فوق برنامه' || classType === 'تشکیل نشده') {
            descriptionTextarea.prop('required', true);
            descriptionTextarea.prev('label').append(' <span class="required-star">*</span>');
        } else {
            descriptionTextarea.prop('required', false);
            descriptionTextarea.prev('label').find('.required-star').remove();
        }
    }

    function updateContentVisibility() {
        const bookletType = $('#booklet_story_type').val();

        $('#subsection-booklet-details, #subsection-mahnameh, #subsection-bazmandeh, #subsection-story').hide();

        if (bookletType === 'آخرین بازمانده' || bookletType === 'ماهنامه') {
            $('#subsection-booklet-details').show();
            if (bookletType === 'آخرین بازمانده') {
                $('#subsection-bazmandeh').show();
            } else {
                $('#subsection-mahnameh').show();
            }
        } else if (bookletType.includes('داستان')) {
            $('#subsection-story').show();
        }
    }

    $('#class_type').on('change', updateFormVisibility);
    $('#booklet_story_type').on('change', updateContentVisibility);


    // --- Other Content Checkbox ---
    $('#other_content_checkbox').on('change', function() {
        if ($(this).is(':checked')) {
            $('#other_content_text').show().prop('required', true);
        } else {
            $('#other_content_text').hide().prop('required', false);
        }
    });

    // --- Initial State ---
    updateFormVisibility();
    updateContentVisibility();
    $('#present_count').val(totalStudents); // Initial value
});
</script>

<?php
mysqli_close($link);
require_once "../includes/footer.php";
?>
