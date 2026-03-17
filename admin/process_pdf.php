<?php
require_once "../includes/database.php";
require_once "../includes/embedding.php";

/**
 * Extract text from PDF using `pdftotext` (Linux) or other library
 */
function extract_text_from_pdf($path){
    $text = shell_exec("pdftotext ".escapeshellarg($path)." -");
    return strip_tags($text);
}

function process_pdf($path){
    global $conn;

    $content = extract_text_from_pdf($path);
    if(strlen($content) < 50) return;

    // Split into sections (max 500 chars)
    $chunks = str_split($content, 500);

    foreach($chunks as $chunk){
        $chunk = trim($chunk);
        if(empty($chunk)) continue;

        // Generate embedding
        $embedding = generate_embedding($chunk);

        // Insert into knowledge_base securely
        $stmt = $conn->prepare("INSERT INTO knowledge_base (title, category, content, source, embedding) VALUES (?,?,?,?,?)");
        $title = substr($chunk,0,50);
        $category = "PDF";
        $source = basename($path);
        $stmt->bind_param("sssss", $title, $category, $chunk, $source, $embedding);
        $stmt->execute();

        sleep(1); // prevent API overload
    }
}