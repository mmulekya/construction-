<?php

require_once "includes/database.php";
require_once "includes/security.php";

if($_SERVER["REQUEST_METHOD"]=="POST"){

$email = sanitize_input($_POST['email']);
$password = $_POST['password'];

$stmt = $conn->prepare(
"SELECT id,password FROM users WHERE email=?"
);

$stmt->bind_param("s",$email);
$stmt->execute();

$result = $stmt->get_result();

if($result->num_rows === 1){

$user = $result->fetch_assoc();

if(password_verify($password,$user['password'])){

$_SESSION['user_id'] = $user['id'];

header("Location: chat.php");
exit();

}

}

echo "Invalid login";

}

?>

<form method="POST">

<input type="email" name="email" required>

<input type="password" name="password" required>

<button type="submit">Login</button>

</form>