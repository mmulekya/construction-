<?php

require_once "../includes/config.php";
require_once "../includes/database.php";
require_once "../includes/security.php";

header("Content-Type: application/json");

// CSRF
$csrf = $_POST['csrf_token'] ?? '';
if(!verify_csrf_token($csrf)){
    exit(json_encode(["error"=>"Invalid CSRF token"]));
}

$email = trim($_POST['email'] ?? '');
$password = trim($_POST['password'] ?? '');

if(empty($email) || empty($password)){
    exit(json_encode(["error"=>"All fields required"]));
}

// Rate limit
if(is_rate_limited($conn, $email)){
    log_login_attempt($conn, $email, 0);
    exit(json_encode(["error"=>"Too many attempts. Try later."]));
}

// Fetch user
$stmt = $conn->prepare("SELECT id, password, role, status FROM users WHERE email=? LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Validate
if(!$user || !password_verify($password, $user['password'])){
    log_login_attempt($conn, $email, 0);
    exit(json_encode(["error"=>"Invalid credentials"]));
}

// Block suspended users
if($user['status'] !== 'active'){
    exit(json_encode(["error"=>"Account suspended"]));
}

// Session fixation protection
session_regenerate_id(true);

$_SESSION['user_id'] = $user['id'];
$_SESSION['role'] = $user['role'];

log_login_attempt($conn, $email, 1);

echo json_encode(["success"=>true]);