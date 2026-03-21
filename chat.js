// Load CSRF token
fetch('includes/get_csrf.php')
.then(res => res.json())
.then(data => {
    localStorage.setItem("csrf_token", data.token);
    document.querySelectorAll("#csrf_token").forEach(el=>{
        el.value = data.token;
    });
});

// Ask AI
function askAI(){
    let question = document.getElementById("question").value;

    fetch("api/ask_ai.php",{
        method:"POST",
        headers: {"Content-Type":"application/x-www-form-urlencoded"},
        body: "question="+encodeURIComponent(question)+"&csrf_token="+localStorage.getItem("csrf_token")
    })
    .then(res=>res.json())
    .then(data=>{
        document.getElementById("chat-box").innerHTML += 
        "<p><b>You:</b> "+question+"</p>"+
        "<p><b>AI:</b> "+data.response+"</p>";
    });
}

// Logout
function logout(){
    fetch("api/logout.php")
    .then(()=>location.href="login.php");
}

// Admin: Load users
function loadUsers(){
    fetch("api/admin/get_users.php")
    .then(res=>res.json())
    .then(data=>{
        document.getElementById("admin-data").innerHTML =
        JSON.stringify(data.users, null, 2);
    });
}

// Admin: Load chats
function loadChats(){
    fetch("api/admin/get_chats.php")
    .then(res=>res.json())
    .then(data=>{
        document.getElementById("admin-data").innerHTML =
        JSON.stringify(data.chats, null, 2);
    });
}

// Create project
function createProject(){
    let name = document.getElementById("project_name").value;

    fetch("api/create_projects.php",{
        method:"POST",
        headers: {"Content-Type":"application/x-www-form-urlencoded"},
        body: "name="+encodeURIComponent(name)+"&csrf_token="+localStorage.getItem("csrf_token")
    })
    .then(res=>res.json())
    .then(data=>{
        alert("Project created");
    });
}