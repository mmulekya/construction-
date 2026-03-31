<?php
require_once "includes/security.php";
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>BuildSmart AI Chat</title>

<style>
body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: #f5f7fa;
    display: flex;
    flex-direction: column;
    height: 100vh;
}

header {
    background: #007bff;
    color: white;
    padding: 15px;
    text-align: center;
    font-size: 20px;
}

.chat-container {
    flex: 1;
    display: flex;
    flex-direction: column;
    overflow-y: auto;
    padding: 10px;
}

.message {
    padding: 10px 15px;
    margin: 5px 0;
    border-radius: 20px;
    max-width: 80%;
    word-wrap: break-word;
}

.message.user {
    background: #007bff;
    color: white;
    align-self: flex-end;
}

.message.ai {
    background: #e4e6eb;
    color: black;
    align-self: flex-start;
}

.input-bar {
    display: flex;
    padding: 10px;
    background: #fff;
    border-top: 1px solid #ccc;
}

.input-bar input {
    flex: 1;
    padding: 10px;
    border-radius: 20px;
    border: 1px solid #ccc;
    font-size: 16px;
}

.input-bar button {
    margin-left: 10px;
    padding: 10px 20px;
    background: #28a745;
    color: white;
    border: none;
    border-radius: 20px;
    font-size: 16px;
    cursor: pointer;
}

.input-bar button:hover {
    background: #218838;
}

/* Dark mode */
body.dark {
    background: #181818;
    color: #eee;
}

body.dark .message.ai {
    background: #333;
    color: #eee;
}

body.dark .message.user {
    background: #0d6efd;
}

body.dark .input-bar {
    background: #222;
    border-top: 1px solid #555;
}

body.dark .input-bar input {
    background: #333;
    color: #eee;
    border: 1px solid #555;
}
</style>
</head>
<body>

<header>
    BuildSmart AI Chat
    <button onclick="toggleMode()" style="float:right;padding:5px 10px;">🌗</button>
</header>

<div class="chat-container" id="chat-box"></div>

<div class="input-bar">
    <input type="text" id="question" placeholder="Type your question..." autocomplete="off">
    <button onclick="sendQuestion()">Send</button>
    <input type="hidden" id="csrf_token">
</div>

<script>
// CSRF token
let CSRF_TOKEN = document.getElementById("csrf_token").value;

// Load CSRF
fetch('includes/get_csrf.php')
.then(res => res.json())
.then(data => CSRF_TOKEN = data.token);

// JWT from localStorage
let JWT = localStorage.getItem("jwt");

// Chat box
const chatBox = document.getElementById("chat-box");

// =======================
// ADD MESSAGE
// =======================
function addMessage(text, type){
    let div = document.createElement("div");
    div.className = "message " + type;
    div.innerText = text;
    chatBox.appendChild(div);
    chatBox.scrollTop = chatBox.scrollHeight;
    return div;
}

// =======================
// STREAM AI RESPONSE
// =======================
function streamText(element, text){
    let i = 0;
    function type(){
        if(i < text.length){
            element.innerText += text.charAt(i);
            i++;
            setTimeout(type, 10);
        }
    }
    type();
}

// =======================
// SEND QUESTION
// =======================
function sendQuestion(){
    let input = document.getElementById("question");
    let q = input.value.trim();
    if(!q) return;

    addMessage(q, "user");
    input.value = "";

    let aiMsg = addMessage("Typing...", "ai");

    fetch("api/ask_ai.php",{
        method:"POST",
        headers: {
            "Content-Type": "application/json",
            "Authorization": JWT
        },
        body: JSON.stringify({question:q, csrf_token:CSRF_TOKEN})
    })
    .then(res=>res.json())
    .then(data=>{
        aiMsg.innerText = "";
        if(data.error) aiMsg.innerText = data.error;
        else streamText(aiMsg, data.answer || data.response);
    })
    .catch(()=> aiMsg.innerText = "Network error");
}

// =======================
// LOAD HISTORY
// =======================
function loadHistory(){
    fetch("api/history.php",{
        headers:{ "Authorization": JWT }
    })
    .then(res=>res.json())
    .then(data=>{
        if(data.history){
            data.history.reverse().forEach(chat=>{
                addMessage(chat.message, "user");
                addMessage(chat.response, "ai");
            });
        }
    });
}

loadHistory();

// =======================
// TOGGLE DARK MODE
// =======================
function toggleMode(){
    document.body.classList.toggle("dark");
}
</script>

</body>
</html>