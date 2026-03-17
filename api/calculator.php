<?php

require_once "../includes/database.php";
require_once "../includes/config.php";

if(!isset($_SESSION['user_id'])){
    exit("Unauthorized");
}

if(!verify_csrf($_POST['token'])){
    exit("Invalid token");
}

$calc_id = intval($_POST['calculator_id']);
$params = $_POST['params'] ?? [];

/* Fetch calculator securely */
$stmt = $conn->prepare("SELECT name, formula FROM calculators WHERE id=? LIMIT 1");
$stmt->bind_param("i", $calc_id);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows === 0){
    exit("Calculator not found");
}

$calc = $result->fetch_assoc();

/* Evaluate formula safely */
$result_value = null;

switch($calc['name']){
    case 'Concrete Volume':
        $length = floatval($params['length'] ?? 0);
        $width = floatval($params['width'] ?? 0);
        $height = floatval($params['height'] ?? 0);
        $result_value = $length * $width * $height;
        break;

    case 'Brick Quantity':
        $wall_area = floatval($params['wall_area'] ?? 0);
        $brick_area = floatval($params['brick_area'] ?? 0);
        $result_value = $brick_area > 0 ? $wall_area / $brick_area : 0;
        break;

    case 'Cement Bags':
        $cement_volume = floatval($params['cement_volume'] ?? 0);
        $bag_volume = floatval($params['bag_volume'] ?? 0);
        $result_value = $bag_volume > 0 ? $cement_volume / $bag_volume : 0;
        break;

    case 'Steel Reinforcement':
        $length = floatval($params['length'] ?? 0);
        $steel_area = floatval($params['steel_area'] ?? 0);
        $steel_density = floatval($params['steel_density'] ?? 0);
        $result_value = $length * $steel_area * $steel_density;
        break;

    case 'Slab Thickness Check':
        $span = floatval($params['span'] ?? 0);
        $result_value = $span / 20;
        break;

    case 'Footing Size':
        $load = floatval($params['load'] ?? 0);
        $soil_capacity = floatval($params['soil_capacity'] ?? 1);
        $result_value = sqrt($load / $soil_capacity);
        break;

    default:
        $result_value = 0;
}

echo json_encode([
    "calculator"=>$calc['name'],
    "result"=>$result_value
]);