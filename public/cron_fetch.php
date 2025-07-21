<?php
// This script is intended to be run periodically by a cron job on the server.
// For now, you can run it manually by navigating to its URL.

echo "Starting data fetching process...<br>";

require_once '../app/controllers/ScrapingController.php';

$scraper = new ScrapingController();
$scraper->fetchAllCompetitorData();

echo "<br>Data fetching process finished.";
?>
