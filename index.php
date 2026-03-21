<?php
require_once "includes/config.php";
require_once "includes/security.php";

if(!is_logged_in()){
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>AI Chat System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<h2>Welcome to AI System</h2>

<button onclick="logout()">Logout</button>

<div id="chat-box"></div>

<input type="text" id="question" placeholder="Ask something...">
<input type="hidden" id="csrf_token">

<button onclick="askAI()">Send</button>

<script src="chat.js"></script>

</body>
</html>