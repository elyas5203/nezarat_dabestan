<?php
session_start();
require_once "includes/db_singleton.php";
require_once "includes/functions.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.php");
    exit;
}

$link = get_db_connection();
$query = isset($_GET['q']) ? mysqli_real_escape_string($link, trim($_GET['q'])) : '';
$results = [];
$suggestions = [];

if (!empty($query)) {
    // A simple search across a few tables. This can be expanded.
    // Search in users
    $sql_users = "SELECT id, first_name, last_name FROM users WHERE CONCAT(first_name, ' ', last_name) LIKE '%$query%' OR username LIKE '%$query%'";
    $res_users = mysqli_query($link, $sql_users);
    while($row = mysqli_fetch_assoc($res_users)){
        $results[] = [
            'title' => 'کاربر: ' . htmlspecialchars($row['first_name'] . ' ' . $row['last_name']),
            'link' => 'admin/edit_user.php?id=' . $row['id'],
            'type' => 'user'
        ];
    }

    // Search in classes
    $sql_classes = "SELECT id, class_name FROM classes WHERE class_name LIKE '%$query%'";
    $res_classes = mysqli_query($link, $sql_classes);
    while($row = mysqli_fetch_assoc($res_classes)){
        $results[] = [
            'title' => 'کلاس: ' . htmlspecialchars($row['class_name']),
            'link' => 'admin/edit_class.php?id=' . $row['id'],
            'type' => 'class'
        ];
    }

    // ... add more search queries for other sections ...

}

if (empty($results) && !empty($query)) {
    // If no results, provide suggestions
    $suggestions = [
        ['title' => 'داشبورد مدیریت', 'link' => 'admin/index.php'],
        ['title' => 'مدیریت کاربران', 'link' => 'admin/manage_users.php'],
        ['title' => 'مدیریت کلاس‌ها', 'link' => 'admin/manage_classes.php'],
        ['title' => 'تیکت‌های من', 'link' => 'user/my_tickets.php'],
    ];
}


require_once "includes/header.php";
?>

<div class="page-content">
    <h2>نتایج جستجو برای: "<?php echo htmlspecialchars($query); ?>"</h2>

    <?php if (!empty($results)): ?>
        <ul class="list-group">
            <?php foreach ($results as $result): ?>
                <li class="list-group-item">
                    <a href="<?php echo htmlspecialchars($result['link']); ?>"><?php echo $result['title']; ?></a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <div class="alert alert-warning">
            <h4>موردی یافت نشد!</h4>
            <p>متاسفانه، هیچ نتیجه‌ای برای جستجوی شما پیدا نشد.</p>
            <hr>
            <p><strong>شاید دنبال این بخش‌ها می‌گردید:</strong></p>
            <ul>
                <?php foreach ($suggestions as $suggestion): ?>
                    <li><a href="<?php echo htmlspecialchars($suggestion['link']); ?>"><?php echo $suggestion['title']; ?></a></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
</div>

<?php
mysqli_close($link);
require_once "includes/footer.php";
?>
