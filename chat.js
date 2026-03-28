// =========================
// 🔐 CSRF (SAFE HANDLING)
// =========================
let CSRF_TOKEN = document.getElementById("csrf_token")?.value || "";

// =========================
// 📦 ELEMENTS
// =========================
const chatBox = document.getElementById("chat-box");

function downloadBackup(){
    window.location.href = "api/admin/backup_db.php";
}

// =========================
// 🚀 LOAD HISTORY
// =========================
window.onload = loadHistory;

// =========================
// 💬 SEND QUESTION
// =========================
function sendQuestion(){
    let input = document.getElementById("question");
    let q = input.value.trim();

    if(!q || q.length < 3){
        alert("Enter a valid question");
        return;
    }

    // Show user message
    addMessage(q, "user");
    input.value = "";

    // Show typing placeholder
    let aiMsg = addMessage("Typing...", "ai");

    fetch("api/ask_ai.php",{
        method:"POST",
        headers: {"Content-Type":"application/x-www-form-urlencoded"},
        body: "question="+encodeURIComponent(q)+"&csrf_token="+CSRF_TOKEN
    })
    .then(res=>res.json())
    .then(data=>{
        aiMsg.innerText = "";

        if(data.error){
            aiMsg.innerText = data.error;
        } else {
            streamText(aiMsg, data.response);
        }
    })
    .catch(()=>{
        aiMsg.innerText = "Network error. Try again.";
    });
}

// =========================
// ✍️ STREAM TEXT (AI typing)
// =========================
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

// =========================
// 🧱 ADD MESSAGE
// =========================
function addMessage(text, type){
    let div = document.createElement("div");
    div.className = "message " + type;
    div.innerText = text;

    chatBox.appendChild(div);
    chatBox.scrollTop = chatBox.scrollHeight;

    return div;
}

// =========================
// 📜 LOAD HISTORY
// =========================
function loadHistory(){
    fetch("api/history.php")
    .then(res=>res.json())
    .then(data=>{
        if(data.history){
            data.history.reverse().forEach(chat=>{
                addMessage(chat.message, "user");
                addMessage(chat.response, "ai");
            });
        }
    })
    .catch(()=>console.log("History load failed"));
}

// =========================
// 🧹 CLEAR CHAT (UI ONLY)
// =========================
function clearChat(){
    chatBox.innerHTML = "";
}

// =========================
// 🚪 LOGOUT
// =========================
function logout(){
    fetch("api/logout.php")
    .then(()=>window.location.href="login.php");
}

// =========================
// 🌗 DARK / LIGHT MODE
// =========================
function toggleMode(){
    document.body.classList.toggle("light");
}

// =========================
// ⚡ QUICK ASK
// =========================
function quickAsk(q){
    document.getElementById("question").value = q;
    sendQuestion();
}

// =========================
// 🛠 ADMIN: LOAD USERS
// =========================
function loadUsers(){
    fetch("api/admin/get_users.php")
    .then(res=>res.json())
    .then(data=>{
        document.getElementById("admin-data").innerText =
        JSON.stringify(data.users, null, 2);
    });
}

// =========================
// 🛠 ADMIN: LOAD CHATS
// =========================
function loadChats(){
    fetch("api/admin/get_chats.php")
    .then(res=>res.json())
    .then(data=>{
        document.getElementById("admin-data").innerText =
        JSON.stringify(data.chats, null, 2);
    });
}

// =========================
// 📁 CREATE PROJECT
// =========================
function createProject(){
    let name = document.getElementById("project_name").value;

    if(!name || name.length < 3){
        alert("Project name too short");
        return;
    }

    fetch("api/create_projects.php",{
        method:"POST",
        headers: {"Content-Type":"application/x-www-form-urlencoded"},
        body: "name="+encodeURIComponent(name)+"&csrf_token="+CSRF_TOKEN
    })
    .then(res=>res.json())
    .then(data=>{
        if(data.error){
            alert(data.error);
        } else {
            alert("Project created successfully");
        }
    });
}