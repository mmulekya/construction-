<?php<?php
session_start();
require_once "includes/config.php";
require_once "includes/database.php";
require_once "includes/security.php";
session_start();
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

<h2>📄 Upload PDF</h2>

<form id="pdfForm" enctype="multipart/form-data">
  <input type="file" name="pdf" accept="application/pdf" required><br><br>
  <button type="submit">Upload PDF</button>
</form>

<p id="status"></p>

<h3>Preview Extracted Text:</h3>
<div id="preview" style="background:#eee;padding:10px;"></div>

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