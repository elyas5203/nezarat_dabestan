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
    // ... (PHP submission logic remains the same)
}

// Fetch all available and rentable inventory items
$items = [];
$sql_items = "SELECT i.id, i.name, i.description, i.quantity, i.image_path, c.name as category_name
              FROM inventory_items i
              LEFT JOIN inventory_categories c ON i.category_id = c.id
              WHERE i.quantity > 0 AND i.is_rentable = 1
              ORDER BY c.name, i.name ASC";
$result_items = mysqli_query($link, $sql_items);
if($result_items) {
    $items = mysqli_fetch_all($result_items, MYSQLI_ASSOC);
}

require_once "../includes/header.php";
?>
<style>
.rental-item-card {
    background: var(--widget-bg);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-md);
    transition: all 0.3s ease;
    display: flex;
    flex-direction: column;
    height: 100%;
}
.rental-item-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-lg);
}
.item-image-container {
    height: 200px;
    background-color: #f0f0f0;
    border-radius: var(--radius-lg) var(--radius-lg) 0 0;
    overflow: hidden;
}
.item-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    cursor: pointer;
}
.item-content {
    padding: 20px;
    flex-grow: 1;
    display: flex;
    flex-direction: column;
}
.item-content h5 {
    font-weight: 700;
    margin-bottom: 5px;
}
.item-content .item-category {
    font-size: 0.8rem;
    color: var(--text-muted);
    margin-bottom: 15px;
}
.item-content .item-description {
    font-size: 0.9rem;
    color: var(--text-color);
    flex-grow: 1;
    margin-bottom: 15px;
}
.item-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-top: 1px solid var(--border-color);
    padding: 15px 20px;
}
.quantity-selector {
    display: flex;
    align-items: center;
}
.quantity-selector button {
    width: 35px;
    height: 35px;
    border: 1px solid var(--border-color);
    background: #fff;
    cursor: pointer;
    font-size: 1.2rem;
}
.quantity-selector input {
    width: 50px;
    height: 35px;
    text-align: center;
    border: 1px solid var(--border-color);
    border-right: none;
    border-left: none;
    padding: 5px;
}
</style>

<div class="page-content">
    <h2>درخواست کرایه لوازم</h2>
    <p class="text-muted">لوازم مورد نیاز خود را انتخاب و درخواست خود را ثبت کنید.</p>

    <?php
    if(!empty($err)){ echo '<div class="alert alert-danger">' . $err . '</div>'; }
    if(!empty($success_msg)){ echo '<div class="alert alert-success">' . $success_msg . '</div>'; }
    ?>

    <form action="rental_items.php" method="post">
        <div class="card mb-4">
            <div class="card-header">
                <h4>لیست لوازم</h4>
            </div>
            <div class="card-body">
                <div class="row rental-items-grid">
                    <?php if (empty($items)): ?>
                        <p class="text-center">در حال حاضر هیچ قلمی برای کرایه موجود نیست.</p>
                    <?php else: ?>
                        <?php foreach ($items as $item): ?>
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="rental-item-card">
                                    <div class="item-image-container">
                                        <a href="<?php echo !empty($item['image_path']) ? '../' . htmlspecialchars($item['image_path']) : '../assets/images/placeholder.png'; ?>">
                                            <img src="<?php echo !empty($item['image_path']) ? '../' . htmlspecialchars($item['image_path']) : '../assets/images/placeholder.png'; ?>"
                                                 alt="<?php echo htmlspecialchars($item['name']); ?>"
                                                 class="item-image">
                                        </a>
                                    </div>
                                    <div class="item-content">
                                        <h5><?php echo htmlspecialchars($item['name']); ?></h5>
                                        <p class="item-category"><?php echo htmlspecialchars($item['category_name'] ?? 'بدون دسته'); ?></p>
                                        <p class="item-description"><?php echo htmlspecialchars($item['description']); ?></p>
                                    </div>
                                    <div class="item-footer">
                                        <div class="quantity-selector">
                                            <button type="button" class="btn-minus" data-item-id="<?php echo $item['id']; ?>">-</button>
                                            <input type="number" name="items[<?php echo $item['id']; ?>]" id="item-<?php echo $item['id']; ?>" value="0" min="0" max="<?php echo $item['quantity']; ?>" readonly>
                                            <button type="button" class="btn-plus" data-item-id="<?php echo $item['id']; ?>">+</button>
                                        </div>
                                        <span class="text-muted">موجودی: <?php echo $item['quantity']; ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header">
                <h4>اطلاعات درخواست</h4>
            </div>
            <div class="card-body">
                 <div class="form-group">
                    <label for="event_date">تاریخ نیاز <span class="text-danger">*</span></label>
                    <input type="text" name="event_date" id="event_date" class="form-control persian-datepicker" required autocomplete="off">
                </div>
                <div class="form-group">
                    <label for="notes">توضیحات اضافی</label>
                    <textarea name="notes" id="notes" class="form-control" rows="3"></textarea>
                </div>
            </div>
        </div>

        <div class="form-group mt-4">
            <button type="submit" name="request_items" class="btn btn-primary btn-lg">ثبت نهایی درخواست</button>
        </div>
    </form>
</div>

<!-- Image Modal -->
<div id="imageModal" class="modal fade" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-body">
        <img src="" id="modalImage" class="img-fluid" style="width: 100%;">
      </div>
    </div>
  </div>
</div>


<script>
document.addEventListener('DOMContentLoaded', function() {
    // Quantity selectors
    document.querySelectorAll('.btn-plus').forEach(button => {
        button.addEventListener('click', function() {
            const itemId = this.dataset.itemId;
            const input = document.getElementById('item-' + itemId);
            let currentValue = parseInt(input.value);
            const max = parseInt(input.max);
            if (currentValue < max) {
                input.value = currentValue + 1;
            }
        });
    });

    document.querySelectorAll('.btn-minus').forEach(button => {
        button.addEventListener('click', function() {
            const itemId = this.dataset.itemId;
            const input = document.getElementById('item-' + itemId);
            let currentValue = parseInt(input.value);
            if (currentValue > 0) {
                input.value = currentValue - 1;
            }
        });
    });
});

function showImageModal(src) {
    document.getElementById('modalImage').src = src;
    var myModal = new bootstrap.Modal(document.getElementById('imageModal'));
    myModal.show();
}
</script>

<?php
require_once "../includes/footer.php";
?>
