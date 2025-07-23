<?php
require_once '../includes/db_singleton.php';

$new_password = 'Admin_dabestan_site_110_59';
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

$db = Database::getInstance();
$conn = $db->getConnection();

$sql = "UPDATE users SET password = ? WHERE username = 'admin'";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $hashed_password);

if ($stmt->execute()) {
    echo "رمز عبور کاربر 'admin' با موفقیت به 'Admin_dabestan_site_110_59' تغییر یافت.";
} else {
    echo "خطا در تغییر رمز عبور: " . $conn->error;
}

$stmt->close();
?>
