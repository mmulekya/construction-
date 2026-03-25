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

// 🔐 Clean old login attempts (reduce DB size)
clean_old_attempts($conn);

/* =========================
   🔐 AUTH (SESSION OR JWT)
========================= */

$user_id = null;

// Session first
if(isset($_SESSION['user_id'])){
    $user_id = $_SESSION['user_id'];
}

// JWT fallback
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
   🔐 RATE LIMIT (STRICT)
========================= */
check_rate_limit($conn, "ask_ai_" . $user_id, 5, 60); // 5 requests per minute

/* =========================
   🔐 INPUT (SAFE JSON)
========================= */
$input = json_decode(file_get_contents("php://input"), true);

if(!$input){
    exit(json_encode(["error"=>"Invalid request"]));
}

/* =========================
   🔐 CSRF
========================= */
$csrf = $input['csrf_token'] ?? '';

if(!verify_csrf_token($csrf)){
    exit(json_encode(["error"=>"Invalid CSRF token"]));
}

/* =========================
   🧾 VALIDATION
========================= */
$question = trim($input['question'] ?? '');
$project_id = intval($input['project_id'] ?? 0);

if(strlen($question) < 3){
    exit(json_encode(["error"=>"Invalid input"]));
}

if(strlen($question) > 500){
    exit(json_encode(["error"=>"Question too long"]));
}

/* =========================
   🛑 ANTI-BOT DELAY
========================= */
usleep(300000); // 0.3 sec

/* =========================
   🔐 AI DAILY LIMIT (REDUCED)
========================= */
$stmt = $conn->prepare("
    SELECT requests FROM ai_usage 
    WHERE user_id=? AND last_request=CURDATE()
");
$stmt->bind_param("i", $user_id);
$stmt->execute();

$usage = $stmt->get_result()->fetch_assoc();

if($usage && $usage['requests'] >= 20){
    exit(json_encode([
        "error"=>"Daily AI limit reached (20 requests)"
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

    save_memory($conn, $user_id, "chat", substr($question,0,200));
    save_memory($conn, $user_id, "chat", substr($calc,0,200));

    echo json_encode([
        "status"=>"success",
        "answer"=>$calc,
        "source"=>"calculator"
    ]);
    exit;
}

/* =========================
   STEP 2: MEMORY (LIMITED)
========================= */
$memory_text = "";

try{
    $memory_data = get_memory($conn, $user_id, "chat");

    if($memory_data && isset($memory_data['memory_value'])){
        $memory_text = substr($memory_data['memory_value'], 0, 300);
    }
}catch(Exception $e){
    $memory_text = "";
}

/* =========================
   STEP 3: PROJECT CONTEXT (LIMITED)
========================= */
$project_context = "";

if($project_id > 0){

    $check = $conn->prepare("SELECT id FROM projects WHERE id=? AND user_id=?");
    $check->bind_param("ii", $project_id, $user_id);
    $check->execute();

    if($check->get_result()->num_rows > 0){

        $stmt = $conn->prepare("
            SELECT data 
            FROM project_data 
            WHERE project_id=? 
            ORDER BY id DESC 
            LIMIT 10
        ");

        $stmt->bind_param("i", $project_id);
        $stmt->execute();

        $result = $stmt->get_result();

        while($row = $result->fetch_assoc()){
            $project_context .= substr($row['data'],0,200) . "\n";
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
You are BuildSmart AI, a construction expert.

Memory:
$memory_text

Project Context:
$project_context

PDF Knowledge:
$pdf_context

Question:
$question

Answer clearly using the provided PDF and project data when relevant.
";
";

    try{
        $response = gpt_request($prompt);
    }catch(Exception $e){
        $response = "AI service temporarily unavailable.";
    }
}

/* =========================
   STEP 6: SAVE (LIMITED)
========================= */
save_chat_history($conn, $user_id, $question, $response);

save_memory($conn, $user_id, "chat", substr($question,0,200));
save_memory($conn, $user_id, "chat", substr($response,0,200));

/* =========================
   OUTPUT
========================= */
echo json_encode([
    "status"=>"success",
    "answer"=>$response,
    "source"=>"ai"
]);