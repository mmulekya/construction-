<?php
require_once "../../includes/config.php";
require_once "../../includes/database.php";
require_once "../../includes/security.php";

session_start();
require_admin();

$data = json_decode(file_get_contents("php://input"), true);

$content = sanitize($data['content'] ?? '');

if(!$content){
    echo json_encode(["error"=>"Empty content"]);
    exit;
}

$stmt = $conn->prepare("INSERT INTO knowledge_base (content) VALUES (?)");
$stmt->bind_param("s", $content);

$stmt->execute();

echo json_encode(["success"=>"Knowledge added"]);