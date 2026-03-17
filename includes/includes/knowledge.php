<?php

function get_knowledge($conn, $question){

$keywords = explode(" ", $question);

$results = [];

foreach($keywords as $word){

$stmt = $conn->prepare(
"SELECT content FROM knowledge_base
WHERE content LIKE CONCAT('%', ?, '%')
LIMIT 2"
);

$stmt->bind_param("s",$word);
$stmt->execute();

$res = $stmt->get_result();

while($row = $res->fetch_assoc()){
$results[] = $row['content'];
}

}

return implode("\n", array_unique($results));
}