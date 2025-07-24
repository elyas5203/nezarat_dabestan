<?php
session_start();
require_once "../includes/db.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !$_SESSION["is_admin"]) {
    header("location: ../index.php");
    exit;
}

$err = $success_msg = "";
$booklet_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($booklet_id <= 0) {
    header("location: manage_booklets.php");
    exit;
}

// Fetch booklet data
$sql = "SELECT name, price, description FROM booklets WHERE id = ?";
if ($stmt = mysqli_prepare($link, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $booklet_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $booklet = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$booklet) {
        header("location: manage_booklets.php");
        exit;
    }
}

// Handle Update Booklet POST Request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_booklet'])) {
    $name = trim($_POST['name']);
    $price = trim($_POST['price']);
    $description = trim($_POST['description']);

    if (empty($name) || !is_numeric($price)) {
        $err = "نام و قیمت (عددی) جزوه الزامی است.";
    } else {
        $sql = "UPDATE booklets SET name = ?, price = ?, description = ? WHERE id = ?";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "sdsi", $name, $price, $description, $booklet_id);
            if (mysqli_stmt_execute($stmt)) {
                $success_msg = "جزوه با موفقیت به‌روزرسانی شد.";
                // Refresh booklet data
                $booklet['name'] = $name;
                $booklet['price'] = $price;
                $booklet['description'] = $description;
            } else {
                $err = "خطا در به‌روزرسانی جزوه.";
            }
            mysqli_stmt_close($stmt);
        }
    }
}

require_once "../includes/header.php";
?>

<div class="page-content">
    <h2>ویرایش جزوه</h2>
    <a href="manage_booklets.php" class="btn btn-secondary mb-3">بازگشت به لیست جزوات</a>

    <?php
    if(!empty($err)){ echo '<div class="alert alert-danger">' . $err . '</div>'; }
    if(!empty($success_msg)){ echo '<div class="alert alert-success">' . $success_msg . '</div>'; }
    ?>

    <div class="form-container">
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . "?id=" . $booklet_id; ?>" method="post">
            <div class="form-group">
                <label for="name">نام جزوه <span style="color: red;">*</span></label>
                <input type="text" name="name" id="name" class="form-control" value="<?php echo htmlspecialchars($booklet['name']); ?>" required>
            </div>
            <div class="form-group">
                <label for="price">قیمت (به تومان) <span style="color: red;">*</span></label>
                <input type="number" step="0.01" name="price" id="price" class="form-control" value="<?php echo htmlspecialchars($booklet['price']); ?>" required>
            </div>
            <div class="form-group">
                <label for="description">توضیحات</label>
                <input type="text" name="description" id="description" class="form-control" value="<?php echo htmlspecialchars($booklet['description']); ?>">
            </div>
            <div class="form-group">
                <input type="submit" name="update_booklet" class="btn btn-primary" value="به‌روزرسانی جزوه">
            </div>
        </form>
    </div>
</div>

<?php
mysqli_close($link);
require_once "../includes/footer.php";
?>
