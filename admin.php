<?php
require_once "includes/security.php";

if(!is_logged_in() || !is_admin()){
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
</head>
<body>

<h2>Admin Dashboard</h2>

<button onclick="loadUsers()">Users</button>
<button onclick="loadChats()">Chats</button>
<button onclick="logout()">Logout</button>
<button onclick="downloadBackup()">Download Backup</button>

<div id="admin-data"></div>

<h3>🚫 Banned IPs</h3>
<div id="banned"></div>

<script src="chat.js"></script>

</body>
</html>