<?php

class Ollama {
    private $apiUrl;
    private $model;

    public function __construct() {
        require_once __DIR__ . '/../config.php';
        $this->apiUrl = OLLAMA_API_URL;
        $this->model = OLLAMA_MODEL;
    }

    public function generate($prompt) {
        $data = [
            'model' => $this->model,
            'prompt' => $prompt,
            'stream' => false
        ];
        $json_data = json_encode($data);

        $ch = curl_init($this->apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($json_data)
        ]);
        // Add a timeout to prevent long waits
        curl_setopt($ch, CURLOPT_TIMEOUT, 60); // 60 seconds timeout

        $result = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            // Log the actual error for debugging
            error_log("cURL Error: " . $error);
            return "Error communicating with Ollama API.";
        }

        if ($result === false) {
            return "Error: No response from Ollama API.";
        }

        $response = json_decode($result, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            // Log the invalid JSON for debugging
            error_log("Invalid JSON response from Ollama: " . $result);
            return "Error: Invalid response from model.";
        }

        return $response['response'] ?? 'No response from model.';
    }
}
?>
