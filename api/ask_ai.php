<?php
require_once "../includes/config.php";
require_once "../includes/database.php";
require_once "../includes/security.php";
require_once "../includes/knowledge.php";
require_once "../includes/history.php";

session_start();
require_login();

header("Content-Type: application/json");

// Get user
$user_id = $_SESSION['user_id'];

// Read input
$data = json_decode(file_get_contents("php://input"), true);
$question = sanitize($data['question'] ?? '');

if(empty($question)){
    echo json_encode(["error"=>"Question is required"]);
    exit;
}

// Get AI answer
$answer = get_knowledge($conn, $question);

// If found
if($answer){
    save_chat($conn, $user_id, $question, $answer);

    echo json_encode([
        "status" => "success",
        "answer" => $answer
    ]);
    exit;
}

// Fallback
$fallback = "I couldn't find an exact answer. Try asking about construction topics like concrete, foundation, or materials.";

save_chat($conn, $user_id, $question, $fallback);

echo json_encode([
    "status" => "success",
    "answer" => $fallback
]);