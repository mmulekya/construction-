<?php
require_once "../includes/config.php";
require_once "../includes/database.php";
require_once "../includes/security.php";

session_start();

$data = json_decode(file_get_contents("php://input"), true);

$email = sanitize($data['email'] ?? '');
$password = $data['password'] ?? '';

$stmt = $conn->prepare("SELECT id,password FROM users WHERE email=?");
$stmt->bind_param("s",$email);
$stmt->execute();

$result = $stmt->get_result();

if($user = $result->fetch_assoc()){
    if(password_verify($password, $user['password'])){
        $_SESSION['user_id'] = $user['id'];
        echo json_encode(["success"=>"Login successful"]);
        exit;
    }
}

echo json_encode(["error"=>"Invalid credentials"]);