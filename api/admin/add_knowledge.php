<?php
require_once "../../includes/config.php";
require_once "../../includes/database.php";
require_once "../../includes/security.php";

header("Content-Type: application/json");
session_start();
require_login();
require_admin();

$title = sanitize($_POST['title'] ?? '');
$content = sanitize($_POST['content'] ?? '');

$stmt = db_prepare("
INSERT INTO knowledge_base (title, content, created_at)
VALUES (?, ?, NOW())
");

$stmt->bind_param("ss",$title,$content);
$stmt->execute();

echo json_encode(["success"=>true]);