<?php
require_once "../../includes/config.php";
require_once "../../includes/database.php";
require_once "../../includes/security.php";

header("Content-Type: application/json");
session_start();
$user_id = require_admin_jwt();

$stmt = db_prepare("SELECT id,name,email,role,status,created_at FROM users");
$stmt->execute();

$result = $stmt->get_result();
$data = $result->fetch_all(MYSQLI_ASSOC);

echo json_encode(["success"=>true,"users"=>$data]);