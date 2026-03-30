<?php
require_once "../includes/config.php";
require_once "../includes/database.php";
require_once "../includes/security.php";

header("Content-Type: application/json");

if(session_status() === PHP_SESSION_NONE){
    session_start();
}

$user_id = get_authenticated_user();

if(!$user_id){
    exit(json_encode(["error"=>"Unauthorized"]));
}

$stmt = db_prepare("
SELECT message, response, created_at 
FROM chat_messages 
WHERE user_id=? 
ORDER BY id DESC 
LIMIT 50
");

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();
$data = $result->fetch_all(MYSQLI_ASSOC);

$stmt->close();

echo json_encode([
    "success"=>true,
    "history"=>$data
]);