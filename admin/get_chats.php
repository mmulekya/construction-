<?php
require_once "../../includes/config.php";
require_once "../../includes/database.php";
require_once "../../includes/security.php";

session_start();
require_admin();

$result = $conn->query("
SELECT users.username, chat_history.question, chat_history.answer, chat_history.created_at
FROM chat_history
JOIN users ON users.id = chat_history.user_id
ORDER BY chat_history.id DESC
LIMIT 100
");

$data = [];
while($row = $result->fetch_assoc()){
    $data[] = $row;
}

echo json_encode($data);