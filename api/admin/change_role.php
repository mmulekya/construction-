<?php
require_once "../../includes/config.php";
require_once "../../includes/database.php";
require_once "../../includes/security.php";

session_start();
require_admin();

$data = json_decode(file_get_contents("php://input"), true);

$user_id = intval($data['user_id']);
$role = $data['role']; // user | admin

$stmt = $conn->prepare("UPDATE users SET role=? WHERE id=?");
$stmt->bind_param("si", $role, $user_id);
$stmt->execute();

echo json_encode(["success"=>true]);