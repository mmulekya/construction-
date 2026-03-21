<?php

require_once "../../includes/config.php";
require_once "../../includes/database.php";
require_once "../../includes/security.php";

header("Content-Type: application/json");

require_login();
require_admin();

// CSRF
if(!verify_csrf_token($_POST['csrf_token'] ?? '')){
    exit(json_encode(["error"=>"Invalid CSRF"]));
}

$id = intval($_POST['id'] ?? 0);

$stmt = $conn->prepare("DELETE FROM chat_history WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();

echo json_encode(["success"=>true]);