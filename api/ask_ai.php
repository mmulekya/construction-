<?php
require_once "../includes/rate_limit.php";
check_rate_limit($conn, "ask_ai", 10, 60); // 10 requests per minute
require_once "../includes/config.php";
require_once "../includes/database.php";
require_once "../includes/security.php";
require_once "../includes/knowledge.php";
require_once "../includes/history.php";
require_once "../includes/gpt_ai.php";
require_once "../includes/calculator.php";
require_once "../includes/memory.php";

session_start();
require_login();

$user_id = $_SESSION['user_id'];

$data = json_decode(file_get_contents("php://input"), true);
$question = sanitize($data['question'] ?? '');
$project_id = intval($data['project_id'] ?? 0);

if(!$question){
    echo json_encode(["error"=>"Empty question"]);
    exit;
}

$response = "";

/* ---------------------------
   STEP 1: CALCULATOR CHECK
----------------------------*/
$calc = calculate_engineering($question);
if($calc){
    save_chat($conn, $user_id, $question, $calc);
    save_memory($conn, $user_id, "chat", $question);
    save_memory($conn, $user_id, "chat", $calc);

    echo json_encode([
        "status"=>"success",
        "answer"=>$calc,
        "source"=>"calculator"
    ]);
    exit;
}

/* ---------------------------
   STEP 2: LOAD MEMORY
----------------------------*/
$memory_data = get_memory($conn, $user_id);
$memory_text = summarize_memory($memory_data);

/* ---------------------------
   STEP 3: PROJECT CONTEXT
----------------------------*/
$project_context = "";

if($project_id > 0){
    $stmt = $conn->prepare("SELECT type, content FROM project_data WHERE project_id=? ORDER BY id DESC");
    $stmt->bind_param("i", $project_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while($row = $result->fetch_assoc()){
        $project_context .= $row['type'] . ": " . $row['content'] . "\n";
    }
}

/* ---------------------------
   STEP 4: KNOWLEDGE + PDF SEARCH
----------------------------*/
$knowledge_answer = get_knowledge_answer($conn, $question);

if($knowledge_answer){
    $response = $knowledge_answer;
} else {

    /* ---------------------------
       STEP 5: GPT FALLBACK
    ----------------------------*/

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

    $response = gpt_request($prompt);
}

/* ---------------------------
   STEP 6: SAVE CHAT + MEMORY
----------------------------*/
save_chat($conn, $user_id, $question, $response);
save_memory($conn, $user_id, "chat", $question);
save_memory($conn, $user_id, "chat", $response);

/* ---------------------------
   OUTPUT
----------------------------*/
echo json_encode([
    "status"=>"success",
    "answer"=>$response,
    "source"=>"ai"
]);