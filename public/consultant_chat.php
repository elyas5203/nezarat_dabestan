<?php
require_once 'dashboard_header.php';
// Logic for handling chat will be added in the next step.
?>

<h1>چت با مشاور هوشمند</h1>
<p>با دستیار خود صحبت کنید، از او سوال بپرسید و برای رشد کسب و کار خود ایده بگیرید.</p>

<div class="chat-container">
    <div class="chat-box" id="chat-box">
        <!-- Chat messages will appear here -->
        <div class="chat-message bot">
            سلام! من دستیار هوشمند شما هستم. چطور می تونم امروز بهت کمک کنم تا فروشت رو بیشتر کنی؟
        </div>
    </div>
    <div class="chat-input">
        <form id="chat-form">
            <input type="text" id="user-input" placeholder="پیام خود را بنویسید..." autocomplete="off">
            <button type="submit">ارسال</button>
        </form>
    </div>
</div>

<style>
    .chat-container { display: flex; flex-direction: column; height: 70vh; border: 1px solid #ccc; border-radius: 8px; background: white; }
    .chat-box { flex-grow: 1; padding: 20px; overflow-y: auto; display: flex; flex-direction: column; }
    .chat-message { max-width: 80%; padding: 10px 15px; border-radius: 15px; margin-bottom: 10px; line-height: 1.4; }
    .chat-message.user { background: #dcf8c6; align-self: flex-end; }
    .chat-message.bot { background: #f1f0f0; align-self: flex-start; }
    .chat-input { padding: 10px; border-top: 1px solid #ccc; }
    .chat-input form { display: flex; }
    .chat-input input { flex-grow: 1; border: 1px solid #ccc; border-radius: 20px; padding: 10px; }
    .chat-input button { background: #333; color: white; border: none; padding: 10px 20px; border-radius: 20px; margin-right: 10px; cursor: pointer; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const chatForm = document.getElementById('chat-form');
    const userInput = document.getElementById('user-input');
    const chatBox = document.getElementById('chat-box');

    chatForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const messageText = userInput.value.trim();
        if (messageText === '') return;

        appendMessage(messageText, 'user');
        userInput.value = '';

        fetch('chat_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ message: messageText })
        })
        .then(response => response.json())
        .then(data => {
            appendMessage(data.reply, 'bot');
        })
        .catch(error => {
            console.error('Error:', error);
            appendMessage('متاسفانه مشکلی در ارتباط با سرور پیش اومد.', 'bot');
        });
    });

    function appendMessage(text, type) {
        const messageElement = document.createElement('div');
        messageElement.classList.add('chat-message', type);
        messageElement.textContent = text;
        chatBox.appendChild(messageElement);
        chatBox.scrollTop = chatBox.scrollHeight;
    }
});
</script>

<?php require_once 'dashboard_footer.php'; ?>
