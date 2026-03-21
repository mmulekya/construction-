<?php

require_once "../../includes/config.php";
require_once "../../includes/database.php";
require_once "../../includes/security.php";

header("Content-Type: application/json");

require_login();
require_admin();

$result = $conn->query("
    SELECT * FROM login_attempts ORDER BY created_at DESC LIMIT 200
");

$logs = [];

while($row = $result->fetch_assoc()){
    $logs[] = $row;
}

echo json_encode(["success"=>true, "logs"=>$logs]);