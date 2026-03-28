<?php
require_once "../includes/config.php";
require_once "../includes/database.php";
require_once "../includes/security.php";
require_once "../includes/rate_limit.php";

header("Content-Type: application/json");
session_start();

// 🔐 RATE LIMIT PER IP
check_rate_limit("login_" . ($_SERVER['REMOTE_ADDR'] ?? ''), 5, 60);

// 🔐 CSRF token
$csrf = $_POST['csrf_token'] ?? '';
if(!verify_csrf_token($csrf)){
    exit(json_encode(["error"=>"Invalid CSRF token"]));
}

// Input sanitization
$email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$password = trim($_POST['password'] ?? '');

if(!$email || !$password){
    exit(json_encode(["error"=>"Email and password required"]));
}

// Brute-force check
if(is_rate_limited($email)){
    log_login_attempt($email, 0);
    exit(json_encode(["error"=>"Too many attempts, try later"]));
}

// Fetch user
$stmt = db_prepare("SELECT id, password, role, status FROM users WHERE email=? LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if(!$user || !password_verify($password, $user['password'])){
    log_login_attempt($email, 0);
    exit(json_encode(["error"=>"Invalid credentials"]));
}

// Successful login
$_SESSION['user_id'] = $user['id'];
$_SESSION['role'] = $user['role'];
log_login_attempt($email, 1);

echo json_encode(["success"=>true,"message"=>"Login successful"]);