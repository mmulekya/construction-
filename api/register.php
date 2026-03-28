<?php
require_once "../includes/config.php";
require_once "../includes/database.php";
require_once "../includes/security.php";

header("Content-Type: application/json");
session_start();

// CSRF
$csrf = $_POST['csrf_token'] ?? '';
if(!verify_csrf_token($csrf)){
    exit(json_encode(["error"=>"Invalid CSRF token"]));
}

// Input validation
$name = sanitize($_POST['name'] ?? '');
$email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$password = trim($_POST['password'] ?? '');

if(!$name || !$email || !$password){
    exit(json_encode(["error"=>"All fields are required"]));
}

// Check if email exists
$stmt = db_prepare("SELECT id FROM users WHERE email=? LIMIT 1");
$stmt->bind_param("s",$email);
$stmt->execute();
if($stmt->get_result()->num_rows > 0){
    $stmt->close();
    exit(json_encode(["error"=>"Email already exists"]));
}
$stmt->close();

// Hash password
$hash = password_hash($password, PASSWORD_BCRYPT);

// Insert user
$stmt = db_prepare("INSERT INTO users (name,email,password,role,status) VALUES (?,?,?, 'user', 'active')");
$stmt->bind_param("sss",$name,$email,$hash);
$stmt->execute();
$stmt->close();

echo json_encode(["success"=>true,"message"=>"Registration successful"]);