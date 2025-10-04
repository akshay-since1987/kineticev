<?php
header('Content-Type: application/json');
require_once('../config.php');
require_once('../DatabaseHandler.php');

try {
    $db = new DatabaseHandler();
    $pdo = $db->getConnection();

    // Query to get all allowed cities
    $stmt = $pdo->prepare("
        SELECT 
            id,
            city_name,
            coordinates,
            is_allowed,
            created_at,
            updated_at
        FROM allowed_cities 
        WHERE is_allowed = true 
        ORDER BY city_name ASC
    ");
    
    $stmt->execute();
    $cities = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'cities' => $cities,
        'count' => count($cities)
    ]);

} catch (PDOException $e) {
    error_log("Database Error in get-allowed-cities.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Unable to fetch cities',
        'debug' => $config['DEBUG'] ? $e->getMessage() : null
    ]);
}