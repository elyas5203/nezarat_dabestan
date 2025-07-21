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

        $options = [
            'http' => [
                'header' => "Content-type: application/json\r\n",
                'method' => 'POST',
                'content' => json_encode($data)
            ]
        ];

        $context = stream_context_create($options);
        $result = file_get_contents($this->apiUrl, false, $context);

        if ($result === FALSE) {
            return "Error communicating with Ollama API.";
        }

        $response = json_decode($result, true);
        return $response['response'] ?? 'No response from model.';
    }
}
?>
