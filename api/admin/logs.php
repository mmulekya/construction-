<?php
require_once "../../includes/config.php";
require_once "../../includes/database.php";
require_once "../../includes/security.php";

header("Content-Type: application/json");
session_start();
require_login();
require_admin();

$result = $conn->query("
SELECT ip_address, COUNT(*) as attempts
FROM logs
GROUP BY ip_address
HAVING attempts > 3
ORDER BY attempts DESC
");

$attackers = [];

while($row = $result->fetch_assoc()){
    $attackers[] = $row;
}

$banned = $conn->query("SELECT * FROM banned_ips ORDER BY banned_at DESC");

$banned_ips = [];

while($row = $banned->fetch_assoc()){
    $banned_ips[] = $row;
}

echo json_encode([
    "attackers"=>$attackers,
    "banned"=>$banned_ips
]);

echo json_encode(["attackers"=>$attackers]);