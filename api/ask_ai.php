<?php

require_once "../includes/config.php";
require_once "../includes/database.php";
require_once "../includes/security.php";
require_once "../includes/rate_limit.php";
require_once "../includes/knowledge.php";
require_once "../includes/history.php";
require_once "../includes/gpt_ai.php";
require_once "../includes/calculator.php";
require_once "../includes/project.php";

header("Content-Type: application/json");

// Start session
if(session_status() === PHP_SESSION_NONE){
    session_start();
}

/* =========================
   🔐 AUTH
========================= */
$user_id = $_SESSION['user_id'] ?? null;

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
   🔐 RATE LIMIT
========================= */
check_rate_limit($conn, "ask_ai_" . $user_id, 5, 60);

/* =========================
   🔐 INPUT
========================= */
$input = json_decode(file_get_contents("php://input"), true);

if(!$input){
    exit(json_encode(["error"=>"Invalid request"]));
}

/* =========================
   🔐 CSRF
========================= */
if(!verify_csrf_token($input['csrf_token'] ?? '')){
    exit(json_encode(["error"=>"Invalid CSRF token"]));
}

/* =========================
   🧾 VALIDATION
========================= */
$question = trim($input['question'] ?? '');
$project_id = intval($input['project_id'] ?? 0);

if(strlen($question) < 5){
    exit(json_encode(["error"=>"Ask a more detailed question"]));
}

if(strlen($question) > 500){
    exit(json_encode(["error"=>"Question too long"]));
}

/* =========================
   🛑 SMALL DELAY
========================= */
usleep(200000);

/* =========================
   🔐 DAILY LIMIT
========================= */
$stmt = $conn->prepare("
    SELECT requests FROM ai_usage 
    WHERE user_id=? AND last_request=CURDATE()
");
$stmt->bind_param("i", $user_id);
$stmt->execute();

$usage = $stmt->get_result()->fetch_assoc();

if($usage && $usage['requests'] >= 20){
    exit(json_encode(["error"=>"Daily AI limit reached"]));
}

// Update usage
$stmt = $conn->prepare("
    INSERT INTO ai_usage (user_id, requests, last_request)
    VALUES (?,1,CURDATE())
    ON DUPLICATE KEY UPDATE requests=requests+1
");
$stmt->bind_param("i", $user_id);
$stmt->execute();

/* =========================
   STEP 1: CALCULATOR
========================= */
$calc = calculate_engineering($question);

if($calc){
    save_message($conn, $user_id, "user", $question);
    save_message($conn, $user_id, "ai", $calc);

    echo json_encode([
        "status"=>"success",
        "answer"=>$calc,
        "source"=>"calculator"
    ]);
    exit;
}

/* =========================
   STEP 2: CHAT MEMORY (CONTEXT)
========================= */
$messages = get_recent_messages($conn, $user_id, 8);

/* =========================
   STEP 3: PROJECT CONTEXT
========================= */
$project_context = "";

if($project_id > 0){

    $check = $conn->prepare("SELECT id FROM projects WHERE id=? AND user_id=?");
    $check->bind_param("ii", $project_id, $user_id);
    $check->execute();

    if($check->get_result()->num_rows > 0){

        $stmt = $conn->prepare("
            SELECT data FROM project_data 
            WHERE project_id=? 
            ORDER BY id DESC 
            LIMIT 10
        ");

        $stmt->bind_param("i", $project_id);
        $stmt->execute();

        $res = $stmt->get_result();

        while($row = $res->fetch_assoc()){
            $project_context .= substr($row['data'],0,200)."\n";
        }
    }
}

/* =========================
   STEP 4: KNOWLEDGE
========================= */
$knowledge = search_knowledge($conn, $question);

if($knowledge && $knowledge->num_rows > 0){
    $row = $knowledge->fetch_assoc();

    $response = $row['content'];

    save_message($conn, $user_id, "user", $question);
    save_message($conn, $user_id, "ai", $response);

    echo json_encode([
        "status"=>"success",
        "answer"=>$response,
        "source"=>"knowledge"
    ]);
    exit;
}

/* =========================
   STEP 5: PDF SEMANTIC SEARCH
========================= */
$pdf_context = "";

try{

    $q_embed = get_cached_embedding($conn, $question);

    $res = $conn->query("
        SELECT content, embedding 
        FROM pdf_chunks 
        ORDER BY id DESC 
        LIMIT 200
    ");

    $scores = [];

    while($row = $res->fetch_assoc()){

        $emb = json_decode($row['embedding'], true);
        if(!$emb) continue;

        $semantic = cosine_similarity($q_embed, $emb);
        $keyword = stripos($row['content'], $question) !== false ? 1 : 0;

        $score = ($semantic * 0.7) + ($keyword * 0.3);

        $scores[] = [
            "content"=>$row['content'],
            "score"=>$score
        ];
    }

    usort($scores, fn($a,$b)=>$b['score'] <=> $a['score']);

    $top = array_slice($scores,0,5);

    $max_chars = 1500;

    foreach($top as $t){

        if(strlen($pdf_context) + strlen($t['content']) > $max_chars){
            break;
        }

        $pdf_context .= substr($t['content'],0,300)."\n";
    }

}catch(Exception $e){
    $pdf_context = "";
}

/* =========================
   STEP 6: GPT WITH CONTEXT
========================= */
$chat_history = [];

foreach($messages as $msg){
    $chat_history[] = [
        "role" => $msg['role'] === 'ai' ? "assistant" : "user",
        "content" => $msg['message']
    ];
}

// Add system context
$chat_history[] = [
    "role" => "system",
    "content" => "Project:\n".$project_context."\nPDF:\n".$pdf_context
];

// Add current question
$chat_history[] = [
    "role" => "user",
    "content" => $question
];

try{
    $response = gpt_request_with_history($chat_history);
}catch(Exception $e){
    $response = "AI service unavailable.";
}

/* =========================
   SAVE CONVERSATION
========================= */
save_message($conn, $user_id, "user", $question);
save_message($conn, $user_id, "ai", $response);

/* =========================
   OUTPUT
========================= */
echo json_encode([
    "status"=>"success",
    "answer"=>$response,
    "source"=>"ai"
]);