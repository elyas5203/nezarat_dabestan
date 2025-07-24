<?php
session_start();
require_once "includes/db.php";
require_once "includes/functions.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.php");
    exit;
}

$query = isset($_GET['q']) ? mysqli_real_escape_string($link, $_GET['q']) : '';
$results = [
    'users' => [],
    'classes' => [],
    'forms' => []
];

if (!empty($query)) {
    // Search Users
    $sql_users = "SELECT id, username, first_name, last_name FROM users WHERE username LIKE ? OR first_name LIKE ? OR last_name LIKE ?";
    if ($stmt = mysqli_prepare($link, $sql_users)) {
        $search_term = "%" . $query . "%";
        mysqli_stmt_bind_param($stmt, "sss", $search_term, $search_term, $search_term);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $results['users'] = mysqli_fetch_all($result, MYSQLI_ASSOC);
        mysqli_stmt_close($stmt);
    }

    // Search Classes
    $sql_classes = "SELECT id, class_name, description FROM classes WHERE class_name LIKE ? OR description LIKE ?";
    if ($stmt = mysqli_prepare($link, $sql_classes)) {
        $search_term = "%" . $query . "%";
        mysqli_stmt_bind_param($stmt, "ss", $search_term, $search_term);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $results['classes'] = mysqli_fetch_all($result, MYSQLI_ASSOC);
        mysqli_stmt_close($stmt);
    }

    // Search Forms
    $sql_forms = "SELECT id, form_name, form_description FROM forms WHERE form_name LIKE ? OR form_description LIKE ?";
    if ($stmt = mysqli_prepare($link, $sql_forms)) {
        $search_term = "%" . $query . "%";
        mysqli_stmt_bind_param($stmt, "ss", $search_term, $search_term);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $results['forms'] = mysqli_fetch_all($result, MYSQLI_ASSOC);
        mysqli_stmt_close($stmt);
    }
}

require_once "includes/header.php";
?>

<div class="page-content">
    <h2>نتایج جستجو برای: "<?php echo htmlspecialchars($query); ?>"</h2>

    <div class="search-results">
        <!-- User Results -->
        <div class="card">
            <div class="card-header"><h4>کاربران</h4></div>
            <div class="card-body">
                <?php if (!empty($results['users'])): ?>
                    <ul>
                        <?php foreach ($results['users'] as $user): ?>
                            <li><a href="admin/edit_user.php?id=<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name'] . ' (' . $user['username'] . ')'); ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p>هیچ کاربری یافت نشد.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Class Results -->
        <div class="card mt-4">
            <div class="card-header"><h4>کلاس‌ها</h4></div>
            <div class="card-body">
                <?php if (!empty($results['classes'])): ?>
                    <ul>
                        <?php foreach ($results['classes'] as $class): ?>
                            <li><a href="admin/edit_class.php?id=<?php echo $class['id']; ?>"><?php echo htmlspecialchars($class['class_name']); ?></a>: <?php echo htmlspecialchars($class['description']); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p>هیچ کلاسی یافت نشد.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Form Results -->
        <div class="card mt-4">
            <div class="card-header"><h4>فرم‌ها</h4></div>
            <div class="card-body">
                <?php if (!empty($results['forms'])): ?>
                    <ul>
                        <?php foreach ($results['forms'] as $form): ?>
                            <li><a href="admin/design_form.php?form_id=<?php echo $form['id']; ?>"><?php echo htmlspecialchars($form['form_name']); ?></a>: <?php echo htmlspecialchars($form['form_description']); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p>هیچ فرمی یافت نشد.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
mysqli_close($link);
require_once "includes/footer.php";
?>
