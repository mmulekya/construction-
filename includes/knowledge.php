<?php

require_once "database.php";
require_once "gpt_ai.php"; // for embeddings

/* =========================
   CLEAN INPUT
========================= */
function clean_query($query){
    $query = trim($query);
    $query = strtolower($query);
    return substr($query, 0, 255);
}

/* =========================
   COSINE SIMILARITY
========================= */
function cosine_similarity($a, $b){

    if(count($a) !== count($b)) return 0;

    $dot = 0; 
    $magA = 0; 
    $magB = 0;

    for($i = 0; $i < count($a); $i++){
        $dot += $a[$i] * $b[$i];
        $magA += $a[$i] * $a[$i];
        $magB += $b[$i] * $b[$i];
    }

    if($magA == 0 || $magB == 0) return 0;

    return $dot / (sqrt($magA) * sqrt($magB));
}

/* =========================
   KEYWORD SEARCH (FAST)
========================= */
function keyword_search($conn, $query){

    $query = clean_query($query);
    if(empty($query)) return [];

    $like = "%{$query}%";

    $stmt = $conn->prepare("
        SELECT content 
        FROM knowledge_base 
        WHERE content LIKE ?
        ORDER BY id DESC
        LIMIT 5
    ");

    $stmt->bind_param("s", $like);
    $stmt->execute();

    $res = $stmt->get_result();

    $data = [];

    while($row = $res->fetch_assoc()){
        $data[] = [
            "content"=>$row['content'],
            "score"=>0.3 // base score
        ];
    }

    return $data;
}

/* =========================
   SEMANTIC PDF SEARCH
========================= */
function semantic_pdf_search($conn, $question){

    $question = clean_query($question);
    if(empty($question)) return [];

    // Generate embedding
    $question_embedding = get_embedding($question);

    if(empty($question_embedding)) return [];

    $stmt = $conn->prepare("
        SELECT content, embedding 
        FROM pdf_chunks
        LIMIT 200
    ");

    $stmt->execute();
    $res = $stmt->get_result();

    $scores = [];

    while($row = $res->fetch_assoc()){

        $embedding = json_decode($row['embedding'], true);
        if(!$embedding) continue;

        $semantic = cosine_similarity($question_embedding, $embedding);

        // keyword boost
        $keyword = stripos($row['content'], $question) !== false ? 1 : 0;

        $score = ($semantic * 0.7) + ($keyword * 0.3);

        $scores[] = [
            "content"=>$row['content'],
            "score"=>$score
        ];
    }

    // sort best first
    usort($scores, function($a, $b){
        return $b['score'] <=> $a['score'];
    });

    return array_slice($scores, 0, 5);
}

/* =========================
   MAIN KNOWLEDGE SYSTEM
========================= */
function get_knowledge($conn, $query){

    $query = clean_query($query);
    if(empty($query)) return null;

    $results = [];

    // 🔹 1. Keyword search (fast fallback)
    $keyword = keyword_search($conn, $query);
    if(!empty($keyword)){
        $results = array_merge($results, $keyword);
    }

    // 🔹 2. Semantic PDF search (smart)
    $semantic = semantic_pdf_search($conn, $query);
    if(!empty($semantic)){
        $results = array_merge($results, $semantic);
    }

    if(empty($results)) return null;

    // 🔹 Sort combined results
    usort($results, function($a, $b){
        return $b['score'] <=> $a['score'];
    });

    // 🔹 Build final context (limit size)
    $final = "";
    $max_chars = 1500;

    foreach($results as $item){

        $text = substr($item['content'], 0, 300);

        if(strlen($final) + strlen($text) > $max_chars){
            break;
        }

        $final .= $text . "\n\n";
    }

    return $final ?: null;
}