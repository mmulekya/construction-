<?php

require_once "config.php";

/* =========================
   FORCE HTTPS (OPTIONAL)
========================= */
function enforce_https(){
    if(empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on'){
        header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
        exit;
    }
}

/* =========================
   SANITIZE INPUT
========================= */
function sanitize($data){
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/* =========================
   CSRF PROTECTION (STANDARDIZED)
========================= */
function generate_csrf_token(){
    if(empty($_SESSION['csrf_token'])){
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token){
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/* =========================
   AUTH CHECKS
========================= */
function is_logged_in(){
    return isset($_SESSION['user_id']);
}

function is_admin(){
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function require_login(){
    if(!is_logged_in()){
        http_response_code(401);
        echo json_encode(["error" => "Unauthorized"]);
        exit;
    }
}

function require_admin(){
    if(!is_logged_in() || !is_admin()){
        http_response_code(403);
        echo json_encode(["error" => "Forbidden"]);
        exit;
    }
}

/* =========================
   RATE LIMIT (LOGIN)
========================= */
function is_rate_limited($conn, $identifier){

    $stmt = $conn->prepare("
        SELECT COUNT(*) as attempts 
        FROM login_attempts 
        WHERE identifier=? 
        AND success=0 
        AND created_at > (NOW() - INTERVAL 15 MINUTE)
    ");

    $stmt->bind_param("s", $identifier);
    $stmt->execute();

    $result = $stmt->get_result()->fetch_assoc();

    return ($result['attempts'] >= 5);
}

/* =========================
   LOG LOGIN ATTEMPTS
========================= */
function log_login_attempt($conn, $identifier, $success){

    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

    $stmt = $conn->prepare("
        INSERT INTO login_attempts (identifier, ip, success, created_at)
        VALUES (?, ?, ?, NOW())
    ");

    $stmt->bind_param("ssi", $identifier, $ip, $success);
    $stmt->execute();
}