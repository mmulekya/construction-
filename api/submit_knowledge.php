<?php
require_once "../includes/config.php";
require_once "../includes/database.php";
require_once "../includes/embedding.php";
require_once "../includes/security.php";

if(!has_permission($conn,$_SESSION['user_id'],'add_knowledge'))
    exit(json_response(["error"=>"Permission denied"]));

$text = trim($_POST['text'] ?? '');
$title = trim($_POST['title'] ?? 'User Submission');
if(strlen($text)==0) exit(json_response(["error"=>"Empty submission"]));

$embedding = generate_embedding($text);

$stmt = $conn->prepare("INSERT INTO knowledge_base (title, content, embedding, source_type) VALUES (?,?,?,?)");
$stmt->bind_param("ssss", $title, $text, $embedding, $source_type='user');
$stmt->execute();

// Log submission
log_action($conn,"User_Knowledge_Submission","Added knowledge: $title", $_SESSION['user_id']);
json_response(["success"=>"Knowledge submitted and AI updated"]);