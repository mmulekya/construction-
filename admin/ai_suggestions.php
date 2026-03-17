<?php
require_once "../includes/config.php";
require_once "../includes/database.php";
require_once "../includes/embedding.php";
require_once "../includes/security.php";

if(!has_permission($conn,$_SESSION['user_id'],'add_knowledge')) exit("Access denied");

// Fetch all knowledge base content
$kb_result = $conn->query("SELECT title, content FROM knowledge_base");
$kb_text = '';
while($row = $kb_result->fetch_assoc()){
    $kb_text .= $row['content'] . "\n";
}

// Prepare AI prompt
$prompt = "Analyze the following construction knowledge base and suggest missing topics or improvements for AI: \n\n";
$prompt .= $kb_text;

// Call OpenAI API for suggestions
$ch = curl_init("https://api.openai.com/v1/completions");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        "Content-Type: application/json",
        "Authorization: Bearer ".OPENAI_API_KEY
    ],
    CURLOPT_POSTFIELDS => json_encode([
        "model" => "text-davinci-003",
        "prompt" => $prompt,
        "max_tokens" => 500,
        "temperature" => 0.5
    ])
]);

$response = curl_exec($ch);
if(curl_errno($ch)) { error_log("AI Suggestion Error: ".curl_error($ch)); exit("Error generating suggestions"); }
curl_close($ch);

$result = json_decode($response,true);
$suggestions = $result['choices'][0]['text'] ?? 'No suggestions';

// Log AI suggestion
log_action($conn,"AI_Suggestion","Generated AI knowledge improvement suggestions", $_SESSION['user_id']);

// Display to admin
echo "<h3>AI Knowledge Improvement Suggestions</h3>";
echo "<pre>".htmlspecialchars($suggestions)."</pre>";