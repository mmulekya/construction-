<?php
require_once "../includes/config.php";
require_once "../includes/database.php";

header("Content-Type: application/json");

$token = $_GET['token'] ?? '';

if(!$token){
    exit(json_encode(["error"=>"Invalid token"]));
}

$stmt = db_prepare("
UPDATE users SET status='active', verify_token=NULL
WHERE verify_token=?
");

$stmt->bind_param("s", $token);
$stmt->execute();

echo json_encode(["success"=>true]);