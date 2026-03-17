<?php

require_once "../includes/database.php";
require_once "../includes/config.php";
require_once "../includes/embedding.php";

$result = $conn->query("SELECT id, content FROM knowledge_base WHERE embedding IS NULL");

while($row = $result->fetch_assoc()){

$embedding = generate_embedding($row['content']);

$stmt = $conn->prepare(
"UPDATE knowledge_base SET embedding=? WHERE id=?"
);

$stmt->bind_param("si",$embedding,$row['id']);
$stmt->execute();

echo "Processed ID: ".$row['id']."\n";

sleep(1); // prevent API overload
}