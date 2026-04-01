<?php
require_once "includes/security.php";

if(session_status() === PHP_SESSION_NONE){
    session_start();
}

// Redirect if not logged in (no session + no JWT)
if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>BuildSmart AI</title>

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
    font-size: 18px;
    position: relative;
}

header button {
    position: absolute;
    right: 10px;
    top: 10px;
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
}

.input-bar button {
    margin-left: 10px;
    padding: 10px 20px;
    background: #28a745;
    color: white;
    border-radius: 20px;
    border: none;
    cursor: pointer;
}

/* Dark mode */
body.dark { background:#181818;color:#eee; }
body.dark .message.ai { background:#333;color:#eee; }
body.dark .message.user { background:#0d6efd; }
body.dark .input-bar { background:#222;border-top:1px solid #555; }
body.dark input { background:#333;color:#eee;border:1px solid #555; }
</style>

</head>
<body>

<header>
    BuildSmart AI 🤖
    <button onclick="toggleMode()">🌗</button>
    <button onclick="logout()" style="right:60px;background:red;">Logout</button>
</header>

<div class="chat-container" id="chat-box"></div>

<div class="input-bar">
    <input type="text" id="question" placeholder="Ask something..." autocomplete="off">
    <button onclick="sendQuestion()">Send</button>
</div>

<input type="hidden" id="csrf_token">

<script>

// =======================
// INIT
// =======================
let CSRF_TOKEN = "";
let JWT = localStorage.getItem("jwt");

// If no JWT → redirect
if(!JWT){
    window.location.href = "login.php";
}

// Load CSRF
fetch('includes/get_csrf.php')
.then(res=>res.json())
.then(data=>{
    CSRF_TOKEN = data.token;
});

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
function streamText(el, text){
    let i = 0;
    function type(){
        if(i < text.length){
            el.innerText += text.charAt(i);
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

    if(!q || q.length < 2) return;

    addMessage(q, "user");
    input.value = "";

    let aiMsg = addMessage("Thinking...", "ai");

    fetch("api/ask_ai.php",{
        method:"POST",
        headers:{
            "Content-Type":"application/json",
            "Authorization": JWT
        },
        body: JSON.stringify({
            question:q,
            csrf_token:CSRF_TOKEN
        })
    })
    .then(res=>res.json())
    .then(data=>{
        aiMsg.innerText = "";

        if(data.error){
            aiMsg.innerText = data.error;
        } else {
            streamText(aiMsg, data.answer || data.response);
        }
    })
    .catch(()=>{
        aiMsg.innerText = "Network error";
    });
}

// =======================
// ENTER KEY SUPPORT
// =======================
document.getElementById("question").addEventListener("keypress", function(e){
    if(e.key === "Enter"){
        sendQuestion();
    }
});

// =======================
// LOAD HISTORY
// =======================
function loadHistory(){
    fetch("api/history.php",{
        headers: { "Authorization": JWT }
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
// LOGOUT
// =======================
function logout(){
    localStorage.removeItem("jwt");
    fetch("api/logout.php")
    .then(()=> window.location.href="login.php");
}

// =======================
// DARK MODE
// =======================
function toggleMode(){
    document.body.classList.toggle("dark");
}

</script>

</body>
</html>