<?php

require_once "../includes/config.php";
require_once "../includes/database.php";
require_once "../includes/security.php";
require_once "../includes/mailer.php";

header("Content-Type: application/json");

$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

// 🔐 BLOCKED IP CHECK
if(is_ip_blocked($conn)){
    exit(json_encode(["error"=>"Your IP has been blocked due to suspicious activity"]));
}

// 🔐 CSRF
$csrf = $_POST['csrf_token'] ?? '';
if(!verify_csrf_token($csrf)){
    exit(json_encode(["error"=>"Invalid CSRF token"]));
}

$email = trim($_POST['email'] ?? '');
$password = trim($_POST['password'] ?? '');

if(empty($email) || empty($password)){
    exit(json_encode(["error"=>"All fields required"]));
}

// 🔐 RATE LIMIT (EMAIL)
if(is_rate_limited($conn, $email)){
    log_login_attempt($conn, $email, 0);
    auto_block_ip($conn, $ip);
    exit(json_encode(["error"=>"Too many attempts. Try later."]));
}

// 🔐 RATE LIMIT (IP)
if(is_rate_limited($conn, $ip)){
    log_login_attempt($conn, $email, 0);
    auto_block_ip($conn, $ip);
    exit(json_encode(["error"=>"Too many requests from your IP"]));
}

// 🔍 FETCH USER
$stmt = $conn->prepare("SELECT id, password, role, status FROM users WHERE email=? LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// ❌ INVALID LOGIN
if(!$user || !password_verify($password, $user['password'])){
    log_login_attempt($conn, $email, 0);
    auto_block_ip($conn, $ip);
    exit(json_encode(["error"=>"Invalid credentials"]));
}

// ❌ ACCOUNT STATUS
if($user['status'] !== 'active'){
    exit(json_encode(["error"=>"Account suspended"]));
}

// 🔐 GENERATE OTP
$otp = rand(100000, 999999);

$stmt = $conn->prepare("
    UPDATE users 
    SET otp_code=?, otp_expires=DATE_ADD(NOW(), INTERVAL 5 MINUTE)
    WHERE id=?
");
$stmt->bind_param("si", $otp, $user['id']);
$stmt->execute();

// 📧 SEND OTP EMAIL
$message = "
<h3>Your Login OTP</h3>
<p>Your OTP code is: <b>$otp</b></p>
<p>This code expires in 5 minutes.</p>
";

send_email($email, "Your Login OTP", $message);

// ✅ LOG SUCCESS ATTEMPT
log_login_attempt($conn, $email, 1);

// 🔐 DO NOT CREATE SESSION YET (WAIT FOR OTP)
echo json_encode([
    "otp_required" => true,
    "user_id" => $user['id']
]);