<?php

require_once "../../includes/config.php";
require_once "../../includes/database.php";
require_once "../../includes/security.php";

header("Content-Type: application/json");

require_login();
require_admin();

if(!verify_csrf_token($_POST['csrf_token'] ?? '')){
    exit(json_encode(["error"=>"Invalid CSRF"]));
}

$id = intval($_POST['id'] ?? 0);

// Toggle status
$stmt = $conn->prepare("
    UPDATE users 
    SET status = IF(status='active','suspended','active')
    WHERE id=?
");

$stmt->bind_param("i", $id);
$stmt->execute();

echo json_encode(["success"=>true]);