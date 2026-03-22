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

// Start session safely
if(session_status() === PHP_SESSION_NONE){
    session_start();
}

/* =========================
   🔐 AUTH (SESSION OR JWT)
========================= */

$user_id = null;

// Try session first
if(isset($_SESSION['user_id'])){
    $user_id = $_SESSION['user_id'];
}

// Fallback to JWT
if(!$user_id){
    $headers = getallheaders();
    $token = $headers['Authorization'] ?? '';

    if($token){
        $user_id = verify_jwt($token);
    }
}

if(!$user_id){
    exit(json_encode(["error"=>"Unauthorized"]));
}

/* =========================
   🔐 RATE LIMIT (ANTI-SPAM)
========================= */
check_rate_limit($conn, "ask_ai_" . $user_id, 10, 60);

/* =========================
   🔐 INPUT (SAFE JSON)
========================= */
$input = json_decode(file_get_contents("php://input"), true);

if(!$input){
    exit(json_encode(["error"=>"Invalid request"]));
}

/* =========================
   🔐 CSRF PROTECTION
========================= */
$csrf = $input['csrf_token'] ?? '';

if(!verify_csrf_token($csrf)){
    exit(json_encode(["error"=>"Invalid CSRF token"]));
}

/* =========================
   INPUT VALIDATION
========================= */
$question = trim($input['question'] ?? '');
$project_id = intval($input['project_id'] ?? 0);

if(empty($question)){
    exit(json_encode(["error"=>"Empty question"]));
}

if(strlen($question) > 1000){
    exit(json_encode(["error"=>"Question too long"]));
}

/* =========================
   🔐 AI DAILY LIMIT
========================= */
$stmt = $conn->prepare("
    SELECT requests FROM ai_usage 
    WHERE user_id=? AND last_request=CURDATE()
");
$stmt->bind_param("i", $user_id);
$stmt->execute();

$usage = $stmt->get_result()->fetch_assoc();

if($usage && $usage['requests'] >= 50){
    exit(json_encode([
        "error"=>"Daily AI limit reached (50 requests)"
    ]));
}

// Update usage safely
$stmt = $conn->prepare("
    INSERT INTO ai_usage (user_id, requests, last_request)
    VALUES (?, 1, CURDATE())
    ON DUPLICATE KEY UPDATE requests = requests + 1
");
$stmt->bind_param("i", $user_id);
$stmt->execute();

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

    if($memory_data && isset($memory_data['memory_value'])){
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

    // 🔐 Ensure project belongs to user
    $check = $conn->prepare("SELECT id FROM projects WHERE id=? AND user_id=?");
    $check->bind_param("ii", $project_id, $user_id);
    $check->execute();

    if($check->get_result()->num_rows > 0){

        $stmt = $conn->prepare("
            SELECT data 
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