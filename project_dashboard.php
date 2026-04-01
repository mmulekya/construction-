<?php
require_once "includes/security.php";

if(!is_logged_in()){
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Project Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>

<h2>My Projects</h2>

<input type="text" id="project_name" placeholder="Project name">
<button onclick="createProject()">Create</button>

<div id="projects"></div>

<script src="chat.js"></script>

</body>
</html>
