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

$id = intval($_POST['id']);
$role = $_POST['role'] ?? 'user';

$stmt = db_prepare("UPDATE users SET role=? WHERE id=?");
$stmt->bind_param("si",$role,$id);
$stmt->execute();

echo json_encode(["success"=>true]);