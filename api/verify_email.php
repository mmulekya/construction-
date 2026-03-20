<?php
require_once "../includes/config.php";
require_once "../includes/database.php";

header("Content-Type: application/json");

$token = $_GET['token'] ?? '';

if(empty($token)){
    echo json_encode(["error"=>"Invalid token"]);
    exit;
}

$stmt = $conn->prepare("SELECT id FROM users WHERE verification_token=?");
$stmt->bind_param("s", $token);
$stmt->execute();

$res = $stmt->get_result();

if($user = $res->fetch_assoc()){

    $stmt = $conn->prepare("UPDATE users SET email_verified=1, verification_token=NULL WHERE id=?");
    $stmt->bind_param("i", $user['id']);
    $stmt->execute();

    echo json_encode(["success"=>true, "message"=>"Email verified successfully"]);
} else {
    echo json_encode(["error"=>"Invalid or expired token"]);
}