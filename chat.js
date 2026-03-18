const API_URL = "api/ask_ai.php";

function addMessage(text, type) {
  const chatBox = document.getElementById("chat-box");
  const div = document.createElement("div");
  div.classList.add("message", type);
  div.innerText = text;
  chatBox.appendChild(div);
  chatBox.scrollTop = chatBox.scrollHeight;
}

function quickAsk(q){
  document.getElementById("question").value = q;
  sendQuestion();
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
        question: question,
        token: CSRF_TOKEN
      })
    });

    const data = await res.json();

    if(data.answer){
      addMessage("BuildSmart AI: " + data.answer, "ai");
    } else {
      addMessage("AI error occurred", "ai");
    }

  } catch (err) {
    addMessage("Server error. Try again later.", "ai");
  }
}