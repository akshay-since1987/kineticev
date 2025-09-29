<?php

// Prevent direct access
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    http_response_code(404);
    exit();
}

/**
 * SalesforceService - Handles mapping and submission of form data to Salesforce Web-to-Lead
 * 
 * This service maps internal form fields to Salesforce field names and submits
 * data to the Salesforce Web-to-Lead endpoint with comprehensive logging.
 */

class SalesforceService
{
    private $logger;
    private $config;
    private $salesforceEndpoint;
    private $organizationId;
    private $recordType;
    private $returnUrl;
    private $leadSource;
    private $status;
    private $fieldMappings;

    public function __construct($logger = null, $config = null)
    {
        $this->logger = $logger ?: Logger::getInstance();

        // Load config if not provided
        if ($config === null) {
            $config = include __DIR__ . '/config.php';
        }
        $this->config = $config;

        // Set Salesforce configuration from config file
        $sfConfig = $this->config['salesforce']['web_to_lead'];
        $this->salesforceEndpoint = $sfConfig['endpoint'];
        $this->organizationId = $sfConfig['organization_id'];
        $this->recordType = $sfConfig['record_type'];

        // Set initial return URL from config (will be overridden dynamically)
        $this->returnUrl = $sfConfig['return_url'];

        $this->leadSource = $sfConfig['lead_source'];
        $this->status = $sfConfig['status'];

        // Load field mappings from config
        $this->fieldMappings = $this->config['salesforce']['field_mappings'];

        $this->logger->info('[SALESFORCE_SERVICE] SalesforceService initialized', [
            'endpoint' => $this->salesforceEndpoint,
            'org_id' => $this->organizationId,
            'lead_source' => $this->leadSource
        ], 'salesforce_logs.txt');
    }

    /**
     * Dynamically set the return URL to the current page
     */
    private function setDynamicReturnUrl()
    {
        // Get protocol (HTTP or HTTPS)
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';

        // Get host
        $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';

        // Get the current URI (path + query string)
        $uri = $_SERVER['REQUEST_URI'] ?? '/';

        // Build the complete URL
        $currentUrl = $protocol . $host . $uri;

        // Update the return URL
        $this->returnUrl = $currentUrl;

        $this->logger->info('[SALESFORCE_SERVICE] Dynamic return URL set', [
            'return_url' => $this->returnUrl,
            'protocol' => $protocol,
            'host' => $host,
            'uri' => $uri
        ], 'salesforce_logs.txt');
    }

    /**
     * Set a safe return URL that prevents redirect loops
     * This method avoids setting the return URL to check-status.php to prevent infinite redirects
     */
    private function setSafeReturnUrl()
    {
        // Get protocol (HTTP or HTTPS)
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';

        // Get host
        $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';

        // Check if current page is check-status.php to prevent redirect loops
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $isCheckStatusPage = strpos($uri, '/api/check-status') !== false;

        if ($isCheckStatusPage) {
            // Use a safe redirect URL instead of the current check-status page
            $safeUrl = $protocol . $host . '/thank-you.php';
            $this->logger->info('[SALESFORCE_SERVICE] Prevented redirect loop by using safe URL', [
                'original_uri' => $uri,
                'safe_url' => $safeUrl,
                'reason' => 'check-status page detected'
            ], 'salesforce_logs.txt');
        } else {
            // Use current URL if it's not check-status page
            $safeUrl = $protocol . $host . $uri;
            $this->logger->info('[SALESFORCE_SERVICE] Using current URL as return URL', [
                'url' => $safeUrl
            ], 'salesforce_logs.txt');
        }

        // Update the return URL
        $this->returnUrl = $safeUrl;

        $this->logger->info('[SALESFORCE_SERVICE] Safe return URL set', [
            'return_url' => $this->returnUrl,
            'protocol' => $protocol,
            'host' => $host,
            'uri' => $uri,
            'is_check_status_page' => $isCheckStatusPage
        ], 'salesforce_logs.txt');
    }

    /**
     * Send form data to Salesforce
     * 
     * @param array $formData The form data from your application
     * @param string $formType The type of form (contact, test_ride, book_now, choose_variant)
     * @return array Response with success status and details
     */
    public function sendToSalesforce($formData, $formType = 'unknown')
    {
        $this->logger->info('[SALESFORCE_SERVICE] Starting Salesforce submission', [
            'form_type' => $formType,
            'input_fields' => array_keys($formData),
            'timestamp' => date('Y-m-d H:i:s')
        ], 'salesforce_logs.txt');

        try {
            // Use safe return URL to prevent redirect loops
            // Send to Salesforce if it's an enquiry, dealership enquiry, payment transaction (book_now), or test ride
            $helpType = $formData['help_type'] ?? '';
            if ($helpType == 'enquiry' || $helpType == "dealership" || $formType == 'book_now' || $formType == 'test_ride') {
                $this->setSafeReturnUrl();
                $salesforceData = $this->mapToSalesforceFields($formData, $formType);

                $this->logger->info('[SALESFORCE_SERVICE] Field mapping completed', [
                    'form_type' => $formType,
                    'mapped_fields' => array_keys($salesforceData),
                    'salesforce_data' => $salesforceData
                ], 'salesforce_logs.txt');

                // Submit to Salesforce
                $response = $this->submitToSalesforce($salesforceData);

                if ($response['success']) {
                    $this->logger->success('[SALESFORCE_SERVICE] Successfully submitted to Salesforce', [
                        'form_type' => $formType,
                        'response_code' => $response['http_code'],
                        'response_time_ms' => $response['response_time_ms'] ?? 0,
                        'submitted_data' => $salesforceData
                    ], 'salesforce_logs.txt');
                } else {
                    $this->logger->error('[SALESFORCE_SERVICE] Failed to submit to Salesforce', [
                        'form_type' => $formType,
                        'error' => $response['error'],
                        'response_code' => $response['http_code'] ?? 'unknown',
                        'response_body' => $response['response_body'] ?? ''
                    ], 'salesforce_logs.txt');
                }
            } else {
                $this->logger->success('[SALESFORCE_SERVICE] Successfully submitted to Salesforce', [
                    'form_type' => $formType,
                    'response_code' => 200,
                    'response_time_ms' => $response['response_time_ms'] ?? 0,
                    'submitted_data' => "Data not Sent To Salesforce as it is not Enquiry or Dealership Enquiry"
                ], 'salesforce_logs.txt');

                $response['error'] = null;
                $response['success'] = array(
                    'form' => 'Data not Sent To Salesforce as it is not Enquiry or Dealership Enquiry'
                );
            }
            return $response;

        } catch (Exception $e) {
            $this->logger->error('[SALESFORCE_SERVICE] Exception during Salesforce submission', [
                'form_type' => $formType,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'input_data' => $formData
            ], 'salesforce_logs.txt');

            return [
                'success' => false,
                'error' => 'Exception: ' . $e->getMessage(),
                'exception' => true
            ];
        }
    }

    /**
     * Format date to DD-MM-YYYY format for Salesforce
     * 
     * @param string $date Input date in various formats
     * @return string Formatted date in DD-MM-YYYY format or empty string
     */
    private function formatDateForSalesforce($date)
    {
        if (empty($date)) {
            return '';
        }

        // If already in DD-MM-YYYY format, return as is
        if (preg_match('/^\d{2}-\d{2}-\d{4}$/', $date)) {
            return $date;
        }

        // Try to parse and convert to DD-MM-YYYY
        $timestamp = strtotime($date);
        if ($timestamp !== false) {
            return date('d-m-Y', $timestamp);
        }

        // If parsing fails, return original (might be already formatted correctly)
        return $date;
    }

    /**
     * Map internal form fields to Salesforce field names
     * 
     * @param array $formData
     * @param string $formType
     * @return array Mapped Salesforce data
     */
    private function mapToSalesforceFields($formData, $formType)
    {
        // Split full name if needed
        $firstName = '';
        $lastName = '';

        if (!empty($formData['full_name'])) {
            $nameParts = explode(' ', trim($formData['full_name']), 2);
            $firstName = $nameParts[0] ?? '';
            $lastName = $nameParts[1] ?? '';
        } elseif (!empty($formData['firstname'])) {
            $firstName = $formData['firstname'];
        }

        // Build complete address if city/state available
        $fullAddress = $formData['address'] ?? '';
        if (!empty($formData['city']) || !empty($formData['state']) || !empty($formData['pincode'])) {
            $addressParts = array_filter([
                $fullAddress,
                $formData['city'] ?? '',
                $formData['state'] ?? '',
                $formData['pincode'] ?? ''
            ]);
            $fullAddress = implode(', ', $addressParts);
        }

        // Get transaction details from database if transaction ID exists
        $transactionDetails = $this->getTransactionDetails($formData);
        $helpType = strtolower($formData['help_type'] ?? '');

        // Determine record type based on help_type
        if ($helpType === 'dealership' || $helpType === 'dealership enquiry') {
            $recordType = $this->recordType['dealership'];
            $this->logger->info('[SALESFORCE_SERVICE] Using dealership record type', [
                'help_type' => $helpType,
                'record_type' => $recordType
            ], 'salesforce_logs.txt');
        } elseif ($helpType === 'test_ride' || $helpType === 'test-ride') {
            $recordType = $this->recordType['retail'];  // Use retail record type for test rides
            // Set test ride specific fields
            if (!empty($formData['date'])) {
                $formData['test_ride_date'] = $formData['date'];  // Ensure test ride date is properly mapped
            }
            $this->logger->info('[SALESFORCE_SERVICE] Using retail record type for test ride', [
                'help_type' => $helpType,
                'record_type' => $recordType
            ], 'salesforce_logs.txt');
        } else {
            $recordType = $this->recordType['retail'];
            $this->logger->info('[SALESFORCE_SERVICE] Using retail record type', [
                'help_type' => $helpType,
                'record_type' => $recordType
            ], 'salesforce_logs.txt');
        }
        // Base Salesforce data structure
        $salesforceData = [
            // Required Salesforce fields
            'oid' => $this->organizationId,
            'recordType' => $recordType,
            'status' => $this->status,
            'lead_source' => $this->leadSource,
            'encoding' => 'UTF-8',

            // Direct field mappings from config
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $formData['email'] ?? '',
            'phone' => $formData['phone'] ?? '',
        ];

        // Add custom field mappings from config
        $customFields = $this->fieldMappings['custom_fields'];

        // Map form data to Salesforce custom fields using the field mappings
        foreach ($customFields as $salesforceFieldId => $formFieldName) {
            switch ($formFieldName) {
                case 'pincode':
                    $salesforceData[$salesforceFieldId] = $formData['pincode'] ?? '';
                    break;
                case 'whatsapp_number':
                    $salesforceData[$salesforceFieldId] = $formData['phone'] ?? ''; // Use phone as WhatsApp number
                    break;
                case 'address':
                    $salesforceData[$salesforceFieldId] = $formData['address'] ?? '';
                    break;
                case 'product_variant':
                    $salesforceData[$salesforceFieldId] = $this->buildProductVariant($transactionDetails, $formData);
                    break;
                case 'variant':
                    $salesforceData[$salesforceFieldId] = $this->mapVariant($transactionDetails['variant'] ?? $formData['variant'] ?? '');
                    break;
                case 'color':
                    $salesforceData[$salesforceFieldId] = $transactionDetails['color'] ?? $formData['color'] ?? '';
                    break;
                case 'test_ride_date':
                    $salesforceData[$salesforceFieldId] = $this->formatDateForSalesforce($formData['date'] ?? '');
                    break;
                case 'booking_amount':
                    $salesforceData[$salesforceFieldId] = $transactionDetails['amount'] ?? $this->getBookingAmount($formType);
                    break;
                case 'payment_method':
                    $salesforceData[$salesforceFieldId] = $this->getPaymentMethod($formType, $transactionDetails);
                    break;
                case 'payment_date':
                    $salesforceData[$salesforceFieldId] = $this->getPaymentDate($formType, $transactionDetails);
                    break;
                case 'transaction_id':
                    $salesforceData[$salesforceFieldId] = $formData['txnid'] ?? $formData['transaction_id'] ?? '';
                    break;
                case 'message':
                    $salesforceData[$salesforceFieldId] = $formData['message'] ?? '';
                    break;
                case 'concern':
                    $salesforceData[$salesforceFieldId] = $this->mapConcern($formData['help_type'] ?? '');
                    break;
                case 'payment_status':
                    $salesforceData[$salesforceFieldId] = $this->mapPaymentStatus($formData['payment_status'] ?? '');
                    break;
            }
        }

        // Add retURL only for form submissions, not payment status updates
        // This prevents redirect loops from payment status check pages
        $isPaymentStatusUpdate = isset($formData['transaction_id']) &&
            (isset($formData['payment_status']) ||
                strpos($_SERVER['REQUEST_URI'] ?? '', '/api/check-status') !== false);

        if (!$isPaymentStatusUpdate) {
            $salesforceData['retURL'] = $this->returnUrl;
            $this->logger->info('[SALESFORCE_SERVICE] Added retURL for form submission', [
                'retURL' => $this->returnUrl,
                'submission_type' => 'form'
            ], 'salesforce_logs.txt');
        } else {
            $this->logger->info('[SALESFORCE_SERVICE] Skipped retURL for payment status update', [
                'reason' => 'prevent_redirect_loop',
                'submission_type' => 'payment_status'
            ], 'salesforce_logs.txt');
        }

        // Remove empty fields to keep the submission clean
        $salesforceData = array_filter($salesforceData, function ($value) {
            return $value !== '' && $value !== null;
        });

        return $salesforceData;
    }

    /**
     * Map variant values using config-based mappings
     */
    private function mapVariant($variant)
    {
        if (empty($variant)) {
            return '';
        }

        $variant = trim(strtolower($variant));
        $variantMappings = $this->fieldMappings['value_mappings']['variant'] ?? [];

        return $variantMappings[$variant] ?? $variant; // Return mapped value or original if no mapping found
    }

    /**
     * Build product variant string combining variant and color
     * 
     * @param array $transactionDetails Transaction details from database
     * @param array $formData Form data submitted
     * @return string Combined variant + color string
     */
    private function buildProductVariant($transactionDetails, $formData)
    {
        // Get variant (prioritize transaction details from DB)
        $variant = $transactionDetails['variant'] ?? $formData['variant'] ?? '';
        $mappedVariant = $this->mapVariant($variant);

        // Get color (prioritize transaction details from DB)
        $color = $transactionDetails['color'] ?? $formData['color'] ?? '';

        // Build combined string
        if (!empty($mappedVariant) && !empty($color)) {
            return $mappedVariant . ' ' . $color;
        } elseif (!empty($mappedVariant)) {
            return $mappedVariant;
        } elseif (!empty($color)) {
            return $color;
        }

        return '';
    }

    /**
     * Map concern values using config-based mappings
     */
    private function mapConcern($concern)
    {
        if (empty($concern)) {
            return '';
        }

        $concern = trim(strtolower($concern));
        $concernMappings = $this->fieldMappings['value_mappings']['concern'] ?? [];

        return $concernMappings[$concern] ?? $concern; // Return mapped value or original if no mapping found
    }

    /**
     * Map payment status values using config-based mappings
     */
    private function mapPaymentStatus($status)
    {
        if (empty($status)) {
            return '';
        }

        $status = trim(strtolower($status));
        $paymentStatusMappings = $this->fieldMappings['value_mappings']['payment_status'] ?? [];

        return $paymentStatusMappings[$status] ?? $status; // Return mapped value or original if no mapping found
    }

    /**
     * Get booking amount based on form type
     */
    private function getBookingAmount($formType)
    {
        return $formType === 'book_now' ? '1000' : '';
    }

    /**
     * Get payment date based on form type and transaction details
     * Returns date only for book_now forms, empty string for test ride and contact forms
     */
    private function getPaymentDate($formType, $transactionDetails = [])
    {
        // Only return a date for book_now forms
        if ($formType !== 'book_now') {
            return '';
        }

        // For book_now forms, return transaction date if available, otherwise current date
        return $this->formatDateForSalesforce($transactionDetails['transaction_date'] ?? date('d-m-Y'));
    }

    /**
     * Get payment method based on form type and transaction details
     */
    private function getPaymentMethod($formType, $transactionDetails = [])
    {
        // For non-book_now forms, always return empty string
        if ($formType !== 'book_now') {
            return '';
        }

        // For book_now forms, try to extract payment mode from payment gateway response
        if (!empty($transactionDetails['payment_details'])) {
            $paymentDetails = json_decode($transactionDetails['payment_details'], true);

            // Extract payment mode from paymentDetails array
            if (isset($paymentDetails['paymentDetails'][0]['paymentMode'])) {
                $paymentMode = $paymentDetails['paymentDetails'][0]['paymentMode'];

                $this->logger->info('[SALESFORCE_SERVICE] Payment mode extracted from gateway response', [
                    'payment_mode' => $paymentMode,
                    'transaction_id' => $transactionDetails['transaction_id'] ?? 'unknown'
                ], 'salesforce_logs.txt');

                return $paymentMode;
            }
        }

        // Fallback to UPI for book_now forms if payment mode cannot be extracted
        $this->logger->info('[SALESFORCE_SERVICE] Using fallback payment mode UPI for book_now', [
            'reason' => 'payment_mode_not_found_in_response',
            'transaction_id' => $transactionDetails['transaction_id'] ?? 'unknown'
        ], 'salesforce_logs.txt');

        return 'UPI';
    }

    /**
     * Get transaction details from database using transaction ID
     */
    private function getTransactionDetails($formData)
    {
        $transactionId = $formData['txnid'] ?? $formData['transaction_id'] ?? '';

        if (empty($transactionId)) {
            return [];
        }

        try {
            // Include DatabaseHandler if not already included
            if (!class_exists('DatabaseHandler')) {
                require_once __DIR__ . '/DatabaseHandler.php';
            }

            $db = new DatabaseHandler();
            $transaction = $db->getTransaction($transactionId);

            if ($transaction) {
                $this->logger->info('[SALESFORCE_SERVICE] Transaction details retrieved from database', [
                    'transaction_id' => $transactionId,
                    'amount' => $transaction['amount'] ?? null,
                    'variant' => $transaction['variant'] ?? null,
                    'color' => $transaction['color'] ?? null,
                    'transaction_date' => $transaction['created_at'] ?? null
                ], 'salesforce_logs.txt');

                return [
                    'amount' => $transaction['amount'] ?? '',
                    'variant' => $transaction['variant'] ?? '',
                    'color' => $transaction['color'] ?? '',
                    'transaction_date' => $transaction['created_at'] ? date('d-m-Y', strtotime($transaction['created_at'])) : '',
                    'payment_details' => $transaction['payment_details'] ?? null
                ];
            } else {
                $this->logger->warning('[SALESFORCE_SERVICE] Transaction not found in database', [
                    'transaction_id' => $transactionId
                ], 'salesforce_logs.txt');
            }

        } catch (Exception $e) {
            $this->logger->error('[SALESFORCE_SERVICE] Error retrieving transaction details', [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage()
            ], 'salesforce_logs.txt');
        }

        return [];
    }

    /**
     * Build comprehensive message including additional data
     */
    private function buildMessage($formData, $formType)
    {
        $messageParts = [];

        // Add original message if provided
        if (!empty($formData['message'])) {
            $messageParts[] = $formData['message'];
        }

        // Add form type context
        $messageParts[] = "Form Type: " . ucwords(str_replace('_', ' ', $formType));

        // Add additional context based on form type
        if ($formType === 'book_now') {
            if (isset($formData['ownedBefore']) && $formData['ownedBefore']) {
                $messageParts[] = "Previously owned Kinetic DX: Yes";
            }
            if (!empty($formData['terms'])) {
                $messageParts[] = "Terms & Conditions: Accepted";
            }
        }

        // Add timestamp
        $messageParts[] = "Submitted: " . date('d-m-Y H:i:s');

        return implode("\n\n", array_filter($messageParts));
    }

    /**
     * Submit data to Salesforce Web-to-Lead endpoint
     */
    private function submitToSalesforce($salesforceData)
    {
        $startTime = microtime(true);

        $this->logger->info('[SALESFORCE_SERVICE] Initiating HTTP request to Salesforce', [
            'endpoint' => $this->salesforceEndpoint,
            'data_fields' => array_keys($salesforceData)
        ], 'salesforce_logs.txt');

        // Initialize cURL
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $this->salesforceEndpoint,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($salesforceData),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_USERAGENT => 'KineticEV-WebToLead/1.0',
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded',
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8'
            ]
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $responseTime = (microtime(true) - $startTime) * 1000; // Convert to milliseconds

        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);

            $this->logger->error('[SALESFORCE_SERVICE] cURL error during submission', [
                'error' => $error,
                'http_code' => $httpCode,
                'response_time_ms' => round($responseTime, 2)
            ], 'salesforce_logs.txt');

            return [
                'success' => false,
                'error' => 'cURL Error: ' . $error,
                'http_code' => $httpCode,
                'response_time_ms' => round($responseTime, 2)
            ];
        }

        curl_close($ch);

        // Salesforce Web-to-Lead typically returns 200 and redirects on success
        $success = ($httpCode >= 200 && $httpCode < 400);

        $this->logger->info('[SALESFORCE_SERVICE] HTTP response received', [
            'http_code' => $httpCode,
            'response_time_ms' => round($responseTime, 2),
            'response_length' => strlen($response),
            'success' => $success
        ], 'salesforce_logs.txt');

        return [
            'success' => $success,
            'http_code' => $httpCode,
            'response_body' => $response,
            'response_time_ms' => round($responseTime, 2),
            'submitted_data' => $salesforceData
        ];
    }
}
