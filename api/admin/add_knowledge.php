<?php

require_once "../../includes/config.php";
require_once "../../includes/database.php";
require_once "../../includes/security.php";

header("Content-Type: application/json");

require_login();
require_admin();

if(!verify_csrf_token($_POST['csrf_token'] ?? '')){
    exit(json_encode(["error"=>"Invalid CSRF"]));
}

$title = trim($_POST['title'] ?? '');
$content = trim($_POST['content'] ?? '');

$stmt = $conn->prepare("
    INSERT INTO knowledge_base (title, content, created_at)
    VALUES (?, ?, NOW())
");

$stmt->bind_param("ss", $title, $content);
$stmt->execute();

echo json_encode(["success"=>true]);