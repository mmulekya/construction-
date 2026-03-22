<?php

require_once "../includes/config.php";
require_once "../includes/database.php";
require_once "../includes/security.php";
require_once "../includes/mailer.php";

header("Content-Type: application/json");

// Get client IP safely
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

/* =========================
   🔐 BLOCKED IP CHECK
========================= */
if(is_ip_blocked($conn)){
    exit(json_encode([
        "error"=>"Access denied. Your IP has been blocked."
    ]));
}

/* =========================
   🔐 CSRF PROTECTION
========================= */
$csrf = $_POST['csrf_token'] ?? '';
if(!verify_csrf_token($csrf)){
    exit(json_encode([
        "error"=>"Invalid CSRF token"
    ]));
}

/* =========================
   🧾 INPUT VALIDATION
========================= */
$email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$password = trim($_POST['password'] ?? '');

if(!$email || empty($password)){
    exit(json_encode([
        "error"=>"Valid email and password required"
    ]));
}

/* =========================
   🔐 RATE LIMIT (EMAIL)
========================= */
if(is_rate_limited($conn, $email)){
    log_login_attempt($conn, $email, 0);
    auto_block_ip($conn, $ip);

    exit(json_encode([
        "error"=>"Too many login attempts. Try again later."
    ]));
}

/* =========================
   🔐 RATE LIMIT (IP)
========================= */
if(is_rate_limited($conn, $ip)){
    log_login_attempt($conn, $email, 0);
    auto_block_ip($conn, $ip);

    exit(json_encode([
        "error"=>"Too many requests from your IP"
    ]));
}

/* =========================
   🔍 FETCH USER
========================= */
$stmt = $conn->prepare("
    SELECT id, password, role, status 
    FROM users 
    WHERE email=? LIMIT 1
");
$stmt->bind_param("s", $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

/* =========================
   ❌ INVALID LOGIN
========================= */
if(!$user || !password_verify($password, $user['password'])){
    log_login_attempt($conn, $email, 0);
    auto_block_ip($conn, $ip);

    exit(json_encode([
        "error"=>"Invalid credentials"
    ]));
}

/* =========================
   ❌ ACCOUNT STATUS CHECK
========================= */
if($user['status'] !== 'active'){
    exit(json_encode([
        "error"=>"Account is suspended or not verified"
    ]));
}

/* =========================
   🔐 GENERATE SECURE OTP
========================= */
try {
    $otp = random_int(100000, 999999); // more secure than rand()
} catch (Exception $e) {
    $otp = rand(100000, 999999); // fallback
}

/* =========================
   💾 STORE OTP
========================= */
$stmt = $conn->prepare("
    UPDATE users 
    SET otp_code=?, otp_expires=DATE_ADD(NOW(), INTERVAL 5 MINUTE)
    WHERE id=?
");
$stmt->bind_param("si", $otp, $user['id']);
$stmt->execute();

/* =========================
   📧 SEND OTP EMAIL
========================= */
$message = "
<h3>Login Verification</h3>
<p>Your OTP code is:</p>
<h2>$otp</h2>
<p>This code will expire in 5 minutes.</p>
<p>If you did not request this, ignore this email.</p>
";

send_email($email, "Your Login OTP", $message);

/* =========================
   ✅ LOG SUCCESS
========================= */
log_login_attempt($conn, $email, 1);

/* =========================
   🔐 RESPONSE (NO SESSION YET)
========================= */
echo json_encode([
    "otp_required" => true,
    "user_id" => $user['id']
]);