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

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">داشبورد اصلی</h1>
</div>

<p>به مرکز فرماندهی دستیار هوشمند خود خوش آمدید.</p>

<div class="row">
    <div class="col-md-6 mb-3">
        <div class="card text-center">
            <div class="card-body">
                <h5 class="card-title">تعداد رقبا</h5>
                <p class="card-text fs-1"><?php echo $total_competitors; ?></p>
                <a href="manage_competitors.php" class="btn btn-primary">مدیریت رقبا</a>
            </div>
        </div>
    </div>
    <div class="col-md-6 mb-3">
        <div class="card text-center">
            <div class="card-body">
                <h5 class="card-title">تعداد تحلیل ها</h5>
                <p class="card-text fs-1"><?php echo $total_analyses; ?></p>
                <a href="view_analyses.php" class="btn btn-success">مشاهده تحلیل ها</a>
            </div>
        </div>
    </div>
</div>

<?php require_once 'dashboard_footer.php'; ?>
