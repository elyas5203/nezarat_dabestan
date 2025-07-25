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
            $ids_to_delete_str = implode(',', array_map('intval', $ids_to_delete));

            // --- FIX for role_permissions ---
            $sql_delete_conflicts_roles = "DELETE rp_del FROM role_permissions AS rp_del
                                           JOIN role_permissions AS rp_keep ON rp_del.role_id = rp_keep.role_id
                                           WHERE rp_del.permission_id IN ($ids_to_delete_str)
                                           AND rp_keep.permission_id = $id_to_keep";
            if (mysqli_query($link, $sql_delete_conflicts_roles)) {
                $messages[] = "حذف دسترسی‌های متعارض از `role_permissions` برای '{$name}' انجام شد.";
            }

            $sql_update_roles = "UPDATE role_permissions SET permission_id = $id_to_keep WHERE permission_id IN ($ids_to_delete_str)";
            if (!mysqli_query($link, $sql_update_roles)) {
                 $messages[] = "خطا در بروزرسانی `role_permissions` برای '{$name}': " . mysqli_error($link);
            }

            // --- FIX for user_permissions ---
            $sql_delete_conflicts_users = "DELETE up_del FROM user_permissions AS up_del
                                           JOIN user_permissions AS up_keep ON up_del.user_id = up_keep.user_id
                                           WHERE up_del.permission_id IN ($ids_to_delete_str)
                                           AND up_keep.permission_id = $id_to_keep";
            if (mysqli_query($link, $sql_delete_conflicts_users)) {
                $messages[] = "حذف دسترسی‌های متعارض از `user_permissions` برای '{$name}' انجام شد.";
            }

            $sql_update_users = "UPDATE user_permissions SET permission_id = $id_to_keep WHERE permission_id IN ($ids_to_delete_str)";
            if (!mysqli_query($link, $sql_update_users)) {
                $messages[] = "خطا در بروزرسانی `user_permissions` برای '{$name}': " . mysqli_error($link);
            }

            // --- Finally, delete the master permission records ---
            $sql_delete_perms = "DELETE FROM permissions WHERE id IN ($ids_to_delete_str)";
            if (mysqli_query($link, $sql_delete_perms)) {
                $messages[] = "دسترسی‌های تکراری اصلی برای '{$name}' با موفقیت حذف شدند.";
            } else {
                $messages[] = "خطا در حذف دسترسی‌های اصلی برای '{$name}': " . mysqli_error($link);
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
