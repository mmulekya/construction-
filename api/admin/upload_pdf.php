<?php

require_once "../../includes/config.php";
require_once "../../includes/database.php";
require_once "../../includes/security.php";

header("Content-Type: application/json");

require_login();
require_admin();

if(!verify_csrf_token($_POST['csrf_token'] ?? '')){
    exit(json_encode(["error"=>"Invalid CSRF"]));
}

if(!isset($_FILES['pdf'])){
    exit(json_encode(["error"=>"No file uploaded"]));
}

$file = $_FILES['pdf'];

$allowed = ['application/pdf'];

if(!in_array($file['type'], $allowed)){
    exit(json_encode(["error"=>"Only PDF allowed"]));
}

// Limit size (5MB)
if($file['size'] > 5 * 1024 * 1024){
    exit(json_encode(["error"=>"File too large"]));
}

$filename = time() . "_" . basename($file['name']);
$target = "../../uploads/pdfs/" . $filename;

if(move_uploaded_file($file['tmp_name'], $target)){
    echo json_encode(["success"=>true, "file"=>$filename]);
} else {
    echo json_encode(["error"=>"Upload failed"]);
}