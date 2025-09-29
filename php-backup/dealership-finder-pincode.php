<?php
/**
 * Dealership Finder by Pincode
 * 
 * API endpoint to find dealerships by pincode
 */

// Get pincode from query parameters
$pincode = isset($_GET['pincode']) ? trim($_GET['pincode']) : '';

// Include necessary files
require_once __DIR__ . '/DealershipFinder.php';

// Set content type to JSON
header('Content-Type: application/json');

// Validate pincode
if (empty($pincode) || !preg_match('/^\d{6}$/', $pincode)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid pincode format. Please provide a 6-digit pincode.'
    ]);
    exit;
}

try {
    // Create an instance of DealershipFinder
    $finder = new DealershipFinder();
    
    // Get dealerships by pincode
    $dealerships = $finder->findDealershipsByPincode($pincode);
    
    if (empty($dealerships)) {
        echo json_encode([
            'success' => true,
            'dealerships' => [],
            'message' => 'No dealerships found for this pincode.'
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'dealerships' => $dealerships,
            'count' => count($dealerships)
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
