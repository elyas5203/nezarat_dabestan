<?php
require_once '../app/config.php';

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

// SQL to create competitors table
$sql_competitors = "CREATE TABLE IF NOT EXISTS competitors (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    website VARCHAR(255),
    instagram VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($conn->query($sql_competitors) === TRUE) {
  echo "Table 'competitors' created successfully<br>";
} else {
  echo "Error creating table: " . $conn->error;
}

// SQL to create analyses table
$sql_analyses = "CREATE TABLE IF NOT EXISTS analyses (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    competitor_id INT(6) UNSIGNED NOT NULL,
    content_type VARCHAR(50),
    keywords TEXT,
    products_promoted TEXT,
    analysis_summary TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (competitor_id) REFERENCES competitors(id) ON DELETE CASCADE
)";

if ($conn->query($sql_analyses) === TRUE) {
  echo "Table 'analyses' created successfully<br>";
} else {
  echo "Error creating table: " . $conn->error;
}

$conn->close();
?>
