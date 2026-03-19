<?php
session_start();
require_once "includes/security.php";
require_login();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Project Dashboard - BuildSmart</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<h2>🏗️ Project Dashboard</h2>

<div>
    <h3>Create New Project</h3>
    <input type="text" id="project_name" placeholder="Project Name">
    <input type="text" id="project_desc" placeholder="Description">
    <button onclick="createProject()">Create</button>
</div>

<hr>

<div>
    <h3>Your Projects</h3>
    <ul id="project_list"></ul>
</div>

<hr>

<div id="project_details" style="display:none;">
    <h3 id="project_title"></h3>

    <textarea id="project_note" placeholder="Add note/material/cost"></textarea>
    <select id="data_type">
        <option value="note">Note</option>
        <option value="material">Material</option>
        <option value="cost">Cost</option>
    </select>

    <button onclick="addProjectData()">Add</button>

    <h4>Project Data</h4>
    <ul id="project_data"></ul>
</div>

<script src="chat.js"></script>

</body>
</html>