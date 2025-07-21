<?php

require_once __DIR__ . '/../core/Ollama.php';
require_once __DIR__ . '/../config.php';

class CompetitorController {
    private $db;
    private $ollama;

    public function __construct() {
        $this->db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($this->db->connect_error) {
            die("Connection failed: " . $this->db->connect_error);
        }
        $this->ollama = new Ollama();
    }

    public function addCompetitor($name, $website, $instagram) {
        $stmt = $this->db->prepare("INSERT INTO competitors (name, website, instagram) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $website, $instagram);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    public function searchCompetitor($name) {
        $prompt = "Find the official website and Instagram page for a company named '{$name}'. I am looking for a luxury and regular stationery company. Please provide the URLs in JSON format, for example: {\"website\": \"https://example.com\", \"instagram\": \"https://instagram.com/example\"}. If you can't find one, return null for that field.";
        $response = $this->ollama->generate($prompt);

        // Basic parsing of the JSON from the response string
        preg_match('/\{.*\}/s', $response, $matches);
        if (isset($matches[0])) {
            return json_decode($matches[0], true);
        }

        return null;
    }

    public function __destruct() {
        $this->db->close();
    }
}
?>
