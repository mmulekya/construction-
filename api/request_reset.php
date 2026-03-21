<?php

require_once "../includes/config.php";
require_once "../includes/database.php";
require_once "../includes/security.php";

header("Content-Type: application/json");

// CSRF
if(!verify_csrf_token($_POST['csrf_token'] ?? '')){
    exit(json_encode(["error"=>"Invalid CSRF"]));
}

$email = trim($_POST['email'] ?? '');

if(empty($email)){
    exit(json_encode(["error"=>"Email required"]));
}

// Generate reset token
$token = bin2hex(random_bytes(32));

$stmt = $conn->prepare("
    UPDATE users SET reset_token=?, reset_expires=DATE_ADD(NOW(), INTERVAL 1 HOUR)
    WHERE email=?
");

$stmt->bind_param("ss", $token, $email);
$stmt->execute();

echo json_encode([
    "success"=>true,
    "message"=>"Reset link sent (simulate email)"
]);