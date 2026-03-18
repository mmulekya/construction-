<?php
session_start();
require_once "includes/security.php";
require_admin();
?>

<!DOCTYPE html>
<html>
<head>
  <title>Admin Dashboard - BuildSmart</title>
</head>
<body>

<h1>⚙️ Admin Dashboard</h1>

<h2>👥 Users</h2>
<div id="users"></div>

<h2>💬 Chat History</h2>
<div id="chats"></div>

<h2>🧠 Add Knowledge</h2>
<textarea id="knowledge" placeholder="Enter knowledge..."></textarea><br>
<button onclick="addKnowledge()">Add</button>

<script>

async function loadUsers(){
  const res = await fetch("api/admin/get_users.php");
  const data = await res.json();

  let html = "";
  data.forEach(u=>{
    html += `<p>${u.username} (${u.email}) - ${u.role}</p>`;
  });

  document.getElementById("users").innerHTML = html;
}

async function loadChats(){
  const res = await fetch("api/admin/get_chats.php");
  const data = await res.json();

  let html = "";
  data.forEach(c=>{
    html += `<p><b>${c.username}:</b> ${c.question}<br>AI: ${c.answer}</p><hr>`;
  });

  document.getElementById("chats").innerHTML = html;
}

async function addKnowledge(){
  const content = document.getElementById("knowledge").value;

  const res = await fetch("api/admin/add_knowledge.php", {
    method:"POST",
    headers:{"Content-Type":"application/json"},
    body:JSON.stringify({content})
  });

  const data = await res.json();
  alert(data.success || data.error);
}

loadUsers();
loadChats();

</script>

</body>
</html>