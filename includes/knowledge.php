<?php
require_once "security.php";

function get_knowledge($conn, $query){
    $query = strtolower(sanitize($query));

    // Break into keywords
    $keywords = explode(" ", $query);
    $search = "%" . implode("%", $keywords) . "%";

    $stmt = $conn->prepare("SELECT content FROM knowledge_base WHERE content LIKE ? LIMIT 5");
    $stmt->bind_param("s", $search);
    $stmt->execute();

    $result = $stmt->get_result();

    $answers = [];
    while($row = $result->fetch_assoc()){
        $answers[] = $row['content'];
    }

    if(count($answers) > 0){
        return implode("\n\n", $answers);
    }

    return null;
}