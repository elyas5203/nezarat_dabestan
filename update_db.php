<?php
require_once "includes/db_singleton.php";
$link = get_db_connection();

echo "<h1>Database Migration Runner</h1>";

$migrations_dir = __DIR__ . '/migrations/';
$executed_migrations = [];

// Check for migrations table and create if not exists
$result = mysqli_query($link, "SHOW TABLES LIKE 'schema_migrations'");
if (mysqli_num_rows($result) == 0) {
    echo "<p>Creating 'schema_migrations' table...</p>";
    $sql_create_table = "CREATE TABLE `schema_migrations` (
                          `version` varchar(255) NOT NULL,
                          `applied_at` timestamp NOT NULL DEFAULT current_timestamp(),
                          PRIMARY KEY (`version`)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    if (!mysqli_query($link, $sql_create_table)) {
        die("FATAL: Could not create schema_migrations table. " . mysqli_error($link));
    }
} else {
    // Get list of already executed migrations
    $result = mysqli_query($link, "SELECT version FROM `schema_migrations`");
    while ($row = mysqli_fetch_assoc($result)) {
        $executed_migrations[] = $row['version'];
    }
}

// Get all .sql files from the migrations directory
$migration_files = glob($migrations_dir . '*.sql');
sort($migration_files); // Ensure they run in order

if (empty($migration_files)) {
    echo "<p>No migration files found.</p>";
} else {
    foreach ($migration_files as $file) {
        $version = basename($file, '.sql');

        if (in_array($version, $executed_migrations)) {
            echo "<p style='color: #999;'>Skipping already applied migration: {$version}</p>";
            continue;
        }

        echo "<p><b>Applying migration: {$version}...</b></p>";
        $sql_commands = file_get_contents($file);

        // Execute the SQL commands from the file
        if (mysqli_multi_query($link, $sql_commands)) {
            // It's important to clear results from multi_query
            while (mysqli_next_result($link)) {
                if ($res = mysqli_store_result($link)) {
                    mysqli_free_result($res);
                }
            }

            // Record the migration in the schema_migrations table
            $stmt = mysqli_prepare($link, "INSERT INTO `schema_migrations` (`version`) VALUES (?)");
            mysqli_stmt_bind_param($stmt, "s", $version);
            if (mysqli_stmt_execute($stmt)) {
                echo "<p style='color: green;'>Successfully applied and recorded migration: {$version}</p>";
            } else {
                 echo "<p style='color: red;'>ERROR: Could not record migration {$version} in schema_migrations table. " . mysqli_error($link) . "</p>";
            }
            mysqli_stmt_close($stmt);

        } else {
            echo "<p style='color: red;'>ERROR applying migration {$version}: " . mysqli_error($link) . "</p>";
            // Stop on first error
            break;
        }
    }
}

echo "<h2>Migration process complete.</h2>";

// Instead of mysqli_close, use the method from the singleton
Database::getInstance()->closeConnection();
?>
