<?php
require_once 'dashboard_header.php';
require_once '../app/config.php';

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch stats
$total_competitors = $conn->query("SELECT COUNT(*) as count FROM competitors")->fetch_assoc()['count'];
$total_analyses = $conn->query("SELECT COUNT(*) as count FROM analyses")->fetch_assoc()['count'];

$conn->close();
?>

<h1>داشبورد اصلی</h1>
<p>به مرکز فرماندهی دستیار هوشمند خود خوش آمدید.</p>

<div class="stats-container">
    <div class="stat-card">
        <h3>تعداد رقبا</h3>
        <p><?php echo $total_competitors; ?></p>
    </div>
    <div class="stat-card">
        <h3>تعداد تحلیل ها</h3>
        <p><?php echo $total_analyses; ?></p>
    </div>
</div>

<style>
    .stats-container { display: flex; gap: 20px; margin-top: 20px; }
    .stat-card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-align: center; flex-grow: 1; }
    .stat-card h3 { margin: 0 0 10px; }
    .stat-card p { font-size: 2em; margin: 0; }
</style>

<?php require_once 'dashboard_footer.php'; ?>
