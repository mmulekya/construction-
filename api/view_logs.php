<?php
require_once "../includes/config.php";
require_once "../includes/security.php";

if(!has_permission($conn,$_SESSION['user_id'],'view_logs')){
    json_response(["error"=>"Access denied"]);
}

$log_file = '../logs/actions.log';

if(!file_exists($log_file)){
    json_response(["error"=>"Log file not found"]);
}

// Read logs safely (limit last 200 lines)
$lines = array_slice(file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES), -200);
$lines = array_map('htmlspecialchars', $lines);

json_response([
    "success"=>true,
    "logs"=>$lines
]);