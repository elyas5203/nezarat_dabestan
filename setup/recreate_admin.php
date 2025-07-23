<?php
require_once '../includes/db_singleton.php';

$db = Database::getInstance();
$conn = $db->getConnection();

// Delete all existing admin users
$sql_delete = "DELETE FROM users WHERE username = 'admin'";
if ($conn->query($sql_delete) === TRUE) {
    echo "تمام کاربران 'admin' موجود با موفقیت حذف شدند.<br>";
} else {
    echo "خطا در حذف کاربران 'admin': " . $conn->error . "<br>";
}

// Create a new admin user
$first_name = 'ادمین';
$last_name = 'اصلی';
$username = 'admin';
$password = 'Admin_dabestan_site_110_59';
$hashed_password = password_hash($password, PASSWORD_DEFAULT);
$is_admin = 1;

$sql_insert = "INSERT INTO users (first_name, last_name, username, password, is_admin) VALUES (?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql_insert);
$stmt->bind_param('ssssi', $first_name, $last_name, $username, $hashed_password, $is_admin);

if ($stmt->execute()) {
    echo "کاربر 'admin' جدید با موفقیت ایجاد شد.";
} else {
    echo "خطا در ایجاد کاربر 'admin' جدید: " . $conn->error;
}

$stmt->close();
?>
