const API_URL = "api/ask_ai.php";
const CSRF_TOKEN = ""; // Already handled server-side

function addMessage(text,type){
    const chatBox = document.getElementById("chat-box");
    const div = document.createElement("div");
    div.classList.add("message", type);
    div.innerText = text;
    chatBox.appendChild(div);
    chatBox.scrollTop = chatBox.scrollHeight;
}


function createProject(){
    let name = document.getElementById("project_name").value;
    let desc = document.getElementById("project_desc").value;

    fetch("api/create_project.php", {
        method: "POST",
        headers: {"Content-Type":"application/json"},
        body: JSON.stringify({name:name, description:desc})
    })
    .then(res => res.json())
    .then(() => {
        loadProjects();
    });
}
async function sendQuestion(){
    const input = document.getElementById("question");
    const question = input.value.trim();
    if(!question) return;
    addMessage("You: "+question,"user");
    input.value="";

    try{
        const res = await fetch(API_URL,{
            method:"POST",
            headers:{"Content-Type":"application/json"},
            body:JSON.stringify({question})
        });
        const data = await res.json();
        addMessage("BuildSmart AI: "+data.answer,"ai");
    }catch(err){
        addMessage("Server error. Try later.","ai");
    }
}

function quickAsk(q){
    document.getElementById("question").value = q;
    sendQuestion();
}

window.onload = function(){
    if(typeof loadHistory==="function") loadHistory();
}

function loadProjects(){
    fetch("api/get_projects.php")
    .then(res => res.json())
    .then(data => {
        let list = document.getElementById("project_list");
        list.innerHTML = "";

        data.forEach(p => {
            let li = document.createElement("li");
            li.innerHTML = p.name + " - " + p.description;
            li.onclick = () => selectProject(p.id, p.name);
            list.appendChild(li);
        });
    });
}