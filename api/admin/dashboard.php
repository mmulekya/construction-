<?php
require_once "../../includes/config.php";
require_once "../../includes/database.php";
require_once "../../includes/security.php";

session_start();
require_admin();

header("Content-Type: application/json");

// Users count
$users = $conn->query("SELECT COUNT(*) as total FROM users")->fetch_assoc()['total'];

// Chats count
$chats = $conn->query("SELECT COUNT(*) as total FROM chat_history")->fetch_assoc()['total'];

// Projects count
$projects = $conn->query("SELECT COUNT(*) as total FROM projects")->fetch_assoc()['total'];

// Logs count
$logs = $conn->query("SELECT COUNT(*) as total FROM logs")->fetch_assoc()['total'];

echo json_encode([
    "users"=>$users,
    "chats"=>$chats,
    "projects"=>$projects,
    "logs"=>$logs
]);