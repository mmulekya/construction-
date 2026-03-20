<?php
require_once "../../includes/config.php";
require_once "../../includes/database.php";
require_once "../../includes/security.php";

session_start();
require_admin();

$data = json_decode(file_get_contents("php://input"), true);

$user_id = intval($data['user_id']);

$stmt = $conn->prepare("DELETE FROM users WHERE id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();

echo json_encode(["success"=>true]);