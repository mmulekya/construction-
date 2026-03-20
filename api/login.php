<?php

require_once "../includes/config.php";
require_once "../includes/database.php";
require_once "../includes/security.php";

session_start();

header("Content-Type: application/json");

// CSRF check
$csrf = $_POST['csrf_token'] ?? '';
if(!verify_csrf_token($csrf)){
    echo json_encode(["error"=>"Invalid CSRF token"]);
    exit;
}

$email = trim($_POST['email'] ?? '');
$password = trim($_POST['password'] ?? '');

if(empty($email) || empty($password)){
    echo json_encode(["error"=>"All fields required"]);
    exit;
}

// Rate limit reuse from security.php
if(is_rate_limited($conn, $email)){
    log_login_attempt($conn, $email, 0);
    echo json_encode(["error"=>"Too many attempts"]);
    exit;
}

$stmt = $conn->prepare("SELECT * FROM users WHERE email=? LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();

$user = $stmt->get_result()->fetch_assoc();

if(!$user || !password_verify($password, $user['password'])){
    log_login_attempt($conn, $email, 0);
    echo json_encode(["error"=>"Invalid credentials"]);
    exit;
}

$_SESSION['user_id'] = $user['id'];
$_SESSION['role'] = $user['role'];

log_login_attempt($conn, $email, 1);

echo json_encode([
    "success"=>true,
    "message"=>"Login successful"
]);