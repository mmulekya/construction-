<?php
require_once "../includes/config.php";
require_once "../includes/database.php";

if(!isset($_SESSION['admin_id'])) exit("Unauthorized");
if(!verify_csrf($_POST['token'])) exit("Invalid CSRF");

$name = trim($_POST['name']);
$description = trim($_POST['description'] ?? '');
$formula = trim($_POST['formula'] ?? '');

// Validate
if(strlen($name) > 100 || strlen($description) > 1000 || strlen($formula) > 500){
    exit("Invalid input");
}

// Insert securely
$stmt = $conn->prepare("INSERT INTO calculators (name, description, formula) VALUES (?,?,?)");
$stmt->bind_param("sss", $name, $description, $formula);
$stmt->execute();

echo "Calculator added successfully!";