<?php require_once "includes/security.php"; ?>

<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f5f7fa;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .login-container {
            width: 90%;
            max-width: 400px;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0px 5px 15px rgba(0,0,0,0.1);
            text-align: center;
        }

        h2 { margin-bottom: 20px; }

        input {
            width: 100%;
            padding: 12px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 16px;
        }

        button {
            width: 100%;
            padding: 12px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
        }

        button:hover { background: #0056b3; }

        /* OTP popup */
        #otpBox {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 85%;
            max-width: 300px;
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0px 5px 15px rgba(0,0,0,0.2);
            display: none;
            text-align: center;
        }

        #otpBox input { margin-top: 10px; }

        /* Loading spinner */
        #loading {
            display: none;
            margin: 10px auto;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #007bff;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Inline message */
        #message {
            margin: 10px 0;
            color: red;
            font-weight: bold;
        }

        @media (max-width: 480px) {
            .login-container { padding: 15px; }
            input, button { font-size: 14px; }
        }
    </style>
</head>
<body>

<div class="login-container">
    <h2>🔐 Login</h2>
    <form id="loginForm">
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password" required>
        <input type="hidden" name="csrf_token" id="csrf_token">
        <button type="submit">Login</button>
    </form>

    <!-- Inline message -->
    <div id="message"></div>

    <!-- Loading spinner -->
    <div id="loading"></div>
</div>

<!-- OTP POPUP -->
<div id="otpBox">
    <h3>Enter OTP</h3>
    <input type="text" id="otp" placeholder="Enter OTP">
    <button onclick="verifyOTP()">Verify</button>
    <div id="otpMessage" style="margin-top:10px;color:red;font-weight:bold;"></div>
    <div id="otpLoading" style="display:none;margin:10px auto;border:4px solid #f3f3f3;border-top:4px solid #007bff;border-radius:50%;width:30px;height:30px;animation:spin 1s linear infinite;"></div>
</div>

<script>
let currentUserId = null;
let csrfToken = "";

// Load CSRF
fetch('includes/get_csrf.php')
.then(res=>res.json())
.then(data=>{
    document.getElementById('csrf_token').value = data.token;
    csrfToken = data.token;
});

// Helper functions
function showMessage(text, target='message'){ document.getElementById(target).innerText = text; }
function showLoading(show=true, target='loading'){ document.getElementById(target).style.display = show ? 'block' : 'none'; }

// LOGIN
document.getElementById('loginForm').onsubmit = function(e){
    e.preventDefault();
    showMessage('');
    showLoading(true);

    let formData = new FormData(this);

    fetch('api/login.php',{
        method:'POST',
        body:formData
    })
    .then(res=>res.json())
    .then(data=>{
        showLoading(false);

        if(data.otp_required){
            currentUserId = data.user_id;
            document.getElementById("otpBox").style.display = "block";
            showMessage("OTP sent to email", "otpMessage");
        } else {
            showMessage(data.error);
        }
    })
    .catch(()=>{
        showLoading(false);
        showMessage("Network error. Try again.");
    });
};

// VERIFY OTP
function verifyOTP(){
    let otp = document.getElementById("otp").value;
    showMessage('');
    showLoading(true,'otpLoading');

    fetch('api/verify_otp.php',{
        method:'POST',
        headers: {"Content-Type":"application/x-www-form-urlencoded"},
        body: "user_id="+currentUserId+"&otp="+encodeURIComponent(otp)+"&csrf_token="+csrfToken
    })
    .then(res=>res.json())
    .then(data=>{
        showLoading(false,'otpLoading');

        if(data.success){
            localStorage.setItem("jwt", data.token);
            window.location.href = "index.php";
        } else {
            showMessage(data.error,'otpMessage');
        }
    })
    .catch(()=>{
        showLoading(false,'otpLoading');
        showMessage("Network error.",'otpMessage');
    });
}
</script>

</body>
</html>