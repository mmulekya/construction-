<?php
require_once "../includes/config.php";
require_once "../includes/database.php";
require_once "../includes/security.php";

if(!has_permission($conn,$_SESSION['user_id'],'view_logs')) exit("Access denied");

$stmt = $conn->prepare("SELECT * FROM alerts ORDER BY created_at DESC LIMIT 100");
$stmt->execute();
$res = $stmt->get_result();
?>
<table>
<tr><th>ID</th><th>Type</th><th>Details</th><th>User</th><th>Admin</th><th>IP</th><th>Time</th></tr>
<?php while($row=$res->fetch_assoc()): ?>
<tr>
<td><?= $row['id'] ?></td>
<td><?= htmlspecialchars($row['alert_type']) ?></td>
<td><?= htmlspecialchars($row['alert_details']) ?></td>
<td><?= $row['user_id'] ?></td>
<td><?= $row['admin_id'] ?></td>
<td><?= $row['ip_address'] ?></td>
<td><?= $row['created_at'] ?></td>
</tr>
<?php endwhile; ?>
</table>