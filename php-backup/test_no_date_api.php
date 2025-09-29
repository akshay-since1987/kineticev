<?php
/**
 * Test script for submit-test-drive.php without date field
 */

echo "Testing submit-test-drive.php without date field...\n";

// Simulate POST data without date
$_POST = [
    'full_name' => 'Test User',
    'phone' => '9876543210', 
    'email' => 'test@example.com',
    'pincode' => '110001',
    'message' => 'Test message without date field'
];

// Simulate AJAX request
$_SERVER['HTTP_X_REQUESTED_WITH'] = 'xmlhttprequest';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

echo "Input data:\n";
print_r($_POST);
echo "\n";

// Capture output
ob_start();
try {
    include __DIR__ . '/api/submit-test-drive.php';
    $output = ob_get_clean();
    
    echo "API Response:\n";
    echo $output . "\n";
    
    // Try to decode JSON response
    $response = json_decode($output, true);
    if ($response) {
        echo "\nParsed Response:\n";
        echo "Success: " . ($response['success'] ? 'true' : 'false') . "\n";
        echo "Message: " . $response['message'] . "\n";
        if (isset($response['errors'])) {
            echo "Errors: " . print_r($response['errors'], true) . "\n";
        }
    }
    
} catch (Exception $e) {
    ob_get_clean();
    echo "Exception caught: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>