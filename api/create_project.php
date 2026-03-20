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

$name = sanitize($data['name']);
$description = sanitize($data['description']);

$id = create_project($conn, $_SESSION['user_id'], $name, $description);

echo json_encode(["success"=>true, "project_id"=>$id]);