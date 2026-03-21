<?php

require_once "../includes/config.php";
require_once "../includes/database.php";
require_once "../includes/security.php";
require_once "../includes/project.php";

header("Content-Type: application/json");

require_login();

// CSRF
if(!verify_csrf_token($_POST['csrf_token'] ?? '')){
    exit(json_encode(["error"=>"Invalid CSRF"]));
}

$name = trim($_POST['name'] ?? '');
$description = trim($_POST['description'] ?? '');

if(empty($name)){
    exit(json_encode(["error"=>"Project name required"]));
}

create_project($conn, $_SESSION['user_id'], $name, $description);

echo json_encode(["success"=>true]);