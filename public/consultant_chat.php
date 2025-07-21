<?php
require_once 'dashboard_header.php';
// Logic for handling chat will be added in the next step.
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">چت با مشاور هوشمند</h1>
</div>
<p>با دستیار خود صحبت کنید، از او سوال بپرسید و برای رشد کسب و کار خود ایده بگیرید.</p>

<div class="card" style="height: 70vh;">
    <div class="card-body d-flex flex-column">
        <div class="chat-box flex-grow-1 mb-3" id="chat-box">
            <!-- Chat messages will appear here -->
            <div class="chat-message bot">
                سلام! من دستیار هوشمند شما هستم. چطور می تونم امروز بهت کمک کنم تا فروشت رو بیشتر کنی؟
            </div>
        </div>
        <form id="chat-form" class="d-flex">
            <input type="text" id="user-input" class="form-control me-2" placeholder="پیام خود را بنویسید..." autocomplete="off">
            <button type="submit" class="btn btn-primary">ارسال</button>
        </form>
    </div>
</div>

<style>
    .chat-box { overflow-y: auto; }
    .chat-message {
        padding: 0.5rem 1rem;
        border-radius: 1rem;
        margin-bottom: 0.5rem;
        max-width: 80%;
        line-height: 1.5;
    }
    .chat-message.bot {
        background-color: #e9ecef;
        align-self: flex-start;
    }
    .chat-message.user {
        background-color: #0d6efd;
        color: white;
        align-self: flex-end;
    }
    .chat-box {
        display: flex;
        flex-direction: column;
    }
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
