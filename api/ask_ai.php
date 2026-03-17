<?php
require_once "../includes/config.php";
require_once "../includes/database.php";
require_once "../includes/knowledge.php";
require_once "../includes/security.php";

if(!isset($_SESSION['user_id'])) exit(json_response(["error"=>"Unauthorized"]));
if(!has_permission($conn,$_SESSION['user_id'],'ask_ai')) exit(json_response(["error"=>"Permission denied"]));
if(!verify_csrf($_POST['token'])) exit(json_response(["error"=>"Invalid CSRF"]));

$question = trim($_POST['question'] ?? '');
if(empty($question)) exit(json_response(["error"=>"Question required"]));

// Rate limiting
if(!isset($_SESSION['ai_requests'])) $_SESSION['ai_requests'] = [];
$_SESSION['ai_requests'][] = time();
$_SESSION['ai_requests'] = array_filter($_SESSION['ai_requests'], fn($t)=>$t>time()-60);
if(count($_SESSION['ai_requests']) > RATE_LIMIT){
    trigger_alert($conn,"Rate_Limit_Exceeded","User exceeded AI request limit", $_SESSION['user_id']);
    exit(json_response(["error"=>"Rate limit exceeded"]));
}

// Get AI knowledge answer
$knowledge = get_knowledge($conn,$question);

// Log the question
log_action($conn,"AI_Question","Question: ".substr($question,0,500), $_SESSION['user_id']);

json_response(["knowledge"=>$knowledge]);