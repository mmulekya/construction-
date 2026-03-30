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

check_rate_limit($conn, "create_project_" . $user_id, 5, 60);

$csrf = $_POST['csrf_token'] ?? '';
if(!verify_csrf_token($csrf)){
    exit(json_encode(["error"=>"Invalid CSRF token"]));
}

$name = trim($_POST['name'] ?? '');

if(strlen($name) < 3){
    exit(json_encode(["error"=>"Project name too short"]));
}

$stmt = db_prepare("
INSERT INTO projects (user_id, name, created_at)
VALUES (?, ?, NOW())
");

$stmt->bind_param("is", $user_id, $name);
$stmt->execute();
$stmt->close();

echo json_encode(["success"=>true]);