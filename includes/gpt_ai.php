<?php
function call_gpt($prompt){

    $apiKey = OPENAI_API_KEY;

    if(!$apiKey){
        return "API key missing";
    }

    $data = [
        "model" => "gpt-3.5-turbo",
        "messages" => [
            ["role"=>"system","content"=>"You are BuildSmart AI, expert in construction and engineering."],
            ["role"=>"user","content"=>$prompt]
        ],
        "temperature" => 0.3
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.openai.com/v1/chat/completions");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "Authorization: Bearer " . $apiKey
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

    $response = curl_exec($ch);

    if(curl_errno($ch)){
        return "Connection error";
    }

    curl_close($ch);

    $res = json_decode($response, true);

    return $res['choices'][0]['message']['content'] ?? "No response from AI";
}