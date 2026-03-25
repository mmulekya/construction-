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

// Start session
if(session_status() === PHP_SESSION_NONE){
    session_start();
}

// Clean old attempts
clean_old_attempts($conn);

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

if(strlen($question) < 3){
    exit(json_encode(["error"=>"Invalid input"]));
}

if(strlen($question) > 500){
    exit(json_encode(["error"=>"Question too long"]));
}

/* =========================
   🛑 DELAY
========================= */
usleep(300000);

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
    save_chat_history($conn, $user_id, $question, $calc);

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
    $mem = get_memory($conn, $user_id, "chat");
    if($mem && isset($mem['memory_value'])){
        $memory_text = substr($mem['memory_value'], 0, 300);
    }
}catch(Exception $e){}

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
            WHERE project_id=? ORDER BY id DESC LIMIT 10
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
$knowledge_answer = get_knowledge_answer($conn, $question);

if($knowledge_answer){
    $response = $knowledge_answer;
} else {

    /* =========================
       STEP 5: SEMANTIC PDF SEARCH
    ========================== */

    function get_embedding($text){
        $apiKey = getenv("OPENAI_API_KEY");

        $data = [
            "input"=>$text,
            "model"=>"text-embedding-3-small"
        ];

        $ch = curl_init("https://api.openai.com/v1/embeddings");

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer ".$apiKey,
            "Content-Type: application/json"
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        $res = curl_exec($ch);
        curl_close($ch);

        $json = json_decode($res, true);

        return $json['data'][0]['embedding'] ?? [];
    }

    function cosine($a,$b){
        $dot=0;$magA=0;$magB=0;
        for($i=0;$i<count($a);$i++){
            $dot+=$a[$i]*$b[$i];
            $magA+=$a[$i]*$a[$i];
            $magB+=$b[$i]*$b[$i];
        }
        return $dot/(sqrt($magA)*sqrt($magB));
    }

    $pdf_context = "";

    try{
        $q_embed = get_embedding($question);

        $res = $conn->query("SELECT content, embedding FROM pdf_chunks");

        $scores = [];

        while($row = $res->fetch_assoc()){
            $emb = json_decode($row['embedding'], true);
            if(!$emb) continue;

            $score = cosine($q_embed, $emb);

            $scores[] = [
                "content"=>$row['content'],
                "score"=>$score
            ];
        }

        usort($scores, fn($a,$b)=>$b['score'] <=> $a['score']);

        $top = array_slice($scores,0,3);

        foreach($top as $t){
            $pdf_context .= substr($t['content'],0,300)."\n";
        }

    }catch(Exception $e){
        $pdf_context = "";
    }

    /* =========================
       STEP 6: AI
    ========================== */

    $prompt = "
You are BuildSmart AI, a construction expert.

Memory:
$memory_text

Project:
$project_context

PDF Context:
$pdf_context

Question:
$question

Answer clearly and practically.
";

    try{
        $response = gpt_request($prompt);
    }catch(Exception $e){
        $response = "AI service unavailable.";
    }
}

/* =========================
   SAVE
========================= */
save_chat_history($conn, $user_id, $question, $response);

/* =========================
   OUTPUT
========================= */
echo json_encode([
    "status"=>"success",
    "answer"=>$response,
    "source"=>"ai"
]);