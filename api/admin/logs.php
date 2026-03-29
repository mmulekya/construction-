<?php
require_once "../../includes/config.php";
require_once "../../includes/database.php";
require_once "../../includes/security.php";
require_once "../../includes/rate_limit.php";

header("Content-Type: application/json");

if(session_status() === PHP_SESSION_NONE){
    session_start();
}

// 🔐 SECURITY
require_login();
require_admin();

// 🔐 Rate limit admin API
check_rate_limit($conn, "admin_logs_" . ($_SESSION['user_id'] ?? 0), 5, 60);


/* =========================
   1. CLEAN EXPIRED BANS
========================= */
$conn->query("
DELETE FROM banned_ips 
WHERE expires_at IS NOT NULL 
AND expires_at < NOW()
");


/* =========================
   2. DETECT ATTACKERS (ONLY FAILED LOGINS)
========================= */
$attackQuery = "
SELECT ip, COUNT(*) as attempts
FROM login_attempts
WHERE success = 0
AND created_at > NOW() - INTERVAL 15 MINUTE
GROUP BY ip
HAVING attempts >= 5
";

$result = $conn->query($attackQuery);

$attackers = [];

while($row = $result->fetch_assoc()){
    $attackers[] = $row;
}


/* =========================
   3. AUTO BAN SYSTEM (SAFE VERSION)
========================= */
foreach($attackers as $attacker){

    $ip = $attacker['ip'];

    // Check if already banned
    $check = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM banned_ips 
        WHERE ip_address=? AND expires_at > NOW()
    ");
    $check->bind_param("s", $ip);
    $check->execute();

    $exists = $check->get_result()->fetch_assoc()['count'];

    if($exists > 0) continue;

    // Count past offenses
    $history = $conn->prepare("
        SELECT COUNT(*) as offenses 
        FROM banned_ips 
        WHERE ip_address=?
    ");
    $history->bind_param("s", $ip);
    $history->execute();

    $offenses = $history->get_result()->fetch_assoc()['offenses'];

    // Incremental ban logic
    if($offenses == 0){
        $interval = "1 HOUR";
    } elseif($offenses == 1){
        $interval = "12 HOUR";
    } else {
        $interval = "24 HOUR";
    }

    // Insert ban
    $stmt = $conn->prepare("
        INSERT INTO banned_ips (ip_address, banned_at, expires_at, reason)
        VALUES (?, NOW(), DATE_ADD(NOW(), INTERVAL $interval), ?)
    ");

    $reason = "Auto-ban (offense #" . ($offenses + 1) . ")";
    $stmt->bind_param("ss", $ip, $reason);
    $stmt->execute();
}


/* =========================
   4. FETCH ACTIVE BANS
========================= */
$banned = $conn->query("
SELECT ip_address, reason, banned_at, expires_at
FROM banned_ips
ORDER BY expires_at ASC
");

$banned_ips = [];

while($row = $banned->fetch_assoc()){
    $banned_ips[] = $row;
}


/* =========================
   OUTPUT
========================= */
echo json_encode([
    "status" => "success",
    "attackers" => $attackers,
    "banned_ips" => $banned_ips
]);