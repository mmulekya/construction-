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

<script>
fetch('includes/get_csrf.php')
.then(res=>res.json())
.then(data=>{
    document.getElementById('csrf_token').value = data.token;
});

document.getElementById('loginForm').onsubmit = function(e){
    e.preventDefault();

    let formData = new FormData(this);

    fetch('api/login.php',{
        method:'POST',
        body:formData
    })
    .then(res=>res.json())
    .then(data=>{
        if(data.success){
            window.location.href='index.php';
        } else {
            alert(data.error);
        }
    });
};
</script>

</body>
</html>