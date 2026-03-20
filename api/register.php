<?php
require_once "../includes/rate_limit.php";
check_rate_limit($conn, "register", 3, 60);
require_once "../includes/config.php";
require_once "../includes/database.php";
require_once "../includes/security.php";

$data = json_decode(file_get_contents("php://input"), true);

$username = sanitize($data['username'] ?? '');
$email = sanitize($data['email'] ?? '');
$password = $data['password'] ?? '';

if(!$username || !$email || !$password){
    echo json_encode(["error"=>"All fields required"]);
    exit;
}

$hashed = password_hash($password, PASSWORD_BCRYPT);

$stmt = $conn->prepare("INSERT INTO users (username,email,password) VALUES (?,?,?)");
$stmt->bind_param("sss",$username,$email,$hashed);

if($stmt->execute()){
    echo json_encode(["success"=>"Registered"]);
}else{
    echo json_encode(["error"=>"User exists"]);
}