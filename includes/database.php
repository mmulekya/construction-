<?php

require_once __DIR__ . "/config.php";

/* ==============================
   1. LOAD DATABASE CREDENTIALS
============================== */

$host = DB_HOST ?? 'localhost';
$user = DB_USER ?? '';
$pass = DB_PASS ?? '';
$db   = DB_NAME ?? '';

/* ==============================
   2. MYSQLI SECURITY SETTINGS
============================== */

// Disable direct error output
mysqli_report(MYSQLI_REPORT_OFF);

// Set connection timeout (important for InfinityFree)
ini_set('mysql.connect_timeout', 5);
ini_set('default_socket_timeout', 5);

/* ==============================
   3. CREATE CONNECTION
============================== */

$conn = @new mysqli($host, $user, $pass, $db);

/* ==============================
   4. HANDLE CONNECTION ERRORS
============================== */

if ($conn->connect_error) {

    error_log("DB Connection Error: " . $conn->connect_error);

    http_response_code(500);
    exit("System temporarily unavailable.");
}

/* ==============================
   5. SET SAFE CHARACTER SET
============================== */

if (!$conn->set_charset("utf8mb4")) {

    error_log("Charset Error: " . $conn->error);

    http_response_code(500);
    exit("System error.");
}

/* ==============================
   6. STRICT SQL MODE (HARDENED)
============================== */

$conn->query("
SET SESSION sql_mode = 
'STRICT_ALL_TABLES,
ERROR_FOR_DIVISION_BY_ZERO,
NO_ZERO_DATE,
NO_ZERO_IN_DATE,
ONLY_FULL_GROUP_BY'
");

/* ==============================
   7. SECURE QUERY FUNCTION
============================== */

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
        $stmt->close();
        return false;
    }

    return $stmt;
}

/* ==============================
   8. FETCH HELPERS
============================== */

function db_fetch_one($stmt) {
    if(!$stmt) return null;

    $result = $stmt->get_result();
    $data = $result ? $result->fetch_assoc() : null;

    $stmt->close();
    return $data;
}

function db_fetch_all($stmt) {
    if(!$stmt) return [];

    $result = $stmt->get_result();
    $data = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

    $stmt->close();
    return $data;
}

/* ==============================
   9. SAFE INSERT HELPER
============================== */

function db_execute($stmt){
    if(!$stmt) return false;

    $success = $stmt->affected_rows >= 0;
    $stmt->close();

    return $success;
}

?>