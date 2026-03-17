<?php

require_once "../includes/database.php";
require_once "../includes/security.php";
if(!has_permission($conn, $_SESSION['user_id'], 'ask_ai')) 
    exit(json_response(["error"=>"Permission denied"]));
require_once "../includes/config.php";
require_once "../includes/knowledge.php";

if(!isset($_SESSION['user_id'])){
exit("Unauthorized");
}

if(!verify_csrf($_POST['token'])){
exit("Invalid token");
}

$question = sanitize($_POST['question']);

if(strlen($question) < 3 || strlen($question) > 500){
exit("Invalid question");
}

$user_id = $_SESSION['user_id'];
$ip = $_SERVER['REMOTE_ADDR'];

/* Rate limiting */
$stmt = $conn->prepare(
"SELECT COUNT(*) as total FROM messages
WHERE user_id=? AND created_at > NOW() - INTERVAL 1 MINUTE"
);

$stmt->bind_param("i",$user_id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

if($data['total'] > RATE_LIMIT){
exit("Too many requests");
}

/* 🧠 GET KNOWLEDGE */
$knowledge = get_knowledge($conn, $question);

/* SYSTEM PROMPT */
$system_prompt = "You are BuildSmart AI, a professional construction and engineering assistant.";

/* COMBINE QUESTION + KNOWLEDGE */
$user_prompt = $question;

if(!empty($knowledge)){
$user_prompt .= "\n\nUse this engineering knowledge:\n".$knowledge;
}

/* AI REQUEST */
$payload = [
"model" => "gpt-4o-mini",
"messages" => [
["role"=>"system","content"=>$system_prompt],
["role"=>"user","content"=>$user_prompt]
],
"temperature" => 0.4
];

$ch = curl_init("https://api.openai.com/v1/chat/completions");

curl_setopt_array($ch, [
CURLOPT_RETURNTRANSFER => true,
CURLOPT_POST => true,
CURLOPT_HTTPHEADER => [
"Content-Type: application/json",
"Authorization: Bearer ".OPENAI_API_KEY
],
CURLOPT_POSTFIELDS => json_encode($payload)
]);

$response = curl_exec($ch);

if(curl_errno($ch)){
error_log("AI ERROR: ".curl_error($ch));
exit("AI error");
}

curl_close($ch);

$result = json_decode($response,true);

$ai_reply = $result['choices'][0]['message']['content'] ?? "No response";

/* SAVE */
$stmt = $conn->prepare(
"INSERT INTO messages (user_id,question,answer,ip_address)
VALUES (?,?,?,?)"
);

$stmt->bind_param("isss",$user_id,$question,$ai_reply,$ip);
$stmt->execute();

/* LOG */
error_log(
$user_id." | ".$question."\n",
3,
"../logs/ai_queries.log"
);

echo json_encode([
"reply"=>$ai_reply
]);