<?php

require_once "../includes/config.php";
require_once "../includes/database.php";
require_once "../includes/security.php";

header("Content-Type: application/json");

$user_id = intval($_POST['user_id'] ?? 0);
$otp = $_POST['otp'] ?? '';

$stmt = $conn->prepare("
    SELECT role FROM users 
    WHERE id=? AND otp_code=? AND otp_expires > NOW()
");
$stmt->bind_param("is", $user_id, $otp);
$stmt->execute();

$user = $stmt->get_result()->fetch_assoc();

if(!$user){
    exit(json_encode(["error"=>"Invalid OTP"]));
}

// Clear OTP
$conn->query("UPDATE users SET otp_code=NULL, otp_expires=NULL WHERE id=$user_id");

// Session + JWT
session_regenerate_id(true);

$_SESSION['user_id'] = $user_id;
$_SESSION['role'] = $user['role'];

$token = generate_jwt($user_id);

echo json_encode([
    "success"=>true,
    "token"=>$token
]);