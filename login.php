<?php require_once "includes/security.php"; ?>

<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
</head>
<body>

<h2>Login</h2>

<form id="loginForm">
    <input type="email" name="email" placeholder="Email" required>
    <input type="password" name="password" placeholder="Password" required>
    <input type="hidden" name="csrf_token" id="csrf_token">
    <button type="submit">Login</button>
</form>

<!-- OTP POPUP -->
<div id="otpBox" style="display:none; border:1px solid #000; padding:10px;">
    <h3>Enter OTP</h3>
    <input type="text" id="otp" placeholder="Enter OTP">
    <button onclick="verifyOTP()">Verify</button>
</div>

<script>
let currentUserId = null;

// Load CSRF
fetch('includes/get_csrf.php')
.then(res=>res.json())
.then(data=>{
    document.getElementById('csrf_token').value = data.token;
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
            currentUserId = data.user_id;
            document.getElementById("otpBox").style.display = "block";
            alert("OTP sent (check email)");
        } else {
            alert(data.error);
        }
    });
};

// VERIFY OTP
function verifyOTP(){
    let otp = document.getElementById("otp").value;

    fetch('api/verify_otp.php',{
        method:'POST',
        headers: {"Content-Type":"application/x-www-form-urlencoded"},
        body: "user_id="+currentUserId+"&otp="+otp
    })
    .then(res=>res.json())
    .then(data=>{
        if(data.success){
            localStorage.setItem("jwt", data.token);
            window.location.href = "index.php";
        } else {
            alert(data.error);
        }
    });
}
</script>

</body>
</html>