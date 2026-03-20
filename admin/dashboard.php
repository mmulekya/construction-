<?php
session_start();

require_once "../includes/config.php";
require_once "../includes/database.php";
require_once "../includes/security.php";

// 🔐 Ensure user is logged in
if(empty($_SESSION['user_id'])){
    http_response_code(401);
    exit("Unauthorized");
}

// 🔐 Permission check
if(!has_permission($conn, $_SESSION['user_id'], 'view_logs')){
    http_response_code(403);
    exit("Access denied");
}

// AI Questions per day
$ai_stats = $conn->query("
    SELECT DATE(created_at) AS day, COUNT(*) AS total_questions
    FROM logs 
    WHERE action_type='AI_Question' 
    GROUP BY DATE(created_at) 
    ORDER BY day DESC
");

// Calculator usage
$calc_stats = $conn->query("
    SELECT action_details, COUNT(*) AS usage_count
    FROM logs 
    WHERE action_type='Calculator_Use' 
    GROUP BY action_details 
    ORDER BY usage_count DESC 
    LIMIT 10
");

// Knowledge base stats
$knowledge_stats = $conn->query("
    SELECT source_type, COUNT(*) AS total 
    FROM knowledge_base 
    GROUP BY source_type
");
?>

<!DOCTYPE html>
<html>
<head>
    <title>BuildSmart Admin Dashboard</title>
    <style>
        table { border-collapse: collapse; width: 60%; margin-bottom: 20px; }
        th, td { border: 1px solid #ccc; padding: 8px; }
        th { background: #f4f4f4; }
    </style>
</head>
<body>

<h2>🛠️ BuildSmart Analytics Dashboard</h2>

<h3>AI Questions (Per Day)</h3>
<table>
<tr><th>Date</th><th>Total Questions</th></tr>
<?php while($row = $ai_stats->fetch_assoc()): ?>
<tr>
    <td><?= htmlspecialchars($row['day']) ?></td>
    <td><?= (int)$row['total_questions'] ?></td>
</tr>
<?php endwhile; ?>
</table>

<h3>Top 10 Calculator Usage</h3>
<table>
<tr><th>Calculator</th><th>Usage Count</th></tr>
<?php while($row = $calc_stats->fetch_assoc()): ?>
<tr>
    <td><?= htmlspecialchars($row['action_details']) ?></td>
    <td><?= (int)$row['usage_count'] ?></td>
</tr>
<?php endwhile; ?>
</table>

<h3>Knowledge Base Additions</h3>
<table>
<tr><th>Source</th><th>Total Entries</th></tr>
<?php while($row = $knowledge_stats->fetch_assoc()): ?>
<tr>
    <td><?= htmlspecialchars($row['source_type']) ?></td>
    <td><?= (int)$row['total'] ?></td>
</tr>
<?php endwhile; ?>
</table>

</body>
</html>