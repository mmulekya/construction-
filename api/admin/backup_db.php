<?php

require_once "../../includes/config.php";
require_once "../../includes/database.php";
require_once "../../includes/security.php";

header("Content-Type: application/json");

$user_id = require_admin_jwt();

// Tables to backup
$tables = [
    "users",
    "chat_messages",
    "projects",
    "project_data",
    "pdf_documents",
    "pdf_chunks"
];

$output = "";

foreach($tables as $table){

    $result = $conn->query("SELECT * FROM $table");

    while($row = $result->fetch_assoc()){
        $columns = implode("`, `", array_keys($row));
        $values = implode("', '", array_map([$conn,'real_escape_string'], $row));

        $output .= "INSERT INTO `$table` (`$columns`) VALUES ('$values');\n";
    }
}

// Download file
header('Content-Type: application/sql');
header('Content-Disposition: attachment; filename=backup.sql');

echo $output;
exit;