<?php
require_once "../includes/config.php";
require_once "../includes/database.php";
require_once "../includes/security.php";
require_once "../includes/rate_limit.php";

header("Content-Type: application/json");

if(session_status() === PHP_SESSION_NONE){
    session_start();
}

$user_id = get_authenticated_user();

if(!$user_id){
    exit(json_encode(["error"=>"Unauthorized"]));
}

// Only admin allowed
if(!is_admin()){
    exit(json_encode(["error"=>"Admin only"]));
}

check_rate_limit($conn, "knowledge_" . $user_id, 5, 60);

$csrf = $_POST['csrf_token'] ?? '';
if(!verify_csrf_token($csrf)){
    exit(json_encode(["error"=>"Invalid CSRF"]));
}

$title = trim($_POST['title'] ?? '');
$content = trim($_POST['content'] ?? '');

if(strlen($title) < 3 || strlen($content) < 10){
    exit(json_encode(["error"=>"Invalid input"]));
}

$stmt = db_prepare("
INSERT INTO knowledge_base (title, content, created_at)
VALUES (?, ?, NOW())
");

$stmt->bind_param("ss", $title, $content);
$stmt->execute();
$stmt->close();

echo json_encode(["success"=>true]);