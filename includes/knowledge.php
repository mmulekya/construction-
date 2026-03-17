<?php
require_once "embedding.php";

function cosine_similarity($a,$b){
    $a=json_decode($a,true); $b=json_decode($b,true);
    if(!$a||!$b) return 0;
    $dot=$normA=$normB=0;
    for($i=0;$i<count($a);$i++){ $dot+=$a[$i]*$b[$i]; $normA+=$a[$i]**2; $normB+=$b[$i]**2; }
    if($normA==0||$normB==0) return 0;
    return $dot/(sqrt($normA)*sqrt($normB));
}

function get_knowledge($conn,$question){
    if(strlen($question)>500) $question=substr($question,0,500);
    $q_embedding=generate_embedding($question);
    $stmt=$conn->prepare("SELECT content, embedding FROM knowledge_base LIMIT 100");
    $stmt->execute(); $res=$stmt->get_result();
    $best=[];
    while($row=$res->fetch_assoc()){
        if(empty($row['embedding'])) continue;
        $score=cosine_similarity($q_embedding,$row['embedding']);
        if($score>0.6) $best[]= ["text"=>$row['content'],"score"=>$score];
    }
    usort($best,fn($a,$b)=>$b['score']<=>$a['score']);
    $top=array_slice($best,0,3);
    $out=""; foreach($top as $t) $out.=$t['text']."\n\n";
    return $out;
}