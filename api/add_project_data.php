<?php

require_once "../includes/config.php";
require_once "../includes/database.php";
require_once "../includes/security.php";

header("Content-Type: application/json");

require_login();

// CSRF
if(!verify_csrf_token($_POST['csrf_token'] ?? '')){
    exit(json_encode(["error"=>"Invalid CSRF"]));
}

$project_id = intval($_POST['project_id'] ?? 0);
$data = trim($_POST['data'] ?? '');

if($project_id <= 0 || empty($data)){
    exit(json_encode(["error"=>"Invalid input"]));
}

// Ensure ownership
$stmt = $conn->prepare("SELECT id FROM projects WHERE id=? AND user_id=?");
$stmt->bind_param("ii", $project_id, $_SESSION['user_id']);
$stmt->execute();

if($stmt->get_result()->num_rows === 0){
    exit(json_encode(["error"=>"Unauthorized project access"]));
}

// Insert data
$stmt = $conn->prepare("
    INSERT INTO project_data (project_id, data, created_at)
    VALUES (?, ?, NOW())
");

$stmt->bind_param("is", $project_id, $data);
$stmt->execute();

echo json_encode(["success"=>true]);