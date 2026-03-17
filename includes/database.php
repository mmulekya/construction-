<?php

require_once __DIR__ . "/config.php";

/* ==============================
   1. LOAD DATABASE CREDENTIALS
============================== */

$host = defined('DB_HOST') ? DB_HOST : 'localhost';
$user = defined('DB_USER') ? DB_USER : '';
$pass = defined('DB_PASS') ? DB_PASS : '';
$db   = defined('DB_NAME') ? DB_NAME : '';

/* ==============================
   2. CREATE CONNECTION (SECURE)
============================== */

mysqli_report(MYSQLI_REPORT_OFF);

$conn = new mysqli($host, $user, $pass, $db);

/* ==============================
   3. HANDLE CONNECTION ERRORS
============================== */

if ($conn->connect_error) {

    error_log("Database connection failed: " . $conn->connect_error);

    exit("System temporarily unavailable.");
}

/* ==============================
   4. SET SAFE CHARACTER SET
============================== */

if (!$conn->set_charset("utf8mb4")) {

    error_log("Error setting charset: " . $conn->error);
    exit("System error.");
}

/* ==============================
   5. OPTIONAL: STRICT SQL MODE
============================== */

$conn->query("SET sql_mode = 'STRICT_ALL_TABLES'");

/* ==============================
   6. HELPER FUNCTIONS (SAFE DB)
============================== */

/* Safe prepared query helper */
function db_query($conn, $query, $types = "", $params = []) {

    $stmt = $conn->prepare($query);

    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        return false;
    }

    if (!empty($types) && !empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    if (!$stmt->execute()) {
        error_log("Execute failed: " . $stmt->error);
        return false;
    }

    return $stmt;
}

/* Fetch single row */
function db_fetch_one($stmt) {
    $result = $stmt->get_result();
    return $result ? $result->fetch_assoc() : null;
}

/* Fetch all rows */
function db_fetch_all($stmt) {
    $result = $stmt->get_result();
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

?>