<?php
require_once "../../includes/config.php";
require_once "../../includes/database.php";
require_once "../../includes/security.php";

header("Content-Type: application/json");
session_start();
$user_id = require_admin_jwt();

$id = intval($_POST['id']);

$stmt = db_prepare("DELETE FROM chat_messages WHERE id=?");
$stmt->bind_param("i",$id);
$stmt->execute();

echo json_encode(["success"=>true]);