<?php
require_once "../../includes/config.php";
require_once "../../includes/database.php";
require_once "../../includes/security.php";

header("Content-Type: application/json");
session_start();
require_login();
require_admin();

$csrf = $_POST['csrf_token'] ?? '';
if(!verify_csrf_token($csrf)){
    exit(json_encode(["error"=>"Invalid CSRF"]));
}

$id = intval($_POST['id'] ?? 0);

$stmt = db_prepare("DELETE FROM users WHERE id=?");
$stmt->bind_param("i",$id);
$stmt->execute();

// Log action
$db = db_prepare("INSERT INTO logs (action, ip_address, created_at) VALUES ('delete_user', ?, NOW())");
$db->bind_param("s", $_SERVER['REMOTE_ADDR']);
$db->execute();

echo json_encode(["success"=>true]);