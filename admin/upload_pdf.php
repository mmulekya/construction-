<?php
require_once "../includes/config.php";
if(!isset($_SESSION['admin_id'])) exit("Unauthorized");

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    if(!verify_csrf($_POST['token'])) exit("Invalid CSRF");

    if(!isset($_FILES['pdf']) || $_FILES['pdf']['error'] != UPLOAD_ERR_OK){
        exit("Upload failed");
    }

    $file = $_FILES['pdf'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if($ext !== 'pdf') exit("Only PDF allowed");

    $safe_name = bin2hex(random_bytes(8)) . ".pdf";
    $upload_path = __DIR__."/../uploads/pdfs/".$safe_name;

    if(!move_uploaded_file($file['tmp_name'], $upload_path)){
        exit("Failed to move file");
    }

    // Call processing script
    require_once "process_pdf.php";
    process_pdf($upload_path);

    echo "PDF uploaded and processed successfully!";
}
?>

<form method="POST" enctype="multipart/form-data">
    <input type="hidden" name="token" value="<?= csrf_token(); ?>">
    <input type="file" name="pdf" accept=".pdf" required>
    <button type="submit">Upload PDF</button>
</form>