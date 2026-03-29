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

echo json_encode(["attackers"=>$attackers]);