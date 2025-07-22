<?php
require_once "includes/db_singleton.php";

$link = get_db_connection();
$sql = "INSERT INTO permissions (permission_name, permission_description) VALUES ('view_analysis', 'توانایی مشاهده تحلیل‌ها و گزارشات')";
mysqli_query($link, $sql);
mysqli_close($link);

echo "Permission added successfully.";
?>
