<?php
require_once "../includes/db.php";

$username = "admin";
$password = "admin123";
$first_name = "ادمین";
$last_name = "اصلی";
$is_admin = 1;

// Hash the password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Check if admin already exists
$sql_check = "SELECT id FROM users WHERE username = ?";
if ($stmt_check = mysqli_prepare($link, $sql_check)) {
    mysqli_stmt_bind_param($stmt_check, "s", $username);
    mysqli_stmt_execute($stmt_check);
    mysqli_stmt_store_result($stmt_check);

    if (mysqli_stmt_num_rows($stmt_check) == 0) {
        // Admin does not exist, create it
        $sql_insert = "INSERT INTO users (first_name, last_name, username, password, is_admin) VALUES (?, ?, ?, ?, ?)";
        if ($stmt_insert = mysqli_prepare($link, $sql_insert)) {
            mysqli_stmt_bind_param($stmt_insert, "ssssi", $first_name, $last_name, $username, $hashed_password, $is_admin);
            if (mysqli_stmt_execute($stmt_insert)) {
                echo "Admin user created successfully. <br>";
                echo "Username: " . $username . "<br>";
                echo "Password: " . $password . "<br>";
            } else {
                echo "Error creating admin user: " . mysqli_error($link);
            }
            mysqli_stmt_close($stmt_insert);
        }
    } else {
        echo "Admin user already exists.";
    }
    mysqli_stmt_close($stmt_check);
}

mysqli_close($link);
?>
