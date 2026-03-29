<?php
// ==========================================

// 🔒 SECURITY FUNCTIONS
// ==========================================

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';

function check_banned_ip($conn){
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

    $stmt = $conn->prepare("SELECT id FROM banned_ips WHERE ip=? LIMIT 1");
    $stmt->bind_param("s", $ip);
    $stmt->execute();

    if($stmt->get_result()->num_rows > 0){
        http_response_code(403);
        exit("🚫 Your IP has been blocked.");
    }
}

// Run check automatically
check_banned_ip($conn);

// ---------------------------
// 🔐 CSRF VERIFICATION
// ---------------------------
function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// ---------------------------
// 🔐 BRUTE-FORCE / LOGIN ATTEMPTS
// ---------------------------
function log_login_attempt($user, $success = 0) {
    global $mysqli;

    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $stmt = db_prepare("INSERT INTO login_attempts (email, ip, success, created_at) VALUES (?, ?, ?, NOW())");
    if ($stmt) {
        $stmt->bind_param("ssi", $user, $ip, $success);
        $stmt->execute();
        $stmt->close();
    }
}

function is_rate_limited($user, $max_attempts = 5, $interval_minutes = 5) {
    global $mysqli;

    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $stmt = db_prepare("
        SELECT COUNT(*) AS attempts 
        FROM login_attempts 
        WHERE email=? AND ip=? AND created_at > (NOW() - INTERVAL ? MINUTE)
    ");
    if ($stmt) {
        $stmt->bind_param("ssi", $user, $ip, $interval_minutes);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return ($result['attempts'] >= $max_attempts);
    }

    return false;
}

// ---------------------------
// 🔐 CLEAN OLD ATTEMPTS
// ---------------------------
function clean_old_attempts($days = 30) {
    global $mysqli;
    $stmt = db_prepare("DELETE FROM login_attempts WHERE created_at < (NOW() - INTERVAL ? DAY)");
    if ($stmt) {
        $stmt->bind_param("i", $days);
        $stmt->execute();
        $stmt->close();
    }
}

// ---------------------------
// 🔐 RATE LIMIT (GLOBAL API / Actions)
// ---------------------------
function check_rate_limit($key, $limit = 10, $seconds = 60) {
    global $mysqli;

    $stmt = db_prepare("
        INSERT INTO rate_limits (`key`, `requests`, `last_request`) 
        VALUES (?, 1, NOW())
        ON DUPLICATE KEY UPDATE 
            requests = IF(TIMESTAMPDIFF(SECOND, last_request, NOW()) > ?, 1, requests + 1),
            last_request = NOW()
    ");
    if ($stmt) {
        $stmt->bind_param("si", $key, $seconds);
        $stmt->execute();
        $stmt->close();
    }

    // Check count
    $stmt = db_prepare("SELECT requests FROM rate_limits WHERE `key`=?");
    if ($stmt) {
        $stmt->bind_param("s", $key);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($result && $result['requests'] > $limit) {
            http_response_code(429);
            exit(json_encode(["error" => "Rate limit exceeded"]));
        }
    }
}

function auto_ban_ip($conn){

    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

    // Count failed login attempts in last 15 mins
    $stmt = $conn->prepare("
        SELECT COUNT(*) as attempts 
        FROM login_attempts
        WHERE ip=? AND success=0
        AND created_at > NOW() - INTERVAL 15 MINUTE
    ");

    $stmt->bind_param("s", $ip);
    $stmt->execute();

    $result = $stmt->get_result()->fetch_assoc();

    if($result['attempts'] >= 10){

        // Insert into banned_ips
        $stmt = $conn->prepare("
            INSERT IGNORE INTO banned_ips (ip, reason, banned_at)
            VALUES (?, 'Too many failed logins', NOW())
        ");

        $stmt->bind_param("s", $ip);
        $stmt->execute();
    }
}

function log_attack($conn, $type){

    $ip = $_SERVER['REMOTE_ADDR'];

    $stmt = $conn->prepare("
        INSERT INTO logs (action, ip_address, created_at)
        VALUES (?, ?, NOW())
    ");

    $stmt->bind_param("ss", $type, $ip);
    $stmt->execute();
}