<?php

require_once "../includes/config.php";
require_once "../includes/database.php";
require_once "../includes/security.php";
require_once "../includes/history.php";

header("Content-Type: application/json");

// 🔐 Require login
require_login();

// 🔐 Prevent abuse (basic rate limit per user)
if(is_rate_limited($conn, $_SESSION['user_id'])){
    exit(json_encode([
        "error"=>"Too many requests"
    ]));
}

// 🔐 Get user safely
$user_id = intval($_SESSION['user_id']);

// 🔐 Fetch limited history (already capped in history.php)
$result = get_chat_history($conn, $user_id);

$history = [];

while($row = $result->fetch_assoc()){
    $history[] = [
        "message" => $row['message'],
        "response" => $row['response'],
        "created_at" => $row['created_at']
    ];
}

// 🔐 Clean output
echo json_encode([
    "success" => true,
    "history" => $history
]);