<?php
require_once "../includes/config.php";
require_once "../includes/database.php";
require_once "../includes/knowledge.php";
require_once "../includes/security.php";

header("Content-Type: application/json");

// Read JSON input
$data = json_decode(file_get_contents("php://input"), true);

$question = sanitize($data['question'] ?? '');

if(empty($question)){
    echo json_encode(["error"=>"Question cannot be empty"]);
    exit;
}

// Get answer from knowledge base
$answer = get_knowledge($conn, $question);

if($answer){
    echo json_encode([
        "status" => "success",
        "source" => "knowledge_base",
        "answer" => $answer
    ]);
    exit;
}

// Fallback response
echo json_encode([
    "status" => "success",
    "source" => "fallback",
    "answer" => "I couldn't find an exact answer.\n\nTry asking about:\n- Concrete mix ratio\n- Foundation types\n- Structural materials\n- Construction methods"
]);