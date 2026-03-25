<?php

require_once "../includes/config.php";
require_once "../includes/database.php";
require_once "../includes/embedding.php";
require_once "../includes/security.php";

require_login();

// Permission check
if(!has_permission($conn, $_SESSION['user_id'], 'upload_pdf')){
    exit(json_encode(["error"=>"Access denied"]));
}

// File check
if(!isset($_FILES['pdf'])){
    exit(json_encode(["error"=>"No file uploaded"]));
}

$pdf_file = $_FILES['pdf'];

// Strict validation
if($pdf_file['error'] !== UPLOAD_ERR_OK){
    exit(json_encode(["error"=>"Upload error"]));
}

if(mime_content_type($pdf_file['tmp_name']) !== 'application/pdf'){
    exit(json_encode(["error"=>"Invalid file type"]));
}

// 🔥 LIMIT SIZE (SAFE FOR FREE HOSTING)
if($pdf_file['size'] > 2 * 1024 * 1024){
    exit(json_encode(["error"=>"Max 2MB allowed"]));
}

// Safe filename
$random_name = bin2hex(random_bytes(12)).'.pdf';
$upload_path = __DIR__.'/../uploads/pdfs/'.$random_name;

if(!move_uploaded_file($pdf_file['tmp_name'], $upload_path)){
    exit(json_encode(["error"=>"Upload failed"]));
}


// ❌ REMOVE shell_exec (NOT ALLOWED)
// 👉 Instead: store metadata OR pre-process externally

$text = ""; // Placeholder (you can upload extracted text instead)


// 🔥 LIMIT CHUNKS (VERY IMPORTANT)
$chunks = str_split($text, 500);
$limit = 20; // max chunks per upload

$count = 0;

foreach($chunks as $chunk){

    if($count >= $limit) break;

    if(trim($chunk) == '') continue;

    // Safe embedding (optional)
    $embedding = generate_embedding($chunk);

    $source_type = 'pdf';

    $stmt = $conn->prepare("
        INSERT INTO knowledge_base (title, content, embedding, source_type)
        VALUES (?, ?, ?, ?)
    ");

    $stmt->bind_param("ssss", $pdf_file['name'], $chunk, $embedding, $source_type);
    $stmt->execute();

    $count++;
}


// Logging
log_action(
    $conn,
    "PDF_UPLOAD",
    "PDF stored safely: ".$pdf_file['name'],
    $_SESSION['user_id']
);

echo json_encode([
    "success"=>true,
    "message"=>"PDF uploaded (processing limited for safety)"
]);