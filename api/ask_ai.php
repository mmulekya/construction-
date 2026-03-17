<?php
require_once "../includes/config.php";
require_once "../includes/database.php";
require_once "../includes/security.php";
require_once "../includes/knowledge.php";

if(!isset($_SESSION['user_id'])) exit(json_response(["error"=>"Unauthorized"]));
if(!verify_csrf($_POST['token'])) exit(json_response(["error"=>"Invalid CSRF"]));

$question = trim($_POST['question'] ?? '');
if(empty($question)) exit(json_response(["error"=>"Question required"]));

// Rate limiting
if(!isset($_SESSION['ai_requests'])) $_SESSION['ai_requests']=[];
$_SESSION['ai_requests'][]=time();
$_SESSION['ai_requests']=array_filter($_SESSION['ai_requests'], fn($t)=>$t>time()-60);
if(count($_SESSION['ai_requests'])>RATE_LIMIT) exit(json_response(["error"=>"Rate limit exceeded"]));

// Get AI knowledge answer
$knowledge=get_knowledge($conn,$question);

// Log the question
log_action($conn,"AI_Question","Question: ".substr($question,0,500),$_SESSION['user_id']);

// Save to chat_analysis for trends
$stmt=$conn->prepare("INSERT INTO chat_analysis (question, ai_response, topic) VALUES (?,?,?)");
$topic=detect_topic($question);
$stmt->bind_param("sss",$question,$knowledge,$topic);
$stmt->execute();

json_response(["knowledge"=>$knowledge]);