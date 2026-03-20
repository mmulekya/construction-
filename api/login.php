<?php

require_once "../includes/config.php";
require_once "../includes/database.php";
require_once "../includes/security.php";

session_start();

header("Content-Type: application/json");

// Ensure CSRF exists
if(!isset($_POST['csrf_token'])){
    echo json_encode(["error"=>"CSRF token missing"]);
    exit;
}

$csrf = $_POST['csrf_token'];

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

// Rate limiting
if(is_rate_limited($conn, $email)){
    log_login_attempt($conn, $email, 0);
    echo json_encode(["error"=>"Too many attempts"]);
    exit;
}

// Fetch user
$stmt = $conn->prepare("SELECT * FROM users WHERE email=? LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();

$user = $stmt->get_result()->fetch_assoc();

// Verify password
if(!$user || !password_verify($password, $user['password'])){
    log_login_attempt($conn, $email, 0);
    echo json_encode(["error"=>"Invalid credentials"]);
    exit;
}

// Create session
$_SESSION['user_id'] = $user['id'];
$_SESSION['role'] = $user['role'];

// Log success
log_login_attempt($conn, $email, 1);

echo json_encode([
    "success"=>true,
    "message"=>"Login successful"
]);