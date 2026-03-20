<?php
require_once "../includes/config.php";
require_once "../includes/database.php";
require_once "../includes/login_security.php";
require_once "../includes/rate_limit.php";

session_start();

header("Content-Type: application/json");

// 🔐 Rate limiting (anti-hacker)
check_rate_limit($conn, "login", 5, 60);

// Get input
$data = json_decode(file_get_contents("php://input"), true);

$email = sanitize($data['email'] ?? '');
$password = $data['password'] ?? '';

// 🔐 Check if account is temporarily locked
check_login_lock($conn, $email);

// Fetch user
$stmt = $conn->prepare("SELECT id, password, email_verified FROM users WHERE email=?");
$stmt->bind_param("s", $email);
$stmt->execute();

$res = $stmt->get_result();

if($user = $res->fetch_assoc()){

    // 🔐 Email verification check (FIXED HERE)
    if(!$user['email_verified']){
        echo json_encode(["error"=>"Please verify your email before login"]);
        exit;
    }

    // Password check
    if(password_verify($password, $user['password'])){

        // Reset login attempts on success
        reset_login_attempts($conn, $email);

        // Secure session
        session_regenerate_id(true);

        $_SESSION['user_id'] = $user['id'];

        echo json_encode(["success"=>true]);
        exit;
    }
}

// ❌ Failed login handling
record_failed_login($conn, $email);

echo json_encode(["error"=>"Invalid email or password"]);