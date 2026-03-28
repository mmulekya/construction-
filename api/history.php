<?php
require_once "../includes/config.php";
require_once "../includes/database.php";
require_once "../includes/security.php";

header("Content-Type: application/json");
session_start();

// Auth
$user_id = $_SESSION['user_id'] ?? null;
if(!$user_id){
    exit(json_encode(["error"=>"Unauthorized"]));
}

// Optional rate-limit
check_rate_limit("history_" . $user_id, 20, 60);

// Fetch history
$stmt = db_prepare("SELECT question, answer, created_at FROM chat_history WHERE user_id=? ORDER BY id DESC LIMIT 50");
$stmt->bind_param("i",$user_id);
$stmt->execute();
$result = $stmt->get_result();
$history = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

echo json_encode(["status"=>"success","history"=>$history]);