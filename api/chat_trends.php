<?php
require_once "../includes/config.php";
require_once "../includes/security.php";

if(!has_permission($conn,$_SESSION['user_id'],'view_logs')) exit(json_response(["error"=>"Access denied"]));

// Top trending topics
$topics=$conn->query("SELECT topic,COUNT(*) AS frequency FROM chat_analysis GROUP BY topic ORDER BY frequency DESC LIMIT 20");

// Low-confidence responses
$low_conf=$conn->query("SELECT question,ai_response,confidence_score FROM chat_analysis WHERE confidence_score<0.6 ORDER BY created_at DESC LIMIT 50");

json_response([
    "trending_topics"=>$topics->fetch_all(MYSQLI_ASSOC),
    "low_confidence"=>$low_conf->fetch_all(MYSQLI_ASSOC)
]);