<?php
session_start();
// Corrected include to use the singleton pattern for DB connection
require_once "includes/db_singleton.php";
require_once "includes/functions.php";

// Corrected admin check using session variable
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !isset($_SESSION["is_admin"]) || !$_SESSION["is_admin"]) {
    header("location: index.php");
    exit;
}

$messages = [];
$link = get_db_connection(); // Get DB connection using the function

// 1. Check for schema_migrations table
$check_table_sql = "SHOW TABLES LIKE 'schema_migrations'";
$table_exists_result = mysqli_query($link, $check_table_sql);
if (mysqli_num_rows($table_exists_result) == 0) {
    $create_table_sql = "
        CREATE TABLE `schema_migrations` (
          `version` varchar(255) NOT NULL,
          `applied_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`version`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ";
    if (mysqli_query($link, $create_table_sql)) {
        $messages[] = "جدول `schema_migrations` با موفقیت ایجاد شد.";
    } else {
        die("خطا در ایجاد جدول `schema_migrations`: " . mysqli_error($link));
    }
}

// 2. Get applied migrations
$applied_migrations = [];
$applied_sql = "SELECT version FROM schema_migrations";
$applied_result = mysqli_query($link, $applied_sql);
while ($row = mysqli_fetch_assoc($applied_result)) {
    $applied_migrations[] = $row['version'];
}

// 3. Scan migrations directory
$migration_files = glob('migrations/*.sql');
sort($migration_files);

// 4. Apply new migrations
$new_migrations_applied = 0;
foreach ($migration_files as $file) {
    $version = basename($file);
    if (!in_array($version, $applied_migrations)) {
        $messages[] = "در حال اجرای مایگریشن: $version...";

        $sql_script = file_get_contents($file);

        if (empty(trim($sql_script))) {
            $messages[] = "مایگریشن $version خالی است، از آن عبور می‌کنیم.";
            continue;
        }

        // Execute multi-query
        if (mysqli_multi_query($link, $sql_script)) {
            // Must clear results from multi_query
            while (mysqli_more_results($link) && mysqli_next_result($link)) {
                if ($result = mysqli_store_result($link)) {
                    mysqli_free_result($result);
                }
            }

            // Record the migration
            $insert_version_sql = "INSERT INTO schema_migrations (version) VALUES (?)";
            $stmt = mysqli_prepare($link, $insert_version_sql);
            mysqli_stmt_bind_param($stmt, "s", $version);
            mysqli_stmt_execute($stmt);

            $messages[] = "<strong style='color: green;'>مایگریشن $version با موفقیت اجرا شد.</strong>";
            $new_migrations_applied++;
        } else {
            $messages[] = "<strong style='color: red;'>خطا در اجرای مایگریشن $version: " . mysqli_error($link) . "</strong>";
            break; // Stop on error
        }
    }
}

if ($new_migrations_applied == 0 && count($migration_files) > 0) {
    $messages[] = "دیتابیس شما به‌روز است. هیچ مایگریشن جدیدی برای اجرا وجود ندارد.";
} elseif (count($migration_files) == 0) {
    $messages[] = "هیچ فایل مایگریشنی در پوشه `migrations` یافت نشد.";
}

require_once "includes/header.php";
?>

<div class="page-content">
    <h2>اجرای مایگریشن‌های دیتابیس</h2>
    <p>این اسکریپت فایل‌های جدید در پوشه `migrations` را بررسی و بر روی دیتابیس اعمال می‌کند.</p>

    <div class="alert alert-info">
        <strong>گزارش اجرا:</strong><br>
        <?php
        foreach ($messages as $message) {
            echo $message . "<br>";
        }
        ?>
    </div>

    <a href="admin/index.php" class="btn btn-primary">بازگشت به داشبورد مدیریت</a>
</div>

<?php
// Do not close the connection from the singleton here, it will be closed on script shutdown.
// mysqli_close($link);
require_once "includes/footer.php";
?>
