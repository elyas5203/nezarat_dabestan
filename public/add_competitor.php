<?php
require_once '../app/config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Create connection
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $name = $_POST['name'];
    $website = $_POST['website'];
    $instagram = $_POST['instagram'];

    $stmt = $conn->prepare("INSERT INTO competitors (name, website, instagram) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $name, $website, $instagram);

    if ($stmt->execute()) {
        $message = "رقیب جدید با موفقیت اضافه شد.";
    } else {
        $message = "خطا در اضافه کردن رقیب: " . $conn->error;
    }

    $stmt->close();
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>افزودن رقیب جدید</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <h1>افزودن رقیب جدید</h1>

    <?php if (!empty($message)): ?>
        <p><?php echo $message; ?></p>
    <?php endif; ?>

    <form action="add_competitor.php" method="post">
        <label for="name">نام رقیب:</label><br>
        <input type="text" id="name" name="name" required><br><br>
        <label for="website">آدرس وب سایت:</label><br>
        <input type="text" id="website" name="website"><br><br>
        <label for="instagram">آدرس اینستاگرام:</label><br>
        <input type="text" id="instagram" name="instagram"><br><br>
        <input type="submit" value="افزودن">
    </form>
</body>
</html>
