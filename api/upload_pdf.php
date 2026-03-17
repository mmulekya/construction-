<?php
require_once "../includes/config.php";
require_once "../includes/security.php";

if(!has_permission($conn,$_SESSION['user_id'],'upload_pdf')) exit(json_response(["error"=>"Access denied"]));

if(isset($_FILES['pdf'])){
    $file=$_FILES['pdf'];
    $allowed=['application/pdf'];
    if(!in_array($file['type'],$allowed)) exit(json_response(["error"=>"Invalid file type"]));
    $dest='../uploads/pdfs/'.basename($file['name']);
    move_uploaded_file($file['tmp_name'],$dest);

    // Extract text & insert into knowledge base (simplified)
    $text=extract_pdf_text($dest);
    $embedding=generate_embedding($text);
    $stmt=$conn->prepare("INSERT INTO knowledge_base (title, content, embedding, source_type) VALUES (?,?,?,?)");
    $source_type='pdf_upload';
    $stmt->bind_param("ssss",$file['name'],$text,$embedding,$source_type);
    $stmt->execute();

    log_action($conn,"PDF_Uploaded","Uploaded ".$file['name'],$_SESSION['user_id']);
    json_response(["success"=>true]);
} else { json_response(["error"=>"No PDF uploaded"]); }