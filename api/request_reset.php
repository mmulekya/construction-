<?php
require_once "../includes/config.php";
require_once "../includes/database.php";
require_once "../includes/security.php";

header("Content-Type: application/json");
session_start();

// CSRF
$csrf = $_POST['csrf_token'] ?? '';
if(!verify_csrf_token($csrf)){
    exit(json_encode(["error"=>"Invalid CSRF token"]));
}

$email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
if(!$email){
    exit(json_encode(["error"=>"Valid email required"]));
}

// Generate token
$token = bin2hex(random_bytes(32));
$expiry = date("Y-m-d H:i:s", strtotime("+1 hour"));

// Insert into reset table
$stmt = db_prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?,?,?)");
$stmt->bind_param("sss", $email, $token, $expiry);
$stmt->execute();
$stmt->close();

// TODO: Send email using mail() or SMTP
// mail($email, "Password Reset", "Token: $token");

echo json_encode(["success"=>true,"message"=>"Reset email sent if account exists"]);