<!DOCTYPE html>
<html>
<head>
    <title>Reset Password</title>
</head>
<body>

<h2>Reset Password</h2>

<input type="hidden" id="email" value="<?php echo $_GET['email'] ?? ''; ?>">
<input type="hidden" id="token" value="<?php echo $_GET['token'] ?? ''; ?>">

<input type="password" id="password" placeholder="New Password">
<button onclick="resetPassword()">Reset</button>

<script>
function resetPassword(){
    fetch("api/reset_password.php", {
        method: "POST",
        headers: {"Content-Type":"application/json"},
        body: JSON.stringify({
            email: document.getElementById("email").value,
            token: document.getElementById("token").value,
            password: document.getElementById("password").value
        })
    })
    .then(res => res.json())
    .then(data => alert(JSON.stringify(data)));
}
</script>

</body>
</html>