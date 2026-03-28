<?php
// ==========================================
// 🔒 DATABASE CONNECTION (SECURE)
// ==========================================

require_once __DIR__ . '/config.php';

// Use mysqli with utf8mb4 charset
$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Connection check
if ($mysqli->connect_error) {
    http_response_code(500);
    error_log("DB Connection Failed: " . $mysqli->connect_error);
    die("Database connection failed");
}

// Set charset to utf8mb4 (supports emojis & prevents XSS via DB)
$mysqli->set_charset("utf8mb4");

// ---------------------------
// 🔐 PREPARED STATEMENT HELPER
// ---------------------------
function db_prepare($query) {
    global $mysqli;
    $stmt = $mysqli->prepare($query);
    if (!$stmt) {
        error_log("DB Prepare Error: " . $mysqli->error);
        return false;
    }
    return $stmt;
}

// ---------------------------
// 🔐 ESCAPE FUNCTION
// ---------------------------
function db_escape($value) {
    global $mysqli;
    return $mysqli->real_escape_string($value);
}

// ---------------------------
// 🔒 SAFE QUERY EXECUTION
// ---------------------------
function db_query($query, $types = null, $params = []) {
    global $mysqli;

    $stmt = $mysqli->prepare($query);
    if (!$stmt) {
        error_log("DB Prepare Error: " . $mysqli->error);
        return false;
    }

    if ($types && $params) {
        $stmt->bind_param($types, ...$params);
    }

    if (!$stmt->execute()) {
        error_log("DB Execute Error: " . $stmt->error);
        return false;
    }

    $result = $stmt->get_result();
    $stmt->close();

    return $result;
}