<?php

function calculate_engineering($question){

    $q = strtolower($question);

    // 🔹 Concrete calculation (slab)
    if(preg_match('/(\d+)\s*x\s*(\d+)/', $q, $matches)){
        $length = floatval($matches[1]);
        $width  = floatval($matches[2]);
        $thickness = 0.15; // default 150mm slab

        $volume = $length * $width * $thickness;

        return "Concrete required: " . round($volume,2) . " cubic meters (assuming 150mm thickness)";
    }

    // 🔹 Cement for 1m³ (1:2:4 mix)
    if(strpos($q, "cement") !== false && strpos($q, "1 cubic") !== false){
        return "For 1m³ concrete (1:2:4 mix): Cement ≈ 6 bags (50kg each)";
    }

    // 🔹 Bricks calculation
    if(strpos($q, "bricks") !== false){
        return "Approx: 500 bricks per cubic meter of wall";
    }

    return null;
}