<?php

require_once "../../includes/config.php";
require_once "../../includes/database.php";
require_once "../../includes/security.php";

header("Content-Type: application/json");

require_login();
require_admin();

$result = $conn->query("SELECT id, name, email, role, status FROM users ORDER BY id DESC");

$users = [];

while($row = $result->fetch_assoc()){
    $users[] = $row;
}

echo json_encode(["success"=>true, "users"=>$users]);