<?php
require_once '../app/controllers/AnalysisController.php';
session_start();

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $input = json_decode(file_get_contents('php://input'), true);
    $user_message = $input['message'] ?? '';

    if (empty($user_message)) {
        echo json_encode(['reply' => 'لطفا یک پیام بنویسید.']);
        exit;
    }

    // In a real app, conversation history would be managed more robustly.
    if (!isset($_SESSION['chat_history'])) {
        $_SESSION['chat_history'] = [];
    }
    $_SESSION['chat_history'][] = ['role' => 'user', 'content' => $user_message];

    $controller = new AnalysisController();
    $bot_reply = $controller->getChatResponse($_SESSION['chat_history']);

    $_SESSION['chat_history'][] = ['role' => 'assistant', 'content' => $bot_reply];

    echo json_encode(['reply' => $bot_reply]);
}
?>
