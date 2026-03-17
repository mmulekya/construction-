<?php

function generate_embedding($text){

$payload = [
"input" => $text,
"model" => "text-embedding-3-small"
];

$ch = curl_init("https://api.openai.com/v1/embeddings");

curl_setopt_array($ch, [
CURLOPT_RETURNTRANSFER => true,
CURLOPT_POST => true,
CURLOPT_HTTPHEADER => [
"Content-Type: application/json",
"Authorization: Bearer ".OPENAI_API_KEY
],
CURLOPT_POSTFIELDS => json_encode($payload)
]);

$response = curl_exec($ch);

if(curl_errno($ch)){
error_log("Embedding error: ".curl_error($ch));
return null;
}

curl_close($ch);

$result = json_decode($response,true);

return json_encode($result['data'][0]['embedding']);
}