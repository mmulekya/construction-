<?php
require_once "../includes/config.php";
require_once "../includes/database.php";
require_once "../includes/security.php";
require_once "../includes/history.php";

session_start();
require_login();

$user_id = $_SESSION['user_id'];

$result = get_user_history($conn, $user_id);

$history = [];

while($row = $result->fetch_assoc()){
    $history[] = $row;
}

echo json_encode($history);