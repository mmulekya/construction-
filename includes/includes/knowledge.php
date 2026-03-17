<?php

require_once "embedding.php";

/* SAFE COSINE SIMILARITY */
function cosine_similarity($a, $b){

$a = json_decode($a, true);
$b = json_decode($b, true);

if(!$a || !$b) return 0;

$dot = 0;
$normA = 0;
$normB = 0;

for($i=0; $i<count($a); $i++){
$dot += $a[$i] * $b[$i];
$normA += $a[$i] ** 2;
$normB += $b[$i] ** 2;
}

if($normA == 0 || $normB == 0) return 0;

return $dot / (sqrt($normA) * sqrt($normB));
}

/* MAIN FUNCTION */
function get_knowledge($conn, $question){

if(strlen($question) > 500){
return "";
}

/* Prevent abuse */
$question = substr($question, 0, 500);

$q_embedding = generate_embedding($question);

$stmt = $conn->prepare(
"SELECT content, embedding FROM knowledge_base LIMIT 100"
);

$stmt->execute();
$result = $stmt->get_result();

$best = [];

while($row = $result->fetch_assoc()){

if(empty($row['embedding'])) continue;

$score = cosine_similarity($q_embedding, $row['embedding']);

if($score > 0.6){ // FILTER LOW QUALITY
$best[] = [
"text"=>$row['content'],
"score"=>$score
];
}

}

/* SORT */
usort($best, fn($a,$b) => $b['score'] <=> $a['score']);

/* LIMIT OUTPUT */
$top = array_slice($best, 0, 3);

$output = "";

foreach($top as $item){
$output .= $item['text']."\n\n";
}

return $output;
}