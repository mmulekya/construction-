<?php
require_once "../includes/config.php";
require_once "../includes/database.php";
require_once "../includes/security.php";

header("Content-Type: application/json");
session_start();

$user_id = $_SESSION['user_id'] ?? null;
if(!$user_id) exit(json_encode(["error"=>"Unauthorized"]));

// CSRF
$csrf = $_POST['csrf_token'] ?? '';
if(!verify_csrf_token($csrf)) exit(json_encode(["error"=>"Invalid CSRF token"]));

// Input
$question = trim($_POST['question'] ?? '');
$answer = trim($_POST['answer'] ?? '');
if(!$question || !$answer) exit(json_encode(["error"=>"Both question and answer required"]));

// Save knowledge
$stmt = db_prepare("INSERT INTO knowledge (question, answer, user_id, created_at) VALUES (?,?,?,NOW())");
$stmt->bind_param("ssi", $question, $answer, $user_id);
$stmt->execute();
$stmt->close();

echo json_encode(["success"=>true,"message"=>"Knowledge submitted"]);