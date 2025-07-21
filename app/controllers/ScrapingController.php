<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/CompetitorController.php';
require_once __DIR__ . '/AnalysisController.php';

use Goutte\Client;

class ScrapingController {
    private $db;
    private $client;

    public function __construct() {
        $this->db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($this->db->connect_error) {
            die("Connection failed: " . $this->db->connect_error);
        }
        $this->client = new Client();
    }

    public function fetchAllCompetitorData() {
        $competitors_obj = new CompetitorController();
        $competitors = $competitors_obj->getAllCompetitors();

        foreach ($competitors as $competitor) {
            if (!empty($competitor['website'])) {
                $this->fetchWebsiteData($competitor['id'], $competitor['website']);
            }
            // Instagram scraping is very complex and often blocked.
            // A real solution requires using their official API (Graph API), which is beyond the scope of this implementation.
            // We will simulate this part for now.
        }
    }

    public function fetchWebsiteData($competitor_id, $url) {
        try {
            $crawler = $this->client->request('GET', $url);

            // Extract title
            $title = $crawler->filter('title')->first()->text();

            // Extract meta description
            $description = $crawler->filter('meta[name="description"]')->first()->attr('content');

            // Extract all text from the body
            $body_text = $crawler->filter('body')->first()->text();
            $clean_body_text = preg_replace('/\s+/', ' ', $body_text); // Clean up whitespace

            // For now, we just print it. Later we will save this to be analyzed.
            echo "Fetched from {$url}: Title - {$title}<br>";

            // Pass the content to the analysis controller
            $analysis_controller = new AnalysisController();
            $analysis_controller->analyzeContent($competitor_id, $clean_body_text);

            echo "Analysis complete for {$url}<br>";

        } catch (\Exception $e) {
            echo "Could not fetch from {$url}. Error: " . $e->getMessage() . "<br>";
        }
    }

    public function __destruct() {
        $this->db->close();
    }
}
?>
