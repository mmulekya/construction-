<?php

require_once "../includes/config.php";
require_once "../includes/database.php";
require_once "../includes/security.php";
require_once "../includes/history.php";

header("Content-Type: application/json");

// 🔐 Require login
require_login();

// 🔐 Basic rate limit
if(is_rate_limited($conn, $_SESSION['user_id'])){
    exit(json_encode([
        "error" => "Too many requests"
    ]));
}

$user_id = intval($_SESSION['user_id']);

// 🧠 Get conversation messages (ChatGPT style)
$messages = get_recent_messages($conn, $user_id, 20);

// 🔐 Clean output format
$history = [];

foreach($messages as $msg){

    $history[] = [
        "role" => $msg['role'], // user / ai
        "message" => $msg['message']
    ];
}

// 🔐 Output
echo json_encode([
    "success" => true,
    "history" => $history
]);