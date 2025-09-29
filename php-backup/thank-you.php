<?php

// Include required files
require_once 'components/layout.php';
require_once 'DatabaseHandler.php';
require_once 'SmsService.php';
require_once 'Logger.php';

// Load configuration
$config = include 'config.php';

// Get transaction ID from URL parameter
$txnid = filter_input(INPUT_GET, 'txnid', FILTER_SANITIZE_STRING);

// Initialize Logger
$logger = Logger::getInstance();

$logger->info("Thank you page accessed", [
    'txnid' => $txnid,
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
], 'thank_you_logs.txt');

// Initialize variables
$firstName = '';
$lastName = '';
$bookingReference = '';
$phoneNumber = '';

// If txnid is provided, fetch transaction details from database
if ($txnid) {
    try {
        $dbHandler = new DatabaseHandler();
        $transaction = $dbHandler->getTransaction($txnid);

        if ($transaction) {
            // Extract data from transaction
            $firstName = $transaction['firstname'] ?? '';
            $lastName = $transaction['lastname'] ?? ''; // If lastname exists in DB
            $phoneNumber = $transaction['phone'] ?? '';
            $bookingReference = $transaction['transaction_id'] ?? $txnid;
            $selectedColor = $transaction['color'] ?? 'white';

            // Split firstname if it contains space (in case full name is stored in firstname field)
            if (strpos($firstName, ' ') !== false) {
                $nameParts = explode(' ', $firstName, 2);
                $firstName = $nameParts[0];
                $lastName = $nameParts[1] ?? '';
            }

            // Send thank you SMS if phone number is available
            if ($phoneNumber) {
                try {
                    $smsService = new SmsService();
                    $smsResult = $smsService->sendThankYouSms($phoneNumber, $bookingReference);

                    if ($smsResult['success']) {
                        $logger->success("Thank you SMS sent successfully", [
                            'transaction_id' => $txnid,
                            'phone' => $phoneNumber,
                            'booking_reference' => $bookingReference
                        ], 'thank_you_logs.txt');
                    } else {
                        $logger->error("Failed to send thank you SMS", [
                            'transaction_id' => $txnid,
                            'error' => $smsResult['error'] ?? 'Unknown error',
                            'response' => $smsResult['response'] ?? null
                        ], 'thank_you_logs.txt');
                    }
                } catch (Exception $e) {
                    $logger->error("SMS service error", [
                        'transaction_id' => $txnid,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ], 'thank_you_logs.txt');
                }
            } else {
                $logger->warning("No phone number available for SMS", [
                    'transaction_id' => $txnid,
                    'customer_name' => $firstName
                ], 'thank_you_logs.txt');
            }
        } else {
            // Transaction not found, use txnid as reference
            $bookingReference = $txnid;
            $firstName = 'Customer'; // Default fallback
            $logger->warning("Transaction not found in database", [
                'transaction_id' => $txnid
            ], 'thank_you_logs.txt');
        }
    } catch (Exception $e) {
        // Handle database errors gracefully
        $bookingReference = $txnid;
        $firstName = 'Customer';
        $logger->error("Database error on thank you page", [
            'transaction_id' => $txnid,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], 'thank_you_logs.txt');
    }
} else {
    // No txnid provided, use defaults
    $firstName = 'Customer';
    $bookingReference = 'N/A';
    $logger->warning("Thank you page accessed without transaction ID", [
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ], 'thank_you_logs.txt');
}

$preload_images = [
    "/-/images/new/red/000145.png",
    "/-/images/new/black/000145.png",
    "/-/images/new/white/000145.png",
    "/-/images/new/blue/000145.png",
    "/-/images/new/gray/000145.png"
];

startLayout("Thank You!", [
    'preload_images' => $preload_images,
    'include_video_modal' => false
]);

?>
<main>
    <div class="side-screen-3 show-logo">
        <div class="side-page-content">
            <div class="form-container">
                <div class="legend-form thank-you">
                    <div class="legend-wrapper">
                        <div class="thank-you-wrapper">
                            <div class="heading">
                                <p class="greeting">Thank You!</p>
                                <h2><span
                                        class="bold"><?= htmlspecialchars(strtoupper($firstName)) ?></span><?= $lastName ? ' ' . htmlspecialchars(strtoupper($lastName)) : '' ?>,
                                </h2>
                            </div>
                            <div class="body-content">
                                <div class="booking-info">
                                    <p>Your</p>
                                    <div class="product-badge">
                                        <h2>Kinetic DX/DX+</h2>
                                    </div>
                                </div>

                                <p class="reference">
                                    Pre-Booked, <strong>#<?= $bookingReference ?></strong> is your reference ID<br />
                                    We got your request for <strong>Kinetic DX</strong><br />
                                    Our team will be in touch on your registered number.
                                </p>

                                <a class="submit-button" href="/">BACK TO THE FUTURE</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="background">
            <svg id="Layer_1" xmlns="http://www.w3.org/2000/svg" version="1.1" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 1920 1080">
                <path fill="#e03535" d="M0,716.6h1866.7l-354.5,363.4H0v-363.4h0Z"/>
                <path fill="#1666b7" d="M2186.2,716.5H449.6l309.8-305.6h1426.8v305.6Z"/>
                <image id="image" isolation="isolate" width="1450" height="1300" transform="translate(47.3 66.5) scale(.8)" href="/-/images/new/white/000145.png" class="background-model-image"/>
            </svg>
        </div>
    </div>
    
    <!-- Meta Pixel Purchase Tracking Data -->
    <script>
        // Store transaction data for Meta Pixel tracking
        window.bookingData = {
            transaction_id: '<?= htmlspecialchars($bookingReference) ?>',
            customer_name: '<?= htmlspecialchars($firstName . ($lastName ? ' ' . $lastName : '')) ?>',
            product_name: 'Kinetic DX',
            booking_reference: '<?= htmlspecialchars($bookingReference) ?>',
            txnid: '<?= htmlspecialchars($txnid) ?>'
        };
        
        // Track purchase conversion when page loads
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof fbq !== 'undefined' && window.bookingData.transaction_id) {
                // Track Purchase event
                fbq('track', 'Purchase', {
                    content_name: 'Kinetic DX Vehicle Booking',
                    content_category: 'Vehicle Purchase',
                    content_type: 'product',
                    transaction_id: window.bookingData.transaction_id,
                    currency: 'INR',
                    num_items: 1
                });
                
                // Track CompleteRegistration for booking completion
                fbq('track', 'CompleteRegistration', {
                    content_name: 'Vehicle Booking Complete',
                    registration_method: 'online_booking',
                    status: 'completed'
                });
                
                console.log('📊 Meta Pixel: Purchase and CompleteRegistration tracked for booking:', window.bookingData.transaction_id);
            }
        });
    </script>
</main>

<?php endLayout(['include_test_drive_modal' => true, 'include_video_modal' => true]); ?>