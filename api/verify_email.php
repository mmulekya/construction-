<?php
require_once "../includes/config.php";
require_once "../includes/database.php";
require_once "../includes/security.php";

header("Content-Type: application/json");

// CSRF
$csrf = $_POST['csrf_token'] ?? '';
if(!verify_csrf_token($csrf)) exit(json_encode(["error"=>"Invalid CSRF token"]));

$email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$token = trim($_POST['token'] ?? '');
if(!$email || !$token) exit(json_encode(["error"=>"Missing parameters"]));

// Verify token
$stmt = db_prepare("SELECT id FROM users WHERE email=? AND email_token=? LIMIT 1");
$stmt->bind_param("ss",$email,$token);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if(!$user) exit(json_encode(["error"=>"Invalid token"]));

// Activate account
$stmt = db_prepare("UPDATE users SET status='active', email_token=NULL WHERE id=?");
$stmt->bind_param("i",$user['id']);
$stmt->execute();
$stmt->close();

echo json_encode(["success"=>true,"message"=>"Email verified successfully"]);