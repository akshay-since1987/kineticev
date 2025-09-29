<?php
require_once __DIR__ . '/../vendor/autoload.php';
// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');


require_once '../DatabaseHandler.php';
require_once '../Logger.php';
require_once '../EmailHandler.php';

// Load configuration
$config = include '../config.php';

// Initialize logger
$logger = Logger::getInstance();

$logger->info("=== PAYMENT PROCESS STARTED ===", [
    'timestamp' => (new DateTime('now', new DateTimeZone('Asia/Kolkata')))->format('Y-m-d H:i:s'),
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
], 'payment_logs.txt');

$logger->info("POST data received", $_POST, 'payment_logs.txt');

// Extract and sanitize input data FIRST
$firstname = filter_input(INPUT_POST, 'firstname', FILTER_SANITIZE_STRING);
$phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);
$email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
$address = filter_input(INPUT_POST, 'address', FILTER_SANITIZE_STRING);
$city = filter_input(INPUT_POST, 'city', FILTER_SANITIZE_STRING);
$state = filter_input(INPUT_POST, 'state', FILTER_SANITIZE_STRING);
$pincode = filter_input(INPUT_POST, 'pincode', FILTER_SANITIZE_STRING);
$ownedBefore = isset($_POST['ownedBefore']) ? 1 : 0;
$productinfo = filter_input(INPUT_POST, 'variant', FILTER_SANITIZE_STRING);
$color = filter_input(INPUT_POST, 'color', FILTER_SANITIZE_STRING);
$terms = isset($_POST['terms']) ? 1 : 0;
$txnid = filter_input(INPUT_POST, 'txnid', FILTER_SANITIZE_STRING) ?: ('TXN' . time() . rand(1000, 9999));
$amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
$merchant_id = $txnid;

$logger->info("Input data extracted", [
    'txnid' => $txnid,
    'firstname' => $firstname,
    'phone' => $phone,
    'email' => $email,
    'amount' => $amount,
    'variant' => $productinfo,
    'color' => $color,
    'terms' => $terms
], 'payment_logs.txt');

// Input validation (AFTER extraction)
$errors = [];

// Validate firstname
if (empty($firstname)) {
    $errors[] = 'This field is required (Full Name)';
} elseif (strlen($firstname) < 2) {
    $errors[] = 'Name should be larger than 2 characters';
} elseif (!preg_match('/^[a-zA-Z\s]+$/', $firstname)) {
    $errors[] = 'Enter a valid name';
}

// Validate phone
if (empty($phone)) {
    $errors[] = 'This field is required (Phone)';
} elseif (!preg_match('/^[6-9]\d{9}$/', preg_replace('/\D/', '', $phone))) {
    $errors[] = 'Please enter a valid 10-digit mobile number';
}

// Validate email
if (empty($email)) {
    $errors[] = 'This field is required (Email)';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Please enter a valid email address';
}

// Validate address
if (empty($address)) {
    $errors[] = 'This field is required (Address)';
} elseif (strlen($address) < 5) {
    $errors[] = 'Address must be at least 5 characters';
}

// Validate city
if (empty($city)) {
    $errors[] = 'This field is required (City)';
} elseif (!preg_match('/^[a-zA-Z\s]+$/', $city)) {
    $errors[] = 'Enter a valid city name';
}

// Validate state
if (empty($state)) {
    $errors[] = 'This field is required (State)';
} elseif (!preg_match('/^[a-zA-Z\s]+$/', $state)) {
    $errors[] = 'Enter a valid state name';
}

// Validate pincode
if (empty($pincode)) {
    $errors[] = 'This field is required (Pincode)';
} elseif (!preg_match('/^[1-9][0-9]{5}$/', $pincode)) {
    $errors[] = 'Please enter a valid 6-digit pin code';
}

// Validate variant
if (empty($productinfo) || !in_array($productinfo, ['dx', 'dx-plus'])) {
    $errors[] = 'Please select a valid variant';
}

// Validate color
if (empty($color) || !in_array($color, ['red', 'blue', 'white', 'black', 'grey'])) {
    $errors[] = 'Please select a valid color';
}

// Validate terms
if (!$terms) {
    $errors[] = 'You must agree to the terms and conditions';
}

// If there are validation errors, redirect back with error message
if (!empty($errors)) {
    $logger->error("Validation errors in book-now form", [
        'errors' => $errors,
        'input_data' => [
            'firstname' => $firstname,
            'phone' => $phone,
            'email' => $email
        ]
    ], 'payment_logs.txt');
    
    // Redirect back to book-now with error
    $errorMessage = urlencode(implode('. ', $errors));
    header("Location: /book-now?error=" . $errorMessage);
    exit;
}

// Variables are already extracted above, continue with payment processing

// Load PhonePe configuration from config
$clientId = $config['phonepe']['clientId'];
$clientVersion = $config['phonepe']['clientVersion'];
$clientSecret = $config['phonepe']['clientSecret'];
$env = $config['phonepe']['env'];
$redirectUrl = $config['phonepe']['redirectUrl'] . '?txnid=' . $txnid;

$logger->info("PhonePe credentials loaded from config", [
    'clientId' => $clientId,
    'env' => $env,
    'redirect_url' => $redirectUrl
], 'payment_logs.txt');


// Store all customer details
$allCustomerDetails = [
    'firstname' => $firstname,
    'phone' => $phone,
    'email' => $email,
    'address' => $address,
    'city' => $city,
    'state' => $state,
    'pincode' => $pincode,
    'ownedBefore' => $ownedBefore,
    'productinfo' => $productinfo,
    'color' => $color,
    'terms' => $terms,
    'transaction_id' => $txnid,
    'variant' => $productinfo,
    'amount' => $amount,
    'status' => 'PENDING',
    'merchant_id' => $merchant_id
];

$logger->info("Customer details array prepared", [
    'array_keys' => array_keys($allCustomerDetails),
    'firstname' => $firstname,
    'email' => $email,
    'amount' => $amount,
    'txnid' => $txnid
], 'payment_logs.txt');

$logger->info("About to create DatabaseHandler instance", [], 'payment_logs.txt');

try {
    $emailHandler = new EmailHandler();
    // Initialize DatabaseHandler with config
    $db = new DatabaseHandler();
    $logger->info("DatabaseHandler instance created successfully", [], 'payment_logs.txt');

    $logger->info("About to call createTransaction method", [
        'txnid' => $txnid,
        'allCustomerDetails' => $allCustomerDetails,
        'amount' => $amount
    ], 'payment_logs.txt');

    $result = $db->createTransaction($txnid, $allCustomerDetails, $amount);

    $logger->info("createTransaction method returned", [
        'result' => $result,
        'txnid' => $txnid
    ], 'payment_logs.txt');

    if ($result) {
        $logger->success("Transaction created in database successfully", [
            'txnid' => $txnid,
            'status' => 'PENDING'
        ], 'payment_logs.txt');

        // Note: Success emails will be sent from check-status.php when payment is confirmed

    } else {
        $logger->error("Transaction creation returned false", [
            'txnid' => $txnid
        ], 'payment_logs.txt');

        // --- EMAIL: TRANSACTION FAILURE ---
        // Admin email
        $admin_subject = "[KineticEV] Booking Failure: $txnid";
        $admin_html = (function ($txnid, $details) {
            ob_start();
            include __DIR__ . '/../email-templates/transaction-failure-admin.tpl.php';
            return ob_get_clean();
        })($txnid, $allCustomerDetails);
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

        // Customer email
        $customer_subject = "Your Booking Failed: $txnid";
        $customer_html = (function ($txnid, $details) {
            ob_start();
            include __DIR__ . '/../email-templates/transaction-failure-customer.tpl.php';
            return ob_get_clean();
        })($txnid, $allCustomerDetails);
        $customer_from = $config['aws_ses']['from_email'] ?? 'info@kineticev.in';
        $emailHandler->sendEmail($email, $customer_subject, $customer_html, $customer_from, true);
    }

} catch (Exception $e) {
    $logger->error("CRITICAL ERROR: Exception during database transaction creation", [
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ], 'payment_logs.txt');

    // Show error page and exit
    echo '<!DOCTYPE html>
        <html>
        <head>
            <title>Database Error - KineticEV</title>
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
                <div class="error-icon">💾</div>
                <div class="error-title">Database Error</div>
                <div class="error-message">
                    We encountered a technical issue while saving your transaction. Please try again or contact support if the problem persists.
                </div>
                <a href="/book-now" class="btn-retry">Try Again Now</a>
            </div>
        </body>
        </html>';
    $logger->error("FLOW EXIT: Database Exception - redirecting to book-now", [], 'payment_logs.txt');
    exit;
}

$logger->info("Continuing to PhonePe OAuth authentication process", [], 'payment_logs.txt');

// Test point to confirm script execution continues
$logger->info("=== CHECKPOINT: Script execution continuing after database operations ===", [], 'payment_logs.txt');

// --- EMAIL LOGGING FOR BOOK NOW FLOW ---
$logger->info('[BOOK_NOW] Preparing to send booking confirmation email', [
    'customer_name' => $firstname,
    'customer_email' => $email,
    'txnid' => $txnid,
    'amount' => $amount,
    'variant' => $productinfo,
    'color' => $color,
    'pincode' => $pincode
], 'email_logs.txt');

// Simulate email sending (replace with actual handler as needed)
$emailSendResult = [
    'success' => true, // or false if failed
    'email_id' => uniqid('booknow_', true),
    'timestamp' => (new DateTime('now', new DateTimeZone('Asia/Kolkata')))->format('Y-m-d H:i:s')
];

$logger->info('[BOOK_NOW] Booking confirmation email send result', [
    'customer_email' => $email,
    'email_id' => $emailSendResult['email_id'],
    'success' => $emailSendResult['success'],
    'timestamp' => $emailSendResult['timestamp']
], 'email_logs.txt');

if (!$emailSendResult['success']) {
    $logger->warning('[BOOK_NOW] Booking confirmation email failed to send', [
        'customer_email' => $email,
        'error' => $emailSendResult['error'] ?? 'Unknown error',
        'txnid' => $txnid
    ], 'email_logs.txt');
}


/*
 * API to get access token from PhonePe
 * This script retrieves an access token using client credentials
 */
$logger->info("=== STARTING PHONEPE OAUTH PROCESS ===", [], 'payment_logs.txt');
$logger->info("Starting PhonePe OAuth authentication request", [], 'payment_logs.txt');

$logger->info("Initializing cURL for OAuth request", [], 'payment_logs.txt');
$curlauth = curl_init();

if ($curlauth === false) {
    $logger->error("CRITICAL ERROR: Failed to initialize cURL for OAuth", [], 'payment_logs.txt');
    echo '<!DOCTYPE html>
        <html>
        <head>
            <title>System Error - KineticEV</title>
            <style>
                body { font-family: Arial, sans-serif; text-align: center; padding: 50px; background-color: #f8f9fa; }
                .error-container { max-width: 600px; margin: 0 auto; background: white; padding: 40px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
                .error-icon { font-size: 64px; color: #dc3545; margin-bottom: 20px; }
                .error-title { color: #dc3545; font-size: 28px; margin-bottom: 20px; font-weight: bold; }
                .error-message { font-size: 18px; color: #666; margin-bottom: 30px; line-height: 1.5; }
                .btn-retry { display: inline-block; background: #007bff; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin-top: 20px; font-weight: bold; }
                .btn-retry:hover { background: #0056b3; }
            </style>
        </head>
        <body>
            <div class="error-container">
                <div class="error-icon">⚙️</div>
                <div class="error-title">System Error</div>
                <div class="error-message">A system component failed to initialize. Please try again or contact support.</div>
                <a href="/book-now" class="btn-retry">Try Again Now</a>
            </div>
        </body>
        </html>';
    $logger->error("FLOW EXIT: cURL initialization failed", [], 'payment_logs.txt');
    exit;
}

$logger->info("cURL initialized successfully, setting up OAuth request options", [], 'payment_logs.txt');

// Get auth URL from config
$authUrl = $config['phonepe']['api_base_url']['auth'];

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
curl_close($curlauth);

$logger->info("OAuth authentication response received", [
    'httpCode' => $httpCodeAuth,
    'responseLength' => strlen($authResponse),
    'hasResponse' => !empty($authResponse)
], 'payment_logs.txt');


// Check if first request was successful
if ($httpCodeAuth === 200 && $authResponse) {
    $logger->info("OAuth authentication successful, processing response", [], 'payment_logs.txt');

    // Decode JSON response
    $dataAuth = json_decode($authResponse, true);

    if (json_last_error() === JSON_ERROR_NONE && isset($dataAuth['access_token'])) {
        $accessToken = $dataAuth['access_token'];
        $logger->info("Access token retrieved successfully", [
            'tokenLength' => strlen($accessToken)
        ], 'payment_logs.txt');

        // echo "<pre>";
        // echo "Access Token: " . $accessToken . "\n";
        // echo "<br>" . $txnid . "<br>";
        // echo "</pre>";
        // Proceed with the second API call to process payment
        $logger->info("Starting payment request to PhonePe", [], 'payment_logs.txt');

        $curlPay = curl_init();
        $payloadData = [
            'merchantOrderId' => $txnid,
            'amount' => $amount * 100,
            'metaInfo' => [
                'udf1' => 'additional-information-1',
                'udf2' => 'additional-information-2',
                'udf3' => 'additional-information-3',
                'udf4' => 'additional-information-4',
                'udf5' => 'additional-information-5'
            ],
            'paymentFlow' => [
                'type' => 'PG_CHECKOUT',
                'message' => 'Payment for invoice',
                'merchantUrls' => [
                    'redirectUrl' => $config['phonepe']['redirectUrl'] . '?txnid=' . $txnid
                ]
            ]
        ];

        $logger = Logger::getInstance();
        $logger->info("Payment payload prepared", [
            'merchantOrderId' => $txnid,
            'amount' => $amount * 100,
            'redirectUrl' => $payloadData['paymentFlow']['merchantUrls']['redirectUrl']
        ], 'payment-flow.log');

        // Get checkout URL from config
        $checkoutUrl = $config['phonepe']['api_base_url']['checkout'];

        $payRequest = array(
            CURLOPT_URL => $checkoutUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30, // 30 second timeout instead of unlimited
            CURLOPT_CONNECTTIMEOUT => 10, // 10 second connection timeout
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($payloadData),
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
        );

        curl_setopt_array($curlPay, $payRequest);

        $responsePay = curl_exec($curlPay);
        $httpCodePay = curl_getinfo($curlPay, CURLINFO_HTTP_CODE);
        $curlErrorPay = curl_error($curlPay);
        $curlInfo = curl_getinfo($curlPay);
        curl_close($curlPay);

        $logger->info("Payment request response received", [
            'httpCode' => $httpCodePay,
            'responseLength' => strlen($responsePay),
            'hasResponse' => !empty($responsePay),
            'curlError' => $curlErrorPay,
            'totalTime' => $curlInfo['total_time'] ?? 'unknown',
            'connectTime' => $curlInfo['connect_time'] ?? 'unknown'
        ], 'payment-flow.log');

        // Check for cURL errors first
        if ($curlErrorPay) {
            $logger->error("FLOW: cURL error during payment request", [
                'curlError' => $curlErrorPay,
                'httpCode' => $httpCodePay,
                'totalTime' => $curlInfo['total_time'] ?? 'unknown'
            ], 'payment-flow.log');

            // Handle specific timeout errors
            if (strpos($curlErrorPay, 'timeout') !== false || $httpCodePay === 522) {
                $logger->error("FLOW: Connection timeout to PhonePe - service may be down", [
                    'curlError' => $curlErrorPay,
                    'httpCode' => $httpCodePay,
                    'suggestion' => 'Retry in a few minutes or contact PhonePe support'
                ], 'payment-flow.log');
            }

            $logger->error("FLOW EXIT: cURL Error - redirecting to book-now", [], 'payment-flow.log');
            header('Location: /book-now?error=network');
            exit;
        }

        // Check if payment request was successful
        if ($httpCodePay === 200 && $responsePay) {
            $logger->info("Payment request successful, processing response", [], 'payment-flow.log');

            // Decode JSON response
            $dataPay = json_decode($responsePay, true);
            $redirectUrl = $dataPay['redirectUrl'];

            if (json_last_error() === JSON_ERROR_NONE && isset($dataPay['orderId'])) {
                $logger->info("Payment response parsed successfully", [
                    'orderId' => $dataPay['orderId'],
                    'redirectUrl' => $redirectUrl
                ], 'payment-flow.log');

                $logger->info("Displaying payment redirect page and redirecting to PhonePe", [], 'payment-flow.log');

                echo '<!DOCTYPE html>
                    <html>
                    <head>
                        <title>Processing Payment...</title>
                        <style>
                            body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
                            .loading { font-size: 18px; color: #666; }
                        </style>
                    </head>
                    <body>
                        <div class="loading">
                            <h3>Redirecting to Payment Gateway...</h3>
                            <p>Please wait while we redirect you to complete your payment.</p>
                        </div>
                        
                        <script src="https://mercury.phonepe.com/web/bundle/checkout.js"></script>
                        <script>
                            // Auto-redirect to PhonePe payment page
                            setTimeout(function() {
                                if (window.PhonePeCheckout && window.PhonePeCheckout.transact) {
                                    window.PhonePeCheckout.transact({ 
                                        tokenUrl: "' . $redirectUrl . '" 
                                    });
                                } else {
                                    // Fallback: direct redirect if script fails to load
                                    window.location.href = "' . $redirectUrl . '";
                                }
                            }, 2000); // 2 second delay to show loading message
                        </script>
                    </body>
                    </html>';
                $logger->info("FLOW EXIT: Successful payment - redirecting to PhonePe gateway", [], 'payment-flow.log');
                exit;



                // You can now use the orderId to check the payment status or redirect the user
                // $curl = curl_init();

                // curl_setopt_array($curl, array(
                //     CURLOPT_URL => 'https://api.phonepe.com/apis/pg/checkout/v2/order/T2507301936001088449065/status',
                //     CURLOPT_RETURNTRANSFER => true,
                //     CURLOPT_ENCODING => '',
                //     CURLOPT_MAXREDIRS => 10,
                //     CURLOPT_TIMEOUT => 0,
                //     CURLOPT_FOLLOWLOCATION => true,
                //     CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                //     CURLOPT_CUSTOMREQUEST => 'GET',
                //     CURLOPT_HTTPHEADER => array(
                //         'Content-Type: application/json',
                //         'Authorization: O-Bearer ' . $accessToken
                //     ),
                // ));


            } else {
                $logger->error("FLOW: Payment JSON parsing failed or missing orderId", [
                    'jsonError' => json_last_error_msg(),
                    'hasOrderId' => isset($dataPay['orderId']),
                    'responseData' => $dataPay
                ], 'payment-flow.log');

                // Handle JSON decoding error or missing payment ID
                echo '<!DOCTYPE html>
                    <html>
                    <head>
                        <title>Payment Data Error - KineticEV</title>
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
                            <div class="error-title">Payment Data Error</div>
                            <div class="error-message">
                                There was an issue processing the payment data from the gateway. Please try again or contact support if the problem persists.
                            </div>
                            <div class="redirect-info">
                                Click the button below to return to the booking page.
                            </div>
                            <a href="/book-now" class="btn-retry">Try Again Now</a>
                        </div>
                    </body>
                    </html>';
                $logger->error("FLOW EXIT: Payment Data Error - redirecting to book-now", [], 'payment-flow.log');
                exit;

                /********
                 * You can handle the error here, e.g., log it or display a message to the user
                 * Redirect to booking-page with error message and pre-filled data.
                 */

            }
        } else {
            $logger->error("FLOW: Payment request failed", [
                'httpCode' => $httpCodePay,
                'hasResponse' => !empty($responsePay),
                'responsePreview' => substr($responsePay, 0, 200)
            ], 'payment-flow.log');

            // Special handling for HTTP 522 (Connection timeout)
            if ($httpCodePay === 522) {
                echo '<!DOCTYPE html>
                    <html>
                    <head>
                        <title>Service Temporarily Unavailable - KineticEV</title>
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
                                color: #ffc107; 
                                margin-bottom: 20px; 
                            }
                            .error-title { 
                                color: #ffc107; 
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
                                margin: 10px;
                                font-weight: bold;
                            }
                            .btn-retry:hover { 
                                background: #0056b3; 
                            }
                            .btn-support { 
                                display: inline-block; 
                                background: #28a745; 
                                color: white; 
                                padding: 12px 30px; 
                                text-decoration: none; 
                                border-radius: 5px; 
                                margin: 10px;
                                font-weight: bold;
                            }
                            .btn-support:hover { 
                                background: #1e7e34; 
                            }
                            .technical-info {
                                background: #f8f9fa;
                                padding: 15px;
                                border-radius: 8px;
                                margin: 20px 0;
                                font-size: 14px;
                                color: #666;
                            }
                        </style>
                    </head>
                    <body>
                        <div class="error-container">
                            <div class="error-icon">⏰</div>
                            <div class="error-title">Payment Gateway Timeout</div>
                            <div class="error-message">
                                The payment gateway is currently experiencing high traffic or temporary technical issues. 
                                This is not related to your booking details or payment method.
                            </div>
                            <div class="technical-info">
                                <strong>What happened?</strong><br>
                                Our payment partner (PhonePe) is temporarily unavailable (Error 522: Connection Timeout).<br>
                                Your booking data has been saved and no amount has been charged.
                            </div>
                            <div class="error-message">
                                <strong>What to do next?</strong><br>
                                • Try again in a few minutes<br>
                                • Or contact our support team for immediate assistance<br>
                                • Your booking details are preserved for your convenience
                            </div>
                            <a href="/book-now" class="btn-retry">Try Booking Again</a>
                            <a href="tel:8600096800" class="btn-support">Call Support: 86000 96800</a>
                        </div>
                    </body>
                    </html>';
                $logger->error("FLOW EXIT: HTTP 522 Timeout - PhonePe gateway unavailable", [], 'payment-flow.log');
                exit;
            }

            /******
             * 
             * Technical error occurred while processing the payment.
             * Please try again.
             */

            // Handle error in the payment request
            echo '<!DOCTYPE html>
                <html>
                <head>
                    <title>Payment Request Failed - KineticEV</title>
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
                        <div class="error-icon">💳</div>
                        <div class="error-title">Payment Request Failed</div>
                        <div class="error-message">
                            The payment gateway rejected the request. Please try again with a different payment method or contact support.
                        </div>
                        <div class="redirect-info">
                            Click the button below to return to the booking page.
                        </div>
                        <a href="/book-now" class="btn-retry">Try Again Now</a>
                    </div>
                </body>
                </html>';
            $logger->error("FLOW EXIT: Payment Request Failed - redirecting to book-now", [], 'payment-flow.log');
            exit;

        }

    } else {
        $logger->error("FLOW: OAuth token parsing failed or missing access_token", [
            'jsonError' => json_last_error_msg(),
            'hasAccessToken' => isset($dataAuth['access_token']),
            'responseData' => $dataAuth
        ], 'payment-flow.log');

        /******
         * 
         * Technical error occurred while processing the payment.
         * Please try again.
         */
        // Handle JSON decoding error or missing access token
        echo '<!DOCTYPE html>
            <html>
            <head>
                <title>Payment Error - KineticEV</title>
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
                        We encountered a technical issue while connecting to our payment gateway. 
                        This is usually a temporary problem.
                    </div>
                    <div class="redirect-info">
                        Click the button below to return to the booking page.
                    </div>
                    <a href="/book-now" class="btn-retry">Try Again Now</a>
                </div>
            </body>
            </html>';
        $logger->error("FLOW EXIT: Authentication Error - redirecting to book-now", [], 'payment-flow.log');
        exit;
    }
} else {
    $logger->error("FLOW: OAuth authentication failed", [
        'httpCode' => $httpCodeAuth,
        'hasResponse' => !empty($authResponse),
        'responsePreview' => substr($authResponse, 0, 200)
    ], 'payment-flow.log');

    /******
     * 
     * Technical error occurred while processing the payment.
     * Please try again.
     */
    echo '<!DOCTYPE html>
        <html>
        <head>
            <title>Connection Error - KineticEV</title>
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
                    Unable to connect to the payment gateway. Please check your internet connection and try again.
                </div>
                <div class="redirect-info">
                    Click the button below to return to the booking page.
                </div>
                <a href="/book-now" class="btn-retry">Try Again Now</a>
            </div>
        </body>
        </html>';
    $logger->error("FLOW EXIT: Connection Error - redirecting to book-now", [], 'payment-flow.log');
    exit;
}

// // Decode the JSON response
// $responseData = json_decode($authResponse, true);
// $accessToken = '';

// // Check if the response is valid and contains access_token
// if ($responseData && isset($responseData['access_token'])) {
//     $accessToken = $responseData['access_token'];
// } else {
//     $accessToken = '';
// }

// echo "Access Token: " . $accessToken . "\n";


// echo $responsepay;



//

// {"orderId":"OMO2508011229183275291013","state":"COMPLETED","amount":100000,"payableAmount":100000,"feeAmount":0,"expireAt":1754032458327,"paymentDetails":[{"transactionId":"OM2508011229208810305777","paymentMode":"UPI_INTENT","timestamp":1754031560911,"amount":100000,"payableAmount":100000,"feeAmount":0,"state":"COMPLETED","instrument":{"type":"ACCOUNT","maskedAccountNumber":"XXXXXX1251","ifsc":"SBIN0007249","accountType":"SAVINGS"},"rail":{"type":"UPI","utr":"714921439307","upiTransactionId":"AXLf212edfe3db84341943d0ae22a87e662"},"splitInstruments":[{"instrument":{"type":"ACCOUNT","maskedAccountNumber":"XXXXXX1251","ifsc":"SBIN0007249","accountType":"SAVINGS"},"rail":{"type":"UPI","utr":"714921439307","upiTransactionId":"AXLf212edfe3db84341943d0ae22a87e662"},"amount":100000}]}]}
// $response = curl_exec($curl);

// curl_close($curl);
// echo "<pre>";
// echo $response;
// exit;
?>