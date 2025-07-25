<?php
session_start();
require_once "../includes/db_singleton.php";
require_once "../includes/functions.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !is_admin()) {
    header("location: ../index.php");
    exit;
}

$link = get_db_connection();
$type = isset($_GET['type']) ? $_GET['type'] : '';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$action = isset($_GET['action']) ? $_GET['action'] : 'archive'; // archive or restore

$supported_types = ['class', 'user', 'form'];
if (!in_array($type, $supported_types) || $id === 0) {
    die("نوع نامعتبر یا شناسه ناموجود.");
}

$table_map = [
    'class' => 'classes',
    'user' => 'users',
    'form' => 'forms'
];

$status_column_map = [
    'class' => 'status',
    'user' => 'is_active', // Assuming users table has 'is_active' column
    'form' => 'status'  // Assuming forms table has 'status' column
];

$table = $table_map[$type];
$status_column = $status_column_map[$type];

$archive_status = ($type === 'user') ? 0 : 'archived';
$restore_status = ($type === 'user') ? 1 : 'active';

$new_status = ($action === 'archive') ? $archive_status : $restore_status;

$sql = "UPDATE $table SET $status_column = ? WHERE id = ?";
$stmt = mysqli_prepare($link, $sql);

if ($type === 'user') {
    mysqli_stmt_bind_param($stmt, "ii", $new_status, $id);
} else {
    mysqli_stmt_bind_param($stmt, "si", $new_status, $id);
}


if (mysqli_stmt_execute($stmt)) {
    $message = ($action === 'archive') ? 'بایگانی شد' : 'بازیابی شد';
    $_SESSION['flash_message'] = "مورد با موفقیت " . $message;
} else {
    $_SESSION['flash_error'] = "خطا در عملیات بایگانی/بازیابی.";
}

// Redirect back to the management page
$redirect_map = [
    'class' => 'manage_classes.php',
    'user' => 'manage_users.php',
    'form' => 'manage_forms.php'
];

header("location: " . $redirect_map[$type]);
exit;
?>
