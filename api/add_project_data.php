<?php
require_once "../includes/config.php";
require_once "../includes/database.php";
require_once "../includes/security.php";
require_once "../includes/rate_limit.php";

header("Content-Type: application/json");

if(session_status() === PHP_SESSION_NONE){
    session_start();
}

/* =========================
   🔐 AUTH (SESSION OR JWT)
========================= */
$user_id = get_authenticated_user();

if(!$user_id){
    exit(json_encode(["error"=>"Unauthorized"]));
}

/* =========================
   🔐 RATE LIMIT
========================= */
check_rate_limit($conn, "add_project_data_" . $user_id, 10, 60);

/* =========================
   🔐 CSRF
========================= */
$csrf = $_POST['csrf_token'] ?? '';

if(!verify_csrf_token($csrf)){
    exit(json_encode(["error"=>"Invalid CSRF token"]));
}

/* =========================
   🧾 INPUT VALIDATION
========================= */
$project_id = intval($_POST['project_id'] ?? 0);
$data = trim($_POST['data'] ?? '');

if($project_id <= 0 || strlen($data) < 3){
    exit(json_encode(["error"=>"Invalid project or data"]));
}

if(strlen($data) > 1000){
    exit(json_encode(["error"=>"Data too long"]));
}

/* =========================
   🔐 OWNERSHIP CHECK
========================= */
$stmt = db_prepare("SELECT id FROM projects WHERE id=? AND user_id=? LIMIT 1");
$stmt->bind_param("ii", $project_id, $user_id);
$stmt->execute();

if($stmt->get_result()->num_rows === 0){
    exit(json_encode(["error"=>"Unauthorized project access"]));
}

$stmt->close();

/* =========================
   💾 SAVE DATA
========================= */
$stmt = db_prepare("
    INSERT INTO project_data (project_id, data, created_at) 
    VALUES (?, ?, NOW())
");

$stmt->bind_param("is", $project_id, $data);
$stmt->execute();
$stmt->close();

/* =========================
   🧠 SUCCESS
========================= */
echo json_encode([
    "success"=>true,
    "message"=>"Project data added"
]);