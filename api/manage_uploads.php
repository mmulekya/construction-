<?php
require_once "../includes/config.php";
require_once "../includes/security.php";

if(!has_permission($conn,$_SESSION['user_id'],'manage_uploads')){
    json_response(["error"=>"Access denied"]);
}

// Sanitize filenames for display
function sanitize_filename($name){
    return htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
}

// List PDFs
$pdf_dir = '../uploads/pdfs/';
$images_dir = '../uploads/images/';

$pdf_files = array_diff(scandir($pdf_dir), ['.','..']);
$image_files = array_diff(scandir($images_dir), ['.','..']);

$uploads = [
    "pdfs" => array_map('sanitize_filename', $pdf_files),
    "images" => array_map('sanitize_filename', $image_files)
];

json_response([
    "success"=>true,
    "uploads"=>$uploads
]);