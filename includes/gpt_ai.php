<?php

require_once "config.php";

/* =========================
   🔐 GET API KEY SAFELY
========================= */
function get_openai_key(){

    $key = getenv('OPENAI_API_KEY');

    if(!$key && defined('OPENAI_API_KEY')){
        $key = OPENAI_API_KEY;
    }

    return $key;
}

/* =========================
   🤖 GPT CHAT (FINAL SAFE)
========================= */
function call_gpt($prompt){

    $apiKey = get_openai_key();

    if(empty($apiKey)){
        return "AI configuration error";
    }

    $prompt = trim($prompt);

    if(strlen($prompt) < 3){
        return "Invalid request";
    }

    // Limit prompt size (important for cost + speed)
    $prompt = substr($prompt, 0, 2000);

    $data = [
        "model" => "gpt-4o-mini",
        "messages" => [
            [
                "role" => "system",
                "content" => "You are BuildSmart AI, an expert in construction, engineering, materials, and project management. Provide clear, practical, and accurate answers."
            ],
            [
                "role" => "user",
                "content" => $prompt
            ]
        ],
        "temperature" => 0.3,
        "max_tokens" => 300 // reduced for performance
    ];

    $ch = curl_init("https://api.openai.com/v1/chat/completions");

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => [
            "Content-Type: application/json",
            "Authorization: Bearer " . $apiKey
        ],
        CURLOPT_TIMEOUT => 10 // lower timeout for free hosting
    ]);

    $response = curl_exec($ch);

    if(curl_errno($ch)){
        curl_close($ch);
        return "AI connection error";
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if($httpCode !== 200){
        return "AI service unavailable";
    }

    $res = json_decode($response, true);

    if(!isset($res['choices'][0]['message']['content'])){
        return "AI returned invalid response";
    }

    return trim($res['choices'][0]['message']['content']);
}

/* =========================
   🧠 EMBEDDING (SAFE + FAST)
========================= */
function get_embedding($text){

    $apiKey = get_openai_key();

    if(empty($apiKey)){
        return [];
    }

    $text = trim($text);

    if(strlen($text) < 3){
        return [];
    }

    // Limit size (VERY IMPORTANT for cost)
    $text = substr($text, 0, 800);

    $data = [
        "input" => $text,
        "model" => "text-embedding-3-small"
    ];

    $ch = curl_init("https://api.openai.com/v1/embeddings");

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => [
            "Content-Type: application/json",
            "Authorization: Bearer " . $apiKey
        ],
        CURLOPT_TIMEOUT => 10
    ]);

    $response = curl_exec($ch);

    if(curl_errno($ch)){
        curl_close($ch);
        return [];
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if($httpCode !== 200){
        return [];
    }

    $res = json_decode($response, true);

    if(!isset($res['data'][0]['embedding'])){
        return [];
    }

    return $res['data'][0]['embedding'];
}

/* =========================
   ⚡ EMBEDDING CACHE (OPTIONAL)
========================= */
function get_cached_embedding($conn, $text){

    $text = trim($text);

    if(strlen($text) < 3){
        return [];
    }

    // Check cache
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

    // Generate new embedding
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