<?php
require_once 'config.php';

function generate_embedding($text){
    $text = substr($text,0,2000); // limit size
    $data = [
        'model'=>'text-embedding-3-small',
        'input'=>$text
    ];
    $ch = curl_init('https://api.openai.com/v1/embeddings');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "Authorization: Bearer $GLOBALS[OPENAI_KEY]"
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    $res = curl_exec($ch);
    curl_close($ch);
    $res_arr = json_decode($res,true);
    return $res_arr['data'][0]['embedding'] ?? [];
}