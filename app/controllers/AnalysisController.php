<?php
require_once __DIR__ . '/../core/Ollama.php';
require_once __DIR__ . '/../config.php';

class AnalysisController {
    private $db;
    private $ollama;

    public function __construct() {
        $this->db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($this->db->connect_error) {
            die("Connection failed: " . $this->db->connect_error);
        }
        $this->ollama = new Ollama();
    }

    public function analyzeContent($competitor_id, $content) {
        // Limit content size to avoid overly long prompts
        $content_snippet = substr($content, 0, 4000);

        $prompt = "Analyze the following text from a stationery company's website. Identify the main keywords (e.g., 'luxury pen', 'notebook'), mentioned products, and provide a brief summary of the overall marketing tone (e.g., 'professional', 'playful', 'minimalist'). Return the response in a JSON format like: {\"keywords\": [\"keyword1\", \"keyword2\"], \"products\": [\"product1\", \"product2\"], \"summary\": \"The overall tone is...\"}";

        $prompt .= "\n\nContent to analyze:\n" . $content_snippet;

        $response_text = $this->ollama->generate($prompt);

        // Extract JSON from the response
        preg_match('/\{.*\}/s', $response_text, $matches);
        if (isset($matches[0])) {
            $analysis_result = json_decode($matches[0], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $this->saveAnalysis($competitor_id, 'Website', $analysis_result);
                return $analysis_result;
            }
        }

        return null;
    }

    private function saveAnalysis($competitor_id, $content_type, $analysis_data) {
        $keywords = json_encode($analysis_data['keywords'] ?? []);
        $products = json_encode($analysis_data['products'] ?? []);
        $summary = $analysis_data['summary'] ?? '';

        $stmt = $this->db->prepare("INSERT INTO analyses (competitor_id, content_type, keywords, products_promoted, analysis_summary) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $competitor_id, $content_type, $keywords, $products, $summary);
        $stmt->execute();
        $stmt->close();
    }

    public function getAnalysesForCompetitor($competitor_id) {
        $stmt = $this->db->prepare("SELECT * FROM analyses WHERE competitor_id = ? ORDER BY created_at DESC");
        $stmt->bind_param("i", $competitor_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getAllAnalyses() {
        $query = "SELECT a.*, c.name as competitor_name FROM analyses a JOIN competitors c ON a.competitor_id = c.id ORDER BY a.created_at DESC";
        $result = $this->db->query($query);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function generateGenericContent($user_prompt) {
        $analyses_summary = $this->getRecentAnalysesSummary();

        $prompt = "You are a creative digital marketer for a stationery brand. Your task is to write marketing content in Persian based on a user request and competitor analysis. Be conversational and friendly.\n\n### Competitor Analysis Summary:\n{$analyses_summary}\n\n### User Request:\n{$user_prompt}\n\n### Your Content:";

        return $this->ollama->generate($prompt);
    }

    private function getRecentAnalysesSummary() {
        $analyses = $this->getAllAnalyses();
        if (empty($analyses)) {
            return "No analysis available yet.";
        }
        $summary_text = "";
        foreach (array_slice($analyses, 0, 5) as $analysis) { // Use last 5 analyses
            $summary_text .= "- Competitor '{$analysis['competitor_name']}' focuses on {$analysis['analysis_summary']}. Keywords: " . implode(', ', json_decode($analysis['keywords'])) . ".\n";
        }
        return $summary_text;
    }

    public function getChatResponse($history) {
        $analyses_summary = $this->getRecentAnalysesSummary();

        // Building a simpler, more direct prompt.
        $system_prompt = "You are 'Ares', a helpful and friendly AI assistant for a stationery business. Your goal is to give concise and actionable advice in Persian to help the user. Use the competitor analysis summary to inform your answers.";

        $prompt = "### System Prompt\n" . $system_prompt;
        $prompt .= "\n\n### Competitor Analysis Summary:\n" . $analyses_summary;

        $conversation = "";
        foreach ($history as $message) {
            $conversation .= "\n" . ($message['role'] === 'user' ? 'User' : 'Assistant') . ": " . $message['content'];
        }

        $prompt .= "\n\n### Conversation History:" . $conversation;
        $prompt .= "\n\nAssistant: ";

        return $this->ollama->generate($prompt);
    }

    public function __destruct() {
        $this->db->close();
    }
}
?>
