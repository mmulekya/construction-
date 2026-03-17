<?php

require_once "../includes/database.php";
require_once "../includes/security.php";
require_once "../includes/config.php";

if(!isset($_SESSION['user_id'])){
exit("Unauthorized");
}

if(!verify_token($_POST['token'])){
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
$result = $stmt->get_result()->fetch_assoc();

if($result['total'] > 10){
exit("Too many requests");
}

/* SYSTEM PROMPT (Construction AI) */
$system_prompt = "You are BuildSmart AI, a professional construction and engineering assistant. Provide accurate and practical answers.";

/* Prepare API request */
$data = [
"model" => "gpt-4o-mini",
"messages" => [
["role"=>"system","content"=>$system_prompt],
["role"=>"user","content"=>$question]
],
"temperature" => 0.5
];

/* cURL request */
$ch = curl_init("https://api.openai.com/v1/chat/completions");

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);

curl_setopt($ch, CURLOPT_HTTPHEADER, [
"Content-Type: application/json",
"Authorization: Bearer " . OPENAI_API_KEY
]);

curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

$response = curl_exec($ch);

if(curl_errno($ch)){
error_log("AI API Error: ".curl_error($ch));
exit("AI error");
}

curl_close($ch);

$result = json_decode($response, true);

$ai_reply = $result['choices'][0]['message']['content'] ?? "No response";

/* Save to DB */
$stmt = $conn->prepare(
"INSERT INTO messages (user_id,question,answer,ip_address)
VALUES (?,?,?,?)"
);

$stmt->bind_param("isss",$user_id,$question,$ai_reply,$ip);
$stmt->execute();

/* Log */
error_log(
$user_id." | ".$question."\n",
3,
"../logs/ai_queries.log"
);

/* Return response */
echo json_encode([
"reply"=>$ai_reply
]);