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
        $prompt = "Task: Find the official website and Instagram URL for a stationery company named '{$name}'.\nResponse format: JSON (e.g., {\"website\": \"URL\", \"instagram\": \"URL\"}).\nIf a URL is not found, use null for its value.";

        $response = $this->ollama->generate($prompt);

        // Extract JSON from the response, which might be wrapped in text or code blocks.
        if (preg_match('/\{[^{}]+\}/s', $response, $matches)) {
            $json_part = $matches[0];
            $decoded = json_decode($json_part, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        return ['website' => null, 'instagram' => null];
    }

    public function getAllCompetitors() {
        $result = $this->db->query("SELECT * FROM competitors ORDER BY created_at DESC");
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function deleteCompetitor($id) {
        $stmt = $this->db->prepare("DELETE FROM competitors WHERE id = ?");
        $stmt->bind_param("i", $id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    public function __destruct() {
        $this->db->close();
    }
}
?>
