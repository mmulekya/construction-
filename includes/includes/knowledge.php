<?php

function cosine_similarity($a, $b){

$a = json_decode($a, true);
$b = json_decode($b, true);

$dot = 0;
$normA = 0;
$normB = 0;

for($i=0; $i<count($a); $i++){
$dot += $a[$i] * $b[$i];
$normA += $a[$i] * $a[$i];
$normB += $b[$i] * $b[$i];
}

return $dot / (sqrt($normA) * sqrt($normB));
}

function get_knowledge($conn, $question){

require_once "embedding.php";

$q_embedding = generate_embedding($question);

$result = $conn->query("SELECT content, embedding FROM knowledge_base");

$best_matches = [];

while($row = $result->fetch_assoc()){

if(empty($row['embedding'])) continue;

$score = cosine_similarity($q_embedding, $row['embedding']);

$best_matches[] = [
"content"=>$row['content'],
"score"=>$score
];
}

usort($best_matches, function($a,$b){
return $b['score'] <=> $a['score'];
});

$top = array_slice($best_matches, 0, 3);

$text = "";

foreach($top as $item){
$text .= $item['content']."\n\n";
}

return $text;
}