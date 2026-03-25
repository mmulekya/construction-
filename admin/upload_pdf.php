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

// Validate upload
if($pdf_file['error'] !== UPLOAD_ERR_OK){
    exit(json_encode(["error"=>"Upload error"]));
}

// MIME check
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $pdf_file['tmp_name']);
finfo_close($finfo);

if($mime !== 'application/pdf'){
    exit(json_encode(["error"=>"Invalid file type"]));
}

// Size limit (InfinityFree safe)
if($pdf_file['size'] > 2 * 1024 * 1024){
    exit(json_encode(["error"=>"Max 2MB allowed"]));
}

// Save file
$random_name = bin2hex(random_bytes(12)).'.pdf';
$upload_dir = __DIR__.'/../uploads/pdfs/';

if(!is_dir($upload_dir)){
    mkdir($upload_dir, 0755, true);
}

$upload_path = $upload_dir.$random_name;

if(!move_uploaded_file($pdf_file['tmp_name'], $upload_path)){
    exit(json_encode(["error"=>"Upload failed"]));
}


/* =========================
   🔐 TEXT INPUT (RECOMMENDED)
   =========================
   Expect extracted text sent via POST:
   $_POST['extracted_text']
*/

$extracted_text = trim($_POST['extracted_text'] ?? '');

if(empty($extracted_text)){
    exit(json_encode([
        "error"=>"No extracted text provided. Please extract text before upload."
    ]));
}


/* =========================
   🧠 CHUNKING
========================= */

$chunks = str_split($extracted_text, 800); // optimized chunk size
$limit = 30; // max chunks per PDF

$count = 0;

foreach($chunks as $chunk){

    if($count >= $limit) break;

    $chunk = trim($chunk);
    if(empty($chunk)) continue;

    /* =========================
       🔐 EMBEDDING
    ========================= */

    $embedding = generate_embedding($chunk);

    if(!$embedding){
        continue;
    }

    $embedding_json = json_encode($embedding);

    /* =========================
       💾 STORE IN DATABASE
    ========================= */

    $stmt = $conn->prepare("
        INSERT INTO knowledge_base 
        (title, content, embedding, source_type, created_at)
        VALUES (?, ?, ?, 'pdf', NOW())
    ");

    $stmt->bind_param(
        "sss",
        $pdf_file['name'],
        $chunk,
        $embedding_json
    );

    $stmt->execute();

    $count++;
}


/* =========================
   📊 LOGGING
========================= */

log_action(
    $conn,
    "PDF_UPLOAD",
    "Uploaded PDF: ".$pdf_file['name'],
    $_SESSION['user_id']
);


/* =========================
   ✅ RESPONSE
========================= */

echo json_encode([
    "success" => true,
    "message" => "PDF uploaded and processed with semantic embeddings",
    "chunks_stored" => $count
]);


<form method="POST" enctype="multipart/form-data">
    <input type="hidden" name="token" value="<?= csrf_token(); ?>">
    <input type="file" name="pdf" accept=".pdf" required>
    <button type="submit">Upload PDF</button>
</form>