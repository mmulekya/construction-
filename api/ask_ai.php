<?php
/**
 * 🔒 SECURE AI GATEWAY (FINAL VERSION)
 * Fully hardened: Auto-ban, Rate-limit, Hybrid AI, PDF semantic search
 */

require_once "../includes/cleanup.php";
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

if(session_status() === PHP_SESSION_NONE){
    session_start();
}

$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

/* ==========================================
   🔥 LOG ALL REQUESTS (IMPORTANT)
========================================== */
$stmt = $conn->prepare("
INSERT INTO logs (action, ip_address, created_at)
VALUES ('ask_ai_request', ?, NOW())
");
$stmt->bind_param("s", $ip);
$stmt->execute();

/* ==========================================
   🔒 1. BAN CHECK
========================================== */
$stmt = $conn->prepare("
SELECT id FROM banned_ips 
WHERE ip_address=? AND expires_at > NOW()
");
$stmt->bind_param("s", $ip);
$stmt->execute();

if($stmt->get_result()->num_rows > 0){
    http_response_code(403);
    exit(json_encode([
        "error"=>"Access denied. Your IP is banned."
    ]));
}

/* ==========================================
   🔐 2. AUTH (SESSION OR JWT)
========================================== */
$user_id = $_SESSION['user_id'] ?? null;

if(!$user_id){
    $headers = getallheaders();
    $token = $headers['Authorization'] ?? '';

    if($token){
        $user_id = verify_jwt($token);
    }
}

if(!$user_id){
    http_response_code(401);
    exit(json_encode(["error"=>"Unauthorized"]));
}

/* ==========================================
   ⚡ 3. RATE LIMIT
========================================== */
check_rate_limit($conn, "ask_ai_" . $user_id, 5, 60);

/* ==========================================
   🚨 4. AUTO BAN (AI ABUSE)
========================================== */
$stmt = $conn->prepare("
SELECT COUNT(*) as attempts 
FROM logs 
WHERE ip_address=? 
AND created_at > NOW() - INTERVAL 5 MINUTE
");
$stmt->bind_param("s", $ip);
$stmt->execute();

$abuse = $stmt->get_result()->fetch_assoc();

if($abuse['attempts'] > 20){

    $stmt = $conn->prepare("
    INSERT IGNORE INTO banned_ips (ip_address, banned_at, expires_at, reason)
    VALUES (?, NOW(), DATE_ADD(NOW(), INTERVAL 24 HOUR), 'AI abuse')
    ");
    $stmt->bind_param("s", $ip);
    $stmt->execute();

    exit(json_encode([
        "error"=>"Too many requests. IP banned for 24 hours."
    ]));
}

/* ==========================================
   🔐 5. INPUT + CSRF
========================================== */
$input = json_decode(file_get_contents("php://input"), true);

if(!$input){
    exit(json_encode(["error"=>"Invalid request"]));
}

if(!verify_csrf_token($input['csrf_token'] ?? '')){
    exit(json_encode(["error"=>"Invalid CSRF token"]));
}

$question = trim($input['question'] ?? '');
$project_id = intval($input['project_id'] ?? 0);

if(strlen($question) < 5 || strlen($question) > 500){
    exit(json_encode([
        "error"=>"Question must be 5–500 characters"
    ]));
}

/* ==========================================
   ⚡ 6. CALCULATOR (FAST PATH)
========================================== */
$calc = calculate_engineering($question);

if($calc){
    save_message($conn, $user_id, "user", $question);
    save_message($conn, $user_id, "ai", $calc);

    exit(json_encode([
        "status"=>"success",
        "answer"=>$calc,
        "source"=>"calculator"
    ]));
}

/* ==========================================
   🧠 7. MEMORY
========================================== */
$memory_text = "";
$messages = get_recent_messages($conn, $user_id, 8);

foreach($messages as $msg){
    $memory_text .= $msg['message'] . "\n";
}

/* ==========================================
   🏗️ 8. PROJECT CONTEXT
========================================== */
$project_context = "";

if($project_id > 0){

    $stmt = $conn->prepare("
        SELECT data FROM project_data 
        WHERE project_id=? 
        ORDER BY id DESC LIMIT 10
    ");
    $stmt->bind_param("i", $project_id);
    $stmt->execute();

    $res = $stmt->get_result();

    while($row = $res->fetch_assoc()){
        $project_context .= $row['data'] . "\n";
    }
}

/* ==========================================
   📚 9. KNOWLEDGE SEARCH (FIXED)
========================================== */
$knowledge_context = "";

$k = search_knowledge($conn, $question);

if($k){
    while($row = $k->fetch_assoc()){
        $knowledge_context .= $row['content'] . "\n";
    }
}

/* ==========================================
   📄 10. SEMANTIC PDF SEARCH (SAFE)
========================================== */
$pdf_context = "";

try{
    $q_embed = get_cached_embedding($conn, $question);

    if(!empty($q_embed)){

        $res = $conn->query("
            SELECT content, embedding 
            FROM pdf_chunks 
            ORDER BY id DESC 
            LIMIT 50
        ");

        $scores = [];

        while($row = $res->fetch_assoc()){

            $emb = json_decode($row['embedding'], true);
            if(!$emb) continue;

            $score = 0;
            $len = min(count($q_embed), count($emb));

            for($i=0;$i<$len;$i++){
                $score += $q_embed[$i] * $emb[$i];
            }

            $scores[] = [
                "content"=>$row['content'],
                "score"=>$score
            ];
        }

        usort($scores, fn($a,$b)=> $b['score'] <=> $a['score']);

        foreach(array_slice($scores,0,3) as $s){
            $pdf_context .= $s['content'] . "\n";
        }
    }

}catch(Exception $e){
    $pdf_context = "";
}

/* ==========================================
   ⚡ 11. LIMIT CONTEXT (VERY IMPORTANT)
========================================== */
$pdf_context = substr($pdf_context, 0, 1500);
$knowledge_context = substr($knowledge_context, 0, 1000);

/* ==========================================
   🤖 12. AI CALL
========================================== */
$chat_history = [];

$chat_history[] = [
    "role"=>"system",
    "content"=>"You are BuildSmart AI.

Use:

PDF:
$pdf_context

Knowledge:
$knowledge_context

Project:
$project_context

Answer clearly."
];

foreach($messages as $msg){
    $chat_history[] = [
        "role"=>$msg['role'] === 'ai' ? "assistant" : "user",
        "content"=>$msg['message']
    ];
}

$chat_history[] = [
    "role"=>"user",
    "content"=>$question
];

try{
    $response = gpt_request_with_history($chat_history);
}catch(Exception $e){
    $response = "AI temporarily unavailable.";
}

/* ==========================================
   💾 13. SAVE
========================================== */
save_message($conn, $user_id, "user", $question);
save_message($conn, $user_id, "ai", $response);

/* ==========================================
   ✅ OUTPUT
========================================== */
echo json_encode([
    "status"=>"success",
    "answer"=>$response,
    "source"=>"ai"
]);