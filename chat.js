const API_URL = "api/ask_ai.php";

function addMessage(text, type) {
  const chatBox = document.getElementById("chat-box");
  const div = document.createElement("div");
  div.classList.add("message", type);
  div.innerText = text;
  chatBox.appendChild(div);
  chatBox.scrollTop = chatBox.scrollHeight;
}

async function loadHistory(){
  const res = await fetch("api/history.php");
  const data = await res.json();

  data.reverse().forEach(chat => {
    addMessage("You: " + chat.question, "user");
    addMessage("BuildSmart AI: " + chat.answer, "ai");
  });
}

function clearChat(){
  document.getElementById("chat-box").innerHTML = "";
}

async function sendQuestion() {
  const input = document.getElementById("question");
  const question = input.value.trim();

  if (!question) return;

  addMessage("You: " + question, "user");
  input.value = "";

  try {
    const res = await fetch(API_URL, {
      method: "POST",
      headers: {
        "Content-Type": "application/json"
      },
      body: JSON.stringify({
        question: question
      })
    });

    const data = await res.json();

    addMessage("BuildSmart AI: " + data.answer, "ai");

  } catch (err) {
    addMessage("Server error", "ai");
  }
}

window.onload = loadHistory;