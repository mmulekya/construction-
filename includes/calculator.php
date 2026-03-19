<?php

function calculate_engineering($question){

    $q = strtolower($question);

    // 🔹 Concrete volume (slab)
    if(preg_match('/(\d+)\s*x\s*(\d+)/', $q, $matches)){
        $length = floatval($matches[1]);
        $width  = floatval($matches[2]);
        $thickness = 0.15;

        $volume = $length * $width * $thickness;

        return "Concrete required: " . round($volume,2) . " m³ (150mm slab)";
    }

    // 🔹 Cement for 1m³
    if(strpos($q, "cement") !== false && strpos($q, "1 cubic") !== false){
        return "For 1m³ (1:2:4 mix): Cement ≈ 6 bags";
    }

    // 🔹 Brick estimation
    if(strpos($q, "bricks") !== false){
        return "Approx: 500 bricks per cubic meter";
    }

    // 🔹 COST ESTIMATION (Concrete slab)
    if(strpos($q, "cost") !== false && preg_match('/(\d+)\s*x\s*(\d+)/', $q, $m)){
        $length = floatval($m[1]);
        $width  = floatval($m[2]);
        $thickness = 0.15;

        $volume = $length * $width * $thickness;

        // Approx Kenya prices (editable)
        $cost_per_m3 = 12000; // KES

        $total = $volume * $cost_per_m3;

        return "Estimated concrete cost: KES " . number_format($total) . 
               " (based on KES 12,000 per m³)";
    }

    // 🔹 Simple building estimate
    if(strpos($q, "building cost") !== false){
        return "Average building cost in Kenya: KES 25,000 – 60,000 per m² depending on quality.";
    }

    return null;
}