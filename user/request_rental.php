<?php
session_start();
require_once "../includes/db.php";
require_once "../includes/functions.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../index.php");
    exit;
}

// Fetch rentable items
$sql = "SELECT id, name, quantity, description, image_path FROM inventory_items WHERE is_rentable = 1 ORDER BY name ASC";
$rentable_items = mysqli_fetch_all(mysqli_query($link, $sql), MYSQLI_ASSOC);

// Handle rental request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_rental_request'])) {
    $event_date = $_POST['event_date'];
    $user_id = $_SESSION['id'];
    $items = $_POST['items'];

    // Basic validation
    if (empty($event_date) || empty($items)) {
        $err = "Please select an event date and at least one item.";
    } else {
        // In a real application, you would create a rental request record and then add the items.
        // For now, we'll just send a notification.
        $item_details = "";
        foreach ($items as $item_id => $quantity) {
            if ($quantity > 0) {
                $item_name_result = mysqli_query($link, "SELECT name FROM inventory_items WHERE id = $item_id");
                $item_name = mysqli_fetch_assoc($item_name_result)['name'];
                $item_details .= "$item_name (x$quantity), ";
            }
        }
        $item_details = rtrim($item_details, ', ');

        $message = "New rental request from " . $_SESSION['username'] . " for event on $event_date. Items: $item_details";
        notify_permission('manage_inventory', $message, '/admin/manage_rentals.php');
        $success_msg = "Your rental request has been submitted successfully.";
    }
}

require_once "../includes/header.php";
?>
<link rel="stylesheet" href="https://unpkg.com/persian-datepicker@1.2.0/dist/css/persian-datepicker.min.css"/>
<style>
    .item-card {
        border: 1px solid #ddd;
        border-radius: 5px;
        padding: 15px;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
    }
    .item-image {
        width: 80px;
        height: 80px;
        object-fit: cover;
        border-radius: 5px;
        margin-right: 15px;
        cursor: pointer;
    }
    .item-details { flex-grow: 1; }
    .quantity-selector { display: flex; align-items: center; }
    .quantity-selector input { width: 50px; text-align: center; }
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgba(0,0,0,0.8);
    }
    .modal-content {
        margin: auto;
        display: block;
        width: 80%;
        max-width: 700px;
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
    }
    .close {
        position: absolute;
        top: 15px;
        right: 35px;
        color: #f1f1f1;
        font-size: 40px;
        font-weight: bold;
        transition: 0.3s;
        cursor: pointer;
    }
</style>

<div class="page-content">
    <h2>درخواست کرایه لوازم</h2>
    <p>تاریخ مراسم خود را انتخاب کرده و لوازم مورد نیاز را به سبد خود اضافه کنید.</p>

    <?php
    if(!empty($err)){ echo '<div class="alert alert-danger">' . $err . '</div>'; }
    if(!empty($success_msg)){ echo '<div class="alert alert-success">' . $success_msg . '</div>'; }
    ?>

    <form method="POST">
        <div class="form-group">
            <label for="event_date_picker">تاریخ مراسم</label>
            <input type="text" id="event_date_picker" class="form-control" autocomplete="off"/>
            <input type="hidden" name="event_date" id="event_date"/>
        </div>

        <hr>
        <h3>لوازم قابل کرایه</h3>
        <div id="item-list">
            <?php foreach ($rentable_items as $item): ?>
                <div class="item-card">
                    <img src="../<?php echo htmlspecialchars($item['image_path'] ?? 'assets/images/placeholder.png'); ?>" class="item-image" onclick="openModal(this.src)">
                    <div class="item-details">
                        <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                        <p><?php echo htmlspecialchars($item['description']); ?></p>
                        <small>موجودی: <?php echo $item['quantity']; ?></small>
                    </div>
                    <div class="quantity-selector">
                        <button type="button" class="btn btn-sm btn-secondary" onclick="changeQuantity(this, -1)">-</button>
                        <input type="number" name="items[<?php echo $item['id']; ?>]" value="0" min="0" max="<?php echo $item['quantity']; ?>" class="form-control" readonly>
                        <button type="button" class="btn btn-sm btn-secondary" onclick="changeQuantity(this, 1)">+</button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <button type="submit" name="submit_rental_request" class="btn btn-primary mt-3">ثبت نهایی درخواست</button>
    </form>
</div>

<!-- The Modal -->
<div id="imageModal" class="modal">
  <span class="close" onclick="closeModal()">&times;</span>
  <img class="modal-content" id="modalImage">
</div>


<script src="https://unpkg.com/persian-date@1.1.0/dist/persian-date.min.js"></script>
<script src="https://unpkg.com/persian-datepicker@1.2.0/dist/js/persian-datepicker.min.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        $("#event_date_picker").pDatepicker({
            format: 'YYYY/MM/DD',
            altField: '#event_date',
            altFormat: 'YYYY-MM-DD',
            observer: true,
            autoClose: true
        });
    });

    function changeQuantity(btn, amount) {
        const input = btn.parentElement.querySelector('input');
        let newValue = parseInt(input.value) + amount;
        if (newValue < 0) newValue = 0;
        if (newValue > parseInt(input.max)) newValue = parseInt(input.max);
        input.value = newValue;
    }

    function openModal(src) {
        document.getElementById('imageModal').style.display = "block";
        document.getElementById('modalImage').src = src;
    }

    function closeModal() {
        document.getElementById('imageModal').style.display = "none";
    }
</script>

<?php require_once "../includes/footer.php"; ?>
