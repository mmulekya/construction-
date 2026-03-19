<?php
require_once "../includes/calculator.php";
require_once "../includes/config.php";
require_once "../includes/database.php";
require_once "../includes/security.php";
require_once "../includes/knowledge.php";
require_once "../includes/history.php";
require_once "../includes/gpt_ai.php";

session_start();
require_login();

header("Content-Type: application/json");

$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents("php://input"), true);
$question = sanitize($data['question'] ?? '');

if(empty($question)){
    echo json_encode(["error"=>"Question required"]);
    exit;
}

// Search internal knowledge first
$answer = get_knowledge($conn, $question);

// If found in knowledge/PDFs
if($answer){
    save_chat($conn, $user_id, $question, $answer);
    echo json_encode(["status"=>"success","answer"=>$answer,"source"=>"internal"]);
    exit;
}

// Otherwise call GPT
$gptAnswer = call_gpt($question);
save_chat($conn, $user_id, $question, $gptAnswer);

echo json_encode(["status"=>"success","answer"=>$gptAnswer,"source"=>"GPT"]);