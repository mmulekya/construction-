<?php

require_once "database.php";
require_once "gpt_ai.php";

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

    if(!$a || !$b || count($a) !== count($b)) return 0;

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
   EMBEDDING CACHE (IMPORTANT)
========================= */
function get_cached_embedding($conn, $text){

    $stmt = $conn->prepare("
        SELECT embedding FROM query_cache 
        WHERE question=? LIMIT 1
    ");
    $stmt->bind_param("s", $text);
    $stmt->execute();

    $res = $stmt->get_result()->fetch_assoc();

    if($res){
        return json_decode($res['embedding'], true);
    }

    $embedding = get_embedding($text);

    if(!empty($embedding)){
        $json = json_encode($embedding);

        $stmt = $conn->prepare("
            INSERT INTO query_cache (question, embedding, created_at)
            VALUES (?, ?, NOW())
        ");
        $stmt->bind_param("ss", $text, $json);
        $stmt->execute();
    }

    return $embedding;
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
            "score"=>0.4 // slightly higher weight
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

    // Cached embedding
    $question_embedding = get_cached_embedding($conn, $question);
    if(empty($question_embedding)) return [];

    $stmt = $conn->prepare("
        SELECT content, embedding 
        FROM pdf_chunks
        ORDER BY id DESC
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

        // weighted score
        $score = ($semantic * 0.75) + ($keyword * 0.25);

        if($score < 0.2) continue; // ignore weak matches

        $scores[] = [
            "content"=>$row['content'],
            "score"=>$score
        ];
    }

    usort($scores, fn($a,$b)=> $b['score'] <=> $a['score']);

    return array_slice($scores, 0, 5);
}

/* =========================
   MAIN KNOWLEDGE SYSTEM
========================= */
function get_knowledge($conn, $query){

    $query = clean_query($query);
    if(empty($query)) return null;

    $results = [];

    // 🔹 1. Keyword search
    $keyword = keyword_search($conn, $query);
    if(!empty($keyword)){
        $results = array_merge($results, $keyword);
    }

    // 🔹 2. Semantic PDF search
    $semantic = semantic_pdf_search($conn, $query);
    if(!empty($semantic)){
        $results = array_merge($results, $semantic);
    }

    if(empty($results)) return null;

    // Sort final results
    usort($results, fn($a,$b)=> $b['score'] <=> $a['score']);

    // Build final context
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