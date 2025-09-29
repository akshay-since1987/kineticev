<?php
/**
 * Distance Check API Endpoint
 * Proxy for Google Distance Matrix API to avoid CORS issues
 * Restricts bookings to locations within 50km of Mumbai and Pune
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Load configuration
$config = include __DIR__ . '/../config.php';

// Configuration
$GOOGLE_API_KEY = 'AIzaSyA-2N9fbAPu2cWVLNGYu0qWL8Gs1Xu3QTw';
$MAX_DISTANCE_KM = 50;
$ALLOWED_CITIES = [
    ['name' => 'Mumbai', 'coordinates' => '19.0760,72.8777'],
    ['name' => 'Pune', 'coordinates' => '18.5204,73.8567']
];

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

/**
 * Validate coordinates format
 */
function validateCoordinates($coords) {
    if (!$coords) return false;
    $parts = explode(',', $coords);
    if (count($parts) !== 2) return false;
    
    $lat = floatval(trim($parts[0]));
    $lng = floatval(trim($parts[1]));
    
    // Basic coordinate validation
    if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) return false;
    
    return true;
}

/**
 * Calculate distance using Google Distance Matrix API
 */
function calculateDistance($origins, $destinations, $apiKey) {
    $url = 'https://maps.googleapis.com/maps/api/distancematrix/json?' . http_build_query([
        'origins' => $origins,
        'destinations' => $destinations,
        'units' => 'metric',
        'mode' => 'driving',
        'key' => $apiKey
    ]);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        throw new Exception('cURL error: ' . $error);
    }
    
    if ($httpCode !== 200) {
        throw new Exception('HTTP error: ' . $httpCode);
    }
    
    $data = json_decode($response, true);
    if (!$data) {
        throw new Exception('Invalid JSON response from Google API');
    }
    
    return $data;
}

/**
 * Geocode pincode to get coordinates
 */
function geocodePincode($pincode, $apiKey) {
    $url = 'https://maps.googleapis.com/maps/api/geocode/json?' . http_build_query([
        'address' => $pincode . ',India',
        'key' => $apiKey
    ]);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        throw new Exception('Geocoding cURL error: ' . $error);
    }
    
    if ($httpCode !== 200) {
        throw new Exception('Geocoding HTTP error: ' . $httpCode);
    }
    
    $data = json_decode($response, true);
    if (!$data || $data['status'] !== 'OK' || empty($data['results'])) {
        throw new Exception('Unable to geocode pincode');
    }
    
    return $data;
}

// Main API logic
try {
    // Get input parameters
    $method = $_SERVER['REQUEST_METHOD'];
    
    if ($method === 'GET') {
        // GET request - validate by pincode
        $pincode = $_GET['pincode'] ?? '';
        
        if (!$pincode || !preg_match('/^\d{6}$/', $pincode)) {
            sendError('Valid 6-digit pincode is required');
        }
        
        // Geocode the pincode first
        $geocodeData = geocodePincode($pincode, $GOOGLE_API_KEY);
        $location = $geocodeData['results'][0]['geometry']['location'];
        $coordinates = $location['lat'] . ',' . $location['lng'];
        
        // Extract city and state from address components
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
        
    } elseif ($method === 'POST') {
        // POST request - validate by coordinates
        $input = json_decode(file_get_contents('php://input'), true);
        $coordinates = $input['coordinates'] ?? '';
        $city = $input['city'] ?? 'Unknown City';
        $state = $input['state'] ?? 'Unknown State';
        
        if (!validateCoordinates($coordinates)) {
            sendError('Valid coordinates (lat,lng) are required');
        }
        
    } else {
        sendError('Only GET and POST methods are supported', 405);
    }
    
    // Calculate distances to all allowed cities
    $distances = [];
    $minDistance = PHP_INT_MAX;
    $nearestCity = '';
    
    foreach ($ALLOWED_CITIES as $allowedCity) {
        try {
            $distanceData = calculateDistance($coordinates, $allowedCity['coordinates'], $GOOGLE_API_KEY);
            
            if ($distanceData['status'] === 'OK' && 
                !empty($distanceData['rows']) && 
                !empty($distanceData['rows'][0]['elements']) &&
                $distanceData['rows'][0]['elements'][0]['status'] === 'OK') {
                
                $element = $distanceData['rows'][0]['elements'][0];
                $distanceKm = round($element['distance']['value'] / 1000);
                
                $distances[] = [
                    'city' => $allowedCity['name'],
                    'distanceKm' => $distanceKm,
                    'duration' => $element['duration']['text'],
                    'distanceText' => $element['distance']['text']
                ];
                
                if ($distanceKm < $minDistance) {
                    $minDistance = $distanceKm;
                    $nearestCity = $allowedCity['name'];
                }
            }
        } catch (Exception $e) {
            // Log error but continue with other cities
            error_log('Distance calculation error for ' . $allowedCity['name'] . ': ' . $e->getMessage());
        }
    }
    
    if (empty($distances)) {
        sendError('Unable to calculate distances to any allowed cities');
    }
    
    // Determine if location is allowed
    $isAllowed = $minDistance <= $MAX_DISTANCE_KM;
    
    // Prepare response
    $response = [
        'isAllowed' => $isAllowed,
        'minDistance' => $minDistance,
        'nearestCity' => $nearestCity,
        'maxDistance' => $MAX_DISTANCE_KM,
        'city' => $city,
        'state' => $state,
        'coordinates' => $coordinates,
        'distances' => $distances,
        'allowedCities' => array_column($ALLOWED_CITIES, 'name')
    ];
    
    sendSuccess($response);
    
} catch (Exception $e) {
    error_log('Distance check API error: ' . $e->getMessage());
    sendError('Error checking location: ' . $e->getMessage(), 500);
}
?>
