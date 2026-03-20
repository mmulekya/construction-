<?php
require_once "../includes/config.php";
require_once "../includes/database.php";
require_once "../includes/login_security.php";
require_once "../includes/rate_limit.php";

session_start();

header("Content-Type: application/json");

// Rate limit (extra protection)
check_rate_limit($conn, "login", 5, 60);

$data = json_decode(file_get_contents("php://input"), true);

$email = sanitize($data['email'] ?? '');
$password = $data['password'] ?? '';

// Check lock first
check_login_lock($conn, $email);

$stmt = $conn->prepare("SELECT id, password FROM users WHERE email=?");
$stmt->bind_param("s", $email);
$stmt->execute();

$res = $stmt->get_result();

if($user = $res->fetch_assoc()){

    if(password_verify($password, $user['password'])){

        // Reset attempts
        reset_login_attempts($conn, $email);

        // Secure session
        session_regenerate_id(true);

        $_SESSION['user_id'] = $user['id'];

        echo json_encode(["success"=>true]);
        exit;
    }
}

// Record failed attempt
record_failed_login($conn, $email);

echo json_encode(["error"=>"Invalid email or password"]);