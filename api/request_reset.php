<?php
require_once "../includes/config.php";
require_once "../includes/database.php";
require_once "../includes/security.php";
require_once "../includes/rate_limit.php";

header("Content-Type: application/json");

check_rate_limit($conn, "reset_request", 5, 300);

$csrf = $_POST['csrf_token'] ?? '';
if(!verify_csrf_token($csrf)){
    exit(json_encode(["error"=>"Invalid CSRF"]));
}

$email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);

if(!$email){
    exit(json_encode(["error"=>"Invalid email"]));
}

$token = bin2hex(random_bytes(32));

$stmt = db_prepare("
UPDATE users SET reset_token=?, reset_expires=DATE_ADD(NOW(), INTERVAL 1 HOUR)
WHERE email=?
");

$stmt->bind_param("ss", $token, $email);
$stmt->execute();

echo json_encode(["success"=>true]);