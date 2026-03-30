<?php
require_once "../../includes/config.php";
require_once "../../includes/database.php";
require_once "../../includes/security.php";

header("Content-Type: application/json");
session_start();
$user_id = require_admin_jwt();

$file = $_FILES['pdf'] ?? null;

if(!$file){
    exit(json_encode(["error"=>"No file"]));
}

if(pathinfo($file['name'], PATHINFO_EXTENSION) !== 'pdf'){
    exit(json_encode(["error"=>"Only PDF allowed"]));
}

if($file['size'] > 2 * 1024 * 1024){
    exit(json_encode(["error"=>"Max 2MB allowed"]));
}

$filename = time()."_".$file['name'];
move_uploaded_file($file['tmp_name'], "../../uploads/pdfs/".$filename);

echo json_encode(["success"=>true]);