<?php
session_start();
require_once "../includes/db_singleton.php";
require_once "../includes/functions.php";
require_once "../includes/access_control.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !has_permission('manage_roles')) {
    header("location: ../index.php");
    exit;
}

if (!isset($_GET['role_id']) || empty($_GET['role_id'])) {
    header("location: manage_roles.php");
    exit;
}

$link = get_db_connection();
$role_id = $_GET['role_id'];
$err = $success_msg = "";

// Fetch role details
$role_query = mysqli_query($link, "SELECT role_name FROM roles WHERE id = $role_id");
if(mysqli_num_rows($role_query) == 0){
    echo "نقش یافت نشد."; exit;
}
$role = mysqli_fetch_assoc($role_query);


// Handle Update POST Request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_permissions'])) {
    $new_permissions = $_POST['permissions'] ?? [];

    mysqli_begin_transaction($link);
    try {
        // Delete old permissions for this role
        mysqli_query($link, "DELETE FROM role_permissions WHERE role_id = $role_id");

        // Insert new ones
        if(!empty($new_permissions)){
            $sql_insert_perms = "INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)";
            $stmt_insert_perms = mysqli_prepare($link, $sql_insert_perms);
            foreach($new_permissions as $perm_id){
                mysqli_stmt_bind_param($stmt_insert_perms, "ii", $role_id, $perm_id);
                mysqli_stmt_execute($stmt_insert_perms);
            }
            mysqli_stmt_close($stmt_insert_perms);
        }

        mysqli_commit($link);
        $success_msg = "دسترسی‌ها با موفقیت به‌روزرسانی شد.";
    } catch (Exception $e) {
        mysqli_rollback($link);
        $err = "خطا در به‌روزرسانی دسترسی‌ها.";
    }
}


// Fetch all permissions, grouped by a logical category
$all_permissions_query = mysqli_query($link, "SELECT id, permission_name, permission_description FROM permissions ORDER BY permission_name ASC");
$all_permissions = mysqli_fetch_all($all_permissions_query, MYSQLI_ASSOC);

// Fetch permissions currently assigned to this role
$current_permissions_query = mysqli_query($link, "SELECT permission_id FROM role_permissions WHERE role_id = $role_id");
$current_permissions = array_column(mysqli_fetch_all($current_permissions_query, MYSQLI_ASSOC), 'permission_id');

// Group permissions by category (e.g., 'manage_', 'view_', 'submit_')
$grouped_permissions = [];
foreach ($all_permissions as $perm) {
    $key = 'عمومی'; // Default group
    if (strpos($perm['permission_name'], 'manage_') === 0) {
        $key = 'مدیریت';
    } elseif (strpos($perm['permission_name'], 'view_') === 0) {
        $key = 'مشاهده';
    } elseif (strpos($perm['permission_name'], 'submit_') === 0 || strpos($perm['permission_name'], 'fill_') === 0) {
        $key = 'ارسال و تکمیل';
    }
    $grouped_permissions[$key][] = $perm;
}


require_once "../includes/header.php";
?>
<style>
    .permission-matrix { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 25px; }
    .permission-group { background: #fff; border-radius: 8px; padding: 20px; box-shadow: var(--shadow-sm); }
    .permission-group h4 { margin-top: 0; padding-bottom: 10px; border-bottom: 1px solid #eee;}
    .permission-item { display: flex; align-items: center; margin-bottom: 10px; }
    .permission-item label { margin-right: 10px; display: flex; flex-direction: column; cursor: pointer; }
    .permission-item label small { color: #6c757d; font-size: 0.8rem; }
    .permission-item input[type="checkbox"] { width: 1.2rem; height: 1.2rem; }
</style>

<div class="page-content">
    <a href="manage_roles.php" class="btn btn-secondary" style="margin-bottom: 20px;">&larr; بازگشت به مدیریت نقش‌ها</a>
    <h2>ویرایش دسترسی‌های نقش: <?php echo htmlspecialchars($role['role_name']); ?></h2>

    <?php
    if(!empty($err)){ echo '<div class="alert alert-danger">' . $err . '</div>'; }
    if(!empty($success_msg)){ echo '<div class="alert alert-success">' . $success_msg . '</div>'; }
    ?>

    <form action="edit_role_permissions.php?role_id=<?php echo $role_id; ?>" method="post">
        <div class="permission-matrix">
            <?php foreach($grouped_permissions as $group_name => $permissions): ?>
                <div class="permission-group">
                    <h4><?php echo htmlspecialchars($group_name); ?></h4>
                    <?php foreach($permissions as $perm): ?>
                        <div class="permission-item">
    <input type="checkbox" name="permissions[]" value="<?php echo $perm['id']; ?>" id="perm_<?php echo $perm['id']; ?>"
                                <?php if(in_array($perm['id'], $current_permissions)) echo 'checked'; ?>>
    <label for="perm_<?php echo $perm['id']; ?>">
        <a href="#" class="permission-link" data-permission-name="<?php echo htmlspecialchars($perm['permission_name']); ?>" title="برای اطلاعات بیشتر کلیک کنید">
            <span><?php echo htmlspecialchars($perm['permission_description'] ?: $perm['permission_name']); ?></span>
            <small>(<?php echo htmlspecialchars($perm['permission_name']); ?>)</small>
        </a>
    </label>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="form-group" style="margin-top: 30px;">
            <input type="submit" name="update_permissions" class="btn btn-primary btn-lg" value="ذخیره تغییرات">
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const permissionLinks = document.querySelectorAll('.permission-link');
    permissionLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const permissionName = this.dataset.permissionName;

            // This is a simple mapping. You can make it more sophisticated.
            const pageMap = {
                'manage_users': 'manage_users.php',
                'manage_roles': 'manage_roles.php',
                'manage_classes': 'manage_classes.php',
                'manage_forms': 'manage_forms.php',
                'manage_inventory': 'manage_inventory.php',
                'manage_financials': 'manage_booklets.php', // Or another financial page
                'manage_donations': 'manage_donations.php',
                'manage_recruitment': 'manage_regions.php',
                'manage_meetings': 'manage_parent_meetings.php', // Or another meeting page
                'view_analytics': 'assessment_analysis.php',
                'submit_ticket': '../user/new_ticket.php',
                'fill_self_assessment': '../user/my_self_assessments.php'
            };

            const targetPage = pageMap[permissionName];

            if (targetPage) {
                window.open(targetPage, '_blank');
            } else {
                alert('صفحه مرتبطی برای این دسترسی یافت نشد: ' + permissionName);
            }
        });
    });
});
</script>

<?php
mysqli_close($link);
require_once "../includes/footer.php";
?>
