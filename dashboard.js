async function loadStats() {
  const res = await fetch("https://yourdomain.com/api/chat_trends.php");
  const data = await res.json();

  console.log("Top Questions:", data.trends);
}