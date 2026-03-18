
<?php
require_once "../includes/config.php";
require_once "../includes/database.php";
require_once "../includes/knowledge.php";
require_once "../includes/security.php";

header("Content-Type: application/json");

// Get input safely
$data = json_decode(file_get_contents("php://input"), true);
$question = sanitize($data['question'] ?? '');

if(empty($question)){
    echo json_encode(["error"=>"Empty question"]);
    exit;
}

// 1️⃣ Try local knowledge base FIRST (FAST & SAFE)
$answer = get_knowledge($conn, $question);

if($answer !== "Sorry, no matching knowledge found."){
    echo json_encode([
        "source"=>"knowledge_base",
        "answer"=>$answer
    ]);
    exit;
}

// 2️⃣ Fallback response (since OpenAI may fail)
echo json_encode([
    "source"=>"fallback",
    "answer"=>"Sorry, advanced AI is temporarily unavailable. Please try another construction-related question or check your internet/server configuration."
]);