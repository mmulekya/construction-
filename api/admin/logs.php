<?php
require_once "../../includes/config.php";
require_once "../../includes/database.php";
require_once "../../includes/security.php";

session_start();
require_admin();

header("Content-Type: application/json");

$res = $conn->query("SELECT * FROM logs ORDER BY id DESC LIMIT 100");

$data = [];

while($row = $res->fetch_assoc()){
    $data[] = $row;
}

echo json_encode($data);