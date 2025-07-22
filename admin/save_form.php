<?php
session_start();
require_once "../includes/db_singleton.php";

header('Content-Type: application/json');

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !$_SESSION["is_admin"]) {
    echo json_encode(['success' => false, 'error' => 'دسترسی غیرمجاز']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'درخواست نامعتبر']);
    exit;
}

$link = get_db_connection();
$form_id = $_POST['form_id'] ?? null;
$form_name = $_POST['form_name'] ?? '';
$form_structure = $_POST['form_structure'] ?? '[]';

if (empty($form_name)) {
    echo json_encode(['success' => false, 'error' => 'نام فرم نمی‌تواند خالی باشد.']);
    exit;
}

// Validate JSON
$decoded_structure = json_decode($form_structure);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['success' => false, 'error' => 'ساختار فرم نامعتبر است.']);
    exit;
}

if ($form_id) {
    // Update existing form
    $stmt = mysqli_prepare($link, "UPDATE dynamic_forms SET form_name = ?, form_structure = ? WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "ssi", $form_name, $form_structure, $form_id);
} else {
    // Create new form
    $stmt = mysqli_prepare($link, "INSERT INTO dynamic_forms (form_name, form_structure) VALUES (?, ?)");
    mysqli_stmt_bind_param($stmt, "ss", $form_name, $form_structure);
}

if (mysqli_stmt_execute($stmt)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => mysqli_stmt_error($stmt)]);
}

mysqli_stmt_close($stmt);
mysqli_close($link);
?>
