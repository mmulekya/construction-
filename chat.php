<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>BuildSmart AI Chat</title>
<link rel="stylesheet" href="styles.css">
<style>
body { font-family: Arial; margin:0; padding:0; }
.chat-container { max-width:600px; margin: auto; padding: 1rem; }
.chat-box { border:1px solid #ccc; border-radius:8px; padding:1rem; min-height:400px; overflow-y:auto; }
.chat-input { display:flex; margin-top:1rem; }
.chat-input input { flex:1; padding:0.5rem; border-radius:5px; border:1px solid #ccc; }
.chat-input button { margin-left:0.5rem; padding:0.5rem 1rem; border-radius:5px; background:#4CAF50; color:white; border:none; cursor:pointer; }
.chat-message { margin-bottom:1rem; }
.user-msg { text-align:right; color:blue; }
.ai-msg { text-align:left; color:green; }
</style>
</head>
<body>
<div class="chat-container">
    <h2>BuildSmart AI Chat</h2>
    <div id="chatBox" class="chat-box"></div>
    <div class="chat-input">
        <input type="text" id="question" placeholder="Ask your construction question..." />
        <button onclick="sendQuestion()">Send</button>
    </div>
</div>

<script>
async function sendQuestion(){
    const question = document.getElementById('question').value.trim();
    if(!question) return;
    const chatBox = document.getElementById('chatBox');
    chatBox.innerHTML += `<div class="chat-message user-msg">${question}</div>`;
    document.getElementById('question').value = '';
    
    const token = '<?= csrf_token() ?>'; // Inject CSRF token from PHP
    const formData = new FormData();
    formData.append('question', question);
    formData.append('token', token);

    const res = await fetch('api/ask_ai.php', { method:'POST', body: formData });
    const data = await res.json();
    const reply = data.knowledge || 'No answer found.';
    chatBox.innerHTML += `<div class="chat-message ai-msg">${reply}</div>`;
    chatBox.scrollTop = chatBox.scrollHeight;
}
</script>
</body>
</html>