<?php require_once "includes/security.php"; ?>

<!DOCTYPE html>
<html>
<head>
    <title>Register</title>
</head>
<body>

<h2>Register</h2>

<form id="registerForm">

<input type="text" name="name" placeholder="Name" required>
<input type="email" name="email" placeholder="Email" required>
<input type="password" name="password" placeholder="Password" required>

<input type="hidden" name="csrf_token" id="csrf_token">

<button type="submit">Register</button>

</form>

<script>
fetch('includes/get_csrf.php')
.then(res=>res.json())
.then(data=>{
    document.getElementById('csrf_token').value = data.token;
});

document.getElementById('registerForm').onsubmit = function(e){
    e.preventDefault();

    let formData = new FormData(this);

    fetch('api/register.php',{
        method:'POST',
        body:formData
    })
    .then(res=>res.json())
    .then(data=>{
        alert(data.message || data.error);
        if(data.success){
            window.location.href='login.php';
        }
    });
};
</script>

</body>
</html>