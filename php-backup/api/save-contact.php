<?php
/**
 * API endpoint to save contact form submissions
 * Handles contact form data and stores it in the database
 */

// Set JSON response header
// header('Content-Type: application/json');
// header('Access-Control-Allow-Origin: *');
// header('Access-Control-Allow-Methods: POST');
// header('Access-Control-Allow-Headers: Content-Type');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

require_once '../DatabaseHandler.php';
require_once '../Logger.php';
require_once '../SalesforceService.php';

// Load configuration
$config = include '../config.php';

// Initialize logger
$logger = Logger::getInstance();

try {
    // Get JSON input or form data
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST;
    }

    // Validate and sanitize input
    $name = trim($input['name'] ?? '');
    $phone = trim($input['phone'] ?? '');
    $email = trim($input['email'] ?? '');
    $help_type = trim($input['help'] ?? '');
    $message = trim($input['message'] ?? '');

    // Validation array
    $errors = [];

    // Validate name
    if (empty($name)) {
        $errors[] = 'Full name is required';
    } elseif (strlen($name) < 2) {
        $errors[] = 'Full name must be at least 2 characters';
    } elseif (strlen($name) > 100) {
        $errors[] = 'Full name cannot exceed 100 characters';
    }

    // Validate phone
    if (empty($phone)) {
        $errors[] = 'Phone number is required';
    } elseif (!preg_match('/^[\+]?[0-9\s\-\(\)]{10,15}$/', $phone)) {
        $errors[] = 'Please enter a valid phone number';
    }

    // Validate email
    if (empty($email)) {
        $errors[] = 'Email address is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address';
    } elseif (strlen($email) > 255) {
        $errors[] = 'Email address is too long';
    }

    // Validate help type
    if (empty($help_type) || !in_array($help_type, ['support', 'enquiry', 'others'])) {
        $errors[] = 'Please select a valid concern type';
    }

    // Return validation errors if any
    if (!empty($errors)) {
        http_response_code(400);
        
        // Map generic errors to specific field errors for better UX
        $field_errors = [];
        $error_index = 0;
        
        // Map errors to specific fields based on validation order
        if (empty($name)) {
            $field_errors['name'] = $errors[$error_index] ?? 'Full name is required';
            $error_index++;
        } elseif (strlen($name) < 2) {
            $field_errors['name'] = $errors[$error_index] ?? 'Full name must be at least 2 characters';
            $error_index++;
        } elseif (strlen($name) > 100) {
            $field_errors['name'] = $errors[$error_index] ?? 'Full name cannot exceed 100 characters';
            $error_index++;
        }
        
        if (empty($phone)) {
            $field_errors['phone'] = $errors[$error_index] ?? 'Phone number is required';
            $error_index++;
        } elseif (!preg_match('/^[6-9]\d{9}$/', preg_replace('/\D/', '', $phone))) {
            $field_errors['phone'] = $errors[$error_index] ?? 'Please enter a valid 10-digit mobile number';
            $error_index++;
        }
        
        if (empty($email)) {
            $field_errors['email'] = $errors[$error_index] ?? 'Email is required';
            $error_index++;
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $field_errors['email'] = $errors[$error_index] ?? 'Please enter a valid email address';
            $error_index++;
        }
        
        if (empty($help_type) || !in_array($help_type, ['support', 'enquiry', 'others'])) {
            $field_errors['help'] = $errors[$error_index] ?? 'Please select a valid concern type';
            $error_index++;
        }
        
        echo json_encode([
            'success' => false,
            'message' => 'Please correct the errors below and try again.',
            'errors' => $field_errors
        ]);
        exit;
    }

    // Initialize database handler
    $db = new DatabaseHandler();
    
    // Create contacts table if it doesn't exist
    $db->createContactsTable();

    // Prepare contact data
    $contactData = [
        'full_name' => $name,
        'phone' => $phone,
        'email' => $email,
        'help_type' => $help_type,
        'message' => $message,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
    ];

    // Save contact to database
    $contact_id = $db->saveContact($contactData);

    if ($contact_id) {
        // Get the UUID instead of the auto-increment ID for email templates and API response
        $contact_uuid = $db->getUuidById('contacts', $contact_id);
        $original_id = $contact_id; // Keep original for logging
        $contact_id = $contact_uuid ?: $contact_id; // Use UUID if found, fallback to ID
        
        // Send to Salesforce
        try {
            $salesforceService = new SalesforceService($logger, $config);
            $salesforceResult = $salesforceService->sendToSalesforce($contactData, 'contact');
            
            if ($salesforceResult['success']) {
                $logger->info('[CONTACT_API] Successfully sent to Salesforce', [
                    'contact_id' => $original_id,
                    'contact_uuid' => $contact_id,
                    'salesforce_result' => $salesforceResult,
                    'timestamp' => date('Y-m-d H:i:s')
                ]);
            } else {
                $logger->warning('[CONTACT_API] Failed to send to Salesforce', [
                    'contact_id' => $original_id,
                    'contact_uuid' => $contact_id,
                    'salesforce_error' => $salesforceResult,
                    'timestamp' => date('Y-m-d H:i:s')
                ]);
            }
        } catch (Exception $e) {
            $logger->error('[CONTACT_API] Salesforce integration error', [
                'contact_id' => $original_id,
                'contact_uuid' => $contact_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        }
        
        // Log successful submission
        $logger = Logger::getInstance();
        $logger->info('Contact saved via API', [
            'contact_id' => $original_id,
            'contact_uuid' => $contact_id,
            'name' => $name,
            'email' => $email,
            'help_type' => $help_type,
            'method' => 'API'
        ]);

        // Return success response
        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Contact saved successfully',
            'contact_id' => $contact_id,
            'data' => [
                'reference_id' => $contact_id,
                'submitted_at' => date('Y-m-d H:i:s')
            ]
        ]);
    } else {
        throw new Exception('Failed to save contact to database');
    }

} catch (Exception $e) {
    // Log error
    if (isset($logger)) {
        $logger->error('Contact API error', [
            'error' => $e->getMessage(),
            'input' => $input ?? []
        ]);
    }

    // Return error response
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error. Please try again later.',
        'debug' => [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ]);
}
