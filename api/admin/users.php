<?php
require_once "../../includes/config.php";
require_once "../../includes/database.php";
require_once "../../includes/security.php";

session_start();
require_admin();

header("Content-Type: application/json");

// Fetch users with status included
$sql = "SELECT id, name, email, role, status, created_at FROM users ORDER BY id DESC";
$result = $conn->query($sql);

$users = [];

if($result){
    while($row = $result->fetch_assoc()){
        $users[] = $row;
    }
}

echo json_encode($users);