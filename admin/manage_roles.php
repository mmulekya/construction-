<?php
require_once "../includes/config.php";
require_once "../includes/database.php";
require_once "../includes/security.php";

if(!has_permission($conn,$_SESSION['user_id'],'add_knowledge')) exit("Access denied");

// List users and roles
$stmt = $conn->prepare("
    SELECT u.id as user_id, u.username, r.role_name 
    FROM users u 
    LEFT JOIN user_roles ur ON u.id=ur.user_id
    LEFT JOIN roles r ON ur.role_id=r.id
");
$stmt->execute();
$res = $stmt->get_result();
?>
<table>
<tr><th>User</th><th>Role</th></tr>
<?php while($row=$res->fetch_assoc()): ?>
<tr>
<td><?=htmlspecialchars($row['username'])?></td>
<td><?=htmlspecialchars($row['role_name']??'None')?></td>
</tr>
<?php endwhile; ?>
</table>