<?php
/**
 * OTP Verification API Endpoint
 * Verifies OTP for mobile number
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
    
    if (!isset($input['otp']) || empty(trim($input['otp']))) {
        echo json_encode([
            'success' => false,
            'error' => 'OTP is required',
            'validation_error' => true
        ]);
        exit();
    }
    
    $phone = trim($input['phone']);
    $otp = trim($input['otp']);
    $purpose = isset($input['purpose']) ? trim($input['purpose']) : 'contact_form';
    
    // Validate purpose
    if (!in_array($purpose, ['contact_form', 'test_ride', 'booking_form'])) {
        echo json_encode([
            'success' => false,
            'error' => 'Invalid purpose specified',
            'validation_error' => true
        ]);
        exit();
    }
    
    // Validate OTP format (6 digits)
    if (!preg_match('/^\d{6}$/', $otp)) {
        echo json_encode([
            'success' => false,
            'error' => 'OTP must be 6 digits',
            'validation_error' => true
        ]);
        exit();
    }
    
    $logger->info('[OTP_API] OTP verification request', [
        'phone' => $phone,
        'purpose' => $purpose,
        'otp_length' => strlen($otp),
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ]);
    
    // Initialize OTP service
    $otpService = new OtpService();
    
    // Verify OTP
    $result = $otpService->verifyOtp($phone, $otp, $purpose);
    
    if ($result['success']) {
        $logger->info('[OTP_API] OTP verified successfully', [
            'phone' => $phone,
            'purpose' => $purpose
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => $result['message'],
            'verified' => true
        ]);
    } else {
        $logger->warning('[OTP_API] OTP verification failed', [
            'phone' => $phone,
            'purpose' => $purpose,
            'error' => $result['error'],
            'invalid_otp' => $result['invalid_otp'] ?? false,
            'max_attempts_exceeded' => $result['max_attempts_exceeded'] ?? false
        ]);
        
        echo json_encode([
            'success' => false,
            'error' => $result['error'],
            'invalid_otp' => $result['invalid_otp'] ?? false,
            'max_attempts_exceeded' => $result['max_attempts_exceeded'] ?? false
        ]);
    }
    
} catch (Exception $e) {
    $logger->error('[OTP_API] Unexpected error in OTP verification', [
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
