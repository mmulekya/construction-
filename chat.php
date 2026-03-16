<!DOCTYPE html>
<html>
<head>
<title>BuildSmart AI</title>
</head>

<body>

<h2>BuildSmart AI Assistant</h2>

<input type="text" id="question" placeholder="Ask construction question">

<button onclick="askAI()">Ask</button>

<div id="response"></div>

<script>

function askAI(){

let q = document.getElementById("question").value;

fetch("api/ask_ai.php",{
method:"POST",
headers:{
"Content-Type":"application/x-www-form-urlencoded"
},
body:"question="+q
})
.then(res=>res.json())
.then(data=>{
document.getElementById("response").innerHTML=data.reply;
});

}

</script>

</body>
</html>