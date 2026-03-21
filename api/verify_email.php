<?php

require_once "../includes/config.php";
require_once "../includes/database.php";

header("Content-Type: application/json");

$token = $_GET['token'] ?? '';

if(empty($token)){
    exit(json_encode(["error"=>"Invalid token"]));
}

$stmt = $conn->prepare("
    UPDATE users SET status='active', verify_token=NULL
    WHERE verify_token=?
");

$stmt->bind_param("s", $token);
$stmt->execute();

if($stmt->affected_rows > 0){
    echo json_encode(["success"=>true, "message"=>"Email verified"]);
} else {
    echo json_encode(["error"=>"Invalid or expired token"]);
}