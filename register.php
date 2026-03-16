<?php

require_once "includes/database.php";
require_once "includes/security.php";

if($_SERVER["REQUEST_METHOD"]=="POST"){

if(!verify_token($_POST['token'])){
die("Invalid request");
}

$name = sanitize_input($_POST['name']);
$email = sanitize_input($_POST['email']);
$password = password_hash($_POST['password'], PASSWORD_BCRYPT);

$stmt = $conn->prepare(
"INSERT INTO users (name,email,password)
VALUES (?,?,?)"
);

$stmt->bind_param("sss",$name,$email,$password);
$stmt->execute();

echo "Registration successful";

}

?>

<form method="POST">

<input type="text" name="name" placeholder="Name" required>

<input type="email" name="email" placeholder="Email" required>

<input type="password" name="password" required>

<input type="hidden" name="token" value="<?php echo generate_token(); ?>">

<button type="submit">Register</button>

</form>