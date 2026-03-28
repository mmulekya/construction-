<?php
require_once "../includes/config.php";
require_once "../includes/database.php";
require_once "../includes/security.php";
require_once "../includes/rate_limit.php";

// Start session
if(session_status() === PHP_SESSION_NONE){
    session_start();
}

header("Content-Type: application/json");

// Get endpoint from request
$endpoint = $_GET['endpoint'] ?? '';
$endpoint = preg_replace("/[^a-z0-9_]/i","",$endpoint);

if(!$endpoint){
    http_response_code(400);
    exit(json_encode(["error"=>"No endpoint specified"]));
}

// Whitelist of allowed endpoints
$whitelist = [
    "login","register","logout","ask_ai","history",
    "add_project_data","create_projects","request_reset",
    "submit_knowledge","verify_email","verify_otp"
];

// Check whitelist
if(!in_array($endpoint,$whitelist)){
    http_response_code(404);
    exit(json_encode(["error"=>"Endpoint not found"]));
}

// Load endpoint file safely
$path = __DIR__ . "/$endpoint.php";

if(!file_exists($path)){
    http_response_code(500);
    exit(json_encode(["error"=>"Endpoint file missing"]));
}

// Optional: Rate-limit globally for this endpoint
$user_id = $_SESSION['user_id'] ?? 0;
check_rate_limit($conn, "api_".$endpoint."_".$user_id, 10, 60);

// Require CSRF for POST
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $csrf = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
    if(!verify_csrf_token($csrf)){
        http_response_code(403);
        exit(json_encode(["error"=>"Invalid CSRF token"]));
    }
}

// Include endpoint
require_once $path;