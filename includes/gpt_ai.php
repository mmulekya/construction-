<?php
function call_gpt($prompt){
    $apiKey = OPENAI_API_KEY;

    if(empty($apiKey)){
        return "OpenAI API key missing";
    }

    $data = [
        "model" => "gpt-3.5-turbo",
        "messages" => [
            ["role"=>"system","content"=>"You are BuildSmart AI, an expert in construction and engineering."],
            ["role"=>"user","content"=>$prompt]
        ],
        "temperature" => 0.3
    ];

    $ch = curl_init("https://api.openai.com/v1/chat/completions");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "Authorization: Bearer ".$apiKey
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

    $res = curl_exec($ch);
    curl_close($ch);

    $resData = json_decode($res, true);

    if(isset($resData['choices'][0]['message']['content'])){
        return trim($resData['choices'][0]['message']['content']);
    }

    return "GPT API returned no answer.";
}