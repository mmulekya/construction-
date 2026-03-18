<?php
require_once "../../includes/config.php";
require_once "../../includes/database.php";
require_once "../../includes/security.php";

session_start();
require_admin();

if(!isset($_FILES['pdf'])){
    echo json_encode(["error"=>"No file uploaded"]);
    exit;
}

$file = $_FILES['pdf'];

if($file['type'] !== "application/pdf"){
    echo json_encode(["error"=>"Only PDF allowed"]);
    exit;
}

// Security: size limit (5MB)
if($file['size'] > 5 * 1024 * 1024){
    echo json_encode(["error"=>"File too large"]);
    exit;
}

$filename = time() . "_" . basename($file['name']);
$path = "../../uploads/pdfs/" . $filename;

if(!move_uploaded_file($file['tmp_name'], $path)){
    echo json_encode(["error"=>"Upload failed"]);
    exit;
}

// 🔥 BASIC PDF TEXT EXTRACTION
$content = "";

// Read raw file
$pdf = file_get_contents($path);

// Extract readable text
if(preg_match_all('/\((.*?)\)/s', $pdf, $matches)){
    $content = implode(" ", $matches[1]);
}

// Clean text
$content = preg_replace('/[^A-Za-z0-9\s\.\,\-\:]/', ' ', $content);
$content = substr($content, 0, 50000); // limit size

if(empty($content)){
    $content = "Document uploaded but text extraction failed.";
}

// Save to DB
$stmt = $conn->prepare("INSERT INTO documents (filename, content) VALUES (?,?)");
$stmt->bind_param("ss", $filename, $content);
$stmt->execute();

echo json_encode([
    "success"=>"PDF processed and stored",
    "preview"=>substr($content,0,200)
]);