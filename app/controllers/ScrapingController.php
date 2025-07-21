<?php
<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/CompetitorController.php';
require_once __DIR__ . '/AnalysisController.php';

use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\HttpClient\HttpClient;

class ScrapingController {
    private $db;
    private $browser;

    public function __construct() {
        $this->db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($this->db->connect_error) {
            die("Connection failed: " . $this->db->connect_error);
        }
        $this->browser = new HttpBrowser(HttpClient::create());
    }

    public function fetchAllCompetitorData() {
        $competitors_obj = new CompetitorController();
        $competitors = $competitors_obj->getAllCompetitors();

        foreach ($competitors as $competitor) {
            if (!empty($competitor['website'])) {
                $this->fetchWebsiteData($competitor['id'], $competitor['website']);
            }
            // Instagram scraping remains a complex issue best handled by official APIs.
        }
    }

    public function fetchWebsiteData($competitor_id, $url) {
        try {
            $crawler = $this->browser->request('GET', $url);

            // Extract title
            $title = $crawler->filter('title')->first()->text('No title found');

            // Extract meta description
            $descriptionNode = $crawler->filter('meta[name="description"]');
            $description = $descriptionNode->count() > 0 ? $descriptionNode->first()->attr('content') : 'No description found';

            // Extract all text from the body
            $body_text = $crawler->filter('body')->first()->text('');
            $clean_body_text = preg_replace('/\s+/', ' ', $body_text);

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
