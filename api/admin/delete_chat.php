<?php
require_once "../../includes/config.php";
require_once "../../includes/database.php";
require_once "../../includes/security.php";

session_start();
require_admin();

$data = json_decode(file_get_contents("php://input"), true);

$id = intval($data['id']);

$stmt = $conn->prepare("DELETE FROM chat_history WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();

echo json_encode(["success"=>"Deleted"]);