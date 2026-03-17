<?php
require_once "../includes/config.php";
require_once "../includes/database.php";
require_once "../includes/embedding.php";

if(!isset($_SESSION['admin_id'])) exit("Unauthorized");
if(!verify_csrf($_POST['token'])) exit("Invalid CSRF");

$title = trim($_POST['title']);
$category = trim($_POST['category']);
$content = trim($_POST['content']);
$source = trim($_POST['source'] ?? '');

// Validate length
if(strlen($title) > 255 || strlen($category) > 100 || strlen($content) > 5000){
    exit("Invalid input length");
}

// Generate embedding securely
$embedding = generate_embedding($content);

// Insert securely
$stmt = $conn->prepare("INSERT INTO knowledge_base (title, category, content, source, embedding) VALUES (?,?,?,?,?)");
$stmt->bind_param("sssss", $title, $category, $content, $source, $embedding);
$stmt->execute();

echo "Knowledge added successfully!";