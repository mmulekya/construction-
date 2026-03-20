<?php
require_once "../includes/config.php";
require_once "../includes/database.php";
require_once "../includes/rate_limit.php";

$data = json_decode(file_get_contents("php://input"), true);

// 🔐 Apply rate limiting
check_rate_limit($conn, "register", 3, 60); // 3 requests per minute

$name = sanitize($data['name'] ?? '');
$email = sanitize($data['email'] ?? '');
$password_plain = $data['password'] ?? '';

$password = password_hash($password_plain, PASSWORD_DEFAULT);

// Generate verification token
$token = bin2hex(random_bytes(32));

$stmt = $conn->prepare("INSERT INTO users (name, email, password, verification_token) VALUES (?,?,?,?)");
$stmt->bind_param("ssss", $name, $email, $password, $token);

if($stmt->execute()){

    $verify_link = "https://yourdomain.com/api/verify_email.php?token=" . $token;

    $subject = "Verify your BuildSmart account";
    $message = "Click this link to verify your email:\n\n" . $verify_link;

    @mail($email, $subject, $message);

    echo json_encode(["success"=>true, "message"=>"Check your email to verify your account"]);
} else {
    echo json_encode(["error"=>"Registration failed"]);
}