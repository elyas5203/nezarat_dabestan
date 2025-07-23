<?php
session_start();
require_once "../includes/db.php";
require_once "../includes/functions.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../index.php");
    exit;
}

$user_id = $_SESSION['id'];
$err = $success_msg = "";

// Handle Rental Request Submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['request_items'])) {
    $requested_items = isset($_POST['items']) ? $_POST['items'] : [];
    $event_date = !empty($_POST['event_date']) ? $_POST['event_date'] : null;
    $notes = trim($_POST['notes']);

    if (empty($requested_items)) {
        $err = "شما باید حداقل یک قلم را برای درخواست انتخاب کنید.";
    } elseif (empty($event_date)) {
        $err = "لطفا تاریخ مراسم یا نیاز خود را مشخص کنید.";
    } else {
        // Create a ticket for the rental request
        $ticket_title = "درخواست کرایه لوازم برای تاریخ " . to_persian_date($event_date, 'Y/m/d');

        $message_body = "کاربر: " . $_SESSION['username'] . "\n";
        $message_body .= "تاریخ نیاز: " . to_persian_date($event_date, 'Y/m/d') . "\n";
        $message_body .= "توضیحات: " . $notes . "\n\n";
        $message_body .= "لیست لوازم درخواستی:\n";

        $item_ids_for_query = array_keys($requested_items);
        $sql_items_info = "SELECT id, name FROM inventory_items WHERE id IN (" . implode(',', $item_ids_for_query) . ")";
        $items_info_result = mysqli_query($link, $sql_items_info);
        $items_db_info = mysqli_fetch_all($items_info_result, MYSQLI_ASSOC);
        $items_map = array_column($items_db_info, 'name', 'id');

        foreach($requested_items as $item_id => $quantity) {
             if (isset($items_map[$item_id])) {
                $message_body .= "- " . htmlspecialchars($items_map[$item_id]) . " (تعداد: " . htmlspecialchars($quantity) . ")\n";
             }
        }

        // Find users with 'manage_inventory' permission to assign the ticket
        $assign_to_user_id = null; // Assign to admin by default
        $admin_user_query = mysqli_query($link, "SELECT id FROM users WHERE is_admin = 1 LIMIT 1");
        if(mysqli_num_rows($admin_user_query) > 0) {
            $assign_to_user_id = mysqli_fetch_assoc($admin_user_query)['id'];
        }

        $sql_ticket = "INSERT INTO tickets (title, message, user_id, assigned_to_user_id, status, priority, created_at) VALUES (?, ?, ?, ?, 'open', 'high', NOW())";
        if($stmt = mysqli_prepare($link, $sql_ticket)){
            mysqli_stmt_bind_param($stmt, "ssii", $ticket_title, $message_body, $user_id, $assign_to_user_id);
            if(mysqli_stmt_execute($stmt)){
                $ticket_id = mysqli_insert_id($link);
                $success_msg = "درخواست شما با موفقیت در قالب تیکت شماره $ticket_id ثبت شد. نتیجه از طریق همین تیکت به شما اطلاع داده خواهد شد.";
                // Optionally, send a notification
                if($assign_to_user_id) {
                    $notif_msg = "یک درخواست کرایه جدید ثبت شد.";
                    $notif_link = "user/view_ticket.php?id=$ticket_id";
                    $sql_notif = "INSERT INTO notifications (user_id, message, link, type) VALUES (?, ?, ?, 'ticket')";
                    if($stmt_notif = mysqli_prepare($link, $sql_notif)){
                        mysqli_stmt_bind_param($stmt_notif, "iss", $assign_to_user_id, $notif_msg, $notif_link);
                        mysqli_stmt_execute($stmt_notif);
                    }
                }
            } else {
                $err = "خطا در ثبت درخواست. لطفا دوباره تلاش کنید.";
            }
        }
    }
}

// Fetch all available and rentable inventory items
$items = [];
$sql_items = "SELECT i.id, i.name, i.description, i.quantity, c.name as category_name
              FROM inventory_items i
              LEFT JOIN inventory_categories c ON i.category_id = c.id
              WHERE i.quantity > 0 AND i.is_rentable = 1
              ORDER BY c.name, i.name ASC";
if($result_items = mysqli_query($link, $sql_items)){
    $items = mysqli_fetch_all($result_items, MYSQLI_ASSOC);
}

require_once "../includes/header.php";
?>
<style>
    .item-card { border: 1px solid #ddd; border-radius: 8px; padding: 15px; margin-bottom: 15px; }
</style>

<div class="page-content">
    <h2>درخواست کرایه لوازم</h2>
    <p>لیست لوازم قابل کرایه را مشاهده و موارد مورد نیاز خود را به همراه تعداد انتخاب کنید. درخواست شما برای مسئول مربوطه ارسال خواهد شد.</p>

    <?php
    if(!empty($err)){ echo '<div class="alert alert-danger">' . $err . '</div>'; }
    if(!empty($success_msg)){ echo '<div class="alert alert-success">' . $success_msg . '</div>'; }
    ?>

    <form action="rental_items.php" method="post">
        <div class="card">
            <div class="card-header">
                <h4>انتخاب لوازم</h4>
            </div>
            <div class="card-body">
                <?php if (empty($items)): ?>
                    <p>در حال حاضر هیچ قلمی برای کرایه موجود نیست.</p>
                <?php else: ?>
                    <div class="row">
                    <?php foreach ($items as $item): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="item-card">
                                <h5><?php echo htmlspecialchars($item['name']); ?></h5>
                                <p class="text-muted"><?php echo htmlspecialchars($item['category_name'] ?? 'بدون دسته'); ?></p>
                                <p><?php echo htmlspecialchars($item['description']); ?></p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span>موجودی: <?php echo htmlspecialchars($item['quantity']); ?></span>
                                    <div class="input-group" style="width: 120px;">
                                        <span class="input-group-text">تعداد</span>
                                        <input type="number" name="items[<?php echo $item['id']; ?>]" class="form-control" min="0" max="<?php echo $item['quantity']; ?>" placeholder="0">
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header">
                <h4>اطلاعات درخواست</h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="event_date">تاریخ نیاز <span class="text-danger">*</span></label>
                            <input type="date" name="event_date" id="event_date" class="form-control" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="notes">توضیحات اضافی</label>
                            <textarea name="notes" id="notes" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-group mt-4">
            <button type="submit" name="request_items" class="btn btn-primary btn-lg">ثبت نهایی درخواست</button>
        </div>
    </form>
</div>

<?php
mysqli_close($link);
require_once "../includes/footer.php";
?>
