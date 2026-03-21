<?php

function calculate_engineering($question){

    $q = strtolower(trim($question));

    /* =========================
       🔹 SLAB VOLUME (L x W x thickness)
       Supports: 10x12, 10 x 12, 10.5 x 12.3
    ========================== */
    if(preg_match('/(\d+(\.\d+)?)\s*x\s*(\d+(\.\d+)?)/', $q, $matches)){

        $length = floatval($matches[1]);
        $width  = floatval($matches[3]);

        // Default thickness (meters)
        $thickness = 0.15; // 150mm

        // Allow custom thickness like "200mm"
        if(preg_match('/(\d+)\s*mm/', $q, $t)){
            $thickness = floatval($t[1]) / 1000;
        }

        $volume = $length * $width * $thickness;

        return "Concrete required: " . round($volume, 2) . " m³ (thickness: " . ($thickness*1000) . "mm)";
    }

    /* =========================
       🔹 CEMENT PER CUBIC METER
    ========================== */
    if(strpos($q, "cement") !== false && strpos($q, "1 cubic") !== false){
        return "For 1m³ (1:2:4 mix): Cement ≈ 6–7 bags (50kg each)";
    }

    /* =========================
       🔹 BRICKS ESTIMATION
    ========================== */
    if(strpos($q, "bricks") !== false){
        return "Approximation: 450–500 bricks per cubic meter depending on mortar joints.";
    }

    /* =========================
       🔹 CONCRETE COST (KENYA)
    ========================== */
    if(strpos($q, "cost") !== false && preg_match('/(\d+(\.\d+)?)\s*x\s*(\d+(\.\d+)?)/', $q, $m)){

        $length = floatval($m[1]);
        $width  = floatval($m[3]);
        $thickness = 0.15;

        if(preg_match('/(\d+)\s*mm/', $q, $t)){
            $thickness = floatval($t[1]) / 1000;
        }

        $volume = $length * $width * $thickness;

        // Kenya estimated price (editable)
        $cost_per_m3 = 12000;

        $total = $volume * $cost_per_m3;

        return "Estimated concrete cost: KES " . number_format($total) . 
               " (KES {$cost_per_m3}/m³, volume: " . round($volume,2) . " m³)";
    }

    /* =========================
       🔹 BUILDING COST (KENYA)
    ========================== */
    if(strpos($q, "building cost") !== false){
        return "Average building cost in Kenya:\n- Basic: KES 25,000/m²\n- Standard: KES 35,000–45,000/m²\n- High-end: KES 50,000–70,000/m²";
    }

    /* =========================
       🔹 STEEL ESTIMATION (BASIC)
    ========================== */
    if(strpos($q, "steel") !== false){
        return "Approx steel requirement:\n- Slab: 80–120 kg per m³\n- Beam: 120–180 kg per m³\n- Column: 150–250 kg per m³";
    }

    /* =========================
       🔹 SAND & BALLAST ESTIMATE
    ========================== */
    if(strpos($q, "sand") !== false || strpos($q, "ballast") !== false){
        return "For 1m³ concrete (1:2:4 mix):\n- Sand ≈ 0.5 m³\n- Ballast ≈ 1 m³";
    }

    /* =========================
       🔹 FALLBACK
    ========================== */
    return null;
}