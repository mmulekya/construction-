<?php
require_once "../includes/config.php";
require_once "../includes/database.php";
require_once "../includes/embedding.php";
require_once "../includes/security.php";

if(!has_permission($conn,$_SESSION['user_id'],'upload_pdf')) exit("Access denied");

$pdf_file = $_FILES['pdf'] ?? null;
if(!$pdf_file) exit("No file uploaded");

$allowed_types = ['application/pdf'];
if(!in_array($pdf_file['type'],$allowed_types)) exit("Invalid file type");

$max_size = 10*1024*1024; // 10MB
if($pdf_file['size'] > $max_size) exit("File too large");

$random_name = bin2hex(random_bytes(12)).'.pdf';
$upload_path = __DIR__.'/../uploads/pdfs/'.$random_name;

if(!move_uploaded_file($pdf_file['tmp_name'],$upload_path)){
    trigger_alert($conn,"PDF_Upload_Failed","Failed to upload PDF", $_SESSION['user_id']);
    exit("Upload failed");
}

// Extract text (safe)
$text = shell_exec("pdftotext ".escapeshellarg($upload_path)." -"); 

// Chunk text and embed
$chunks = str_split($text, 500); // 500 chars per chunk
foreach($chunks as $chunk){
    if(trim($chunk)=='') continue;
    $embedding = generate_embedding($chunk);
    $stmt = $conn->prepare("INSERT INTO knowledge_base (title, content, embedding, source_type) VALUES (?,?,?,?)");
    $stmt->bind_param("ssss", $pdf_file['name'], $chunk, $embedding, $source_type='pdf');
    $stmt->execute();
}

// Log the upload
log_action($conn,"PDF_Uploaded","PDF processed and added to knowledge base: ".$pdf_file['name'], $_SESSION['user_id']);
json_response(["success"=>"PDF processed and AI knowledge updated"]);