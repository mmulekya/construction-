<?php

require_once "config.php";

/**
 * Call OpenAI GPT API
 */
function call_gpt($prompt){

    // Load API key from .env or config
    $apiKey = defined('OPENAI_API_KEY') ? OPENAI_API_KEY : null;

    if(empty($apiKey)){
        return "AI configuration error (API key missing)";
    }

    // Validate input
    $prompt = trim($prompt);
    if(empty($prompt)){
        return "Empty prompt";
    }

    // Request payload (UPDATED MODEL)
    $data = [
        "model" => "gpt-4o-mini", // cheaper + faster + modern
        "messages" => [
            [
                "role" => "system",
                "content" => "You are BuildSmart AI, an expert in construction, civil engineering, architecture, materials, and project management. Give clear, practical answers."
            ],
            [
                "role" => "user",
                "content" => $prompt
            ]
        ],
        "temperature" => 0.3,
        "max_tokens" => 500
    ];

    // Initialize CURL
    $ch = curl_init("https://api.openai.com/v1/chat/completions");

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => [
            "Content-Type: application/json",
            "Authorization: Bearer " . $apiKey
        ],
        CURLOPT_TIMEOUT => 20
    ]);

    $response = curl_exec($ch);

    // Handle CURL errors
    if(curl_errno($ch)){
        curl_close($ch);
        return "AI connection error";
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Handle API error responses
    if($httpCode !== 200){
        return "AI service unavailable";
    }

    $res = json_decode($response, true);

    // Validate response structure
    if(!isset($res['choices'][0]['message']['content'])){
        return "Invalid AI response";
    }

    return trim($res['choices'][0]['message']['content']);
}