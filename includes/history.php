<?php
function record_history($action, $details) {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['id'])) {
        return; // Cannot log history if user is not logged in
    }

    $user_id = $_SESSION['id'];
    $link = get_db_connection(); // Assumes get_db_connection is available

    $sql = "INSERT INTO events (user_id, action, details) VALUES (?, ?, ?)";
    $stmt = mysqli_prepare($link, $sql);
    mysqli_stmt_bind_param($stmt, "iss", $user_id, $action, $details);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}
?>
