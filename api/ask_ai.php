<?php

require_once "../includes/config.php";
require_once "../includes/database.php";
require_once "../includes/security.php";
require_once "../includes/rate_limit.php";
require_once "../includes/knowledge.php";
require_once "../includes/history.php";
require_once "../includes/gpt_ai.php";
require_once "../includes/calculator.php";
require_once "../includes/memory.php";

header("Content-Type: application/json");

session_start();
require_login();

$user_id = $_SESSION['user_id'];

/* =========================
   🔐 RATE LIMIT (AFTER DB)
========================= */
check_rate_limit($conn, "ask_ai_" . $user_id, 10, 60);

/* =========================
   🔐 CSRF PROTECTION
========================= */
$data = json_decode(file_get_contents("php://input"), true);

$csrf = $data['csrf_token'] ?? '';

if(!verify_csrf_token($csrf)){
    exit(json_encode(["error"=>"Invalid CSRF token"]));
}

/* =========================
   INPUT VALIDATION
========================= */
$question = trim($data['question'] ?? '');
$project_id = intval($data['project_id'] ?? 0);

if(empty($question)){
    exit(json_encode(["error"=>"Empty question"]));
}

// Limit abuse (anti-spam)
if(strlen($question) > 1000){
    exit(json_encode(["error"=>"Question too long"]));
}

$response = "";

/* =========================
   STEP 1: CALCULATOR
========================= */
$calc = calculate_engineering($question);

if($calc){
    save_chat_history($conn, $user_id, $question, $calc);

    save_memory($conn, $user_id, "chat", $question);
    save_memory($conn, $user_id, "chat", $calc);

    echo json_encode([
        "status"=>"success",
        "answer"=>$calc,
        "source"=>"calculator"
    ]);
    exit;
}

/* =========================
   STEP 2: MEMORY
========================= */
$memory_text = "";

try{
    $memory_data = get_memory($conn, $user_id, "chat");
    if($memory_data){
        $memory_text = substr($memory_data['memory_value'], 0, 500);
    }
}catch(Exception $e){
    $memory_text = "";
}

/* =========================
   STEP 3: PROJECT CONTEXT
========================= */
$project_context = "";

if($project_id > 0){

    $stmt = $conn->prepare("
        SELECT data, created_at 
        FROM project_data 
        WHERE project_id=? 
        ORDER BY id DESC 
        LIMIT 20
    ");

    $stmt->bind_param("i", $project_id);
    $stmt->execute();

    $result = $stmt->get_result();

    while($row = $result->fetch_assoc()){
        $project_context .= $row['data'] . "\n";
    }
}

/* =========================
   STEP 4: KNOWLEDGE SEARCH
========================= */
$knowledge_answer = get_knowledge_answer($conn, $question);

if($knowledge_answer){
    $response = $knowledge_answer;
} else {

    /* =========================
       STEP 5: AI FALLBACK
    ========================== */

    $prompt = "
You are BuildSmart AI, a construction and engineering expert.

User Memory:
$memory_text

Project Context:
$project_context

User Question:
$question

Answer clearly, professionally, and with practical construction guidance.
";

    try{
        $response = gpt_request($prompt);
    }catch(Exception $e){
        $response = "AI service temporarily unavailable.";
    }
}

/* =========================
   STEP 6: SAVE DATA
========================= */
save_chat_history($conn, $user_id, $question, $response);

save_memory($conn, $user_id, "chat", $question);
save_memory($conn, $user_id, "chat", $response);

/* =========================
   OUTPUT
========================= */
echo json_encode([
    "status"=>"success",
    "answer"=>$response,
    "source"=>"ai"
]);