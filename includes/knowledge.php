<?php
function get_knowledge($conn,$query){
    $query = sanitize($query);
    // Simple keyword match (replace with vector search if needed)
    $stmt = $conn->prepare("SELECT content FROM knowledge_base WHERE content LIKE ? LIMIT 1");
    $like = "%$query%";
    $stmt->bind_param("s",$like);
    $stmt->execute();
    $stmt->bind_result($content);
    if($stmt->fetch()) return $content;
    return "Sorry, no matching knowledge found.";
}

function detect_topic($text){
    $text = strtolower($text);
    if(strpos($text,'cement')!==false) return 'cement';
    if(strpos($text,'concrete')!==false) return 'concrete';
    return 'general';
}