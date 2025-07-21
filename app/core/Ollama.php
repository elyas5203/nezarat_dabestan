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
        $this->log("Request Prompt: " . $prompt);
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
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);

        $result = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            $this->log("cURL Error: " . $error);
            return "Error communicating with Ollama API.";
        }

        if ($result === false) {
            $this->log("No response from Ollama API.");
            return "Error: No response from Ollama API.";
        }

        $this->log("Raw Response: " . $result);
        $response = json_decode($result, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->log("Invalid JSON response: " . $result);
            return "Error: Invalid response from model.";
        }

        $final_response = $response['response'] ?? 'No response from model.';
        $this->log("Final Response: " . $final_response);
        return $final_response;
    }

    private function log($message) {
        $logFile = __DIR__ . '/../../ollama_log.txt';
        $timestamp = date('Y-m-d H:i:s');
        file_put_contents($logFile, "[$timestamp] " . $message . "\n", FILE_APPEND);
    }
}
?>
