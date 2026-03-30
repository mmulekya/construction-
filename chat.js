let token = localStorage.getItem("jwt");

fetch("api/ask_ai.php",{
    method:"POST",
    headers:{
        "Content-Type":"application/json",
        "Authorization": token
    },
    body: JSON.stringify({
        question:q,
        csrf_token: csrfToken
    })
})

// ========================================
// 🔐 SAFE CHAT MODULE (NO COLLISIONS)
// ========================================
document.addEventListener("DOMContentLoaded", () => {

    // -----------------------------
    // 📦 SAFE ELEMENT CHECK
    // -----------------------------
    const chatBox = document.getElementById("chat-box");
    if (!chatBox) return; // STOP if not chat page

    const input = document.getElementById("question");
    let csrfToken = document.getElementById("csrf_token")?.value || "";

    // -----------------------------
    // 🔐 FETCH CSRF (SAFE)
    // -----------------------------
    fetch('includes/get_csrf.php', { credentials: "same-origin" })
    .then(res => res.json())
    .then(data => {
        csrfToken = data.token;
    })
    .catch(() => console.warn("CSRF fetch failed"));

    // -----------------------------
    // 🧼 SANITIZE OUTPUT (ANTI-XSS)
    // -----------------------------
    function safeText(text) {
        return text
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;");
    }

    // -----------------------------
    // 🧱 ADD MESSAGE
    // -----------------------------
    function addMessage(text, type) {
        const div = document.createElement("div");
        div.className = "message " + type;
        div.innerHTML = safeText(text);

        chatBox.appendChild(div);
        chatBox.scrollTop = chatBox.scrollHeight;
        return div;
    }

    // -----------------------------
    // ✍️ STREAM AI RESPONSE
    // -----------------------------
    function streamText(el, text) {
        let i = 0;
        function type() {
            if (i < text.length) {
                el.innerHTML += safeText(text.charAt(i));
                i++;
                setTimeout(type, 10);
            }
        }
        type();
    }

    // -----------------------------
    // 🚫 ANTI-SPAM LIMIT
    // -----------------------------
    let lastRequest = 0;

    function canSend() {
        const now = Date.now();
        if (now - lastRequest < 1500) {
            alert("Too fast. Please wait.");
            return false;
        }
        lastRequest = now;
        return true;
    }

    // -----------------------------
    // 💬 SEND MESSAGE
    // -----------------------------
    window.sendQuestion = function () {

        if (!input) return;

        let q = input.value.trim();

        if (q.length < 3 || q.length > 500) {
            alert("Invalid question length");
            return;
        }

        if (!canSend()) return;

        addMessage(q, "user");
        input.value = "";

        let aiMsg = addMessage("Thinking...", "ai");

        fetch("api/ask_ai.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            credentials: "same-origin",
            body: JSON.stringify({
                question: q,
                csrf_token: csrfToken
            })
        })
        .then(res => res.json())
        .then(data => {

            aiMsg.innerHTML = "";

            if (data.error) {
                aiMsg.innerHTML = safeText(data.error);
                return;
            }

            streamText(aiMsg, data.answer || data.response);

        })
        .catch(() => {
            aiMsg.innerHTML = "⚠️ Network error";
        });
    };

    // -----------------------------
    // 📜 LOAD HISTORY
    // -----------------------------
    function loadHistory() {

        fetch("api/history.php", {
            credentials: "same-origin"
        })
        .then(res => res.json())
        .then(data => {

            if (!data.history) return;

            data.history.reverse().forEach(chat => {
                addMessage(chat.message, "user");
                addMessage(chat.response, "ai");
            });

        })
        .catch(() => console.warn("History load failed"));
    }

    loadHistory();

    // -----------------------------
    // 🚪 LOGOUT
    // -----------------------------
    window.logout = function () {
        fetch("api/logout.php", { credentials: "same-origin" })
        .then(() => window.location.href = "login.php");
    };

    // -----------------------------
    // 🛠 ADMIN FUNCTIONS (SAFE)
    // -----------------------------
    const adminDiv = document.getElementById("admin-data");

    if (adminDiv) {

        window.loadUsers = function () {
            fetch("api/admin/get_users.php", { credentials: "same-origin" })
            .then(res => res.json())
            .then(data => {
                adminDiv.textContent = JSON.stringify(data.users, null, 2);
            });
        };

        window.loadChats = function () {
            fetch("api/admin/get_chats.php", { credentials: "same-origin" })
            .then(res => res.json())
            .then(data => {
                adminDiv.textContent = JSON.stringify(data.chats, null, 2);
            });
        };
    }

    // -----------------------------
    // 📁 PROJECT CREATION
    // -----------------------------
    const projectInput = document.getElementById("project_name");

    if (projectInput) {

        window.createProject = function () {

            let name = projectInput.value.trim();

            if (name.length < 3) {
                alert("Project name too short");
                return;
            }

            fetch("api/create_projects.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded"
                },
                credentials: "same-origin",
                body: "name=" + encodeURIComponent(name) + "&csrf_token=" + csrfToken
            })
            .then(res => res.json())
            .then(data => {
                alert(data.success ? "Project created" : data.error);
            });
        };
    }

    // -----------------------------
    // 🚫 BANNED IP VIEW
    // -----------------------------
    const bannedDiv = document.getElementById("banned");

    if (bannedDiv) {
        window.loadBanned = function () {
            fetch("api/admin/logs.php", { credentials: "same-origin" })
            .then(res => res.json())
            .then(data => {
                bannedDiv.innerHTML = "";

                data.banned?.forEach(b => {
                    const p = document.createElement("p");
                    p.style.color = "red";
                    p.textContent = `${b.ip} - ${b.reason}`;
                    bannedDiv.appendChild(p);
                });
            });
        };
    }

});