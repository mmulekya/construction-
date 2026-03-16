<?php

require_once "includes/database.php";
require_once "includes/auth.php";
require_once "includes/security.php";

?>

<!DOCTYPE html>
<html>
<head>
<title>BuildSmart AI</title>
</head>

<body>

<h2>BuildSmart Construction AI</h2>

<input type="text" id="question" placeholder="Ask construction question">

<button onclick="askAI()">Ask</button>

<div id="response"></div>

<script>

function askAI(){

let question = document.getElementById("question").value;

fetch("api/ask_ai.php",{
method:"POST",
headers:{
"Content-Type":"application/x-www-form-urlencoded"
},
body:"question="+encodeURIComponent(question)
})
.then(res=>res.json())
.then(data=>{
document.getElementById("response").innerHTML=data.reply;
});

}

</script>

</body>
</html>