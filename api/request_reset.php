<?php
require_once "../includes/config.php";
require_once "../includes/database.php";

header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);

$email = sanitize($data['email'] ?? '');
$token = $data['token'] ?? '';
$new_password = $data['password'] ?? '';

if(empty($email) || empty($token) || empty($new_password)){
    echo json_encode(["error"=>"Invalid request"]);
    exit;
}

$stmt = $conn->prepare("SELECT id, reset_token, reset_expires FROM users WHERE email=?");
$stmt->bind_param("s", $email);
$stmt->execute();

$res = $stmt->get_result();

if($user = $res->fetch_assoc()){

    // Check expiry
    if(strtotime($user['reset_expires']) < time()){
        echo json_encode(["error"=>"Token expired"]);
        exit;
    }

    // Verify token
    if(!password_verify($token, $user['reset_token'])){
        echo json_encode(["error"=>"Invalid token"]);
        exit;
    }

    // Update password
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("UPDATE users SET password=?, reset_token=NULL, reset_expires=NULL WHERE id=?");
    $stmt->bind_param("si", $hashed_password, $user['id']);
    $stmt->execute();

    echo json_encode(["success"=>true, "message"=>"Password reset successful"]);
}
else {
    echo json_encode(["error"=>"Invalid request"]);
}