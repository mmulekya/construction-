<?php

require_once "database.php";

/* =========================
   CLEAN INPUT
========================= */
function clean_query($query){
    $query = trim($query);
    $query = strtolower($query);
    return substr($query, 0, 255); // prevent long input abuse
}

/* =========================
   SEARCH KNOWLEDGE BASE
========================= */
function search_knowledge($conn, $query){

    $query = clean_query($query);
    if(empty($query)) return [];

    $query = "%{$query}%";

    $stmt = $conn->prepare("
        SELECT content 
        FROM knowledge_base 
        WHERE content LIKE ?
        ORDER BY id DESC
        LIMIT 5
    ");

    $stmt->bind_param("s", $query);
    $stmt->execute();

    $res = $stmt->get_result();

    $data = [];

    while($row = $res->fetch_assoc()){
        $data[] = $row['content'];
    }

    return $data;
}

/* =========================
   SEARCH DOCUMENTS (PDF)
========================= */
function search_documents($conn, $query){

    $query = clean_query($query);
    if(empty($query)) return [];

    $query = "%{$query}%";

    $stmt = $conn->prepare("
        SELECT content 
        FROM documents 
        WHERE content LIKE ?
        ORDER BY id DESC
        LIMIT 3
    ");

    $stmt->bind_param("s", $query);
    $stmt->execute();

    $res = $stmt->get_result();

    $data = [];

    while($row = $res->fetch_assoc()){
        $data[] = $row['content'];
    }

    return $data;
}

/* =========================
   MAIN KNOWLEDGE FETCH
========================= */
function get_knowledge($conn, $query){

    $query = clean_query($query);
    if(empty($query)) return null;

    $results = [];

    // 1. Knowledge base
    $kb = search_knowledge($conn, $query);
    if(!empty($kb)){
        $results = array_merge($results, $kb);
    }

    // 2. Documents (PDFs)
    $docs = search_documents($conn, $query);
    if(!empty($docs)){
        $results = array_merge($results, $docs);
    }

    // 3. Limit total response size (important for AI)
    $results = array_slice($results, 0, 8);

    if(!empty($results)){
        return implode("\n\n", $results);
    }

    return null;
}