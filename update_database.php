<?php
session_start();
require_once "includes/db_singleton.php";
require_once "includes/functions.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !$_SESSION['is_admin']) {
    header("location: index.php");
    exit;
}

$link = get_db_connection();
$messages = [];

// --- 1. Clean up duplicate permissions ---
$messages[] = "<strong>--- پاکسازی دسترسی‌های تکراری ---</strong>";
$sql_find_duplicates = "SELECT permission_name, MIN(id) as id_to_keep, GROUP_CONCAT(id) as all_ids, COUNT(*) as count
                        FROM permissions
                        GROUP BY permission_name
                        HAVING COUNT(*) > 1";

$result_duplicates = mysqli_query($link, $sql_find_duplicates);

if ($result_duplicates && mysqli_num_rows($result_duplicates) > 0) {
    while ($row = mysqli_fetch_assoc($result_duplicates)) {
        $name = $row['permission_name'];
        $id_to_keep = $row['id_to_keep'];
        $ids_to_delete = explode(',', $row['all_ids']);

        // Remove the id to keep from the list of ids to delete
        if (($key = array_search($id_to_keep, $ids_to_delete)) !== false) {
            unset($ids_to_delete[$key]);
        }

        if (!empty($ids_to_delete)) {
            $ids_to_delete_str = implode(',', $ids_to_delete);

            // Update foreign keys before deleting
            mysqli_query($link, "UPDATE role_permissions SET permission_id = $id_to_keep WHERE permission_id IN ($ids_to_delete_str)");
            mysqli_query($link, "UPDATE user_permissions SET permission_id = $id_to_keep WHERE permission_id IN ($ids_to_delete_str)");

            // Delete the duplicate permissions
            $sql_delete = "DELETE FROM permissions WHERE id IN ($ids_to_delete_str)";
            if (mysqli_query($link, $sql_delete)) {
                $messages[] = "دسترسی تکراری '{$name}' پاکسازی شد. شناسه اصلی: {$id_to_keep}.";
            } else {
                $messages[] = "خطا در حذف دسترسی تکراری '{$name}': " . mysqli_error($link);
            }
        }
    }
} else {
    $messages[] = "هیچ دسترسی تکراری یافت نشد.";
}


// --- 2. Add 'image_path' to 'inventory_items' if not exists ---
$messages[] = "<br><strong>--- بررسی جدول انبار ---</strong>";
$sql_check_column = "SHOW COLUMNS FROM `inventory_items` LIKE 'image_path'";
$result_check_column = mysqli_query($link, $sql_check_column);
if (mysqli_num_rows($result_check_column) == 0) {
    $sql_add_column = "ALTER TABLE `inventory_items` ADD `image_path` VARCHAR(255) NULL DEFAULT NULL AFTER `is_rentable`";
    if (mysqli_query($link, $sql_add_column)) {
        $messages[] = "ستون 'image_path' با موفقیت به جدول 'inventory_items' اضافه شد.";
    } else {
        $messages[] = "خطا در افزودن ستون 'image_path': " . mysqli_error($link);
    }
} else {
    $messages[] = "ستون 'image_path' در جدول 'inventory_items' از قبل وجود دارد.";
}


require_once "includes/header.php";
?>

<div class="page-content">
    <h2>اسکریپت به‌روزرسانی دیتابیس</h2>
    <p>این اسکریپت برای اعمال تغییرات و اصلاحات لازم در ساختار دیتابیس اجرا می‌شود.</p>

    <div class="alert alert-info">
        <strong>گزارش اجرا:</strong><br>
        <?php
        foreach ($messages as $message) {
            echo $message . "<br>";
        }
        ?>
    </div>

    <a href="admin/index.php" class="btn btn-primary">بازگشت به داشبورد مدیریت</a>
</div>

<?php
mysqli_close($link);
require_once "includes/footer.php";
?>
