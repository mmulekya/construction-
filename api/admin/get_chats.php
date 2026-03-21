<?php

require_once "../../includes/config.php";
require_once "../../includes/database.php";
require_once "../../includes/security.php";

header("Content-Type: application/json");

require_login();
require_admin();

$result = $conn->query("
    SELECT * FROM chat_history ORDER BY created_at DESC LIMIT 100
");

$chats = [];

while($row = $result->fetch_assoc()){
    $chats[] = $row;
}

echo json_encode(["success"=>true, "chats"=>$chats]);