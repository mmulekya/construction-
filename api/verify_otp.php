<?php
session_start();
header('Content-Type: application/json');

require_once "../includes/config.php";
require_once "../includes/database.php";
require_once "../includes/security.php";

// =========================
// 🔐 RATE LIMIT (PER IP)
// =========================
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
check_rate_limit($conn, "otp_" . $ip, 3, 600);

// =========================
// 🔐 ONLY POST
// =========================
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(["error" => "Method not allowed"]));
}

// =========================
// 🔐 CSRF CHECK
// =========================
$csrf = $_POST['csrf_token'] ?? '';
if (!verify_csrf_token($csrf)) {
    log_attack($conn, "OTP_CSRF_FAIL");
    exit(json_encode(["error" => "Invalid security token"]));
}

// =========================
// 🧾 INPUT
// =========================
$user_otp = trim($_POST['otp'] ?? '');

if (!$user_otp) {
    exit(json_encode(["error" => "OTP required"]));
}

// =========================
// 🔐 SESSION CHECK
// =========================
if (!isset($_SESSION['otp_user_id'], $_SESSION['otp_code'], $_SESSION['otp_expires'])) {
    exit(json_encode(["error" => "Session expired. Please login again."]));
}

// =========================
// ⏱ EXPIRY CHECK
// =========================
if (time() > $_SESSION['otp_expires']) {
    session_unset();
    exit(json_encode(["error" => "OTP expired. Please login again."]));
}

// =========================
// 🔐 VERIFY OTP
// =========================
if (hash_equals((string)$_SESSION['otp_code'], $user_otp)) {

    $user_id = $_SESSION['otp_user_id'];

    // =========================
    // 🔑 GENERATE JWT
    // =========================
    $jwt = generate_secure_jwt($user_id);

    // Clear OTP
    unset($_SESSION['otp_code']);
    unset($_SESSION['otp_expires']);
    unset($_SESSION['otp_user_id']);

    echo json_encode([
        "success" => true,
        "token" => $jwt
    ]);
} 
else {
    // Log failure
    log_login_attempt($conn, "otp_user_" . $_SESSION['otp_user_id'], 0);

    // Optional auto-ban
    auto_ban_ip($conn);

    echo json_encode([
        "error" => "Invalid verification code"
    ]);
}

// =========================
// 🔑 JWT GENERATOR
// =========================
function generate_secure_jwt($user_id) {

    $secret = getenv("CSRF_SECRET");

    $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
    $payload = json_encode([
        'user_id' => $user_id,
        'exp' => time() + 3600
    ]);

    $base64UrlHeader = rtrim(strtr(base64_encode($header), '+/', '-_'), '=');
    $base64UrlPayload = rtrim(strtr(base64_encode($payload), '+/', '-_'), '=');

    $signature = hash_hmac(
        'sha256',
        $base64UrlHeader . "." . $base64UrlPayload,
        $secret,
        true
    );

    $base64UrlSignature = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');

    return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
}