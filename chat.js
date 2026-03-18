document.addEventListener("DOMContentLoaded", function(){

  // ---------------------------
  // 1️⃣ CHAT FUNCTIONALITY
  // ---------------------------
  const chatForm = document.getElementById("chatForm"); // your chat form
  const chatBox = document.getElementById("chat-box"); // where messages appear

  if(chatForm && chatBox){
    chatForm.addEventListener("submit", async function(e){
      e.preventDefault();

      const questionInput = document.getElementById("question");
      if(!questionInput) return;

      const question = questionInput.value.trim();
      if(!question) return;

      // Show user message
      const userMsg = document.createElement("p");
      userMsg.innerText = "You: " + question;
      userMsg.classList.add("user");
      chatBox.appendChild(userMsg);

      questionInput.value = "";

      // Fetch AI response
      try{
        const res = await fetch("api/ask_ai.php", {
          method:"POST",
          headers:{"Content-Type":"application/json"},
          body: JSON.stringify({question})
        });

        const data = await res.json();

        const aiMsg = document.createElement("p");
        aiMsg.classList.add("ai");

        if(data.answer){
          aiMsg.innerText = "BuildSmart AI: " + data.answer;
        } else {
          aiMsg.innerText = "BuildSmart AI: Error retrieving answer";
        }

        chatBox.appendChild(aiMsg);
        chatBox.scrollTop = chatBox.scrollHeight;

      } catch(err){
        const aiMsg = document.createElement("p");
        aiMsg.classList.add("ai");
        aiMsg.innerText = "BuildSmart AI: Failed to get response";
        chatBox.appendChild(aiMsg);
      }
    });
  }

  // ---------------------------
  // 2️⃣ PDF UPLOAD (ADMIN PANEL)
  // ---------------------------
  const pdfForm = document.getElementById("pdfForm");
  const status = document.getElementById("status");
  const preview = document.getElementById("preview");

  if(pdfForm){
    pdfForm.addEventListener("submit", async function(e){
      e.preventDefault();

      if(status) status.innerText = "Uploading...";
      if(preview) preview.innerText = "";

      const formData = new FormData(pdfForm);

      try {
        const res = await fetch("api/admin/upload_pdf.php", {
          method: "POST",
          body: formData
        });

        let data;
        try { data = await res.json(); } catch { throw new Error("Invalid server response"); }

        if(data.success){
          if(status) status.innerText = "✅ " + data.success;
          if(preview) preview.innerText = data.preview || "No preview available";
        } else {
          if(status) status.innerText = "❌ " + (data.error || "Unknown error");
        }

      } catch(err){
        if(status) status.innerText = "❌ Upload failed: " + err.message;
      }

    });
  }

});