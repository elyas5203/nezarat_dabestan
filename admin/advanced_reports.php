<?php
session_start();
require_once "../includes/db.php";
require_once "../includes/functions.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !$_SESSION["is_admin"]) {
    header("location: ../index.php");
    exit;
}

$report_type = $_GET['report_type'] ?? '';
$results = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($report_type)) {
    if ($report_type === 'self_assessment') {
        $date_from = $_POST['date_from'];
        $date_to = $_POST['date_to'];
        $class_id = $_POST['class_id'];

        $sql = "SELECT sa.*, u.username, c.class_name
                FROM self_assessments sa
                JOIN users u ON sa.user_id = u.id
                JOIN classes c ON sa.class_id = c.id
                WHERE 1=1";
        if (!empty($date_from)) $sql .= " AND sa.meeting_date >= '$date_from'";
        if (!empty($date_to)) $sql .= " AND sa.meeting_date <= '$date_to'";
        if (!empty($class_id)) $sql .= " AND sa.class_id = $class_id";

        $result = mysqli_query($link, $sql);
        $results = mysqli_fetch_all($result, MYSQLI_ASSOC);
    }
}

// Fetch classes for filter
$classes_result = mysqli_query($link, "SELECT id, class_name FROM classes ORDER BY class_name");

require_once "../includes/header.php";
?>

<div class="page-content">
    <h2>گزارش‌گیری پیشرفته</h2>

    <div class="card mb-4">
        <div class="card-header">
            <ul class="nav nav-tabs card-header-tabs">
                <li class="nav-item">
                    <a class="nav-link <?php echo $report_type === 'self_assessment' ? 'active' : ''; ?>" href="?report_type=self_assessment">گزارش خوداظهاری</a>
                </li>
                <!-- Add other report types here -->
            </ul>
        </div>
        <div class="card-body">
            <?php if ($report_type === 'self_assessment'): ?>
                <form method="POST" action="?report_type=self_assessment">
                    <div class="row">
                        <div class="col-md-4">
                            <label for="date_from">از تاریخ:</label>
                            <input type="date" name="date_from" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label for="date_to">تا تاریخ:</label>
                            <input type="date" name="date_to" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label for="class_id">کلاس:</label>
                            <select name="class_id" class="form-control">
                                <option value="">همه کلاس‌ها</option>
                                <?php while ($class = mysqli_fetch_assoc($classes_result)): ?>
                                    <option value="<?php echo $class['id']; ?>"><?php echo htmlspecialchars($class['class_name']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary mt-3">ایجاد گزارش</button>
                </form>

                <?php if (!empty($results)): ?>
                    <hr>
                    <h4>نتایج گزارش</h4>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>کلاس</th>
                                <th>مدرس</th>
                                <th>تاریخ جلسه</th>
                                <th>امتیاز</th>
                                <th>مشاهده جزئیات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($results as $row): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['class_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['username']); ?></td>
                                    <td><?php echo to_persian_date($row['meeting_date']); ?></td>
                                    <td><?php echo htmlspecialchars($row['score']); ?></td>
                                    <td><a href="view_submission_details.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-info">مشاهده</a></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
                    <p class="mt-3">هیچ نتیجه‌ای یافت نشد.</p>
                <?php endif; ?>

            <?php else: ?>
                <p>لطفاً یک نوع گزارش را از برگه‌های بالا انتخاب کنید.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
mysqli_close($link);
require_once "../includes/footer.php";
?>
