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
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

if(empty($email) || empty($password)){
    exit(json_encode(["error"=>"All fields required"]));
}

// 🔐 Rate limit (email)
if(is_rate_limited($conn, $email)){
    log_login_attempt($conn, $email, 0);
    exit(json_encode(["error"=>"Too many attempts. Try later."]));
}

// 🔐 Rate limit (IP)
if(is_rate_limited($conn, $ip)){
    log_login_attempt($conn, $email, 0);
    exit(json_encode(["error"=>"Too many requests from your IP"]));
}

// Fetch user
$stmt = $conn->prepare("SELECT id, password, role, status FROM users WHERE email=? LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Validate credentials
if(!$user || !password_verify($password, $user['password'])){
    log_login_attempt($conn, $email, 0);
    exit(json_encode(["error"=>"Invalid credentials"]));
}

// Block suspended users
if($user['status'] !== 'active'){
    exit(json_encode(["error"=>"Account suspended"]));
}

// 🔐 Generate OTP (2FA)
$otp = rand(100000, 999999);

$stmt = $conn->prepare("
    UPDATE users 
    SET otp_code=?, otp_expires=DATE_ADD(NOW(), INTERVAL 5 MINUTE)
    WHERE id=?
");
$stmt->bind_param("si", $otp, $user['id']);
$stmt->execute();

// Log success attempt (before OTP verification step)
log_login_attempt($conn, $email, 1);

// 🔐 IMPORTANT: Do NOT create session yet (wait for OTP)
echo json_encode([
    "otp_required" => true,
    "user_id" => $user['id'],

    // ⚠️ REMOVE THIS IN PRODUCTION
    "otp" => $otp
]);