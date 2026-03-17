<?php
require_once "../includes/config.php";
require_once "../includes/security.php";

if(!has_permission($conn,$_SESSION['user_id'],'add_knowledge')) exit(json_response(["error"=>"Access denied"]));
$title = trim($_POST['title'] ?? '');
$content = trim($_POST['content'] ?? '');
if(!$title || !$content) exit(json_response(["error"=>"Title and content required"]));

$embedding = generate_embedding($content); // AI embedding
$stmt=$conn->prepare("INSERT INTO knowledge_base (title, content, embedding, source_type) VALUES (?,?,?,?)");
$source_type='user_submission';
$stmt->bind_param("ssss",$title,$content,$embedding,$source_type);
$stmt->execute();

log_action($conn,"Knowledge_Added","Added title '$title'",$_SESSION['user_id']);
json_response(["success"=>true]);