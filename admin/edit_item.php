<?php
session_start();
require_once "../includes/db.php";
require_once "../includes/access_control.php";

// Check if user is logged in and has permission
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../index.php");
    exit;
}
require_permission('manage_inventory');

$item_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$err = $success_msg = "";
$item = null;

if ($item_id <= 0) {
    header("location: manage_inventory.php");
    exit;
}

// Fetch item details
$sql_item = "SELECT * FROM inventory_items WHERE id = ?";
if ($stmt_item = mysqli_prepare($link, $sql_item)) {
    mysqli_stmt_bind_param($stmt_item, "i", $item_id);
    mysqli_stmt_execute($stmt_item);
    $result_item = mysqli_stmt_get_result($stmt_item);
    if (mysqli_num_rows($result_item) == 1) {
        $item = mysqli_fetch_assoc($result_item);
    } else {
        header("location: manage_inventory.php");
        exit;
    }
    mysqli_stmt_close($stmt_item);
}

// Fetch categories for the dropdown
$categories = [];
$sql_categories = "SELECT id, name FROM inventory_categories ORDER BY name ASC";
if($result = mysqli_query($link, $sql_categories)){
    $categories = mysqli_fetch_all($result, MYSQLI_ASSOC);
}

// Handle Update Item POST Request
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $item_name = trim($_POST['item_name']);
    $description = trim($_POST['description']);
    $quantity = trim($_POST['quantity']);
    $category_id = trim($_POST['category_id']);
    $is_rentable = isset($_POST['is_rentable']) ? 1 : 0;
    $image_path = $item['image_path']; // Keep old image by default

    // Handle image upload
    if (isset($_FILES['item_image']) && $_FILES['item_image']['error'] == 0) {
        $upload_dir = '../uploads/inventory/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        $file_name = uniqid() . '-' . basename($_FILES['item_image']['name']);
        $target_file = $upload_dir . $file_name;

        if (move_uploaded_file($_FILES['item_image']['tmp_name'], $target_file)) {
            // New image uploaded successfully, delete old one if it exists
            if (!empty($item['image_path']) && file_exists('../' . $item['image_path'])) {
                unlink('../' . $item['image_path']);
            }
            $image_path = 'uploads/inventory/' . $file_name;
        } else {
            $err = "خطا در آپلود عکس.";
        }
    } elseif (isset($_POST['remove_image']) && $_POST['remove_image'] == '1') {
        // Handle image removal
        if (!empty($item['image_path']) && file_exists('../' . $item['image_path'])) {
            unlink('../' . $item['image_path']);
        }
        $image_path = null;
    }


    if (empty($err) && (empty($item_name) || !isset($quantity))) {
        $err = "نام و تعداد قلم الزامی است.";
    }

    if (empty($err)) {
        $sql = "UPDATE inventory_items SET name = ?, description = ?, quantity = ?, category_id = ?, is_rentable = ?, image_path = ? WHERE id = ?";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "ssiiisi", $item_name, $description, $quantity, $category_id, $is_rentable, $image_path, $item_id);
            if (mysqli_stmt_execute($stmt)) {
                $success_msg = "قلم با موفقیت به‌روزرسانی شد.";
                // Refresh item data after update
                $stmt_item = mysqli_prepare($link, "SELECT * FROM inventory_items WHERE id = ?");
                mysqli_stmt_bind_param($stmt_item, "i", $item_id);
                mysqli_stmt_execute($stmt_item);
                $item = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_item));
                mysqli_stmt_close($stmt_item);
            } else {
                $err = "خطا در به‌روزرسانی قلم.";
            }
            mysqli_stmt_close($stmt);
        }
    }
}

require_once "../includes/header.php";
?>

<div class="page-content">
    <h2>ویرایش قلم: <?php echo htmlspecialchars($item['name']); ?></h2>

    <?php
    if(!empty($err)){ echo '<div class="alert alert-danger">' . $err . '</div>'; }
    if(!empty($success_msg)){ echo '<div class="alert alert-success">' . $success_msg . '</div>'; }
    ?>

    <div class="form-container">
        <form action="edit_item.php?id=<?php echo $item_id; ?>" method="post">
            <div class="form-group">
                <label for="item_name">نام قلم <span style="color: red;">*</span></label>
                <input type="text" name="item_name" id="item_name" class="form-control" value="<?php echo htmlspecialchars($item['name']); ?>" required>
            </div>
            <div class="form-group">
                <label for="description">توضیحات</label>
                <input type="text" name="description" id="description" class="form-control" value="<?php echo htmlspecialchars($item['description']); ?>">
            </div>
            <div class="form-group">
                <label for="quantity">تعداد موجود <span style="color: red;">*</span></label>
                <input type="number" name="quantity" id="quantity" class="form-control" value="<?php echo htmlspecialchars($item['quantity']); ?>" required min="0">
            </div>
            <div class="form-group">
                <label for="category_id">دسته‌بندی</label>
                <select name="category_id" id="category_id" class="form-control">
                    <option value="">بدون دسته‌بندی</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>" <?php if($item['category_id'] == $category['id']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group form-check">
                <input type="checkbox" name="is_rentable" id="is_rentable" class="form-check-input" <?php if(!empty($item['is_rentable']) && $item['is_rentable']) echo 'checked'; ?>>
                <label for="is_rentable" class="form-check-label">این کالا توسط مدرسان قابل کرایه است</label>
            </div>
            <div class="form-group">
                <label for="item_image">عکس قلم</label>
                <input type="file" name="item_image" id="item_image" class="form-control">
                <?php if (!empty($item['image_path'])): ?>
                    <div class="mt-2">
                        <img src="../<?php echo htmlspecialchars($item['image_path']); ?>" alt="Current Image" style="width: 100px; height: auto; border-radius: 5px;">
                        <label><input type="checkbox" name="remove_image" value="1"> حذف عکس فعلی</label>
                    </div>
                <?php endif; ?>
            </div>
            <div class="form-group">
                <input type="submit" class="btn btn-primary" value="ذخیره تغییرات">
                <a href="manage_inventory.php" class="btn btn-secondary">بازگشت به لیست</a>
            </div>
        </form>
    </div>
</div>

<?php require_once "../includes/footer.php"; ?>
