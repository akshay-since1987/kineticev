<?php
require_once '../Logger.php';
$logger = Logger::getInstance();
$logger->info('[DEBUG] submit-test-drive.php script started', ['timestamp' => date('Y-m-d H:i:s')], 'debug_logs.txt');
// Ensure Composer autoload is loaded for AWS SDK
require_once __DIR__ . '/../vendor/autoload.php';
/**
 * API endpoint to handle test ride submissions
 * Processes test ride requests and sends email notifications
 */

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

require_once '../DatabaseHandler.php';
require_once '../Logger.php';
require_once '../EmailHandler.php';
require_once '../SalesforceService.php';

// Load configuration
$config = include '../config.php';

// Initialize logger and email handler
$logger = Logger::getInstance();
$emailHandler = new EmailHandler();

$response = ['success' => false, 'message' => ''];

try {
    // Support both form POST and raw JSON input (for AJAX)
    $input = $_POST;
    if (empty($input)) {
        $json = file_get_contents('php://input');
        $input = json_decode($json, true) ?: [];
    }
    $full_name = trim($input['full_name'] ?? '');
    $phone = trim($input['phone'] ?? '');
    $email = trim($input['email'] ?? '');
    $pincode = trim($input['pincode'] ?? '');
    $message = trim($input['message'] ?? '');

    // Validation
    $errors = [];

    if (empty($full_name)) {
        $errors[] = 'This field is required';
    } elseif (strlen($full_name) < 2) {
        $errors[] = 'Name should be larger than 2 characters';
    } elseif (!preg_match('/^[a-zA-Z\s]+$/', $full_name)) {
        $errors[] = 'Enter a valid name';
    }

    if (empty($phone)) {
        $errors[] = 'This field is required';
    } elseif (!preg_match('/^[6-9]\d{9}$/', preg_replace('/\D/', '', $phone))) {
        $errors[] = 'Please enter a valid 10-digit mobile number';
    }

    if (empty($email)) {
        $errors[] = 'This field is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address';
    }

    if (empty($pincode)) {
        $errors[] = 'This field is required';
    } elseif (!preg_match('/^[1-9][0-9]{5}$/', $pincode)) {
        $errors[] = 'Please enter a valid 6-digit pin code';
    }

    if (!empty($errors)) {
        $logger->info('[DEBUG] Validation failed in submit-test-drive.php', [
            'errors' => $errors,
            'input' => [
                'full_name' => $full_name,
                'phone' => $phone,
                'email' => $email,
                'pincode' => $pincode,
                'message' => $message
            ],
            'timestamp' => date('Y-m-d H:i:s')
        ], 'debug_logs.txt');

        // Map generic errors to specific field errors for better UX
        $field_errors = [];
        $error_index = 0;

        // Map errors to specific fields based on validation order
        if (empty($full_name)) {
            $field_errors['full_name'] = $errors[$error_index] ?? 'This field is required';
            $error_index++;
        } elseif (strlen($full_name) < 2) {
            $field_errors['full_name'] = $errors[$error_index] ?? 'Name should be larger than 2 characters';
            $error_index++;
        } elseif (!preg_match('/^[a-zA-Z\s]+$/', $full_name)) {
            $field_errors['full_name'] = $errors[$error_index] ?? 'Enter a valid name';
            $error_index++;
        }

        if (empty($phone)) {
            $field_errors['phone'] = $errors[$error_index] ?? 'This field is required';
            $error_index++;
        } elseif (!preg_match('/^[6-9]\d{9}$/', preg_replace('/\D/', '', $phone))) {
            $field_errors['phone'] = $errors[$error_index] ?? 'Please enter a valid 10-digit mobile number';
            $error_index++;
        }

        if (empty($email)) {
            $field_errors['email'] = $errors[$error_index] ?? 'This field is required';
            $error_index++;
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $field_errors['email'] = $errors[$error_index] ?? 'Please enter a valid email address';
            $error_index++;
        }

        if (empty($pincode)) {
            $field_errors['pincode'] = $errors[$error_index] ?? 'This field is required';
            $error_index++;
        } elseif (!preg_match('/^[1-9][0-9]{5}$/', $pincode)) {
            $field_errors['pincode'] = $errors[$error_index] ?? 'Please enter a valid 6-digit pin code';
            $error_index++;
        }

        $response['message'] = 'Please correct the errors below and try again.';
        $response['errors'] = $field_errors;
    } else {
        // Store in database
        $db = new DatabaseHandler();
        $result = $db->requestTestDrive($full_name, $phone, null, $pincode, $message, $email);

        if ($result !== false) {
            // Get the UUID instead of the auto-increment ID for email templates
            $test_ride_uuid = $db->getUuidById('test_drives', $result);
            $test_ride_id = $test_ride_uuid ?: $result; // Use UUID if found, fallback to ID

            // Send to Salesforce
            try {
                $salesforceService = new SalesforceService($logger, $config);
                $testRideData = [
                    'full_name' => $full_name,
                    'phone' => $phone,
                    'email' => $email,
                    'date' => null,
                    'pincode' => $pincode,
                    'message' => $message,
                    'form_type' => 'test_ride'
                ];

                $salesforceResult = $salesforceService->sendToSalesforce($testRideData, 'test_ride');

                if ($salesforceResult['success']) {
                    $logger->info('[TEST_RIDE] Successfully sent to Salesforce', [
                        'test_ride_id' => $test_ride_id,
                        'salesforce_result' => $salesforceResult,
                        'timestamp' => date('Y-m-d H:i:s')
                    ]);
                } else {
                    $logger->warning('[TEST_RIDE] Failed to send to Salesforce', [
                        'test_ride_id' => $test_ride_id,
                        'salesforce_error' => $salesforceResult,
                        'timestamp' => date('Y-m-d H:i:s')
                    ]);
                }
            } catch (Exception $e) {
                $logger->error('[TEST_RIDE] Salesforce integration error', [
                    'test_ride_id' => $test_ride_id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'timestamp' => date('Y-m-d H:i:s')
                ]);
            }

            // Log system info for debugging
            $emailHandler->logSystemInfo($test_ride_id);

            // Get email configuration
            $emailConfig = $config['email']['test_ride'];
            $to_emails = $emailConfig['recipients'];

            $logger->info('[TEST_RIDE] Starting email notification process', [
                'test_ride_id' => $test_ride_id,
                'customer_name' => $full_name,
                'customer_email' => $email,
                'admin_recipients' => $to_emails
            ]);

            $subject = $emailConfig['subject_prefix'] . " " . $full_name;

            // Use email template for admin email
            $html_message = (function ($test_ride_id, $full_name, $phone, $email, $pincode, $message, $emailConfig) {
                ob_start();
                include __DIR__ . '/../email-templates/test-ride-admin-email.tpl.php';
                return ob_get_clean();
            })($test_ride_id, $full_name, $phone, $email, $pincode, $message, $emailConfig);

            // No plain text admin email (HTML only)
            $text_message = '';

            // Send admin notification emails using enhanced handler
            $from = $config['aws_ses']['from_email'] ?? 'info@kineticev.in';
            $logger->info('[DEBUG] About to send admin email', [
                'to' => $to_emails,
                'subject' => $subject,
                'from' => $from,
                'timestamp' => date('Y-m-d H:i:s')
            ], 'debug_logs.txt');

            // Send to each admin recipient individually (AWS SES requirement)
            $adminEmailResult = true; // Track overall success
            if (is_array($to_emails)) {
                foreach ($to_emails as $recipient) {
                    $result = $emailHandler->sendEmail($recipient, $subject, $html_message, $from, true);
                    if (!$result) {
                        $adminEmailResult = false;
                    }
                }
            } else {
                $adminEmailResult = $emailHandler->sendEmail($to_emails, $subject, $html_message, $from, true);
            }

            $logger->info('[TEST_RIDE] Admin email send result', [
                'test_ride_id' => $test_ride_id,
                'recipients' => $to_emails,
                'success' => $adminEmailResult,
                'timestamp' => date('Y-m-d H:i:s')
            ]);

            // Send confirmation email to customer
            $customer_subject = $emailConfig['customer_subject'];

            $customer_message = (function ($full_name, $test_ride_id, $pincode, $emailConfig, $image_url = 'https://test.kineticev.in/-/images/new/black/000044.png') {
                ob_start();
                include __DIR__ . '/../email-templates/test-ride-customer-email.tpl.php';
                return ob_get_clean();
            })($full_name, $test_ride_id, $pincode, $emailConfig);

            $customer_text = "
Dear {$full_name},

Thank you for your interest in KineticEV! We have received your test ride request.

Your Test Ride Details:
Reference ID: {$test_ride_id}
Location: Pin Code {$pincode}

{$emailConfig['showroom_contact']}

Our team will contact you within {$emailConfig['response_time']} to confirm the test ride details and schedule.

If you have any urgent questions, please feel free to call us at {$emailConfig['support_phone']}.

We look forward to giving you an amazing test ride experience!

Best regards,
KineticEV Test Ride Team
            ";

            // Send customer confirmation email
            $customer_from = $emailConfig['customer_from_email'] ?? ($config['aws_ses']['from_email'] ?? 'info@kineticev.in');
            $logger->info('[DEBUG] About to send customer email', [
                'to' => $email,
                'subject' => $customer_subject,
                'from' => $customer_from,
                'timestamp' => date('Y-m-d H:i:s')
            ], 'debug_logs.txt');
            $customerEmailResult = $emailHandler->sendEmail(
                $email,
                $customer_subject,
                $customer_message,
                $customer_from,
                true
            );

            $logger->info('[TEST_RIDE] Customer confirmation email result', [
                'test_ride_id' => $test_ride_id,
                'customer_email' => $email,
                'email_id' => $customerEmailResult['email_id'] ?? 'unknown',
                'success' => $customerEmailResult['success'] ?? false,
                'duration_ms' => $customerEmailResult['duration_ms'] ?? 0
            ]);

            // Final logging with comprehensive email results
            $overallEmailSuccess = ($adminEmailResult['success'] ?? false) && ($customerEmailResult['success'] ?? false);

            $logger->info('[TEST_RIDE] Test ride request processed successfully', [
                'test_ride_id' => $test_ride_id,
                'customer_details' => [
                    'name' => $full_name,
                    'email' => $email,
                    'phone' => $phone,
                    'pincode' => $pincode
                ],
                'email_results' => [
                    'admin_emails' => [
                        'success' => $adminEmailResult['success'] ?? false,
                        'sent_count' => $adminEmailResult['total_sent'] ?? 0,
                        'failed_count' => $adminEmailResult['total_failed'] ?? 0,
                        'email_id' => $adminEmailResult['email_id'] ?? 'unknown'
                    ],
                    'customer_email' => [
                        'success' => $customerEmailResult['success'] ?? false,
                        'email_id' => $customerEmailResult['email_id'] ?? 'unknown'
                    ],
                    'overall_success' => $overallEmailSuccess
                ]
            ]);

            $response['success'] = true;
            $response['message'] = $overallEmailSuccess
                ? "Thank you for your test ride request. Please check your email"
                : "Thank you for your test ride request. Please check your email";
            
            // Add Meta Pixel tracking data to successful response
            $response['meta_pixel_tracking'] = [
                'event' => 'Lead',
                'data' => [
                    'content_name' => 'Test Ride Request',
                    'content_category' => 'Test Drive',
                    'lead_type' => 'TestRide',
                    'test_ride_id' => $test_ride_id,
                    'source' => 'ajax_form'
                ]
            ];

        } else {
            $logger->error('[TEST_RIDE] Database insertion failed', [
                'customer_details' => [
                    'name' => $full_name,
                    'email' => $email,
                    'phone' => $phone,
                    'pincode' => $pincode
                ],
                'database_result' => $result
            ]);

            $response['message'] = 'Sorry, there was an error processing your test ride request. Please try again later.';
        }
    }

} catch (Exception $e) {
    $response['message'] = 'Sorry, there was an error processing your request. Please try again later.';
    $logger->error('Test ride form error', ['error' => $e->getMessage()]);
}

// Return JSON response for AJAX requests
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// For regular form submissions, you might want to redirect or show a page
// For now, just output the message
echo $response['message'];
?>