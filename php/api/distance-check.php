<?php
/**
 * Distance Check API Endpoint
 * Proxy for Google Distance Matrix API to avoid CORS issues
 * Restricts bookings to locations within 50km of Mumbai and Pune
 */

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

/**
 * Send error response
 */
function sendError($message, $code = 400) {
    http_response_code($code);
    echo json_encode(['error' => $message, 'success' => false]);
    exit();
}

/**
 * Send success response
 */
function sendSuccess($data) {
    echo json_encode(array_merge($data, ['success' => true]));
    exit();
}

// Load required files
require_once __DIR__ . '/../DatabaseUtils.php';
require_once __DIR__ . '/../Logger.php';

// Load configuration
$config = include __DIR__ . '/../config.php';

// Configuration
$GOOGLE_API_KEY = 'AIzaSyA-2N9fbAPu2cWVLNGYu0qWL8Gs1Xu3QTw';
$MAX_DISTANCE_KM = 50;

// Initialize logger
try {
    $logger = Logger::getInstance();
} catch (Exception $e) {
    error_log("Logger initialization failed: " . $e->getMessage());
    sendError('Internal system error', 500);
}

// Get input parameters
$method = $_SERVER['REQUEST_METHOD'];

try {
    // Validate database config
    if (!isset($config['database']['host']) || !isset($config['database']['dbname']) || !isset($config['database']['username'])) {
        throw new Exception("Database configuration is incomplete");
    }
    
    // Build DSN with explicit charset
    $dsn = "mysql:host={$config['database']['host']};dbname={$config['database']['dbname']};charset=utf8mb4";
    
    // Use DatabaseUtils for consistent connection handling
    $db = DatabaseUtils::createConnection(
        $dsn,
        $config['database']['username'],
        $config['database']['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT => 5
        ]
    );

    // Get or create allowed cities
    $db->beginTransaction();
    
    $tableCheck = $db->query("SHOW TABLES LIKE 'allowed_cities'");
    if ($tableCheck->rowCount() === 0) {
        $db->exec("CREATE TABLE IF NOT EXISTS allowed_cities (
            id INT AUTO_INCREMENT PRIMARY KEY,
            city_name VARCHAR(255) NOT NULL,
            coordinates VARCHAR(50) NOT NULL,
            is_allowed TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        
        $db->exec("INSERT INTO allowed_cities (city_name, coordinates) VALUES 
            ('Mumbai', '19.0760,72.8777'),
            ('Pune', '18.5204,73.8567')
        ");
    }

    $db->commit();
    
    // Get allowed cities
    $stmt = $db->query("SELECT city_name, coordinates FROM allowed_cities WHERE is_allowed = 1");
    $ALLOWED_CITIES = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($ALLOWED_CITIES)) {
        throw new Exception('No serviceable cities configured');
    }

    if ($method === 'GET') {
        $pincode = $_GET['pincode'] ?? '';
        
        if (!$pincode || !preg_match('/^\d{6}$/', $pincode)) {
            sendError('Valid 6-digit pincode is required');
        }
        
        // Geocode the pincode
        $url = 'https://maps.googleapis.com/maps/api/geocode/json?' . http_build_query([
            'address' => $pincode . ',India',
            'key' => $GOOGLE_API_KEY
        ]);
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false
        ]);
        
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception('Geocoding error: ' . $error);
        }
        
        $geocodeData = json_decode($response, true);
        if (!$geocodeData || $geocodeData['status'] !== 'OK' || empty($geocodeData['results'])) {
            throw new Exception('Unable to find location for the provided pincode');
        }
        
        $location = $geocodeData['results'][0]['geometry']['location'];
        $coordinates = $location['lat'] . ',' . $location['lng'];
        
        // Extract city and state
        $addressComponents = $geocodeData['results'][0]['address_components'];
        $city = 'Unknown City';
        $state = 'Unknown State';
        
        foreach ($addressComponents as $component) {
            if (in_array('administrative_area_level_1', $component['types'])) {
                $state = $component['long_name'];
            }
            if (in_array('administrative_area_level_2', $component['types']) && $city === 'Unknown City') {
                $city = $component['long_name'];
            }
            if (in_array('locality', $component['types']) && $city === 'Unknown City') {
                $city = $component['long_name'];
            }
        }
        
    } else {
        sendError('Only GET method is supported', 405);
    }
    
    // Calculate distances to all allowed cities
    $distances = [];
    $minDistance = PHP_INT_MAX;
    $nearestCity = '';
    
    foreach ($ALLOWED_CITIES as $allowedCity) {
        $url = 'https://maps.googleapis.com/maps/api/distancematrix/json?' . http_build_query([
            'origins' => $coordinates,
            'destinations' => $allowedCity['coordinates'],
            'mode' => 'driving',
            'units' => 'metric',
            'key' => $GOOGLE_API_KEY
        ]);
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false
        ]);
        
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            error_log("Distance API error for {$allowedCity['city_name']}: $error");
            continue;
        }
        
        $distanceData = json_decode($response, true);
        if (!$distanceData || $distanceData['status'] !== 'OK' || empty($distanceData['rows'][0]['elements'][0])) {
            error_log("Invalid distance response for {$allowedCity['city_name']}: " . json_encode($distanceData));
            continue;
        }
        
        $element = $distanceData['rows'][0]['elements'][0];
        if ($element['status'] !== 'OK') {
            error_log("Distance calculation failed for {$allowedCity['city_name']}: {$element['status']}");
            continue;
        }
        
        $distanceKm = round($element['distance']['value'] / 1000);
        $distances[] = [
            'city' => $allowedCity['city_name'],
            'distanceKm' => $distanceKm,
            'duration' => $element['duration']['text']
        ];
        
        if ($distanceKm < $minDistance) {
            $minDistance = $distanceKm;
            $nearestCity = $allowedCity['city_name'];
        }
    }
    
    if (empty($distances)) {
        throw new Exception('Unable to calculate distance to service areas');
    }
    
    sendSuccess([
        'isAllowed' => $minDistance <= $MAX_DISTANCE_KM,
        'minDistance' => $minDistance,
        'nearestCity' => $nearestCity,
        'city' => $city,
        'state' => $state,
        'coordinates' => $coordinates,
        'distances' => $distances
    ]);

} catch (Exception $e) {
    error_log('[DISTANCE_CHECK] Error: ' . $e->getMessage());
    sendError($e->getMessage(), 500);
}