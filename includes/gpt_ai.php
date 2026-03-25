<?php

require_once "config.php";

/**
 * 🔐 Get API Key safely
 */
function get_openai_key(){

    // Try ENV first
    $key = getenv('OPENAI_API_KEY');

    if(!$key && defined('OPENAI_API_KEY')){
        $key = OPENAI_API_KEY;
    }

    return $key;
}

/**
 * 🤖 CALL GPT (CHAT)
 */
function call_gpt($prompt){

    $apiKey = get_openai_key();

    if(empty($apiKey)){
        return "AI configuration error (API key missing)";
    }

    $prompt = trim($prompt);

    if(empty($prompt)){
        return "Empty prompt";
    }

    $data = [
        "model" => "gpt-4o-mini",
        "messages" => [
            [
                "role" => "system",
                "content" => "You are BuildSmart AI, an expert in construction, engineering, materials, and project management. Give clear and practical answers."
            ],
            [
                "role" => "user",
                "content" => $prompt
            ]
        ],
        "temperature" => 0.3,
        "max_tokens" => 500
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
        CURLOPT_TIMEOUT => 15 // safer for free hosting
    ]);

    $response = curl_exec($ch);

    // CURL error
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
        return "Invalid AI response";
    }

    return trim($res['choices'][0]['message']['content']);
}

/**
 * 🧠 GET EMBEDDING (FOR SEMANTIC SEARCH)
 */
function get_embedding($text){

    $apiKey = get_openai_key();

    if(empty($apiKey)){
        return [];
    }

    $text = trim($text);

    if(empty($text)){
        return [];
    }

    $data = [
        "input" => substr($text, 0, 1000), // limit to reduce cost
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
        CURLOPT_TIMEOUT => 15
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