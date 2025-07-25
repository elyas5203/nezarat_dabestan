<?php
session_start();
require_once "../includes/db.php";
require_once "../includes/functions.php";

if (!isset($_SESSION["loggedin"]) || !$_SESSION["loggedin"] || !is_admin()) {
    header("location: ../index.php");
    exit;
}

$template_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($template_id === 0) {
    header("location: manage_checklist_templates.php");
    exit;
}

$link = get_db_connection();

// Fetch template details
$sql_template = "SELECT * FROM checklist_templates WHERE id = ?";
$stmt_template = mysqli_prepare($link, $sql_template);
mysqli_stmt_bind_param($stmt_template, "i", $template_id);
mysqli_stmt_execute($stmt_template);
$template_result = mysqli_stmt_get_result($stmt_template);
$template = mysqli_fetch_assoc($template_result);
if (!$template) {
    die("قالب یافت نشد.");
}

// Handle POST requests (Add/Update/Delete items)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Add new item
    if (isset($_POST['add_item']) && !empty($_POST['item_text'])) {
        $item_text = trim($_POST['item_text']);
        $sql_add = "INSERT INTO checklist_template_items (template_id, item_text) VALUES (?, ?)";
        $stmt_add = mysqli_prepare($link, $sql_add);
        mysqli_stmt_bind_param($stmt_add, "is", $template_id, $item_text);
        mysqli_stmt_execute($stmt_add);
    }

    // Update existing items
    if (isset($_POST['update_items'])) {
        $items = $_POST['items'] ?? [];
        $sql_update = "UPDATE checklist_template_items SET item_text = ?, item_order = ?, is_required = ?, reminder_frequency_hours = ? WHERE id = ? AND template_id = ?";
        $stmt_update = mysqli_prepare($link, $sql_update);
        foreach ($items as $item_id => $details) {
            $text = trim($details['text']);
            $order = intval($details['order']);
            $is_required = isset($details['required']) ? 1 : 0;
            $reminder = !empty($details['reminder']) ? intval($details['reminder']) : null;
            mysqli_stmt_bind_param($stmt_update, "siiiii", $text, $order, $is_required, $reminder, $item_id, $template_id);
            mysqli_stmt_execute($stmt_update);
        }
    }

    // Delete item
    if (isset($_POST['delete_item'])) {
        $item_to_delete = intval($_POST['delete_item']);
        $sql_delete = "DELETE FROM checklist_template_items WHERE id = ? AND template_id = ?";
        $stmt_delete = mysqli_prepare($link, $sql_delete);
        mysqli_stmt_bind_param($stmt_delete, "ii", $item_to_delete, $template_id);
        mysqli_stmt_execute($stmt_delete);
    }

    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
}


// Fetch all items for this template
$items = [];
$sql_items = "SELECT * FROM checklist_template_items WHERE template_id = ? ORDER BY item_order ASC, id ASC";
$stmt_items = mysqli_prepare($link, $sql_items);
mysqli_stmt_bind_param($stmt_items, "i", $template_id);
mysqli_stmt_execute($stmt_items);
$result_items = mysqli_stmt_get_result($stmt_items);
if ($result_items) {
    $items = mysqli_fetch_all($result_items, MYSQLI_ASSOC);
}


require_once "../includes/header.php";
?>

<div class="page-content">
    <a href="manage_checklist_templates.php" class="btn btn-secondary mb-3">&larr; بازگشت به لیست قالب‌ها</a>
    <h2>ویرایش قالب چک‌لیست: <?php echo htmlspecialchars($template['template_name']); ?></h2>

    <div class="card mb-4">
        <div class="card-header"><h3>آیتم‌های چک‌لیست</h3></div>
        <div class="card-body">
            <form action="" method="post">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ترتیب</th>
                            <th>متن آیتم</th>
                            <th class="text-center">یادآور (ساعت)</th>
                            <th class="text-center">ضروری؟</th>
                            <th>عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td><input type="number" name="items[<?php echo $item['id']; ?>][order]" value="<?php echo $item['item_order']; ?>" class="form-control" style="width: 70px;"></td>
                            <td><input type="text" name="items[<?php echo $item['id']; ?>][text]" value="<?php echo htmlspecialchars($item['item_text']); ?>" class="form-control"></td>
                            <td><input type="number" name="items[<?php echo $item['id']; ?>][reminder]" value="<?php echo $item['reminder_frequency_hours']; ?>" class="form-control" style="width: 90px;" placeholder="مثلا 24"></td>
                            <td class="text-center"><input type="checkbox" name="items[<?php echo $item['id']; ?>][required]" value="1" <?php if ($item['is_required']) echo 'checked'; ?>></td>
                            <td>
                                <button type="submit" name="delete_item" value="<?php echo $item['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('آیا از حذف این آیتم مطمئن هستید؟')">حذف</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <button type="submit" name="update_items" class="btn btn-success">ذخیره تمام تغییرات</button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3>افزودن آیتم جدید</h3></div>
        <div class="card-body">
            <form action="" method="post">
                <div class="form-group">
                    <label for="item_text">متن آیتم جدید</label>
                    <input type="text" name="item_text" class="form-control" required>
                </div>
                <button type="submit" name="add_item" class="btn btn-primary">افزودن آیتم</button>
            </form>
        </div>
    </div>
</div>

<?php
mysqli_close($link);
require_once "../includes/footer.php";
?>
