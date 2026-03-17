const API_URL = "https://yourdomain.com/api/ask_ai.php";

function addMessage(text, type) {
  const chatBox = document.getElementById("chat-box");
  const div = document.createElement("div");
  div.classList.add("message", type);
  div.innerText = text;
  chatBox.appendChild(div);
  chatBox.scrollTop = chatBox.scrollHeight;
}

async function sendQuestion() {
  const input = document.getElementById("question");
  const question = input.value;

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
        token: "CSRF_TOKEN_HERE"
      })
    });

    const data = await res.json();

    addMessage("AI: " + (data.knowledge || "No answer found"), "ai");

  } catch (err) {
    addMessage("Error connecting to AI", "ai");
  }
}