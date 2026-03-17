<?php
require_once "../includes/config.php";
require_once "../includes/database.php";
require_once "../includes/security.php";

if(!has_permission($conn,$_SESSION['user_id'],'view_logs')) exit("Access denied");

$stmt = $conn->prepare("SELECT * FROM notifications WHERE admin_id=? ORDER BY created_at DESC LIMIT 50");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$res = $stmt->get_result();
?>
<table>
<tr><th>Type</th><th>Details</th><th>Time</th><th>Status</th></tr>
<?php while($row=$res->fetch_assoc()): ?>
<tr>
<td><?=htmlspecialchars($row['notification_type'])?></td>
<td><?=htmlspecialchars($row['notification_details'])?></td>
<td><?= $row['created_at'] ?></td>
<td><?= $row['is_read'] ? 'Read' : 'Unread' ?></td>
</tr>
<?php endwhile; ?>
</table>