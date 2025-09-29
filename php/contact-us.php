<?php
// Ensure Composer autoload is loaded for AWS SDK
require_once __DIR__ . '/vendor/autoload.php';

// Contact Form Logging Function - Logs everything to contact_log.txt
function logContactForm($level, $message, $data = []) {
    $timestamp = date('Y-m-d H:i:s');
    $logFile = __DIR__ . '/logs/contact_log.txt';
    
    // Ensure logs directory exists
    $logDir = dirname($logFile);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    // Format log entry
    $logEntry = "[{$timestamp}] [{$level}] {$message}";
    if (!empty($data)) {
        $logEntry .= " " . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
    $logEntry .= "\n";
    
    // Write to log file
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

// Contact form processing
$success_message = '';
$error_message = '';

// Handle success message from session (after redirect)
session_start();
if (isset($_SESSION['contact_success_message'])) {
    $success_message = $_SESSION['contact_success_message'];
    unset($_SESSION['contact_success_message']); // Clear the message after displaying
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    logContactForm('INFO', 'Contact form POST request received', [
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
        'post_data_size' => strlen(http_build_query($_POST)),
        'timestamp' => date('Y-m-d H:i:s')
    ]);

    // Initialize components
    try {
        require_once 'DatabaseHandler.php';
        require_once 'Logger.php';
        require_once 'EmailHandler.php';
        require_once 'SalesforceService.php';
        $logger = Logger::getInstance();
        
        logContactForm('SUCCESS', 'Required components loaded successfully');
    } catch (Exception $requireException) {
        logContactForm('CRITICAL', 'Cannot load required files', [
            'error' => $requireException->getMessage(),
            'trace' => $requireException->getTraceAsString(),
            'php_version' => PHP_VERSION,
            'memory_usage' => memory_get_usage(true)
        ]);
        
        $error_message = 'Sorry, there is a system error. Please contact support directly at 8600096800.';
    }

    // Only proceed if required files loaded successfully
    if (!$error_message) {

        // Load configuration
        $config = include 'config.php';
        
        logContactForm('INFO', 'Configuration loading attempted');

        // Validate configuration loaded successfully
        if (!$config || !isset($config['email']['contact_form'])) {
            logContactForm('ERROR', 'Configuration file error', [
                'config_loaded' => $config ? 'true' : 'false',
                'email_config_exists' => isset($config['email']['contact_form']) ? 'true' : 'false',
                'config_keys' => $config ? array_keys($config) : []
            ]);
            $error_message = 'Sorry, there is a system configuration issue. Please contact support directly at 8600096800.';
        } else {
            logContactForm('SUCCESS', 'Configuration loaded successfully', [
                'email_recipients_count' => count($config['email']['contact_form']['recipients'] ?? [])
            ]);
            
            // Initialize email handler only if config is valid
            $emailHandler = new EmailHandler();
            logContactForm('SUCCESS', 'Email handler initialized');

            // Only proceed with form processing if no configuration error
            if (!$error_message) {
                try {
                    // Validate and sanitize input
                    $full_name = trim($_POST['full_name'] ?? '');
                    $phone = trim($_POST['phone'] ?? '');
                    $email = trim($_POST['email'] ?? '');
                    $help_type = trim($_POST['help'] ?? '');
                    $message = trim($_POST['message'] ?? '');
                    $phone_verified = isset($_POST['phone_verified']) ? $_POST['phone_verified'] : '0';
                    
                    logContactForm('INFO', 'Form data received and sanitized', [
                        'full_name' => $full_name,
                        'phone' => $phone,
                        'email' => $email,
                        'help_type' => $help_type,
                        'message' => substr($message, 0, 100) . (strlen($message) > 100 ? '...' : ''),
                        'phone_verified_raw' => $_POST['phone_verified'] ?? 'not_set',
                        'message_length' => strlen($message)
                    ]);

                    // Convert boolean values to integers for database compatibility
                    if ($phone_verified === 'true' || $phone_verified === true) {
                        $phone_verified = 1;
                    } elseif ($phone_verified === 'false' || $phone_verified === false) {
                        $phone_verified = 0;
                    } else {
                        $phone_verified = (int) $phone_verified; // Convert to integer
                    }

                    logContactForm('INFO', 'Phone verification status processed', [
                        'phone_verified_final' => $phone_verified
                    ]);

                    // Validation
                    $errors = [];

                    if (empty($full_name)) {
                        $errors['full_name'] = 'This field is required';
                    } elseif (strlen($full_name) < 2) {
                        $errors['full_name'] = 'Name should be larger than 2 characters';
                    } elseif (!preg_match('/^[a-zA-Z\s]+$/', $full_name)) {
                        $errors['full_name'] = 'Enter a valid name';
                    }

                    if (empty($phone)) {
                        $errors['phone'] = 'This field is required';
                    } elseif (!preg_match('/^[6-9]\d{9}$/', preg_replace('/\D/', '', $phone))) {
                        $errors['phone'] = 'Please enter a valid 10-digit mobile number';
                    }

                    if (empty($email)) {
                        $errors['email'] = 'This field is required';
                    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $errors['email'] = 'Please enter a valid email address';
                    }

                    if (empty($help_type) || !in_array($help_type, ['support', 'enquiry', 'dealership', 'others', 'careers'])) {
                        $errors['help_type'] = 'This field is required';
                    }

                    if (!empty($errors)) {
                        logContactForm('WARNING', 'Form validation failed', [
                            'errors' => $errors,
                            'submitted_data' => [
                                'full_name' => $full_name,
                                'phone' => $phone,
                                'email' => $email,
                                'help_type' => $help_type
                            ]
                        ]);
                    } else {
                        logContactForm('SUCCESS', 'Form validation passed successfully');
                        
                        // Store in database using DatabaseHandler
                        try {
                            $db = new DatabaseHandler();
                            logContactForm('INFO', 'DatabaseHandler initialized');

                            $contactData = [
                                'full_name' => $full_name,
                                'phone' => $phone,
                                'email' => $email,
                                'help_type' => $help_type,
                                'message' => $message,
                                'phone_verified' => $phone_verified,
                                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
                            ];

                            logContactForm('INFO', 'Attempting to save contact to database', [
                                'contact_data' => $contactData
                            ]);

                            $contact_id = $db->saveContact($contactData);

                            // Validate database save was successful
                            if (!$contact_id) {
                                logContactForm('ERROR', 'Database save failed', [
                                    'error' => 'Failed to save contact to database',
                                    'contact_data' => $contactData,
                                    'db_response' => $contact_id,
                                    'server_info' => [
                                        'php_version' => PHP_VERSION,
                                        'memory_usage' => memory_get_usage(true),
                                        'request_method' => $_SERVER['REQUEST_METHOD'] ?? '',
                                        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
                                    ]
                                ]);
                                $error_message = 'Sorry, we could not save your message due to a database issue. Please try again or contact us directly at 8600096800.';
                            } else {
                                logContactForm('SUCCESS', 'Contact saved to database successfully', [
                                    'contact_id' => $contact_id
                                ]);
                                
                                // Get the UUID instead of the auto-increment ID for email templates
                                $contact_uuid = $db->getUuidById('contacts', $contact_id);
                                $contact_id = $contact_uuid ?: $contact_id; // Use UUID if found, fallback to ID
                                
                                logContactForm('INFO', 'Contact ID processed', [
                                    'original_id' => $contact_id,
                                    'uuid' => $contact_uuid,
                                    'final_id' => $contact_id
                                ]);

                                // Database save successful, send to Salesforce first
                                // Exclude 'careers' from Salesforce integration
                                if ($help_type !== 'careers') {
                                    try {
                                        logContactForm('INFO', 'Initializing Salesforce integration');
                                        $salesforceService = new SalesforceService($logger, $config);
                                        
                                        logContactForm('INFO', 'Sending data to Salesforce', [
                                            'contact_data' => $contactData
                                        ]);
                                        $salesforceResult = $salesforceService->sendToSalesforce($contactData);

                                        if ($salesforceResult['success']) {
                                            logContactForm('SUCCESS', 'Successfully sent to Salesforce', [
                                                'contact_id' => $contact_id,
                                                'salesforce_result' => $salesforceResult
                                            ]);
                                        } else {
                                            logContactForm('WARNING', 'Failed to send to Salesforce', [
                                                'contact_id' => $contact_id,
                                                'salesforce_error' => $salesforceResult
                                            ]);
                                        }
                                    } catch (Exception $e) {
                                        logContactForm('ERROR', 'Salesforce integration error', [
                                            'contact_id' => $contact_id,
                                            'error' => $e->getMessage()
                                        ]);
                                    }
                                } else {
                                    logContactForm('INFO', 'Careers enquiry excluded from Salesforce integration', [
                                        'contact_id' => $contact_id
                                    ]);
                                }

                                // Now proceed with email processing
                                try {
                                    // Verify email handler is available before proceeding
                                    if (!isset($emailHandler)) {
                                        throw new Exception('Email handler not initialized - configuration may be invalid');
                                    }

                                    // Get email configuration with safety checks
                                    if (!isset($config['email']['contact_form'])) {
                                        throw new Exception('Email configuration not found');
                                    }
                                    $emailConfig = $config['email']['contact_form'];

                                    // Validate required email config fields
                                    $requiredFields = ['recipients', 'subject_prefix', 'customer_subject', 'response_time'];
                                    foreach ($requiredFields as $field) {
                                        if (!isset($emailConfig[$field])) {
                                            throw new Exception("Missing email configuration field: {$field}");
                                        }
                                    }

                                    $to_emails = $emailConfig['recipients'];

                                    $subject = $emailConfig['subject_prefix'] . " " . ucfirst($help_type);

                                    // Use email template for admin email
                                    $html_message = (function ($contact_id, $full_name, $phone, $email, $help_type, $message, $emailConfig) {
                                        ob_start();
                                        include __DIR__ . '/email-templates/contact-admin-email.tpl.php';
                                        return ob_get_clean();
                                    })($contact_id, $full_name, $phone, $email, $help_type, $message, $emailConfig);

                                    // No plain text admin email (HTML only)
                                    $text_message = '';

                                    // Log system info for debugging
                                    $emailHandler->logSystemInfo($contact_id);

                                    logContactForm('INFO', 'Starting email notification process', [
                                        'contact_id' => $contact_id,
                                        'customer_name' => $full_name,
                                        'customer_email' => $email,
                                        'help_type' => $help_type,
                                        'admin_recipients' => $to_emails
                                    ]);

                                    // Send admin notification emails using enhanced handler
                                    $adminEmailResult = null;
                                    $customerEmailResult = null;

                                    try {
                                        $from = $emailConfig['admin_from_email'] ?? ($config['aws_ses']['from_email'] ?? 'info@kineticev.in');

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

                                        logContactForm('INFO', 'Admin email send result', [
                                            'contact_id' => $contact_id,
                                            'recipients' => $to_emails,
                                            'success' => $adminEmailResult,
                                            'total_sent' => $adminEmailResult['total_sent'] ?? 0,
                                            'total_failed' => $adminEmailResult['total_failed'] ?? 0,
                                            'duration_ms' => $adminEmailResult['duration_ms'] ?? 0
                                        ]);
                                    } catch (Exception $emailException) {
                                        logContactForm('ERROR', 'Admin email send failed with exception', [
                                            'contact_id' => $contact_id,
                                            'error' => $emailException->getMessage(),
                                            'trace' => $emailException->getTraceAsString()
                                        ]);
                                        $adminEmailResult = ['success' => false, 'error' => $emailException->getMessage()];
                                    }

                                    // Send confirmation email to customer
                                    if ($help_type !== 'careers') {
                                        $customer_subject = $emailConfig['customer_subject'];
                                        // Use email template for customer email
                                        $customer_message = (function ($contact_id, $full_name, $help_type, $emailConfig) {
                                            ob_start();
                                            include __DIR__ . '/email-templates/contact-customer-email.tpl.php';
                                            return ob_get_clean();
                                        })($contact_id, $full_name, $help_type, $emailConfig);

                                        // No plain text customer email (HTML only)
                                        $customer_text = '';

                                        // Send customer confirmation email
                                        try {
                                            $customer_from = $emailConfig['customer_from_email'] ?? ($config['aws_ses']['from_email'] ?? 'info@kineticev.in');
                                            $customerEmailResult = $emailHandler->sendEmail(
                                                $email,
                                                $customer_subject,
                                                $customer_message,
                                                $customer_from,
                                                true
                                            );

                                            logContactForm('INFO', 'Customer confirmation email result', [
                                                'contact_id' => $contact_id,
                                                'customer_email' => $email,
                                                'email_id' => $customerEmailResult['email_id'] ?? 'unknown',
                                                'success' => $customerEmailResult['success'] ?? false,
                                                'duration_ms' => $customerEmailResult['duration_ms'] ?? 0
                                            ]);
                                        } catch (Exception $emailException) {
                                            logContactForm('ERROR', 'Customer confirmation email failed with exception', [
                                                'contact_id' => $contact_id,
                                                'customer_email' => $email,
                                                'error' => $emailException->getMessage(),
                                                'trace' => $emailException->getTraceAsString()
                                            ]);
                                            $customerEmailResult = ['success' => false, 'error' => $emailException->getMessage()];
                                        }
                                    }

                                    // If help_type is 'careers', send customer email using careers template
                                    if ($help_type === 'careers') {
                                        // Use email template for customer email specific to careers
                                        $customer_message = (function ($contact_id, $full_name, $help_type, $emailConfig) {
                                            ob_start();
                                            include __DIR__ . '/email-templates/contact-careers-customer-email.tpl.php';
                                            return ob_get_clean();
                                        })($contact_id, $full_name, $help_type, $emailConfig);

                                        $customer_from = $emailConfig['customer_from_email'] ?? ($config['aws_ses']['from_email'] ?? 'info@kineticev.in');

                                        // Send customer email for careers
                                        $customerEmailResult = $emailHandler->sendEmail(
                                            $email,
                                            $emailConfig['customer_subject'] . ' Careers Enquiry',
                                            $customer_message,
                                            $customer_from,
                                            true
                                        );

                                        logContactForm('INFO', 'Customer email for careers sent', [
                                            'contact_id' => $contact_id,
                                            'customer_email' => $email,
                                            'success' => $customerEmailResult
                                        ]);
                                    }

                                    // Final logging with comprehensive email results
                                    $adminEmailSuccess = ($adminEmailResult['success'] ?? false);
                                    $customerEmailSuccess = ($customerEmailResult['success'] ?? false);
                                    $overallEmailSuccess = $adminEmailSuccess && $customerEmailSuccess;

                                    logContactForm('SUCCESS', 'Contact form processed successfully', [
                                        'contact_id' => $contact_id,
                                        'customer_details' => [
                                            'name' => $full_name,
                                            'email' => $email,
                                            'phone' => $phone,
                                            'help_type' => $help_type
                                        ],
                                        'email_results' => [
                                           
                                                'success' => $adminEmailSuccess,
                                                'sent_count' => $adminEmailResult['total_sent'] ?? 0,
                                                'failed_count' => $adminEmailResult['total_failed'] ?? 0,
                                                'email_id' => $adminEmailResult['email_id'] ?? 'unknown',
                                                'error' => $adminEmailResult['error'] ?? null
                                            ],
                                            'customer_email' => [
                                                'success' => $customerEmailSuccess,
                                                'email_id' => $customerEmailResult['email_id'] ?? 'unknown',
                                                'error' => $customerEmailResult['error'] ?? null
                                            ],
                                            'overall_success' => $overallEmailSuccess
                                        ]
                                    );

                                    // Log email failures separately if they occurred
                                    if (!$adminEmailSuccess) {
                                        logContactForm('WARNING', 'Admin email notification failed but process continued', [
                                            'contact_id' => $contact_id,
                                            'admin_recipients' => $to_emails,
                                            'error' => $adminEmailResult['error'] ?? 'Unknown error'
                                        ]);
                                    }

                                    if (!$customerEmailSuccess) {
                                        logContactForm('WARNING', 'Customer confirmation email failed but process continued', [
                                            'contact_id' => $contact_id,
                                            'customer_email' => $email,
                                            'error' => $customerEmailResult['error'] ?? 'Unknown error'
                                        ]);
                                    }

                                    // Always return success since the contact form was saved to database
                                    // But inform user if there were email issues
                                    $emailIssues = [];
                                    if (!$adminEmailSuccess) {
                                        $emailIssues[] = 'admin notification';
                                    }
                                    if (!$customerEmailSuccess) {
                                        $emailIssues[] = 'confirmation email';
                                    }

                                    if (!empty($emailIssues)) {
                                        $responseTime = isset($emailConfig['response_time']) ? $emailConfig['response_time'] : '24 hours';
                                        $success_message = "Thank you for contacting us! We have received your message and our team will get back to you shortly.";
                                    } else {
                                        $responseTime = isset($emailConfig['response_time']) ? $emailConfig['response_time'] : '24 hours';
                                        $success_message = "Thank you for contacting us! We have received your message and our team will get back to you shortly.";
                                    }

                                    // Clear form data on success
                                    $full_name = $phone = $email = $help_type = $message = '';

                                    // Redirect to clean URL after successful submission (for non-AJAX requests)
                                    if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
                                        // Store success message in session for display after redirect
                                        session_start();
                                        $_SESSION['contact_success_message'] = $success_message;

                                        // Redirect to clean contact-us URL
                                        header('Location: /contact-us?success=1');
                                        exit;
                                    }

                                    // Log successful form submission with complete details
                                    logContactForm('SUCCESS', 'Form submitted successfully', [
                                        'contact_id' => $contact_id,
                                        'success_type' => 'database_saved_with_emails',
                                        'email_status' => [
                                            'admin_email_success' => $adminEmailSuccess ?? false,
                                            'customer_email_success' => $customerEmailSuccess ?? false,
                                            'overall_email_success' => $overallEmailSuccess ?? false
                                        ],
                                        'processing_time' => microtime(true) - ($_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true)),
                                        'timestamp' => date('Y-m-d H:i:s')
                                    ]);

                                } catch (Exception $emailException) {
                                    // Email processing failed but contact was saved
                                    logContactForm('ERROR', 'Email processing failed', [
                                        'contact_id' => $contact_id,
                                        'error' => $emailException->getMessage(),
                                        'trace' => $emailException->getTraceAsString(),
                                        'contact_data' => $contactData,
                                        'timestamp' => date('Y-m-d H:i:s')
                                    ]);

                                    $success_message = "Thank you for contacting us! We have received your message and our team will get back to you shortly. Note: There was an issue with email notifications, but your message has been saved successfully.";

                                    // Clear form data on success even if emails failed
                                    $full_name = $phone = $email = $help_type = $message = '';

                                    // Redirect to clean URL after successful submission (for non-AJAX requests)
                                    if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
                                        // Store success message in session for display after redirect
                                        session_start();
                                        $_SESSION['contact_success_message'] = $success_message;

                                        // Redirect to clean contact-us URL
                                        header('Location: /contact-us?success=1');
                                        exit;
                                    }

                                    // Log successful form submission with email failure details
                                    logContactForm('SUCCESS', 'Form submitted successfully with email issues', [
                                        'contact_id' => $contact_id,
                                        'success_type' => 'database_saved_email_failed',
                                        'email_error' => $emailException->getMessage(),
                                        'processing_time' => microtime(true) - ($_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true)),
                                        'timestamp' => date('Y-m-d H:i:s')
                                    ]);
                                }
                            } // End database save success block

                        } catch (Exception $dbException) {
                            // Database operation failed
                            logContactForm('ERROR', 'Database exception', [
                                'error' => $dbException->getMessage(),
                                'trace' => $dbException->getTraceAsString(),
                                'contact_data' => $contactData ?? [],
                                'timestamp' => date('Y-m-d H:i:s'),
                                'server_info' => [
                                    'php_version' => PHP_VERSION,
                                    'memory_usage' => memory_get_usage(true),
                                    'request_method' => $_SERVER['REQUEST_METHOD'] ?? '',
                                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
                                ]
                            ]);
                            $error_message = 'Sorry, we encountered a database error. Please try again or contact us directly at 8600096800.';
                        }
                    }

                } catch (Exception $processingException) {
                    // Handle any unexpected errors during form processing
                    if (isset($logger)) {
                        logContactForm('ERROR', 'Form processing exception', [
                            'error' => $processingException->getMessage(),
                            'trace' => $processingException->getTraceAsString(),
                            'input_data' => [
                                'name' => $full_name ?? '',
                                'email' => $email ?? '',
                                'help_type' => $help_type ?? ''
                            ],
                            'timestamp' => date('Y-m-d H:i:s')
                        ]);
                    }
                    $error_message = 'Sorry, there was an unexpected error processing your request. Please try again or contact us directly at 8600096800.';
                }

            } // End of form processing conditional (no config error)
        } // End of config validation check
    } // End of required files loaded check
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    header('Content-Type: application/json');

    if (!empty($success_message)) {
        // Store success message in session for display after redirect (same as regular form)
        if (!isset($_SESSION)) {
            session_start();
        }
        $_SESSION['contact_success_message'] = $success_message;

        echo json_encode([
            'success' => true,
            'message' => $success_message,
            'redirect' => '/contact-us?success=1'  // Add redirect URL for AJAX
        ]);
    } else {
        // Send detailed validation errors for AJAX requests
        $response = [
            'success' => false,
            'message' => $error_message ?: 'An error occurred. Please try again.'
        ];

        // Add field-specific errors if available
        if (!empty($validation_errors)) {
            $response['errors'] = $validation_errors;
            $response['field_errors'] = $validation_errors; // Alternative key for compatibility
        }

        echo json_encode($response);
    }
    exit;
}


$name = isset($_GET['name']) ? strtolower(trim($_GET['name'])) : '';
$phone = isset($_GET['phone']) ? preg_replace('/\D/', '', trim($_GET['phone'])) : '';
$email = isset($_GET['email']) ? strtolower(trim($_GET['email'])) : '';
$intent = isset($_GET['intent']) ? strtolower(trim($_GET['intent'])) : '';
$verified = isset($_GET['verified']) ? $_GET['verified'] : '0';

// Map intent URL parameter to help_type for dropdown selection
$help_type = $intent;
// Validate intent value and map to valid dropdown options
$valid_intents = ['support', 'enquiry', 'dealership', 'others'];
if (!in_array($help_type, $valid_intents)) {
    $help_type = ''; // Reset to empty if invalid intent
}


?>
<?php
require_once 'components/layout.php';

$preload_images = [
    "/-/images/new/red/000144.png",
    "/-/images/new/black/000044.png"
];

startLayout("Contact Kinetic EV for Queries and Support Today", [
    'preload_images' => $preload_images,
    'description' => 'Get in touch with Kinetic EV for bookings product information dealer inquiries or expert assistance through email call or an easy online contact form now',
    'canonical' => 'https://kineticev.in/contact-us'
]);
?>

<!-- Include Google Maps for contact page map -->
<?php include 'components/google-maps-script.php'; ?>

<div class="contact-us">
    <section class="intro">
        <div class="container title-section-wrapper">
            <div class="page-title-section">
                <br />
                <br />
                <div class="intro-heading">
                    <h2>Contact</h2>
                </div>
            </div>
        </div>
    </section>
    <div class="contact-us-text container">
        <div class="contact-us-text-wrapper">
            <div class="message">
                We’d love to hear from you.
                Whether it’s a question, feedback, or suggestion, we’re here for you.
                Feel free to call or email us anytime.
            </div>

            <div class="contact-section">
                <div class="address-section">
                    <div class="connect">
                        <h3>Connect with Us</h3>
                        <div class="line">
                            <h5>Toll Free Number</h5>
                            <h4>8600096800</h4>
                        </div>
                        <div class="line">
                            <h5>Enquiry</h5>
                            <h4>info@kineticev.in</h4>
                        </div>
                        <div class="line">
                            <h5>Careers Enquiry</h5>
                            <h4>careers@kineticev.in</h4>
                        </div>
                    </div>
                    <div class="address">
                        <h3>Address</h3>
                        <h3>Kinetic Watts and Volts Limited</h3>
                        <p>D1 Block, Plot No 18/2,
                            Chinchwad, Pune-411019,
                            Maharashtra
                        </p>
                    </div>
                    <div class="map"></div>
                </div>
                <div class="form-section">
                    <section class="contact-form">
                        <?php if ($success_message): ?>
                            <div class="success-message"
                                style="background: #d4edda; color: #155724; padding: 15px; margin-bottom: 20px; border-radius: 5px; border: 1px solid #c3e6cb;">
                                <?php echo htmlspecialchars($success_message); ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($error_message): ?>
                            <div class="error-message"
                                style="background: #f8d7da; color: #721c24; padding: 15px; margin-bottom: 20px; border-radius: 5px; border: 1px solid #f5c6cb;">
                                <?php echo $error_message; ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="" ajax-updated="true" <?php if ($verified === '1'): ?>data-skip-otp="true" <?php endif; ?>>
                            <?php if ($verified === '1'): ?>
                                <div class="verification-status-message"
                                    style="background: #d4edda; color: #155724; padding: 12px 16px; margin-bottom: 15px; border-radius: 4px; border: 1px solid #c3e6cb; font-size: 14px;">
                                    ✅ <strong>Phone Verified:</strong> Your phone number has been verified from the booking
                                    process. No OTP verification required.
                                </div>
                            <?php elseif ($verified === '0' && !empty($phone)): ?>
                                <div class="verification-status-message"
                                    style="background: #fff3cd; color: #856404; padding: 12px 16px; margin-bottom: 15px; border-radius: 4px; border: 1px solid #ffeaa7; font-size: 14px;">
                                    📱 <strong>Phone Verification:</strong> Your phone number will be verified when you
                                    submit this form.
                                </div>
                            <?php endif; ?>

                            <!-- Hidden field to track verification status -->
                            <input type="hidden" name="phone_verified"
                                value="<?php echo htmlspecialchars($verified); ?>">

                            <div class="form-group">
                                <label for="full_name">Full Name</label>
                                <input type="text" id="full_name" name="full_name" placeholder="Your Full Name"
                                    value="<?php echo htmlspecialchars($name ?? $full_name ?? ''); ?>" required
                                    data-validation="required,alphabets_only,min_length:2"
                                    data-error-required="This field is required" data-error-pattern="Enter a valid name"
                                    data-error-min-length="Name should be larger than 2 characters">
                                <div class="error-message"></div>
                            </div>

                            <div class="form-group">
                                <label for="phone">Contact Number</label>
                                <input type="tel" id="phone" name="phone" placeholder="+91 0000 00 0000"
                                    value="<?php echo htmlspecialchars($phone ?? ''); ?>" required
                                    data-validation="required,indian_mobile"
                                    data-error-required="This field is required"
                                    data-error-pattern="Please enter a valid 10-digit mobile number">
                                <div class="error-message"></div>
                            </div>

                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="email" id="email" name="email" placeholder="Email Address"
                                    value="<?php echo htmlspecialchars($email ?? ''); ?>" required
                                    data-validation="required,email" data-error-required="This field is required"
                                    data-error-pattern="Please enter a valid email address">
                                <div class="error-message"></div>
                            </div>

                            <div class="form-group">
                                <label for="help">Concern</label>
                                <select id="help" name="help" required data-validation="required"
                                    data-error-required="This field is required">
                                    <option value="">Select</option>
                                    <option value="support" <?php echo (($help_type ?? '') === 'support') ? 'selected' : ''; ?>>Support</option>
                                    <option value="careers" <?php echo (($help_type ?? '') === 'careers') ? 'selected' : ''; ?>>Careers</option>
                                    <option value="enquiry" <?php echo (($help_type ?? '') === 'enquiry') ? 'selected' : ''; ?>>Booking Enquiry</option>
                                    <option value="dealership" <?php echo (($help_type ?? '') === 'dealership') ? 'selected' : ''; ?>>Dealership Enquiry</option>
                                    <option value="others" <?php echo (($help_type ?? '') === 'others') ? 'selected' : ''; ?>>Others</option>
                                </select>
                                <div class="error-message"></div>
                            </div>

                            <div class="form-group">
                                <label for="message">Message</label>
                                <textarea id="message" name="message"
                                    placeholder="Message"><?php echo htmlspecialchars($message ?? ''); ?></textarea>
                                <div class="error-message"></div>
                            </div>

                            <button type="submit" class="submit-btn">Submit</button>
                        </form>
                    </section>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Auto-hide verification status message and dynamic phone verification -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var statusMessage = document.querySelector('.verification-status-message');
        var phoneInput = document.getElementById('phone');
        var contactForm = document.querySelector('form[method="POST"]');
        var phoneVerifiedField = document.querySelector('input[name="phone_verified"]');

        // Store original phone number from URL parameter (cleaned)
        var originalPhone = '<?php echo preg_replace("/\D/", "", $phone); ?>';
        var originalVerified = '<?php echo $verified; ?>';

        console.log('🚀 Dynamic phone verification initialized:', {
            'originalPhone': originalPhone,
            'originalVerified': originalVerified,
            'hasOtpSystem': typeof OtpVerification !== 'undefined'
        });

        // Auto-hide verification status message after 5 seconds
        if (statusMessage) {
            setTimeout(function () {
                statusMessage.style.transition = 'opacity 0.5s ease-out';
                statusMessage.style.opacity = '0';

                setTimeout(function () {
                    statusMessage.style.display = 'none';
                }, 500);
            }, 5000);
        }

        // Function to normalize phone number (remove country code +91)
        function normalizePhone(phone) {
            // Remove all non-digits
            var cleaned = phone.replace(/\D/g, '');

            // Remove country code 91 if present at the beginning
            if (cleaned.startsWith('91') && cleaned.length === 12) {
                cleaned = cleaned.substring(2);
            }

            return cleaned;
        }

        // Function to update verification status based on phone number
        function updateVerificationStatus() {
            if (!phoneInput || !contactForm) return;

            // Normalize both phone numbers for comparison
            var currentPhone = normalizePhone(phoneInput.value);
            var normalizedOriginal = normalizePhone(originalPhone);

            console.log('📱 Phone verification check:', {
                'current': currentPhone,
                'original': normalizedOriginal,
                'originalVerified': originalVerified,
                'inputValue': phoneInput.value
            });

            // Check if phone matches original and original was verified
            var shouldSkipOtp = (currentPhone === normalizedOriginal && originalVerified === '1');

            console.log('🔍 Verification decision:', shouldSkipOtp ? 'Skip OTP' : 'Require OTP');

            // Update form data-skip-otp attribute
            if (shouldSkipOtp) {
                contactForm.setAttribute('data-skip-otp', 'true');
                if (phoneVerifiedField) {
                    phoneVerifiedField.value = '1';
                }
                console.log('✅ Phone verification status updated: OTP skipped');

                // Remove OTP container if it exists
                var existingOtpContainer = contactForm.querySelector('.otp-verification-container');
                if (existingOtpContainer) {
                    existingOtpContainer.style.display = 'none';
                    existingOtpContainer.classList.remove('otp-container-visible');
                    console.log('🔇 OTP container hidden');
                }
                // Enable submit button if it exists
                var submitButton = contactForm.querySelector('button[type="submit"], input[type="submit"]');
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.textContent = submitButton.getAttribute('data-original-text') || submitButton.textContent;
                    console.log('🔓 Submit button enabled');
                }
            } else {
                contactForm.removeAttribute('data-skip-otp');
                if (phoneVerifiedField) {
                    phoneVerifiedField.value = '0';
                }
                console.log('📱 Phone verification status updated: OTP required');

                // Need to enable OTP verification - reintegrate with OTP system
                if (currentPhone.length >= 10) {
                    console.log('🔐 Attempting to initialize OTP verification...');
                    // Check if OTP system is available and reinitialize OTP for this form
                    if (typeof OtpVerification !== 'undefined') {
                        console.log('✅ OtpVerification system found');
                        var existingOtpContainer = contactForm.querySelector('.otp-verification-container');
                        if (!existingOtpContainer) {
                            console.log('🔧 Creating new OTP container...');
                            // Create new OTP container
                            var otpContainer = OtpVerification.createOtpContainer(phoneInput, 'contact_form');
                            var phoneGroup = phoneInput.closest('.form-group') || phoneInput.parentNode;
                            phoneGroup.parentNode.insertBefore(otpContainer, phoneGroup.nextSibling);

                            // Setup OTP container behaviors
                            OtpVerification.setupOtpInputs(otpContainer);
                            OtpVerification.setupResendButton(otpContainer, phoneInput, 'contact_form');

                            // Store references on form
                            contactForm.otpContainer = otpContainer;
                            contactForm.otpPurpose = 'contact_form';
                            contactForm.phoneField = phoneInput;

                            // Disable submit button
                            var submitButton = contactForm.querySelector('button[type="submit"], input[type="submit"]');
                            if (submitButton) {
                                submitButton.setAttribute('data-original-text', submitButton.textContent);
                                OtpVerification.disableSubmitButton(submitButton, 'Phone verification required');
                                console.log('🔒 Submit button disabled');
                            }
                        }

                        // Show OTP container and generate OTP
                        var otpContainer = contactForm.querySelector('.otp-verification-container');
                        if (otpContainer) {
                            console.log('📤 Showing OTP container and generating OTP...');
                            OtpVerification.showOtpContainer(otpContainer);
                            OtpVerification.generateOtp(phoneInput.value, 'contact_form', otpContainer);
                        }
                    } else {
                        console.warn('⚠️ OtpVerification system not available');
                    }
                } else {
                    console.log('📱 Phone too short, not initializing OTP');
                }
            }

            // Update verification status message if it exists and is visible
            if (statusMessage && statusMessage.style.display !== 'none') {
                if (shouldSkipOtp) {
                    statusMessage.style.background = '#d4edda';
                    statusMessage.style.color = '#155724';
                    statusMessage.style.borderColor = '#c3e6cb';
                    statusMessage.innerHTML = '✅ <strong>Phone Verified:</strong> Your phone number has been verified from the booking process. No OTP verification required.';
                    console.log('💬 Status message updated: Verified');
                } else if (currentPhone && currentPhone.length >= 10) {
                    statusMessage.style.background = '#fff3cd';
                    statusMessage.style.color = '#856404';
                    statusMessage.style.borderColor = '#ffeaa7';
                    statusMessage.innerHTML = '📱 <strong>Phone Verification:</strong> Your phone number will be verified when you submit this form.';
                    console.log('💬 Status message updated: Verification required');
                }
            }
        }

        // Monitor phone input changes
        if (phoneInput) {
            phoneInput.addEventListener('input', function () {
                // Add slight delay to allow for proper phone number formatting
                setTimeout(updateVerificationStatus, 100);
            });
            phoneInput.addEventListener('blur', updateVerificationStatus);

            // Initial check on page load (with delay to ensure OTP system is loaded)
            setTimeout(function () {
                updateVerificationStatus();
            }, 500);
        }
    });
</script>

<?php if ($success_message): ?>
    <!-- Meta Pixel Contact Tracking -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof fbq !== 'undefined') {
                // Track Contact event for successful form submission
                fbq('track', 'Contact', {
                    content_name: 'Contact Form Submission',
                    content_category: 'Customer Support',
                    source: 'contact_page'
                });

                console.log('📊 Meta Pixel: Contact form success tracked');
            }
        });
    </script>
<?php endif; ?>

<?php endLayout(); ?>