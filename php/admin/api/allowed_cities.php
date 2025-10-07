<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

// Comprehensive cache control headers to prevent CDN caching
header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0, private');
header('Pragma: no-cache');
header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');

// CDN-specific headers
header('CDN-Cache-Control: no-cache');
header('Cloudflare-CDN-Cache-Control: no-cache');
header('X-Accel-Expires: 0');
header('Surrogate-Control: no-store');
header('X-Cache-Control: no-cache');

// Content type and security headers
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// Configure session settings
ini_set('session.cookie_lifetime', 3600); // 1 hour
ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
ini_set('session.cookie_httponly', 1); // Prevent XSS
ini_set('session.use_strict_mode', 1); // Prevent session fixation

session_start();
require_once '../../Logger.php';
require_once '../AdminHandler.php';

// Debug logging
$logger = Logger::getInstance();
$logger->debug('[ALLOWED_CITIES_API] Request received', [
    'method' => $_SERVER['REQUEST_METHOD'],
    'get' => $_GET,
    'post' => $_POST,
    'request' => $_REQUEST
]);

// Check authentication
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // Check if this is an AJAX request
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        // For AJAX requests, return JSON with redirect instruction
        http_response_code(401);
        echo json_encode([
            'error' => 'Session expired',
            'redirect' => 'login',
            'message' => 'Please log in again'
        ]);
    } else {
        // For direct requests, redirect to login page
        header('Location: ../login.php');
    }
    exit;
}

$logger = Logger::getInstance();
$config = include '../../config.php';
$adminHandler = new AdminHandler($config);

$requestMethod = $_SERVER['REQUEST_METHOD'];

try {
    // Default to 'list' action for DataTables requests
    $action = 'list';
    
    // Check for specific action in request
    if (isset($_REQUEST['action'])) {
        $action = $_REQUEST['action'];
    }
    // If no specific action but it's a POST, assume it's for DataTables
    else if ($requestMethod === 'POST' && isset($_POST['draw'])) {
        $action = 'list';
    }
    
    $logger->debug('[ALLOWED_CITIES_API] Processing action', [
        'action' => $action,
        'method' => $requestMethod
    ]);

    if ($requestMethod === 'GET') {
        switch ($action) {
            case 'list':
                // Get cities for DataTables
                $start = intval($_REQUEST['start'] ?? 0);
                $length = intval($_REQUEST['length'] ?? 10);
                $search = isset($_REQUEST['search']['value']) ? $_REQUEST['search']['value'] : '';
                $draw = intval($_REQUEST['draw'] ?? 1);
                
                $result = $adminHandler->getAllowedCitiesPaginated($start, $length, $search);
                $total = $adminHandler->getAllowedCitiesCount();
                $filtered = $adminHandler->getAllowedCitiesCount($search);
                
                echo json_encode([
                    'draw' => $draw,
                    'recordsTotal' => $total,
                    'recordsFiltered' => $filtered,
                    'data' => $result
                ]);
                break;
                
            case 'get':
                $cityId = intval($_GET['id'] ?? 0);
                if ($cityId > 0) {
                    $city = $adminHandler->getAllowedCity($cityId);
                    echo json_encode(['success' => true, 'data' => $city]);
                } else {
                    echo json_encode(['error' => 'Invalid city ID']);
                }
                break;
        }
    } elseif ($requestMethod === 'POST') {
        // Handle DataTables POST request
        if (isset($_POST['draw'])) {
            $start = intval($_POST['start'] ?? 0);
            $length = intval($_POST['length'] ?? 10);
            $search = isset($_POST['search']['value']) ? $_POST['search']['value'] : '';
            $draw = intval($_POST['draw'] ?? 1);
            
            $result = $adminHandler->getAllowedCitiesPaginated($start, $length, $search);
            $total = $adminHandler->getAllowedCitiesCount();
            $filtered = $adminHandler->getAllowedCitiesCount($search);
            
            echo json_encode([
                'draw' => $draw,
                'recordsTotal' => $total,
                'recordsFiltered' => $filtered,
                'data' => $result
            ]);
            return;
        }

        // Handle other POST actions
        $cityId = intval($_REQUEST['id'] ?? 0);
        
        switch ($action) {
            case 'update':
                if ($cityId > 0) {
                    $data = [
                        'city_name' => $_POST['city_name'] ?? '',
                        'coordinates' => $_POST['coordinates'] ?? '',
                        'is_allowed' => intval($_POST['is_allowed'] ?? 0)
                    ];
                    $result = $adminHandler->updateAllowedCity($cityId, $data);
                    if ($result) {
                        $city = $adminHandler->getAllowedCity($cityId);
                        echo json_encode([
                            'success' => true,
                            'message' => 'City updated successfully',
                            'data' => $city
                        ]);
                    } else {
                        echo json_encode([
                            'success' => false,
                            'error' => 'Failed to update city'
                        ]);
                    }
                } else {
                    echo json_encode([
                        'success' => false,
                        'error' => 'Invalid city ID'
                    ]);
                }
                break;
                
            case 'add':
                $data = [
                    'city_name' => $_POST['city_name'] ?? '',
                    'coordinates' => $_POST['coordinates'] ?? '',
                    'is_allowed' => intval($_POST['is_allowed'] ?? 0)
                ];
                $result = $adminHandler->addAllowedCity($data);
                echo json_encode(['success' => $result]);
                break;
                
            case 'delete':
                if ($cityId > 0) {
                    $result = $adminHandler->deleteAllowedCity($cityId);
                    echo json_encode(['success' => $result]);
                } else {
                    echo json_encode(['error' => 'Invalid city ID']);
                }
                break;
        }
    }
} catch (Exception $e) {
    $logger->error('[ADMIN_API] Allowed cities error', [
        'error' => $e->getMessage(),
        'method' => $requestMethod,
        'action' => $action ?? 'unknown'
    ]);
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'An error occurred processing your request',
        'debug' => $e->getMessage()  // Only in dev environment
    ]);
}