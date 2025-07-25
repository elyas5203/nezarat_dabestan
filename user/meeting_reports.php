<?php
session_start();
require_once "../includes/db.php";
require_once "../includes/functions.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../index.php");
    exit;
}

$meeting_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($meeting_id === 0) {
    header("location: manage_parent_meetings.php");
    exit;
}

$link = get_db_connection();
$user_id = $_SESSION['id'];

// Fetch meeting details to get the checklist_template_id
$sql_meeting = "SELECT pm.id, pm.meeting_date, c.class_name, pm.checklist_template_id
                FROM parent_meetings pm
                JOIN classes c ON pm.class_id = c.id
                WHERE pm.id = ?";
$stmt_meeting = mysqli_prepare($link, $sql_meeting);
mysqli_stmt_bind_param($stmt_meeting, "i", $meeting_id);
mysqli_stmt_execute($stmt_meeting);
$meeting_result = mysqli_stmt_get_result($stmt_meeting);
$meeting = mysqli_fetch_assoc($meeting_result);

if (!$meeting || !$meeting['checklist_template_id']) {
    // No meeting found or no checklist assigned
    require_once "../includes/header.php";
    echo "<div class='page-content'><div class='alert alert-warning'>جلسه یافت نشد یا هیچ چک‌لیستی برای این جلسه تعیین نشده است.</div> <a href='manage_parent_meetings.php' class='btn btn-secondary'>بازگشت</a></div>";
    require_once "../includes/footer.php";
    exit;
}

// Handle checklist update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_checklist'])) {
    $checklist_items = $_POST['checklist_items'] ?? [];

    $sql_upsert = "INSERT INTO meeting_checklists (meeting_id, meeting_type, template_item_id, is_completed, completed_by, completed_at)
                   VALUES (?, 'parent', ?, ?, ?, NOW())
                   ON DUPLICATE KEY UPDATE
                   is_completed = VALUES(is_completed), completed_by = VALUES(completed_by), completed_at = VALUES(completed_at)";
    $stmt_upsert = mysqli_prepare($link, $sql_upsert);

    foreach ($checklist_items as $item_id => $status) {
        $is_completed = intval($status);
        mysqli_stmt_bind_param($stmt_upsert, "iiii", $meeting_id, $item_id, $is_completed, $user_id);
        mysqli_stmt_execute($stmt_upsert);
    }

    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
}


// Fetch checklist items and their status
$checklist_items = [];
$sql_items = "
    SELECT
        ti.id, ti.item_text, ti.is_required,
        mc.is_completed, mc.completed_at, u.username as completed_by_username
    FROM checklist_template_items ti
    LEFT JOIN meeting_checklists mc ON ti.id = mc.template_item_id AND mc.meeting_id = ? AND mc.meeting_type = 'parent'
    LEFT JOIN users u ON mc.completed_by = u.id
    WHERE ti.template_id = ?
    ORDER BY ti.item_order ASC, ti.id ASC
";
$stmt_items = mysqli_prepare($link, $sql_items);
mysqli_stmt_bind_param($stmt_items, "ii", $meeting_id, $meeting['checklist_template_id']);
mysqli_stmt_execute($stmt_items);
$items_result = mysqli_stmt_get_result($stmt_items);
if ($items_result) {
    $checklist_items = mysqli_fetch_all($items_result, MYSQLI_ASSOC);
}


require_once "../includes/header.php";
?>

<div class="page-content">
    <a href="manage_parent_meetings.php" class="btn btn-secondary mb-3">&larr; بازگشت به لیست جلسات</a>
    <h2>چک‌لیست جلسه کلاس: <?php echo htmlspecialchars($meeting['class_name']); ?></h2>
    <p>تاریخ جلسه: <?php echo htmlspecialchars($meeting['meeting_date']); ?></p>

    <div class="card">
        <div class="card-header"><h3>آیتم‌های چک‌لیست</h3></div>
        <div class="card-body">
            <form action="" method="post">
                <ul class="list-group">
                    <?php foreach ($checklist_items as $item): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <input type="hidden" name="checklist_items[<?php echo $item['id']; ?>]" value="0"> <!-- Default value -->
                                <input class="form-check-input me-2" type="checkbox" name="checklist_items[<?php echo $item['id']; ?>]" value="1" id="item-<?php echo $item['id']; ?>" <?php if ($item['is_completed']) echo 'checked'; ?>>
                                <label class="form-check-label" for="item-<?php echo $item['id']; ?>">
                                    <?php echo htmlspecialchars($item['item_text']); ?>
                                    <?php if ($item['is_required']): ?>
                                        <span class="text-danger">*</span>
                                    <?php endif; ?>
                                </label>
                            </div>
                            <?php if ($item['is_completed']): ?>
                                <small class="text-muted">
                                    تکمیل توسط: <?php echo htmlspecialchars($item['completed_by_username']); ?> در <?php echo htmlspecialchars($item['completed_at']); ?>
                                </small>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <button type="submit" name="update_checklist" class="btn btn-primary mt-3">ذخیره وضعیت چک‌لیست</button>
            </form>
        </div>
    </div>
</div>

<?php
mysqli_close($link);
require_once "../includes/footer.php";
?>
