<?php

require_once "../includes/config.php";
require_once "../includes/database.php";
require_once "../includes/security.php";
require_once "../includes/history.php";

header("Content-Type: application/json");

require_login();

// Fetch history
$result = get_chat_history($conn, $_SESSION['user_id']);

$data = [];

while($row = $result->fetch_assoc()){
    $data[] = $row;
}

echo json_encode([
    "success"=>true,
    "history"=>$data
]);