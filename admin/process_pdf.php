<?php

require_once "../includes/config.php";
require_once "../includes/database.php";
require_once "../includes/embedding.php";
require_once "../includes/security.php";

header("Content-Type: application/json");

// Start session safely
if(session_status() === PHP_SESSION_NONE){
    session_start();
}

require_login();

// 🔐 Permission check
if(!has_permission($conn, $_SESSION['user_id'], 'upload_pdf')){
    exit(json_encode(["error"=>"Access denied"]));
}

// 🔐 File check
if(!isset($_FILES['pdf'])){
    exit(json_encode(["error"=>"No file uploaded"]));
}

$pdf_file = $_FILES['pdf'];

// 🔐 Upload validation
if($pdf_file['error'] !== UPLOAD_ERR_OK){
    exit(json_encode(["error"=>"Upload error"]));
}

if(mime_content_type($pdf_file['tmp_name']) !== 'application/pdf'){
    exit(json_encode(["error"=>"Invalid file type"]);
}

// 🔥 LIMIT SIZE (FREE HOST SAFE)
if($pdf_file['size'] > 2 * 1024 * 1024){
    exit(json_encode(["error"=>"Max 2MB allowed"]));
}

// 🔐 Safe filename
$random_name = bin2hex(random_bytes(12)) . '.pdf';
$upload_path = __DIR__ . '/../uploads/pdfs/' . $random_name;

if(!move_uploaded_file($pdf_file['tmp_name'], $upload_path)){
    exit(json_encode(["error"=>"Upload failed"]));
}

/* =========================
   📄 TEXT INPUT (SAFE MODE)
========================= */

// ❗ IMPORTANT:
// No shell_exec allowed on free hosting
// So we expect TEXT instead of extracting PDF server-side

$text = trim($_POST['extracted_text'] ?? '');

if(empty($text)){
    exit(json_encode([
        "error"=>"No text provided. Extract PDF text before upload."
    ]));
}

/* =========================
   ✂️ CLEAN + SPLIT TEXT
========================= */

// Clean text
$text = preg_replace('/\s+/', ' ', $text);

// Split into chunks
$chunks = str_split($text, 500);

// 🔥 LIMIT chunks (protect server)
$limit = 20;
$count = 0;

/* =========================
   🧠 STORE WITH EMBEDDINGS
========================= */

foreach($chunks as $chunk){

    if($count >= $limit) break;

    $chunk = trim($chunk);
    if(strlen($chunk) < 20) continue;

    // 🔐 Generate embedding (safe API call)
    $embedding = generate_embedding($chunk);
    $embedding_json = json_encode($embedding);

    $stmt = $conn->prepare("
        INSERT INTO pdf_chunks (content, embedding, pdf_name, created_at)
        VALUES (?, ?, ?, NOW())
    ");

    $stmt->bind_param("sss", $chunk, $embedding_json, $pdf_file['name']);
    $stmt->execute();

    $count++;
}

/* =========================
   🔐 LOGGING
========================= */

log_action(
    $conn,
    "PDF_UPLOAD",
    "PDF processed: " . $pdf_file['name'] . " (chunks: $count)",
    $_SESSION['user_id']
);

/* =========================
   ✅ RESPONSE
========================= */

echo json_encode([
    "success"=>true,
    "message"=>"PDF uploaded & processed successfully",
    "chunks_saved"=>$count
]);