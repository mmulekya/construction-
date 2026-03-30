<?php

require_once "../../includes/config.php";
require_once "../../includes/database.php";
require_once "../../includes/security.php";

header("Content-Type: application/json");

$user_id = require_admin_jwt();

// Suspicious IPs
$result = $conn->query("
    SELECT ip, COUNT(*) as attempts 
    FROM login_attempts
    WHERE success=0
    GROUP BY ip
    HAVING attempts > 5
");

$ips = [];

while($row = $result->fetch_assoc()){
    $ips[] = $row;
}

// Top AI users
$usage = $conn->query("
    SELECT user_id, requests 
    FROM ai_usage 
    ORDER BY requests DESC LIMIT 10
");

$top = [];

while($row = $usage->fetch_assoc()){
    $top[] = $row;
}

echo json_encode([
    "suspicious_ips"=>$ips,
    "top_ai_users"=>$top
]);