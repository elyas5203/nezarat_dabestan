<?php
echo "<pre>";
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../app/core/Ollama.php';

echo "Ollama Test Page\n\n";

$ollama = new Ollama();

$prompt = "پایتخت ایران کجاست؟ فقط نام شهر را بگو.";

echo "Sending prompt: '{$prompt}'\n";

$response = $ollama->generate($prompt);

echo "\nReceived response:\n";
print_r($response);

echo "\n\nTest finished.";
echo "</pre>";
?>
