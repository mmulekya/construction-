<?php
require_once "../includes/config.php";
require_once "../includes/database.php";
require_once "../includes/security.php";

header("Content-Type: application/json");
session_start();

$user_id = $_SESSION['user_id'] ?? null;
if(!$user_id) exit(json_encode(["error"=>"Unauthorized"]));

// CSRF
$csrf = $_POST['csrf_token'] ?? '';
if(!verify_csrf_token($csrf)) exit(json_encode(["error"=>"Invalid CSRF token"]));

$project_name = trim($_POST['name'] ?? '');
if(!$project_name) exit(json_encode(["error"=>"Project name required"]));

// Insert project
$stmt = db_prepare("INSERT INTO projects (name, user_id, created_at) VALUES (?,?,NOW())");
$stmt->bind_param("si", $project_name, $user_id);
$stmt->execute();
$project_id = $stmt->insert_id;
$stmt->close();

echo json_encode(["success"=>true,"project_id"=>$project_id]);