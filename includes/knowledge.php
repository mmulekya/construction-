<?php
require_once "security.php";

function search_knowledge($conn, $query){
    $query = "%" . strtolower($query) . "%";

    $stmt = $conn->prepare("SELECT content FROM knowledge_base WHERE content LIKE ? LIMIT 5");
    $stmt->bind_param("s", $query);
    $stmt->execute();

    $res = $stmt->get_result();

    $data = [];
    while($row = $res->fetch_assoc()){
        $data[] = $row['content'];
    }

    return $data;
}

function search_documents($conn, $query){
    $query = "%" . strtolower($query) . "%";

    $stmt = $conn->prepare("SELECT content FROM documents WHERE content LIKE ? LIMIT 3");
    $stmt->bind_param("s", $query);
    $stmt->execute();

    $res = $stmt->get_result();

    $data = [];
    while($row = $res->fetch_assoc()){
        $data[] = $row['content'];
    }

    return $data;
}

function get_knowledge($conn, $query){

    $results = [];

    // Search manual knowledge
    $kb = search_knowledge($conn, $query);
    if(!empty($kb)){
        $results = array_merge($results, $kb);
    }

    // Search PDFs
    $docs = search_documents($conn, $query);
    if(!empty($docs)){
        $results = array_merge($results, $docs);
    }

    if(!empty($results)){
        return implode("\n\n", $results);
    }

    return null;
}