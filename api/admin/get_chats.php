<?php
require_once "../../includes/config.php";
require_once "../../includes/database.php";
require_once "../../includes/security.php";

header("Content-Type: application/json");
session_start();
require_login();
require_admin();

$stmt = db_prepare("
SELECT user_id, message, created_at 
FROM chat_messages 
ORDER BY created_at DESC LIMIT 100
");

$stmt->execute();
$data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

echo json_encode(["success"=>true,"chats"=>$data]);