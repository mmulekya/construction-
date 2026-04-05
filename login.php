<?php require_once "includes/security.php"; ?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Login - BuildSmart AI</title>

<style>
body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: #f5f7fa;
}

/* Header */
header {
    background: #007bff;
    color: white;
    padding: 15px;
    text-align: center;
}

header a {
    color: white;
    margin: 0 10px;
    text-decoration: none;
}

/* Container */
.login-container {
    width: 90%;
    max-width: 400px;
    margin: 50px auto;
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0px 5px 15px rgba(0,0,0,0.1);
    text-align: center;
}

h2 { margin-bottom: 10px; }

p.desc {
    font-size: 14px;
    color: #555;
    margin-bottom: 20px;
}

input {
    width: 100%;
    padding: 12px;
    margin-bottom: 10px;
    border: 1px solid #ccc;
    border-radius: 6px;
}

button {
    width: 100%;
    padding: 12px;
    background: #007bff;
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
}

button:hover { background: #0056b3; }

#message {
    margin-top: 10px;
    color: red;
}

/* OTP */
#otpBox {
    display: none;
    margin-top: 20px;
}
</style>

</head>
<body>

<header>
    <h1>BuildSmart AI</h1>
    <div>
        <a href="index.php">Home</a>
        <a href="about.php">About</a>
        <a href="contact.php">Contact</a>
    </div>
</header>

<div class="login-container">

    <h2>🔐 Login</h2>

    <p class="desc">
        Secure login to access your AI-powered construction assistant.
    </p>

    <form id="loginForm">
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password" required>
        <input type="hidden" name="csrf_token" id="csrf_token">
        <button type="submit">Login</button>
    </form>

    <p style="margin-top:10px;">
        New user? <a href="register.php">Create an account</a>
    </p>

    <div id="message"></div>

    <!-- OTP -->
    <div id="otpBox">
        <h3>Enter OTP</h3>
        <input type="text" id="otp" placeholder="Enter OTP">
        <button onclick="verifyOTP()">Verify</button>
        <div id="otpMessage"></div>
    </div>

</div>

<script>
let csrfToken = "";

// Load CSRF
fetch('includes/get_csrf.php')
.then(res=>res.json())
.then(data=>{
    document.getElementById('csrf_token').value = data.token;
    csrfToken = data.token;
});

// LOGIN
document.getElementById('loginForm').onsubmit = function(e){
    e.preventDefault();

    let formData = new FormData(this);

    fetch('api/login.php',{
        method:'POST',
        body:formData
    })
    .then(res=>res.json())
    .then(data=>{
        if(data.otp_required){
            document.getElementById("otpBox").style.display = "block";
            document.getElementById("otpMessage").innerText = "OTP sent to email";
        } else {
            document.getElementById("message").innerText = data.error;
        }
    })
    .catch(()=>{
        document.getElementById("message").innerText = "Server error";
    });
};

// VERIFY OTP
function verifyOTP(){
    let otp = document.getElementById("otp").value;

    fetch('api/verify_otp.php',{
        method:'POST',
        headers: {"Content-Type":"application/x-www-form-urlencoded"},
        body: "otp="+encodeURIComponent(otp)+"&csrf_token="+csrfToken
    })
    .then(res=>res.json())
    .then(data=>{
        if(data.success){
            localStorage.setItem("jwt", data.token);
            window.location.href = "index.php";
        } else {
            document.getElementById("otpMessage").innerText = data.error;
        }
    });
}
</script>

</body>
</html>