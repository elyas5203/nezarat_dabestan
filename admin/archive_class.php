<?php
session_start();
require_once "../includes/db_singleton.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !$_SESSION["is_admin"]) {
    header("location: ../index.php");
    exit;
}

$link = get_db_connection();
$class_id = $_GET['id'] ?? null;

if ($class_id) {
    $stmt = mysqli_prepare($link, "UPDATE classes SET status = 'archived' WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $class_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

header("location: manage_classes.php");
exit;
?>
