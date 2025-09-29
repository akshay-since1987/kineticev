<?php
/**
 * OTP Generation API Endpoint
 * Generates and sends OTP to mobile number
 */

// Initialize production timezone guard first
require_once __DIR__ . '/../production-timezone-guard.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit();
}

require_once __DIR__ . '/../OtpService.php';
require_once __DIR__ . '/../Logger.php';

$logger = Logger::getInstance();

try {
    // Set a timeout for database operations to prevent hanging
    set_time_limit(30);
    
    // Try to initialize OTP service with error handling
    $otpService = null;
    try {
        $otpService = new OtpService();
    } catch (Exception $dbError) {
        $logger->error('[OTP_API] Database connection failed during OTP service initialization', [
            'error' => $dbError->getMessage(),
            'trace' => $dbError->getTraceAsString()
        ]);
        
        echo json_encode([
            'success' => false,
            'error' => 'Service temporarily unavailable. Please try again in a few minutes.',
            'database_error' => true
        ]);
        exit();
    }
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Also check POST data for form submissions
    if (!$input) {
        $input = $_POST;
    }
    
    // Validate input
    if (!isset($input['phone']) || empty(trim($input['phone']))) {
        echo json_encode([
            'success' => false,
            'error' => 'Phone number is required',
            'validation_error' => true
        ]);
        exit();
    }
    
    $phone = trim($input['phone']);
    $purpose = isset($input['purpose']) ? trim($input['purpose']) : 'contact_form';
    $forceNew = isset($input['force_new']) ? (bool)$input['force_new'] : false;
    
    // Validate purpose
    if (!in_array($purpose, ['contact_form', 'test_ride', 'booking_form'])) {
        echo json_encode([
            'success' => false,
            'error' => 'Invalid purpose specified',
            'validation_error' => true
        ]);
        exit();
    }
    
    // Validate phone number format
    $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
    if (strlen($cleanPhone) < 10) {
        echo json_encode([
            'success' => false,
            'error' => 'Please enter a valid 10-digit mobile number',
            'validation_error' => true
        ]);
        exit();
    }
    
    $logger->info('[OTP_API] OTP generation request', [
        'phone' => $phone,
        'purpose' => $purpose,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ]);
    
    // OTP service already initialized above with error handling
    
    // Generate and send OTP
    $result = $otpService->generateAndSendOtp($phone, $purpose, $forceNew);
    
    if ($result['success']) {
        $logger->info('[OTP_API] OTP generated successfully', [
            'phone' => $phone,
            'purpose' => $purpose,
            'expires_in' => $result['expires_in'] ?? 300
        ]);
        
        $response = [
            'success' => true,
            'message' => $result['message'],
            'expires_in' => $result['expires_in']
        ];
        
        // Include development information only if available
        if (isset($result['development_otp'])) {
            $response['development_otp'] = $result['development_otp'];
            $response['development_mode'] = true;
        } elseif (isset($result['sms_result']['development_mode']) && $result['sms_result']['development_mode']) {
            $response['development_mode'] = true;
        }
        
        echo json_encode($response);
    } else {
        $logger->warning('[OTP_API] OTP generation failed', [
            'phone' => $phone,
            'purpose' => $purpose,
            'error' => $result['error'],
            'rate_limited' => $result['rate_limited'] ?? false
        ]);
        
        echo json_encode([
            'success' => false,
            'error' => $result['error'],
            'rate_limited' => $result['rate_limited'] ?? false
        ]);
    }
    
} catch (Exception $e) {
    $logger->error('[OTP_API] Unexpected error in OTP generation', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
        'input' => $input ?? null
    ]);
    
    echo json_encode([
        'success' => false,
        'error' => 'An unexpected error occurred. Please try again.',
        'exception' => true
    ]);
}
?>
