<?php
session_start();
require_once "../includes/security.php";
require_admin();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard - BuildSmart</title>
</head>
<body>

<h2>🛠️ Admin Security Dashboard</h2>

<div id="stats"></div>

<h3>Users</h3>
<ul id="users"></ul>

<h3>Logs</h3>
<ul id="logs"></ul>

<script>
function loadDashboard(){

    // Load stats
    fetch("../api/admin/dashboard.php")
    .then(res => res.json())
    .then(data => {
        document.getElementById("stats").innerHTML =
            "Users: " + data.users +
            " | Chats: " + data.chats +
            " | Projects: " + data.projects +
            " | Logs: " + data.logs;
    });

    // Load users
    fetch("../api/admin/users.php")
    .then(res => res.json())
    .then(users => {
        renderUsers(users);
    });

    // Load logs
    fetch("../api/admin/logs.php")
    .then(res => res.json())
    .then(logs => {
        let list = document.getElementById("logs");
        list.innerHTML = "";

        logs.forEach(l => {
            let li = document.createElement("li");
            li.innerText = l.action + " | " + l.ip_address + " | " + l.created_at;
            list.appendChild(li);
        });
    });
}

function renderUsers(users){
    let list = document.getElementById("users");
    list.innerHTML = "";

    users.forEach(u => {

        let li = document.createElement("li");

        let toggleStatus = (u.status === 'active') ? 'suspended' : 'active';
        let toggleText = (u.status === 'active') ? 'Suspend' : 'Activate';

        let toggleRole = (u.role === 'admin') ? 'user' : 'admin';
        let roleText = (u.role === 'admin') ? 'Make User' : 'Make Admin';

        li.innerHTML = `
            ${u.name} - ${u.email} (${u.role}) [${u.status}]

            <button onclick="updateStatus(${u.id}, '${toggleStatus}')">${toggleText}</button>
            <button onclick="updateRole(${u.id}, '${toggleRole}')">${roleText}</button>
            <button onclick="deleteUser(${u.id})">Delete</button>
        `;

        list.appendChild(li);
    });
}

function updateStatus(id, status){
    fetch("../api/admin/toggle_user_status.php", {
        method: "POST",
        headers: {"Content-Type":"application/json"},
        body: JSON.stringify({user_id: id, status: status})
    })
    .then(() => loadDashboard());
}

function updateRole(id, role){
    fetch("../api/admin/change_role.php", {
        method: "POST",
        headers: {"Content-Type":"application/json"},
        body: JSON.stringify({user_id: id, role: role})
    })
    .then(() => loadDashboard());
}

function deleteUser(id){
    if(confirm("Are you sure you want to delete this user?")){
        fetch("../api/admin/delete_user.php", {
            method: "POST",
            headers: {"Content-Type":"application/json"},
            body: JSON.stringify({user_id: id})
        })
        .then(() => loadDashboard());
    }
}

loadDashboard();
</script>

</body>
</html>