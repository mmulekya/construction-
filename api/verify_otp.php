<?php
require_once "../includes/config.php";
require_once "../includes/database.php";
require_once "../includes/security.php";

header("Content-Type: application/json");
session_start();

$user_id = $_SESSION['user_id'] ?? null;
if(!$user_id) exit(json_encode(["error"=>"Unauthorized"]));

// CSRF
$csrf = $_POST['csrf_token'] ?? '';
if(!verify_csrf_token($csrf)) exit(json_encode(["error"=>"Invalid CSRF token"]));

$otp = trim($_POST['otp'] ?? '');
if(!$otp) exit(json_encode(["error"=>"OTP required"]));

// Check OTP
$stmt = db_prepare("SELECT expires_at FROM otp_codes WHERE user_id=? AND code=? LIMIT 1");
$stmt->bind_param("is", $user_id, $otp);
$stmt->execute();
$record = $stmt->get_result()->fetch_assoc();
$stmt->close();

if(!$record || strtotime($record['expires_at']) < time()){
    exit(json_encode(["error"=>"Invalid or expired OTP"]));
}

// OTP valid, mark verified
$stmt = db_prepare("DELETE FROM otp_codes WHERE user_id=?");
$stmt->bind_param("i",$user_id);
$stmt->execute();
$stmt->close();

echo json_encode(["success"=>true,"message"=>"OTP verified"]);