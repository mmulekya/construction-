<?php
require_once "../includes/config.php";
require_once "../includes/database.php";
require_once "../includes/security.php";
require_once "../includes/rate_limit.php";

header("Content-Type: application/json");
session_start();

// Auth
$user_id = $_SESSION['user_id'] ?? null;
if(!$user_id) exit(json_encode(["error"=>"Unauthorized"]));

// Rate-limit
check_rate_limit("add_project_data_" . $user_id, 10, 60);

// CSRF
$csrf = $_POST['csrf_token'] ?? '';
if(!verify_csrf_token($csrf)) exit(json_encode(["error"=>"Invalid CSRF token"]));

// Input
$project_id = intval($_POST['project_id'] ?? 0);
$data = trim($_POST['data'] ?? '');
if(!$project_id || !$data) exit(json_encode(["error"=>"Missing project or data"]));

// Save data
$stmt = db_prepare("INSERT INTO project_data (project_id, data, created_at) VALUES (?,?,NOW())");
$stmt->bind_param("is", $project_id, $data);
$stmt->execute();
$stmt->close();

echo json_encode(["success"=>true,"message"=>"Project data added"]);