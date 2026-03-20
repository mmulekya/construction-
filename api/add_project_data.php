<?php
require_once "../includes/rate_limit.php";
check_rate_limit($conn, "project", 15, 60);
require_once "../includes/config.php";
require_once "../includes/database.php";
require_once "../includes/security.php";
require_once "../includes/project.php";

session_start();
require_login();

$data = json_decode(file_get_contents("php://input"), true);

$project_id = intval($data['project_id']);
$type = sanitize($data['type']);
$content = sanitize($data['content']);

add_project_data($conn, $project_id, $type, $content);

echo json_encode(["success"=>true]);