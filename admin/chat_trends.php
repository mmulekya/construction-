<?php
require_once "../includes/config.php";
require_once "../includes/database.php";
require_once "../includes/security.php";

if(!has_permission($conn,$_SESSION['user_id'],'view_logs')) exit("Access denied");

// Trending topics
$topics = $conn->query("
    SELECT topic, COUNT(*) AS frequency
    FROM chat_analysis
    GROUP BY topic
    ORDER BY frequency DESC
    LIMIT 20
");

// Low confidence responses
$low_conf = $conn->query("
    SELECT question, ai_response, confidence_score
    FROM chat_analysis
    WHERE confidence_score < 0.6
    ORDER BY created_at DESC
    LIMIT 50
");
?>
<h2>BuildSmart AI Chat Trends</h2>

<h3>Top 20 Trending Topics</h3>
<table>
<tr><th>Topic</th><th>Frequency</th></tr>
<?php while($row=$topics->fetch_assoc()): ?>
<tr><td><?= htmlspecialchars($row['topic']) ?></td><td><?= $row['frequency'] ?></td></tr>
<?php endwhile; ?>
</table>

<h3>Low Confidence Responses</h3>
<table>
<tr><th>Question</th><th>AI Response</th><th>Confidence</th></tr>
<?php while($row=$low_conf->fetch_assoc()): ?>
<tr>
<td><?= htmlspecialchars($row['question']) ?></td>
<td><?= htmlspecialchars($row['ai_response']) ?></td>
<td><?= $row['confidence_score'] ?></td>
</tr>
<?php endwhile; ?>
</table>