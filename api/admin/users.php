<?php
require_once "../../includes/config.php";
require_once "../../includes/database.php";
require_once "../../includes/security.php";

session_start();
require_admin();

header("Content-Type: application/json");

$res = $conn->query("SELECT id, name, email, role, created_at FROM users ORDER BY id DESC");

$data = [];

while($row = $res->fetch_assoc()){
    $data[] = $row;
}

echo json_encode($data);