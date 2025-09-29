<?php
// Configure session settings - match these with index.php
ini_set('session.cookie_lifetime', 3600); // 1 hour
ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
ini_set('session.cookie_httponly', 1); // Prevent XSS
ini_set('session.use_strict_mode', 1); // Prevent session fixation

// Start session FIRST before any output
session_start();

// Debug session info to error log
error_log("SESSION INFO: " . json_encode($_SESSION));

// Include required files AFTER session start
$config = include '../config.php';
require_once 'AdminHandler.php';

// Check if session is active and user is logged in
// Match the EXACT same check as index.php uses
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // Instead of redirecting (which causes the JSON error), return proper JSON error
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'Not logged in or session expired',
        'login_required' => true
    ]);
    exit;
}

// Create AdminHandler instance
$adminHandler = new AdminHandler($config);

// Set content type to JSON
header('Content-Type: application/json');

// Handle POST request for creating/editing dealership
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    try {
        // Check for required fields
        if (!isset($data['name']) || !isset($data['address']) || !isset($data['city']) || 
            !isset($data['state']) || !isset($data['pincode'])) {
            throw new Exception("Required fields are missing");
        }
        
        // Check if we're updating or creating
        if (isset($data['id']) && !empty($data['id'])) {
            // Update existing dealership
            $result = $adminHandler->updateDealership($data);
            echo json_encode(['success' => true, 'message' => 'Dealership updated successfully', 'data' => $result]);
        } else {
            // Create new dealership
            $result = $adminHandler->createDealership($data);
            echo json_encode(['success' => true, 'message' => 'Dealership created successfully', 'data' => $result]);
        }
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Handle GET request for fetching dealership data
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        // First check if the dealership table exists
        $tables = $adminHandler->getAllTables();
        if (!in_array('dealerships', $tables)) {
            http_response_code(200); // Use 200 instead of 400 to avoid console errors
            echo json_encode([
                'success' => false, 
                'error' => 'Dealership table does not exist',
                'setup_needed' => true
            ]);
            exit;
        }
        
        // Check if specific dealership ID is requested
        if (isset($_GET['id'])) {
            $dealership = $adminHandler->getDealership($_GET['id']);
            echo json_encode(['success' => true, 'data' => $dealership]);
        } else {
            // Get all dealerships
            $dealerships = $adminHandler->getAllDealerships();
            echo json_encode(['success' => true, 'data' => $dealerships]);
        }
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Handle DELETE request for deleting dealership
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    try {
        // Get ID from URL parameter
        $id = isset($_GET['id']) ? $_GET['id'] : null;
        
        if (!$id) {
            throw new Exception("Dealership ID is required");
        }
        
        $result = $adminHandler->deleteDealership($id);
        echo json_encode(['success' => true, 'message' => 'Dealership deleted successfully']);
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Handle unsupported HTTP methods
http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed']);
exit;
