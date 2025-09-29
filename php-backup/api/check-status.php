<?php
// Start session for email deduplication tracking
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database-based deduplication functions for Salesforce submissions
require_once '../DatabaseHandler.php';

/**
 * Check if transaction has already been sent to Salesforce
 * @param string $txnid Transaction ID
 * @param string $formType Form type (book_now, test_ride, contact)
 * @param string $submissionType Submission type (success, pending, failed)
 * @return bool True if already sent, false otherwise
 */
function hasTransactionBeenSentToSalesforce($txnid, $formType = 'book_now', $submissionType = 'success') {
    try {
        $dbHandler = new DatabaseHandler();
        return $dbHandler->hasSalesforceSubmission($txnid, $formType, $submissionType);
    } catch (Exception $e) {
        error_log("Failed to check Salesforce submission status: " . $e->getMessage());
        return false; // Default to allow submission on error
    }
}

/**
 * Mark transaction as sent to Salesforce
 * @param string $txnid Transaction ID
 * @param string $formType Form type (book_now, test_ride, contact)
 * @param string $submissionType Submission type (success, pending, failed)
 * @param string $customerEmail Customer email
 * @param string $customerPhone Customer phone
 * @param string $helpType Contact form help type (optional)
 * @param array $salesforceResponse Salesforce API response (optional)
 * @return bool True on success
 */
function markTransactionSentToSalesforce($txnid, $formType, $submissionType, $customerEmail, $customerPhone, $helpType = null, $salesforceResponse = null) {
    try {
        $dbHandler = new DatabaseHandler();
        return $dbHandler->recordSalesforceSubmission($txnid, $formType, $submissionType, $customerEmail, $customerPhone, $helpType, $salesforceResponse);
    } catch (Exception $e) {
        error_log("Failed to record Salesforce submission: " . $e->getMessage());
        return false;
    }
}

// SALESFORCE SUBMISSION CONTROL FLAG
// Set to true to send ALL payment statuses (failed, success, pending) to Salesforce
// Set to false to send ONLY successful payments to Salesforce (default behavior)
$SEND_ALL_PAYMENTS_TO_SALESFORCE = true;

require_once __DIR__ . '/../vendor/autoload.php';
require_once '../EmailHandler.php';
require_once '../SalesforceService.php';
require_once '../EmailNotificationsMigration.php';

// Auto-ensure email notifications table exists
EmailNotificationsMigration::ensureTableExists();

/**
 * Send transaction status notification emails to admin and customer
 * @param string $status 'success' or 'failure'
 * @param string $txnid Transaction ID
 * @param array $details Customer and transaction details
 * @param array $config App config
 */
function sendTransactionStatusEmails($status, $txnid, $details, $config)
{
    $emailHandler = new EmailHandler();
    if ($status === 'success') {
        // Admin
        $admin_subject = "[KineticEV] Booking Success: $txnid";
        $admin_html = (function ($txnid, $details) {
            ob_start();
            include __DIR__ . '/../email-templates/transaction-success-admin.tpl.php';
            return ob_get_clean();
        })($txnid, $details);
        $admin_to = $config['email']['payment']['recipients'] ?? ['info@kineticev.in'];
        $admin_from = $config['email']['payment']['admin_from_email'] ?? ($config['aws_ses']['from_email'] ?? 'info@kineticev.in');

        // Send to each admin recipient individually (AWS SES requirement)
        if (is_array($admin_to)) {
            foreach ($admin_to as $recipient) {
                $emailHandler->sendEmail($recipient, $admin_subject, $admin_html, $admin_from, true);
            }
        } else {
            $emailHandler->sendEmail($admin_to, $admin_subject, $admin_html, $admin_from, true);
        }

        // Customer
        $customer_subject = "Your Booking is Confirmed: $txnid";
        $customer_html = (function ($txnid, $details) {
            ob_start();
            include __DIR__ . '/../email-templates/transaction-success-customer.tpl.php';
            return ob_get_clean();
        })($txnid, $details);
        $customer_from = $config['aws_ses']['from_email'] ?? 'info@kineticev.in';
        $emailHandler->sendEmail($details['email'], $customer_subject, $customer_html, $customer_from, true);
    } else {
        // Admin
        $admin_subject = "[KineticEV] Booking Failure: $txnid";
        $admin_html = (function ($txnid, $details) {
            ob_start();
            include __DIR__ . '/../email-templates/transaction-failure-admin.tpl.php';
            return ob_get_clean();
        })($txnid, $details);
        $admin_to = $config['email']['payment']['recipients'] ?? ['info@kineticev.in'];
        $admin_from = $config['email']['payment']['admin_from_email'] ?? ($config['aws_ses']['from_email'] ?? 'info@kineticev.in');

        // Send to each admin recipient individually (AWS SES requirement)
        if (is_array($admin_to)) {
            foreach ($admin_to as $recipient) {
                $emailHandler->sendEmail($recipient, $admin_subject, $admin_html, $admin_from, true);
            }
        } else {
            $emailHandler->sendEmail($admin_to, $admin_subject, $admin_html, $admin_from, true);
        }

        // Customer
        $customer_subject = "Your Booking Failed: $txnid";
        $customer_html = (function ($txnid, $details) {
            ob_start();
            include __DIR__ . '/../email-templates/transaction-failure-customer.tpl.php';
            return ob_get_clean();
        })($txnid, $details);
        $customer_from = $config['aws_ses']['from_email'] ?? 'info@kineticev.in';
        $emailHandler->sendEmail($details['email'], $customer_subject, $customer_html, $customer_from, true);
    }
}
// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Include required files
require_once '../DatabaseHandler.php';
require_once '../Logger.php';

// Load configuration
$config = include '../config.php';

// Initialize logger
$logger = Logger::getInstance();

// Add 3 second delay to allow PhonePe to process the transaction
usleep(3000000); // 3 seconds = 3,000,000 microseconds

// Get transaction ID from URL parameters
$txnid = isset($_GET['txnid']) ? htmlspecialchars($_GET['txnid'], ENT_QUOTES, 'UTF-8') : null;

// DEBUG: Log all incoming data to understand what's being received
$logger->info("=== DEBUGGING URL PARAMETERS ===", [
    'REQUEST_URI' => $_SERVER['REQUEST_URI'] ?? 'not-set',
    'QUERY_STRING' => $_SERVER['QUERY_STRING'] ?? 'not-set',
    'GET_PARAMS' => $_GET,
    'POST_PARAMS' => $_POST,
    'REQUEST_METHOD' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
    'HTTP_REFERER' => $_SERVER['HTTP_REFERER'] ?? 'not-set',
    'extracted_txnid' => $txnid,
    'txnid_empty' => empty($txnid),
    'GET_txnid_isset' => isset($_GET['txnid']),
    'GET_txnid_raw' => $_GET['txnid'] ?? 'not-found'
], 'payment-flow.log');

// Try alternative parameter names that PhonePe might use
$alternativeTxnid = null;
$possibleParams = ['txnid', 'transactionId', 'merchantOrderId', 'orderId', 'merchant_order_id', 'transaction_id'];
foreach ($possibleParams as $param) {
    if (isset($_GET[$param]) && !empty($_GET[$param])) {
        $alternativeTxnid = $_GET[$param];
        $logger->info("Found transaction ID in parameter: $param", [
            'parameter' => $param,
            'value' => $alternativeTxnid
        ], 'payment-flow.log');
        break;
    }
}

// Use alternative if main txnid is empty
if (empty($txnid) && !empty($alternativeTxnid)) {
    $txnid = htmlspecialchars($alternativeTxnid, ENT_QUOTES, 'UTF-8');
    $logger->info("Using alternative parameter for txnid", [
        'original_txnid' => $_GET['txnid'] ?? 'not-found',
        'alternative_txnid' => $txnid
    ], 'payment-flow.log');
}

$logger->info('=== STATUS CHECK STARTED BY AKSHAY ===', [
    'txnid' => $txnid,
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
    'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown'
], 'payment-flow.log');

// Log the start of status checking process
$logger->info("=== STATUS CHECK STARTED ===", [
    'txnid' => $txnid,
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
    'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown'
], 'payment-flow.log');

// Load PhonePe configuration from config
$clientId = $config['phonepe']['clientId'];
$clientVersion = $config['phonepe']['clientVersion'];
$clientSecret = $config['phonepe']['clientSecret'];
$env = $config['phonepe']['env'];

// Log configuration being used
$logger->info("Using PhonePe configuration from config.php", [
    'client_id' => $clientId,
    'environment' => $env,
    'client_version' => $clientVersion
], 'payment-flow.log');


/*
 * API to get access token from PhonePe
 * This script retrieves an access token using client credentials
 */
// Get auth URL from config
$authUrl = $config['phonepe']['api_base_url']['auth'];

$logger->info("Starting OAuth authentication request", [
    'auth_url' => $authUrl
], 'payment-flow.log');

$curlauth = curl_init();

curl_setopt_array($curlauth, array(
    CURLOPT_URL => $authUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30, // 30 second timeout instead of unlimited
    CURLOPT_CONNECTTIMEOUT => 10, // 10 second connection timeout
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_POSTFIELDS => 'client_id=' . $clientId . '&client_version=1&client_secret=' . $clientSecret . '&grant_type=client_credentials',
    CURLOPT_HTTPHEADER => array(
        'Content-Type: application/x-www-form-urlencoded'
    ),
    // Security: Verify SSL certificates
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2,
    // Add retry and error handling options
    CURLOPT_USERAGENT => 'KineticEV-PHP/1.0',
    CURLOPT_FRESH_CONNECT => true,
    CURLOPT_FORBID_REUSE => true,
));

$authResponse = curl_exec($curlauth);
$httpCodeAuth = curl_getinfo($curlauth, CURLINFO_HTTP_CODE);
$curlErrorAuth = curl_error($curlauth);
curl_close($curlauth);

// Log authentication response with more details
$logger->info("OAuth authentication response received", [
    'http_code' => $httpCodeAuth,
    'response_length' => strlen($authResponse),
    'response_exists' => !empty($authResponse),
    'curl_error' => $curlErrorAuth ?: 'none'
], 'payment-flow.log');

// Log first 200 characters of response for debugging
if ($authResponse) {
    $logger->info("OAuth response preview", [
        'response_preview' => substr($authResponse, 0, 200)
    ], 'payment-flow.log');
}


// Check if first request was successful
if ($httpCodeAuth === 200 && $authResponse) {
    $logger->info("Authentication successful, parsing response", 'payment-flow.log');
    $dataAuth = json_decode($authResponse, true);

    if (json_last_error() === JSON_ERROR_NONE && isset($dataAuth['access_token'])) {
        $accessToken = $dataAuth['access_token'];
        $logger->info("Access token obtained successfully", [
            'token_length' => strlen($accessToken),
            'token_prefix' => substr($accessToken, 0, 10) . '...'
        ], 'payment-flow.log');

        // Get status URL from config and replace placeholder with txnid
        $statusUrlTemplate = $config['phonepe']['api_base_url']['status'];
        $statusUrl = str_replace('{#merchantOrderId#}', $txnid, $statusUrlTemplate);

        $logger->info("Starting payment status check request", [
            'status_url' => $statusUrl,
            'txnid' => $txnid
        ], 'payment-flow.log');

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $statusUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30, // 30 second timeout instead of unlimited
            CURLOPT_CONNECTTIMEOUT => 10, // 10 second connection timeout
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: O-Bearer ' . $accessToken
            ),
            // Security: Verify SSL certificates
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            // Add retry and error handling options
            CURLOPT_USERAGENT => 'KineticEV-PHP/1.0',
            CURLOPT_FRESH_CONNECT => true,
            CURLOPT_FORBID_REUSE => true,
        ));

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curlError = curl_error($curl);
        curl_close($curl);

        // Log payment status response with comprehensive details
        $logger->info("Payment status response received", [
            'http_code' => $httpCode,
            'response_length' => strlen($response),
            'response_exists' => !empty($response),
            'curl_error' => $curlError ?: 'none'
        ], 'payment-flow.log');

        // Log response preview for debugging
        if ($response) {
            $logger->info("Payment status response preview", [
                'response_preview' => substr($response, 0, 500)
            ], 'payment-flow.log');
        }

        if ($httpCode === 200 && $response) {
            $logger->info("Payment status response successful, parsing data", 'payment-flow.log');
            $data = json_decode($response, true);

            // Check for JSON parsing errors
            if (json_last_error() !== JSON_ERROR_NONE) {
                $logger->error("JSON parsing error in payment status response", [
                    'json_error' => json_last_error_msg(),
                    'response_sample' => substr($response, 0, 200)
                ], 'payment-flow.log');
                echo '<!DOCTYPE html>
                    <html>
                    <head>
                        <title>Data Error - KineticEV</title>
                        <style>
                            body { 
                                font-family: Arial, sans-serif; 
                                text-align: center; 
                                padding: 50px; 
                                background-color: #f8f9fa;
                            }
                            .error-container { 
                                max-width: 600px; 
                                margin: 0 auto; 
                                background: white; 
                                padding: 40px; 
                                border-radius: 10px; 
                                box-shadow: 0 4px 6px rgba(0,0,0,0.1);
                            }
                            .error-icon { 
                                font-size: 64px; 
                                color: #dc3545; 
                                margin-bottom: 20px; 
                            }
                            .error-title { 
                                color: #dc3545; 
                                font-size: 28px; 
                                margin-bottom: 20px; 
                                font-weight: bold;
                            }
                            .error-message { 
                                font-size: 18px; 
                                color: #666; 
                                margin-bottom: 30px; 
                                line-height: 1.5;
                            }
                            .redirect-info { 
                                font-size: 16px; 
                                color: #007bff; 
                                margin-bottom: 20px;
                            }
                            .countdown { 
                                font-size: 24px; 
                                color: #007bff; 
                                font-weight: bold;
                            }
                            .btn-retry { 
                                display: inline-block; 
                                background: #007bff; 
                                color: white; 
                                padding: 12px 30px; 
                                text-decoration: none; 
                                border-radius: 5px; 
                                margin-top: 20px;
                                font-weight: bold;
                            }
                            .btn-retry:hover { 
                                background: #0056b3; 
                            }
                        </style>
                    </head>
                    <body>
                        <div class="error-container">
                            <div class="error-icon">⚠️</div>
                            <div class="error-title">Data Processing Error</div>
                            <div class="error-message">
                                There was an issue processing the payment status data. Please try again or contact support if the problem persists.
                            </div>
                            <div class="redirect-info">
                                Click the button below to return to the booking page.
                            </div>
                            <a href="/book-now" class="btn-retry">Try Again Now</a>
                        </div>
                    </body>
                    </html>';
                exit;
            }

            // Process the payment status response
            if (isset($data['state'])) {
                $status = $data['state'];
                $logger->info("Payment status retrieved", [
                    'status' => $status,
                    'txnid' => $txnid,
                    'full_response' => $data
                ], 'payment-flow.log');
            } elseif (isset($data['status'])) {
                // Try alternative field name
                $status = $data['status'];
                $logger->info("Payment status retrieved from 'status' field", [
                    'status' => $status,
                    'txnid' => $txnid,
                    'full_response' => $data
                ], 'payment-flow.log');
            } elseif (isset($data['transaction_status'])) {
                // Try another alternative field name
                $status = $data['transaction_status'];
                $logger->info("Payment status retrieved from 'transaction_status' field", [
                    'status' => $status,
                    'txnid' => $txnid,
                    'full_response' => $data
                ], 'payment-flow.log');
            } else {
                // No recognizable status field found
                $logger->error("No recognizable status field in PhonePe response", [
                    'txnid' => $txnid,
                    'available_fields' => array_keys($data),
                    'full_response' => $data
                ], 'payment-flow.log');
                
                // Set status as PENDING and continue processing
                $status = 'PENDING';
                $logger->info("Setting status to PENDING due to missing status field", [
                    'txnid' => $txnid,
                    'reason' => 'missing_status_field'
                ], 'payment-flow.log');
            }

            if (isset($status)) {
                // Handle different payment statuses
                if ($status === 'COMPLETED') {
                    // Update transaction status in database
                    $dbHandler = null;
                    try {
                        $dbHandler = new DatabaseHandler();
                        $updateResult = $dbHandler->updateTransactionStatus($txnid, 'COMPLETED', $data);

                        if ($updateResult) {
                            $logger->success("Transaction status updated successfully in database", [
                                'txnid' => $txnid,
                                'new_status' => 'COMPLETED',
                                'payment_data_stored' => true
                            ], 'payment-flow.log');
                        } else {
                            $logger->error("Failed to update transaction status in database", [
                                'txnid' => $txnid,
                                'attempted_status' => 'COMPLETED'
                            ], 'payment-flow.log');
                        }
                    } catch (Exception $e) {
                        $logger->error("Database update error for completed transaction", [
                            'txnid' => $txnid,
                            'error' => $e->getMessage(),
                            'status' => 'COMPLETED'
                        ], 'payment-flow.log');
                        // Continue with redirect even if database update fails
                    }

                    // Send transaction data to Salesforce after successful payment verification
                    try {
                        $salesforceService = new SalesforceService($logger, $config);

                        // Get transaction details for Salesforce
                        $dbHandler = new DatabaseHandler();
                        $transactionDetails = $dbHandler->getTransaction($txnid);

                        if ($transactionDetails) {
                            $paymentData = [
                                'full_name' => $transactionDetails['firstname'] ?? '',
                                'phone' => $transactionDetails['phone'] ?? '',
                                'email' => $transactionDetails['email'] ?? '',
                                'city' => $transactionDetails['city'] ?? '',
                                'state' => $transactionDetails['state'] ?? '',
                                'address_1' => $transactionDetails['address'] ?? '',
                                'pincode' => $transactionDetails['pincode'] ?? '',
                                'variant_id' => $transactionDetails['variant'] ?? '',
                                'color_name' => $transactionDetails['color'] ?? '',
                                'amount' => $transactionDetails['amount'] ?? '',
                                'transaction_id' => $txnid,
                                'payment_status' => 'COMPLETED',
                                'form_type' => 'book_now'
                            ];

                            // Check if transaction was already sent to Salesforce to prevent duplicates
                            if (!hasTransactionBeenSentToSalesforce($txnid, 'book_now', 'success')) {
                                $salesforceResult = $salesforceService->sendToSalesforce($paymentData, 'book_now');

                                if ($salesforceResult['success']) {
                                    // Mark transaction as successfully sent
                                    markTransactionSentToSalesforce(
                                        $txnid, 
                                        'book_now', 
                                        'success', 
                                        $transactionDetails['email'] ?? '', 
                                        $transactionDetails['phone'] ?? '', 
                                        null, 
                                        $salesforceResult
                                    );
                                    
                                    $logger->info('[PAYMENT_SUCCESS] Successfully sent to Salesforce', [
                                        'txnid' => $txnid,
                                        'salesforce_result' => $salesforceResult,
                                        'timestamp' => (new DateTime('now', new DateTimeZone('Asia/Kolkata')))->format('Y-m-d H:i:s')
                                    ], 'payment-flow.log');
                                } else {
                                    $logger->warning('[PAYMENT_SUCCESS] Failed to send to Salesforce', [
                                        'txnid' => $txnid,
                                        'salesforce_error' => $salesforceResult,
                                        'timestamp' => (new DateTime('now', new DateTimeZone('Asia/Kolkata')))->format('Y-m-d H:i:s')
                                    ], 'payment-flow.log');
                                }
                            } else {
                                $logger->info('[SALESFORCE_DUPLICATE_PREVENTED] SUCCESS transaction already sent to Salesforce', [
                                    'txnid' => $txnid,
                                    'payment_status' => 'Success',
                                    'prevention_time' => (new DateTime('now', new DateTimeZone('Asia/Kolkata')))->format('Y-m-d H:i:s')
                                ], 'payment-flow.log');
                            }
                        } else {
                            $logger->warning('[PAYMENT_SUCCESS] Transaction details not found for Salesforce submission', [
                                'txnid' => $txnid
                            ], 'payment-flow.log');
                        }
                    } catch (Exception $e) {
                        $logger->error('[PAYMENT_SUCCESS] Salesforce integration error', [
                            'txnid' => $txnid,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                            'timestamp' => (new DateTime('now', new DateTimeZone('Asia/Kolkata')))->format('Y-m-d H:i:s')
                        ], 'payment-flow.log');
                        // Continue with email process even if Salesforce fails
                    }

                    // Send success notification emails
                    try {
                        // Reuse the database handler or create new one if previous failed
                        if (!$dbHandler) {
                            $dbHandler = new DatabaseHandler();
                        }
                        $transactionDetails = $dbHandler->getTransaction($txnid);

                        if ($transactionDetails) {
                            // Format amount display (amount is already stored in rupees)
                            $transactionDetails['amount'] = number_format($transactionDetails['amount'], 2);

                            // Robust email deduplication check (session + database)
                            $email_sent_key = "email_sent_{$txnid}_success";
                            $email_already_sent = isset($_SESSION[$email_sent_key]) || EmailNotificationsMigration::isEmailSent($txnid, 'success');
                            
                            if (!$email_already_sent) {
                                $logger->info("Sending transaction success emails", [
                                    'txnid' => $txnid,
                                    'customer_email' => $transactionDetails['email']
                                ], 'payment-flow.log');

                                sendTransactionStatusEmails('success', $txnid, $transactionDetails, $config);
                                
                                // Record in both session and database
                                $_SESSION[$email_sent_key] = time();
                                EmailNotificationsMigration::recordEmailSent($txnid, 'success', [
                                    'admin' => $config['admin_email'] ?? 'info@kineticev.in',
                                    'customer' => $transactionDetails['email']
                                ]);

                                $logger->success("Transaction success emails sent successfully", [
                                    'txnid' => $txnid
                                ], 'payment-flow.log');
                            } else {
                                $logger->info("Duplicate success email prevented", [
                                    'txnid' => $txnid,
                                    'prevented_by' => isset($_SESSION[$email_sent_key]) ? 'session' : 'database',
                                    'first_sent_at' => isset($_SESSION[$email_sent_key]) ? date('Y-m-d H:i:s', $_SESSION[$email_sent_key]) : 'database_record'
                                ], 'payment-flow.log');
                            }
                        } else {
                            $logger->error("Transaction details not found in database for email", [
                                'txnid' => $txnid
                            ], 'payment-flow.log');
                        }
                    } catch (Exception $e) {
                        $logger->error("Failed to send transaction success emails", [
                            'txnid' => $txnid,
                            'error' => $e->getMessage()
                        ], 'payment-flow.log');
                        // Continue with redirect even if email fails
                    }

                    $logger->success("Payment successful, redirecting to thank-you page", [
                        'txnid' => $txnid
                    ], 'payment-flow.log');
                    // Redirect to thank-you page
                    header('Location: /thank-you?txnid=' . urlencode($txnid));
                    exit;
                } elseif ($status === 'FAILED') {
                    // Update transaction status in database for failed payment
                    $dbHandler = null;
                    try {
                        $dbHandler = new DatabaseHandler();
                        $updateResult = $dbHandler->updateTransactionStatus($txnid, 'FAILED', $data);

                        if ($updateResult) {
                            $logger->success("Failed transaction status updated successfully in database", [
                                'txnid' => $txnid,
                                'new_status' => 'FAILED',
                                'payment_data_stored' => true
                            ], 'payment-flow.log');
                        } else {
                            $logger->error("Failed to update failed transaction status in database", [
                                'txnid' => $txnid,
                                'attempted_status' => 'FAILED'
                            ], 'payment-flow.log');
                        }
                    } catch (Exception $e) {
                        $logger->error("Database update error for failed transaction", [
                            'txnid' => $txnid,
                            'error' => $e->getMessage(),
                            'status' => 'FAILED'
                        ], 'payment-flow.log');
                        // Continue with redirect even if database update fails
                    }

                    // Send failure notification emails
                    try {
                        // Reuse the database handler or create new one if previous failed
                        if (!$dbHandler) {
                            $dbHandler = new DatabaseHandler();
                        }
                        $transactionDetails = $dbHandler->getTransaction($txnid);

                        if ($transactionDetails) {
                            // Format amount display (amount is already stored in rupees)
                            $transactionDetails['amount'] = number_format($transactionDetails['amount'], 2);

                            // Robust email deduplication check (session + database)
                            $email_sent_key = "email_sent_{$txnid}_failure";
                            $email_already_sent = isset($_SESSION[$email_sent_key]) || EmailNotificationsMigration::isEmailSent($txnid, 'failure');
                            
                            if (!$email_already_sent) {
                                $logger->info("Sending transaction failure emails", [
                                    'txnid' => $txnid,
                                    'customer_email' => $transactionDetails['email']
                                ], 'payment-flow.log');

                                sendTransactionStatusEmails('failure', $txnid, $transactionDetails, $config);
                                
                                // Record in both session and database
                                $_SESSION[$email_sent_key] = time();
                                EmailNotificationsMigration::recordEmailSent($txnid, 'failure', [
                                    'admin' => $config['admin_email'] ?? 'info@kineticev.in',
                                    'customer' => $transactionDetails['email']
                                ]);

                                $logger->success("Transaction failure emails sent successfully", [
                                    'txnid' => $txnid
                                ], 'payment-flow.log');
                            } else {
                                $logger->info("Duplicate failure email prevented", [
                                    'txnid' => $txnid,
                                    'prevented_by' => isset($_SESSION[$email_sent_key]) ? 'session' : 'database',
                                    'first_sent_at' => isset($_SESSION[$email_sent_key]) ? date('Y-m-d H:i:s', $_SESSION[$email_sent_key]) : 'database_record'
                                ], 'payment-flow.log');
                            }
                        } else {
                            $logger->error("Transaction details not found in database for failure email", [
                                'txnid' => $txnid
                            ], 'payment-flow.log');
                        }
                    } catch (Exception $e) {
                        $logger->error("Failed to send transaction failure emails", [
                            'txnid' => $txnid,
                            'error' => $e->getMessage()
                        ], 'payment-flow.log');
                        // Continue with redirect even if email fails
                    }

                    // Send transaction data to Salesforce for failed payment (only if flag is enabled)
                    if ($SEND_ALL_PAYMENTS_TO_SALESFORCE) {
                        try {
                            $salesforceService = new SalesforceService($logger, $config);

                            // Get transaction details for Salesforce (reuse if available)
                            if (!$dbHandler) {
                                $dbHandler = new DatabaseHandler();
                            }
                            $transactionDetails = $dbHandler->getTransaction($txnid);

                            if ($transactionDetails) {
                                $paymentData = [
                                    'full_name' => $transactionDetails['firstname'] ?? '',
                                    'phone' => $transactionDetails['phone'] ?? '',
                                    'email' => $transactionDetails['email'] ?? '',
                                    'city' => $transactionDetails['city'] ?? '',
                                    'state' => $transactionDetails['state'] ?? '',
                                    'address_1' => $transactionDetails['address'] ?? '',
                                    'pincode' => $transactionDetails['pincode'] ?? '',
                                    'variant_id' => $transactionDetails['variant'] ?? '',
                                    'color_name' => $transactionDetails['color'] ?? '',
                                    'amount' => $transactionDetails['amount'] ?? '',
                                    'transaction_id' => $txnid,
                                    'payment_status' => 'FAILED',
                                    'form_type' => 'book_now'
                                ];

                                // Check if transaction was already sent to Salesforce to prevent duplicates
                                if (!hasTransactionBeenSentToSalesforce($txnid, 'book_now', 'failed')) {
                                    $salesforceResult = $salesforceService->sendToSalesforce($paymentData, 'book_now');

                                    if ($salesforceResult['success']) {
                                        // Mark transaction as successfully sent
                                        markTransactionSentToSalesforce(
                                            $txnid, 
                                            'book_now', 
                                            'failed', 
                                            $transactionDetails['email'] ?? '', 
                                            $transactionDetails['phone'] ?? '', 
                                            null, 
                                            $salesforceResult
                                        );
                                        
                                        $logger->info('[PAYMENT_FAILURE] Successfully sent to Salesforce', [
                                            'txnid' => $txnid,
                                            'salesforce_result' => $salesforceResult,
                                            'timestamp' => (new DateTime('now', new DateTimeZone('Asia/Kolkata')))->format('Y-m-d H:i:s')
                                        ], 'payment-flow.log');
                                    } else {
                                        $logger->warning('[PAYMENT_FAILURE] Failed to send to Salesforce', [
                                            'txnid' => $txnid,
                                            'salesforce_error' => $salesforceResult,
                                            'timestamp' => (new DateTime('now', new DateTimeZone('Asia/Kolkata')))->format('Y-m-d H:i:s')
                                        ], 'payment-flow.log');
                                    }
                                } else {
                                    $logger->info('[SALESFORCE_DUPLICATE_PREVENTED] FAILED transaction already sent to Salesforce', [
                                        'txnid' => $txnid,
                                        'payment_status' => 'Failed',
                                        'prevention_time' => (new DateTime('now', new DateTimeZone('Asia/Kolkata')))->format('Y-m-d H:i:s')
                                    ], 'payment-flow.log');
                                }
                            } else {
                                $logger->warning('[PAYMENT_FAILURE] Transaction details not found for Salesforce submission', [
                                    'txnid' => $txnid
                                ], 'payment-flow.log');
                            }
                        } catch (Exception $e) {
                            $logger->error('[PAYMENT_FAILURE] Salesforce integration error', [
                                'txnid' => $txnid,
                                'error' => $e->getMessage(),
                                'trace' => $e->getTraceAsString()
                            ], 'payment-flow.log');
                            // Continue with redirect even if Salesforce fails
                        }
                    } else {
                        // Log rejected failed payment with full details
                        try {
                            if (!$dbHandler) {
                                $dbHandler = new DatabaseHandler();
                            }
                            $transactionDetails = $dbHandler->getTransaction($txnid);
                            
                            $rejectedPaymentData = [
                                'txnid' => $txnid,
                                'status' => 'REJECTED',
                                'payment_status' => 'FAILED',
                                'form_type' => 'book_now',
                                'rejection_reason' => 'Salesforce flag disabled for failed payments',
                                'flag_setting' => $SEND_ALL_PAYMENTS_TO_SALESFORCE,
                                'customer_details' => $transactionDetails ? [
                                    'full_name' => $transactionDetails['firstname'] ?? '',
                                    'phone' => $transactionDetails['phone'] ?? '',
                                    'email' => $transactionDetails['email'] ?? '',
                                    'city' => $transactionDetails['city'] ?? '',
                                    'state' => $transactionDetails['state'] ?? '',
                                    'address_1' => $transactionDetails['address'] ?? '',
                                    'pincode' => $transactionDetails['pincode'] ?? '',
                                    'variant_id' => $transactionDetails['variant'] ?? '',
                                    'color_name' => $transactionDetails['color'] ?? '',
                                    'amount' => $transactionDetails['amount'] ?? ''
                                ] : 'Transaction details not found',
                                'timestamp' => (new DateTime('now', new DateTimeZone('Asia/Kolkata')))->format('Y-m-d H:i:s')
                            ];
                            
                            $logger->info('[PAYMENT_FAILURE] Salesforce submission REJECTED', $rejectedPaymentData, 'salesforce_logs.txt');
                        } catch (Exception $e) {
                            $logger->error('[PAYMENT_FAILURE] Error logging rejected payment', [
                                'txnid' => $txnid,
                                'error' => $e->getMessage()
                            ], 'payment-flow.log');
                        }
                    }

                    $logger->info("Payment failed, redirecting to payment-failed page", [
                        'txnid' => $txnid
                    ], 'payment-flow.log');
                    // Redirect to payment failed page
                    header('Location: /');
                    exit;
                } else {
                    // Log detailed information about non-completed status
                    $logger->info("Payment status is not COMPLETED", [
                        'status' => $status,
                        'txnid' => $txnid,
                        'expected' => 'COMPLETED',
                        'actual_response' => $data
                    ], 'payment-flow.log');

                    // Update transaction status in database for pending/unknown status
                    try {
                        $dbHandler = new DatabaseHandler();
                        $updateResult = $dbHandler->updateTransactionStatus($txnid, 'PENDING', $data);

                        if ($updateResult) {
                            $logger->info("Transaction status updated to PENDING in database", [
                                'txnid' => $txnid,
                                'new_status' => 'PENDING',
                                'payment_data_stored' => true,
                                'original_status' => $status
                            ], 'payment-flow.log');
                        } else {
                            $logger->error("Failed to update transaction to PENDING status", [
                                'txnid' => $txnid,
                                'attempted_status' => 'PENDING',
                                'original_status' => $status
                            ], 'payment-flow.log');
                        }

                        // Send transaction data to Salesforce for pending payment (only if flag is enabled)
                        if ($SEND_ALL_PAYMENTS_TO_SALESFORCE) {
                            try {
                                $salesforceService = new SalesforceService($logger, $config);

                                // Get transaction details for Salesforce (reuse if available)
                                if (!$dbHandler) {
                                    $dbHandler = new DatabaseHandler();
                                }
                                $transactionDetails = $dbHandler->getTransaction($txnid);

                                if ($transactionDetails) {
                                    $paymentData = [
                                        'full_name' => $transactionDetails['firstname'] ?? '',
                                        'phone' => $transactionDetails['phone'] ?? '',
                                        'email' => $transactionDetails['email'] ?? '',
                                        'city' => $transactionDetails['city'] ?? '',
                                        'state' => $transactionDetails['state'] ?? '',
                                        'address_1' => $transactionDetails['address'] ?? '',
                                        'pincode' => $transactionDetails['pincode'] ?? '',
                                        'variant_id' => $transactionDetails['variant'] ?? '',
                                        'color_name' => $transactionDetails['color'] ?? '',
                                        'amount' => $transactionDetails['amount'] ?? '',
                                        'transaction_id' => $txnid,
                                    'payment_status' => 'PENDING',
                                    'form_type' => 'book_now'
                                ];

                                // Check if transaction was already sent to Salesforce to prevent duplicates
                                if (!hasTransactionBeenSentToSalesforce($txnid, 'book_now', 'pending')) {
                                    $salesforceResult = $salesforceService->sendToSalesforce($paymentData, 'book_now');

                                    if ($salesforceResult['success']) {
                                        // Mark transaction as successfully sent
                                        markTransactionSentToSalesforce(
                                            $txnid, 
                                            'book_now', 
                                            'pending', 
                                            $transactionDetails['email'] ?? '', 
                                            $transactionDetails['phone'] ?? '', 
                                            null, 
                                            $salesforceResult
                                        );
                                        
                                        $logger->info('[PAYMENT_PENDING] Successfully sent to Salesforce', [
                                            'txnid' => $txnid,
                                            'salesforce_result' => $salesforceResult,
                                            'timestamp' => (new DateTime('now', new DateTimeZone('Asia/Kolkata')))->format('Y-m-d H:i:s')
                                        ], 'payment-flow.log');
                                    } else {
                                        $logger->warning('[PAYMENT_PENDING] Failed to send to Salesforce', [
                                            'txnid' => $txnid,
                                            'salesforce_error' => $salesforceResult,
                                            'timestamp' => (new DateTime('now', new DateTimeZone('Asia/Kolkata')))->format('Y-m-d H:i:s')
                                        ], 'payment-flow.log');
                                    }
                                } else {
                                    $logger->info('[SALESFORCE_DUPLICATE_PREVENTED] PENDING transaction already sent to Salesforce', [
                                        'txnid' => $txnid,
                                        'payment_status' => 'Pending',
                                        'prevention_time' => (new DateTime('now', new DateTimeZone('Asia/Kolkata')))->format('Y-m-d H:i:s')
                                    ], 'payment-flow.log');
                                }
                                } else {
                                    $logger->warning('[PAYMENT_PENDING] Transaction details not found for Salesforce submission', [
                                        'txnid' => $txnid
                                    ], 'payment-flow.log');
                                }
                            } catch (Exception $e) {
                                $logger->error('[PAYMENT_PENDING] Salesforce integration error', [
                                    'txnid' => $txnid,
                                    'error' => $e->getMessage(),
                                    'trace' => $e->getTraceAsString()
                                ], 'payment-flow.log');
                                // Continue with redirect even if Salesforce fails
                            }
                        } else {
                            // Log rejected pending payment with full details
                            try {
                                if (!$dbHandler) {
                                    $dbHandler = new DatabaseHandler();
                                }
                                $transactionDetails = $dbHandler->getTransaction($txnid);
                                
                                $rejectedPaymentData = [
                                    'txnid' => $txnid,
                                    'status' => 'REJECTED',
                                    'payment_status' => 'PENDING',
                                    'form_type' => 'book_now',
                                    'rejection_reason' => 'Salesforce flag disabled for pending payments',
                                    'flag_setting' => $SEND_ALL_PAYMENTS_TO_SALESFORCE,
                                    'customer_details' => $transactionDetails ? [
                                        'full_name' => $transactionDetails['firstname'] ?? '',
                                        'phone' => $transactionDetails['phone'] ?? '',
                                        'email' => $transactionDetails['email'] ?? '',
                                        'city' => $transactionDetails['city'] ?? '',
                                        'state' => $transactionDetails['state'] ?? '',
                                        'address_1' => $transactionDetails['address'] ?? '',
                                        'pincode' => $transactionDetails['pincode'] ?? '',
                                        'variant_id' => $transactionDetails['variant'] ?? '',
                                        'color_name' => $transactionDetails['color'] ?? '',
                                        'amount' => $transactionDetails['amount'] ?? ''
                                    ] : 'Transaction details not found',
                                    'timestamp' => (new DateTime('now', new DateTimeZone('Asia/Kolkata')))->format('Y-m-d H:i:s')
                                ];
                                
                                $logger->info('[PAYMENT_PENDING] Salesforce submission REJECTED', $rejectedPaymentData, 'salesforce_logs.txt');
                            } catch (Exception $e) {
                                $logger->error('[PAYMENT_PENDING] Error logging rejected payment', [
                                    'txnid' => $txnid,
                                    'error' => $e->getMessage()
                                ], 'payment-flow.log');
                            }
                        }
                    } catch (Exception $e) {
                        $logger->info("Database update error for pending transaction", [
                            'txnid' => $txnid,
                            'error' => $e->getMessage(),
                            'status' => 'PENDING'
                        ]);
                    }

                    $logger->info("Payment status pending or unknown, showing status page", [
                        'status' => $status
                    ]);

                    // Show user-friendly status page for pending/unknown payments
                    echo '<!DOCTYPE html>
                        <html>
                        <head>
                            <title>Payment Status - KineticEV</title>
                            <style>
                                body { 
                                    font-family: Arial, sans-serif; 
                                    text-align: center; 
                                    padding: 50px; 
                                    background-color: #f8f9fa;
                                }
                                .status-container { 
                                    max-width: 600px; 
                                    margin: 0 auto; 
                                    background: white; 
                                    padding: 40px; 
                                    border-radius: 10px; 
                                    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
                                }
                                .status-icon { 
                                    font-size: 64px; 
                                    color: #ffc107; 
                                    margin-bottom: 20px; 
                                }
                                .status-title { 
                                    color: #ffc107; 
                                    font-size: 28px; 
                                    margin-bottom: 20px; 
                                    font-weight: bold;
                                }
                                .status-message { 
                                    font-size: 18px; 
                                    color: #666; 
                                    margin-bottom: 30px; 
                                    line-height: 1.5;
                                }
                                .transaction-info {
                                    background: #f8f9fa;
                                    padding: 20px;
                                    border-radius: 8px;
                                    margin: 20px 0;
                                    font-family: monospace;
                                }
                                .action-buttons {
                                    margin-top: 30px;
                                }
                                .btn { 
                                    display: inline-block; 
                                    padding: 12px 30px; 
                                    text-decoration: none; 
                                    border-radius: 5px; 
                                    margin: 10px;
                                    font-weight: bold;
                                    font-size: 16px;
                                }
                                .btn-primary { 
                                    background: #007bff; 
                                    color: white; 
                                }
                                .btn-primary:hover { 
                                    background: #0056b3; 
                                }
                                .btn-secondary { 
                                    background: #6c757d; 
                                    color: white; 
                                }
                                .btn-secondary:hover { 
                                    background: #545b62; 
                                }
                                .note {
                                    font-size: 14px;
                                    color: #666;
                                    margin-top: 20px;
                                    font-style: italic;
                                }
                            </style>
                        </head>
                        <body>
                            <div class="status-container">
                                <div class="status-icon">⏳</div>
                                <div class="status-title">Payment Status: ' . htmlspecialchars($status) . '</div>
                                <div class="status-message">
                                    Your payment is currently being processed or verification is pending. 
                                    This may take a few minutes to complete.
                                </div>
                                
                                <div class="transaction-info">
                                    <strong>Transaction ID:</strong> ' . htmlspecialchars($txnid) . '<br>
                                    <strong>Status:</strong> ' . htmlspecialchars($status) . '<br>
                                    <strong>Time:</strong> ' . (new DateTime('now', new DateTimeZone('Asia/Kolkata')))->format('Y-m-d H:i:s') . '
                                </div>

                                <div class="status-message">
                                    <strong>What happens next?</strong><br>
                                    • If payment is successful, you\'ll receive a confirmation email<br>
                                    • If payment fails, amount will be refunded within 5-7 working days<br>
                                    • You can retry booking if needed
                                </div>

                                <div class="action-buttons">
                                    <a href="/api/check-status?txnid=' . urlencode($txnid) . '" class="btn btn-primary">Check Status Again</a>
                                    <a href="/book-now" class="btn btn-secondary">New Booking</a>
                                </div>

                                <div class="note">
                                    If you continue to see this status after 10 minutes, please contact our support team.
                                </div>
                            </div>
                        </body>
                        </html>';
                    exit;
                }
            } else {
                $logger->info("Invalid response structure - missing state field", [
                    'response_keys' => array_keys($data),
                    'full_response' => $data
                ]);
                // Invalid response structure
                echo '<!DOCTYPE html>
                    <html>
                    <head>
                        <title>Invalid Response - KineticEV</title>
                        <style>
                            body { 
                                font-family: Arial, sans-serif; 
                                text-align: center; 
                                padding: 50px; 
                                background-color: #f8f9fa;
                            }
                            .error-container { 
                                max-width: 600px; 
                                margin: 0 auto; 
                                background: white; 
                                padding: 40px; 
                                border-radius: 10px; 
                                box-shadow: 0 4px 6px rgba(0,0,0,0.1);
                            }
                            .error-icon { 
                                font-size: 64px; 
                                color: #dc3545; 
                                margin-bottom: 20px; 
                            }
                            .error-title { 
                                color: #dc3545; 
                                font-size: 28px; 
                                margin-bottom: 20px; 
                                font-weight: bold;
                            }
                            .error-message { 
                                font-size: 18px; 
                                color: #666; 
                                margin-bottom: 30px; 
                                line-height: 1.5;
                            }
                            .redirect-info { 
                                font-size: 16px; 
                                color: #007bff; 
                                margin-bottom: 20px;
                            }
                            .countdown { 
                                font-size: 24px; 
                                color: #007bff; 
                                font-weight: bold;
                            }
                            .btn-retry { 
                                display: inline-block; 
                                background: #007bff; 
                                color: white; 
                                padding: 12px 30px; 
                                text-decoration: none; 
                                border-radius: 5px; 
                                margin-top: 20px;
                                font-weight: bold;
                            }
                            .btn-retry:hover { 
                                background: #0056b3; 
                            }
                        </style>
                    </head>
                    <body>
                        <div class="error-container">
                            <div class="error-icon">❓</div>
                            <div class="error-title">Invalid Response</div>
                            <div class="error-message">
                                The payment status response is incomplete. Please try checking again or contact support.
                            </div>
                            <div class="redirect-info">
                                Click the button below to return to the booking page.
                            </div>
                            <a href="/book-now" class="btn-retry">Try Again Now</a>
                        </div>
                    </body>
                    </html>';
                exit;
            }
        } else {
            $logger->info("Payment status request failed", [
                'http_code' => $httpCode,
                'response_length' => strlen($response),
                'curl_error' => curl_error($curl) ?? 'No curl error'
            ]);
            echo '<!DOCTYPE html>
                <html>
                <head>
                    <title>Status Check Error - KineticEV</title>
                    <style>
                        body { 
                            font-family: Arial, sans-serif; 
                            text-align: center; 
                            padding: 50px; 
                            background-color: #f8f9fa;
                        }
                        .error-container { 
                            max-width: 600px; 
                            margin: 0 auto; 
                            background: white; 
                            padding: 40px; 
                            border-radius: 10px; 
                            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
                        }
                        .error-icon { 
                            font-size: 64px; 
                            color: #dc3545; 
                            margin-bottom: 20px; 
                        }
                        .error-title { 
                            color: #dc3545; 
                            font-size: 28px; 
                            margin-bottom: 20px; 
                            font-weight: bold;
                        }
                        .error-message { 
                            font-size: 18px; 
                            color: #666; 
                            margin-bottom: 30px; 
                            line-height: 1.5;
                        }
                        .redirect-info { 
                            font-size: 16px; 
                            color: #007bff; 
                            margin-bottom: 20px;
                        }
                        .countdown { 
                            font-size: 24px; 
                            color: #007bff; 
                            font-weight: bold;
                        }
                        .btn-retry { 
                            display: inline-block; 
                            background: #007bff; 
                            color: white; 
                            padding: 12px 30px; 
                            text-decoration: none; 
                            border-radius: 5px; 
                            margin-top: 20px;
                            font-weight: bold;
                        }
                        .btn-retry:hover { 
                            background: #0056b3; 
                        }
                    </style>
                </head>
                <body>
                    <div class="error-container">
                        <div class="error-icon">🔌</div>
                        <div class="error-title">Connection Error</div>
                        <div class="error-message">
                            Unable to retrieve payment status from the gateway. Please check your internet connection and try again.
                        </div>
                        <div class="redirect-info">
                            Click the button below to return to the booking page.
                        </div>
                        <a href="/book-now" class="btn-retry">Try Again Now</a>
                    </div>
                </body>
                </html>';
            exit;
        }
    } else {
        $logger->error("Failed to parse OAuth response or missing access token", [
            'json_error' => json_last_error_msg(),
            'response_data' => $dataAuth ?? null,
            'has_access_token' => isset($dataAuth['access_token']) ? 'yes' : 'no'
        ], 'payment-flow.log');

        echo '<!DOCTYPE html>
            <html>
            <head>
                <title>Authentication Error - KineticEV</title>
                <style>
                    body { 
                        font-family: Arial, sans-serif; 
                        text-align: center; 
                        padding: 50px; 
                        background-color: #f8f9fa;
                    }
                    .error-container { 
                        max-width: 600px; 
                        margin: 0 auto; 
                        background: white; 
                        padding: 40px; 
                        border-radius: 10px; 
                        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
                    }
                    .error-icon { 
                        font-size: 64px; 
                        color: #dc3545; 
                        margin-bottom: 20px; 
                    }
                    .error-title { 
                        color: #dc3545; 
                        font-size: 28px; 
                        margin-bottom: 20px; 
                        font-weight: bold;
                    }
                    .error-message { 
                        font-size: 18px; 
                        color: #666; 
                        margin-bottom: 30px; 
                        line-height: 1.5;
                    }
                    .btn-retry { 
                        display: inline-block; 
                        background: #007bff; 
                        color: white; 
                        padding: 12px 30px; 
                        text-decoration: none; 
                        border-radius: 5px; 
                        margin-top: 20px;
                        font-weight: bold;
                    }
                    .btn-retry:hover { 
                        background: #0056b3; 
                    }
                </style>
            </head>
            <body>
                <div class="error-container">
                    <div class="error-icon">⚠️</div>
                    <div class="error-title">Authentication Error</div>
                    <div class="error-message">
                        We encountered a technical issue while connecting to our payment status system. 
                        This is usually a temporary problem.
                    </div>
                    <a href="/book-now" class="btn-retry">Try Again Now</a>
                </div>
            </body>
            </html>';
        exit;
    }
} else {
        $logger->error("Failed to parse OAuth response or missing access token", [
            'json_error' => json_last_error_msg(),
            'response_data' => $dataAuth,
            'has_access_token' => isset($dataAuth['access_token']) ? 'yes' : 'no'
        ], 'payment-flow.log');

        echo '<!DOCTYPE html>
            <html>
            <head>
                <title>Authentication Error - KineticEV</title>
                <style>
                    body { 
                        font-family: Arial, sans-serif; 
                        text-align: center; 
                        padding: 50px; 
                        background-color: #f8f9fa;
                    }
                    .error-container { 
                        max-width: 600px; 
                        margin: 0 auto; 
                        background: white; 
                        padding: 40px; 
                        border-radius: 10px; 
                        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
                    }
                    .error-icon { 
                        font-size: 64px; 
                        color: #dc3545; 
                        margin-bottom: 20px; 
                    }
                    .error-title { 
                        color: #dc3545; 
                        font-size: 28px; 
                        margin-bottom: 20px; 
                        font-weight: bold;
                    }
                    .error-message { 
                        font-size: 18px; 
                        color: #666; 
                        margin-bottom: 30px; 
                        line-height: 1.5;
                    }
                    .redirect-info { 
                        font-size: 16px; 
                        color: #007bff; 
                        margin-bottom: 20px;
                    }
                    .countdown { 
                        font-size: 24px; 
                        color: #007bff; 
                        font-weight: bold;
                    }
                    .btn-retry { 
                        display: inline-block; 
                        background: #007bff; 
                        color: white; 
                        padding: 12px 30px; 
                        text-decoration: none; 
                        border-radius: 5px; 
                        margin-top: 20px;
                        font-weight: bold;
                    }
                    .btn-retry:hover { 
                        background: #0056b3; 
                    }
                </style>
            </head>
            <body>
                <div class="error-container">
                    <div class="error-icon">⚠️</div>
                    <div class="error-title">Authentication Error</div>
                    <div class="error-message">
                        We encountered a technical issue while connecting to our payment status system. 
                        This is usually a temporary problem.
                    </div>
                    <div class="redirect-info">
                        Click the button below to return to the booking page.
                    </div>
                    <a href="/book-now" class="btn-retry">Try Again Now</a>
                </div>
            </body>
            </html>';
        exit;
    }
?>
