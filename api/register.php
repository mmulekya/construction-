<?php

require_once "../includes/config.php";
require_once "../includes/database.php";
require_once "../includes/security.php";

header("Content-Type: application/json");

// CSRF
if(!verify_csrf_token($_POST['csrf_token'] ?? '')){
    exit(json_encode(["error"=>"Invalid CSRF"]));
}

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if(empty($name) || empty($email) || empty($password)){
    exit(json_encode(["error"=>"All fields required"]));
}

// Validate email
if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
    exit(json_encode(["error"=>"Invalid email"]));
}

// Strong password
if(strlen($password) < 6){
    exit(json_encode(["error"=>"Password must be at least 6 characters"]);
}

// Check existing
$stmt = $conn->prepare("SELECT id FROM users WHERE email=?");
$stmt->bind_param("s", $email);
$stmt->execute();

if($stmt->get_result()->num_rows > 0){
    exit(json_encode(["error"=>"Email already exists"]));
}

// Hash password
$hashed = password_hash($password, PASSWORD_BCRYPT);

// Email verification token
$token = bin2hex(random_bytes(32));

$stmt = $conn->prepare("
    INSERT INTO users (name, email, password, verify_token, status)
    VALUES (?, ?, ?, ?, 'inactive')
");
$stmt->bind_param("ssss", $name, $email, $hashed, $token);
$stmt->execute();

// NOTE: Send email here (or simulate)
echo json_encode([
    "success"=>true,
    "message"=>"Registered. Please verify your email."
]);