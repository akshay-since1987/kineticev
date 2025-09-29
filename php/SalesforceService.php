<?php

// Prevent direct access
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    http_response_code(404);
    exit();
}

/**
 * ===============================================================================
 * SALESFORCE SERVICE - K2 KINETIC EV LEAD MANAGEMENT SYSTEM
 * ===============================================================================
 * 
 * PURPOSE:
 * This service handles mapping and submission of form data from K2 Kinetic EV website
 * to Salesforce Web-to-Lead API with comprehensive logging and duplicate prevention.
 * 
 * SUPPORTED FORM TYPES:
 * - test_ride: Test ride booking forms
 * - book_now: Vehicle booking with payment
 * - contact: General inquiries and support
 * - dealership: Dealership partnership inquiries
 * 
 * KEY FEATURES:
 * - Automatic field mapping from internal forms to Salesforce fields
 * - Duplicate submission prevention for payment transactions
 * - Form-specific data handling (dates, amounts, etc.)
 * - Comprehensive logging for debugging and monitoring
 * - Safe redirect URL handling to prevent loops
 * 
 * SALESFORCE INTEGRATION:
 * - Uses Web-to-Lead API for lead creation
 * - Supports both retail and dealership record types
 * - Maps custom fields based on configuration
 * - Handles transaction data from payment gateway
 * 
 * @author K2 Kinetic EV Development Team
 * @version 2.0
 * @since 2024
 */

class SalesforceService
{
    // ========================================================================
    // CLASS PROPERTIES
    // ========================================================================
    
    private $logger;                    // Logger instance for comprehensive logging
    private $config;                    // Configuration array from config file
    private $salesforceEndpoint;        // Salesforce Web-to-Lead endpoint URL
    private $organizationId;            // Salesforce Organization ID
    private $recordType;                // Record type mapping (retail/dealership)
    private $returnUrl;                 // Dynamic return URL after submission
    private $leadSource;                // Lead source identifier (Website)
    private $status;                    // Default lead status
    private $fieldMappings;             // Custom field mappings configuration

    // ========================================================================
    // INITIALIZATION
    // ========================================================================

    /**
     * Initialize SalesforceService with configuration and logging
     * 
     * @param Logger|null $logger Optional logger instance
     * @param array|null $config Optional configuration array
     */
    public function __construct($logger = null, $config = null)
    {
        // Initialize logger
        $this->logger = $logger ?: Logger::getInstance();

        // Load configuration
        $this->loadConfiguration($config);
        
        // Initialize Salesforce settings
        $this->initializeSalesforceSettings();
        
        // Log successful initialization
        $this->logger->info('[SALESFORCE_SERVICE] SalesforceService initialized', [
            'endpoint' => $this->salesforceEndpoint,
            'org_id' => $this->organizationId,
            'lead_source' => $this->leadSource
        ], 'salesforce_logs.txt');
    }

    /**
     * Load and validate configuration settings
     */
    private function loadConfiguration($config)
    {
        if ($config === null) {
            $config = include __DIR__ . '/config.php';
        }
        $this->config = $config;
        
        // Validate required configuration sections
        if (!isset($this->config['salesforce']['web_to_lead'])) {
            throw new Exception('Salesforce web_to_lead configuration missing');
        }
        
        if (!isset($this->config['salesforce']['field_mappings'])) {
            throw new Exception('Salesforce field_mappings configuration missing');
        }
    }

    /**
     * Initialize Salesforce-specific settings from configuration
     */
    private function initializeSalesforceSettings()
    {
        $sfConfig = $this->config['salesforce']['web_to_lead'];
        
        $this->salesforceEndpoint = $sfConfig['endpoint'];
        $this->organizationId = $sfConfig['organization_id'];
        $this->recordType = $sfConfig['record_type'];
        $this->returnUrl = $sfConfig['return_url']; // Will be overridden dynamically
        $this->leadSource = $sfConfig['lead_source'];
        $this->status = $sfConfig['status'];
        $this->fieldMappings = $this->config['salesforce']['field_mappings'];
    }

    // ========================================================================
    // PUBLIC API METHODS
    // ========================================================================

    /**
     * Main entry point: Send form data to Salesforce
     * 
     * This method handles the complete flow:
     * 1. Validates and logs incoming data
     * 2. Checks for duplicate submissions
     * 3. Determines if data should be sent to Salesforce
     * 4. Maps fields and submits to Salesforce API
     * 
     * @param array $formData Form data from website
     * @param string $formType Type of form (test_ride, book_now, contact, etc.)
     * @return array Response with success status and details
     */
    public function sendToSalesforce($formData, $formType = 'unknown')
    {
        // Log incoming request
        $this->logger->info('[SALESFORCE_SERVICE] Starting Salesforce submission', [
            'form_type' => $formType,
            'input_fields' => array_keys($formData),
            'timestamp' => date('Y-m-d H:i:s')
        ], 'salesforce_logs.txt');

        try {
            // Step 1: Check for duplicate submissions (prevents double processing)
            if ($this->isDuplicateSubmission($formData, $formType)) {
                return $this->handleDuplicateSubmission($formData, $formType);
            }

            // Step 2: Determine if this submission should go to Salesforce
            if (!$this->shouldSendToSalesforce($formData, $formType)) {
                return $this->handleSkippedSubmission($formType);
            }

            // Step 3: Process and submit to Salesforce
            return $this->processAndSubmitToSalesforce($formData, $formType);

        } catch (Exception $e) {
            return $this->handleException($e, $formData, $formType);
        }
    }

    // ========================================================================
    // SUBMISSION LOGIC METHODS
    // ========================================================================

    /**
     * Check if submission should be sent to Salesforce based on form type and help_type
     */
    private function shouldSendToSalesforce($formData, $formType)
    {
        $helpType = $formData['help_type'] ?? '';
        
        // Define conditions for Salesforce submission
        $shouldSend = (
            $helpType === 'enquiry' ||          // General inquiries
            $helpType === 'dealership' ||       // Dealership inquiries  
            $formType === 'book_now' ||         // Vehicle booking forms
            $formType === 'test_ride'           // Test ride bookings
        );
        
        $this->logger->info('[SALESFORCE_SERVICE] Salesforce submission eligibility check', [
            'form_type' => $formType,
            'help_type' => $helpType,
            'should_send' => $shouldSend,
            'reason' => $shouldSend ? 'eligible_form_type' : 'non_eligible_form_type'
        ], 'salesforce_logs.txt');
        
        return $shouldSend;
    }

    /**
     * Handle duplicate submission detection and response
     */
    private function handleDuplicateSubmission($formData, $formType)
    {
        $transactionId = $formData['txnid'] ?? $formData['transaction_id'] ?? '';
        $paymentStatus = $formData['payment_status'] ?? '';
        
        $this->logger->info('[SALESFORCE_SERVICE] Duplicate submission detected - skipping', [
            'transaction_id' => $transactionId,
            'payment_status' => $paymentStatus,
            'form_type' => $formType,
            'reason' => 'already_submitted'
        ], 'salesforce_logs.txt');

        return [
            'success' => true,
            'message' => 'Duplicate submission - already processed',
            'http_code' => 200,
            'duplicate' => true
        ];
    }

    /**
     * Handle submissions that are skipped (not sent to Salesforce)
     */
    private function handleSkippedSubmission($formType)
    {
        $message = 'Data not sent to Salesforce - form type not eligible for Salesforce submission';
        
        $this->logger->success('[SALESFORCE_SERVICE] Submission completed without Salesforce', [
            'form_type' => $formType,
            'response_code' => 200,
            'reason' => 'non_eligible_form_type',
            'message' => $message
        ], 'salesforce_logs.txt');

        return [
            'success' => true,
            'error' => null,
            'form' => $message,
            'http_code' => 200
        ];
    }

    /**
     * Process eligible submission and send to Salesforce
     */
    private function processAndSubmitToSalesforce($formData, $formType)
    {
        // Set safe return URL to prevent redirect loops
        $this->setSafeReturnUrl();
        
        // Map form data to Salesforce fields
        $salesforceData = $this->mapToSalesforceFields($formData, $formType);

        $this->logger->info('[SALESFORCE_SERVICE] Field mapping completed', [
            'form_type' => $formType,
            'mapped_fields' => array_keys($salesforceData),
            'salesforce_data' => $salesforceData
        ], 'salesforce_logs.txt');

        // Submit to Salesforce API
        $response = $this->submitToSalesforce($salesforceData);

        // Log response
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

        return $response;
    }

    /**
     * Handle exceptions during processing
     */
    private function handleException($e, $formData, $formType)
    {
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

    // ========================================================================
    // URL MANAGEMENT METHODS
    // ========================================================================

    /**
     * Set a safe return URL that prevents redirect loops
     * 
     * This method prevents infinite redirects when payment status check pages
     * try to set themselves as the return URL.
     */
    private function setSafeReturnUrl()
    {
        // Build current URL components
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';

        // Check if current page could cause redirect loops
        $isCheckStatusPage = strpos($uri, '/api/check-status') !== false;

        if ($isCheckStatusPage) {
            // Use safe fallback URL to prevent loops
            $safeUrl = $protocol . $host . '/thank-you.php';
            $this->logger->info('[SALESFORCE_SERVICE] Prevented redirect loop by using safe URL', [
                'original_uri' => $uri,
                'safe_url' => $safeUrl,
                'reason' => 'check-status page detected'
            ], 'salesforce_logs.txt');
        } else {
            // Current URL is safe to use
            $safeUrl = $protocol . $host . $uri;
            $this->logger->info('[SALESFORCE_SERVICE] Using current URL as return URL', [
                'url' => $safeUrl
            ], 'salesforce_logs.txt');
        }

        $this->returnUrl = $safeUrl;

        $this->logger->info('[SALESFORCE_SERVICE] Safe return URL set', [
            'return_url' => $this->returnUrl,
            'protocol' => $protocol,
            'host' => $host,
            'uri' => $uri,
            'is_check_status_page' => $isCheckStatusPage
        ], 'salesforce_logs.txt');
    }

    // ========================================================================
    // FIELD MAPPING METHODS
    // ========================================================================

    /**
     * Map internal form fields to Salesforce field structure
     * 
     * This is the core mapping logic that transforms website form data
     * into the format expected by Salesforce Web-to-Lead API.
     * 
     * @param array $formData Raw form data from website
     * @param string $formType Type of form being processed
     * @return array Mapped Salesforce data ready for submission
     */
    private function mapToSalesforceFields($formData, $formType)
    {
        // Step 1: Extract and process basic contact information
        $contactInfo = $this->extractContactInformation($formData);
        
        // Step 2: Get transaction details if available
        $transactionDetails = $this->getTransactionDetails($formData);
        
        // Step 3: Determine appropriate record type
        $recordType = $this->determineRecordType($formData, $formType);
        
        // Step 4: Build base Salesforce data structure
        $salesforceData = $this->buildBaseSalesforceData($contactInfo, $recordType);
        
        // Step 5: Map custom fields based on configuration
        $salesforceData = $this->mapCustomFields($salesforceData, $formData, $formType, $transactionDetails);
        
        // Step 6: Add return URL if appropriate
        $salesforceData = $this->addReturnUrlIfNeeded($salesforceData, $formData);
        
        // Step 7: Clean up empty fields
        return $this->cleanupEmptyFields($salesforceData);
    }

    /**
     * Extract and process contact information from form data
     */
    private function extractContactInformation($formData)
    {
        $firstName = '';
        $lastName = '';

        // Handle full name splitting
        if (!empty($formData['full_name'])) {
            $nameParts = explode(' ', trim($formData['full_name']), 2);
            $firstName = $nameParts[0] ?? '';
            $lastName = $nameParts[1] ?? '';
        } elseif (!empty($formData['firstname'])) {
            $firstName = $formData['firstname'];
        }

        return [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $formData['email'] ?? '',
            'phone' => $formData['phone'] ?? '',
        ];
    }

    /**
     * Determine appropriate Salesforce record type based on form data
     */
    private function determineRecordType($formData, $formType)
    {
        $helpType = strtolower($formData['help_type'] ?? '');

        // Dealership inquiries use dealership record type
        if ($helpType === 'dealership' || $helpType === 'dealership enquiry') {
            $recordType = $this->recordType['dealership'];
            $this->logger->info('[SALESFORCE_SERVICE] Using dealership record type', [
                'help_type' => $helpType,
                'record_type' => $recordType
            ], 'salesforce_logs.txt');
        } 
        // Test rides and all other forms use retail record type
        else {
            $recordType = $this->recordType['retail'];
            
            // Special handling for test rides
            if ($helpType === 'test_ride' || $helpType === 'test-ride') {
                // Ensure test ride date is properly mapped from 'date' field
                if (!empty($formData['date'])) {
                    $formData['test_ride_date'] = $formData['date'];
                }
                
                $this->logger->info('[SALESFORCE_SERVICE] Using retail record type for test ride', [
                    'help_type' => $helpType,
                    'record_type' => $recordType
                ], 'salesforce_logs.txt');
            } else {
                $this->logger->info('[SALESFORCE_SERVICE] Using retail record type', [
                    'help_type' => $helpType,
                    'record_type' => $recordType
                ], 'salesforce_logs.txt');
            }
        }

        return $recordType;
    }

    /**
     * Build base Salesforce data structure with required fields
     */
    private function buildBaseSalesforceData($contactInfo, $recordType)
    {
        return [
            // Salesforce required fields
            'oid' => $this->organizationId,
            'recordType' => $recordType,
            'status' => $this->status,
            'lead_source' => $this->leadSource,
            'encoding' => 'UTF-8',
            
            // Contact information
            'first_name' => $contactInfo['first_name'],
            'last_name' => $contactInfo['last_name'],
            'email' => $contactInfo['email'],
            'phone' => $contactInfo['phone'],
        ];
    }

    /**
     * Map custom fields based on configuration
     * 
     * This method processes each custom field mapping defined in the configuration
     * and applies form-specific business logic for data transformation.
     */
    private function mapCustomFields($salesforceData, $formData, $formType, $transactionDetails)
    {
        $customFields = $this->fieldMappings['custom_fields'];
        $helpType = strtolower($formData['help_type'] ?? '');

        foreach ($customFields as $salesforceFieldId => $formFieldName) {
            switch ($formFieldName) {
                // ---- BASIC FORM FIELDS ----
                case 'pincode':
                    $salesforceData[$salesforceFieldId] = $formData['pincode'] ?? '';
                    break;
                    
                case 'whatsapp_number':
                    // Use phone number as WhatsApp number (common practice)
                    $salesforceData[$salesforceFieldId] = $formData['phone'] ?? '';
                    break;
                    
                case 'address':
                    // Handle both 'address_1' (from book_now form) and 'address'
                    $addressValue = $formData['address_1'] ?? $formData['address'] ?? '';
                    $this->logAddressMapping($formData, $addressValue, $salesforceFieldId);
                    $salesforceData[$salesforceFieldId] = $addressValue;
                    break;
                    
                case 'message':
                    $salesforceData[$salesforceFieldId] = $formData['message'] ?? '';
                    break;

                // ---- PRODUCT RELATED FIELDS ----
                case 'product_variant':
                    $salesforceData[$salesforceFieldId] = $this->buildProductVariant($transactionDetails, $formData);
                    break;
                    
                case 'variant':
                    $salesforceData[$salesforceFieldId] = $this->mapVariant($transactionDetails['variant'] ?? $formData['variant'] ?? '');
                    break;
                    
                case 'color':
                    $salesforceData[$salesforceFieldId] = $transactionDetails['color'] ?? $formData['color'] ?? '';
                    break;

                // ---- DATE FIELDS ----
                case 'test_ride_date':
                    $salesforceData[$salesforceFieldId] = $this->getTestRideDate($formType, $helpType, 'test_ride_date');
                    break;
                    
                case 'test_ride_date_web':
                    $salesforceData[$salesforceFieldId] = $this->getTestRideDate($formType, $helpType, 'test_ride_date_web');
                    break;

                // ---- PAYMENT RELATED FIELDS ----
                case 'booking_amount':
                    $salesforceData[$salesforceFieldId] = $transactionDetails['amount'] ?? $this->getBookingAmount($formType);
                    break;
                    
                case 'payment_method':
                    $salesforceData[$salesforceFieldId] = $this->getPaymentMethod($formType, $transactionDetails);
                    break;
                    
                case 'payment_date':
                case 'payment_date_web':
                    $salesforceData[$salesforceFieldId] = $this->getPaymentDate($formType, $transactionDetails);
                    break;
                    
                case 'transaction_id':
                    $salesforceData[$salesforceFieldId] = $formData['txnid'] ?? $formData['transaction_id'] ?? '';
                    break;
                    
                case 'payment_status':
                    $salesforceData[$salesforceFieldId] = $this->mapPaymentStatus($formData['payment_status'] ?? '');
                    break;

                // ---- MAPPED VALUES ----
                case 'concern':
                    $salesforceData[$salesforceFieldId] = $this->mapConcern($formData['help_type'] ?? '');
                    break;
            }
        }

        return $salesforceData;
    }

    /**
     * Add return URL for form submissions (not payment status updates)
     */
    private function addReturnUrlIfNeeded($salesforceData, $formData)
    {
        // Detect if this is a payment status update (should not have return URL)
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

        return $salesforceData;
    }

    /**
     * Remove empty fields to keep Salesforce submission clean
     */
    private function cleanupEmptyFields($salesforceData)
    {
        return array_filter($salesforceData, function ($value) {
            return $value !== '' && $value !== null;
        });
    }

    // ========================================================================
    // SPECIALIZED FIELD PROCESSING METHODS
    // ========================================================================

    /**
     * Get test ride date - only populated for actual test ride forms
     * 
     * BUSINESS LOGIC:
     * - Test ride forms: Send today's date
     * - All other forms: Send empty string
     */
    private function getTestRideDate($formType, $helpType, $fieldName)
    {
        $isTestRideForm = (
            $formType === 'test_ride' || 
            $helpType === 'test_ride' || 
            $helpType === 'test-ride'
        );

        if ($isTestRideForm) {
            $todayDate = $this->formatDateForSalesforce(date('Y-m-d'));
            
            $this->logger->info("[SALESFORCE_SERVICE] Setting {$fieldName} for test ride form", [
                'form_type' => $formType,
                'help_type' => $helpType,
                $fieldName => $todayDate
            ], 'salesforce_logs.txt');
            
            return $todayDate;
        } else {
            $this->logger->info("[SALESFORCE_SERVICE] Skipping {$fieldName} for non-test-ride form", [
                'form_type' => $formType,
                'help_type' => $helpType,
                'reason' => 'not_test_ride_form'
            ], 'salesforce_logs.txt');
            
            return '';
        }
    }

    /**
     * Get payment date - only for book_now forms
     * 
     * BUSINESS LOGIC:
     * - book_now forms: Use transaction date from DB or current date
     * - Other forms: Empty string
     */
    private function getPaymentDate($formType, $transactionDetails = [])
    {
        if ($formType !== 'book_now') {
            return '';
        }

        // Prioritize transaction date from database
        if (!empty($transactionDetails['transaction_date'])) {
            $this->logger->info('[SALESFORCE_SERVICE] Using transaction date from database', [
                'transaction_date' => $transactionDetails['transaction_date'],
                'transaction_id' => $transactionDetails['transaction_id'] ?? 'unknown'
            ], 'salesforce_logs.txt');
            
            return $transactionDetails['transaction_date'];
        }

        // Fallback to current date
        $currentDate = $this->formatDateForSalesforce(date('Y-m-d'));
        
        $this->logger->info('[SALESFORCE_SERVICE] Using current date as payment date fallback', [
            'payment_date' => $currentDate,
            'reason' => 'no_transaction_date_found'
        ], 'salesforce_logs.txt');
        
        return $currentDate;
    }

    /**
     * Get payment method - only for book_now forms
     */
    private function getPaymentMethod($formType, $transactionDetails = [])
    {
        if ($formType !== 'book_now') {
            return '';
        }

        // Try to extract from payment gateway response
        if (!empty($transactionDetails['payment_details'])) {
            $paymentDetails = json_decode($transactionDetails['payment_details'], true);

            if (isset($paymentDetails['paymentDetails'][0]['paymentMode'])) {
                $paymentMode = $paymentDetails['paymentDetails'][0]['paymentMode'];

                $this->logger->info('[SALESFORCE_SERVICE] Payment mode extracted from gateway response', [
                    'payment_mode' => $paymentMode,
                    'transaction_id' => $transactionDetails['transaction_id'] ?? 'unknown'
                ], 'salesforce_logs.txt');

                return $paymentMode;
            }
        }

        // Fallback to UPI for book_now forms
        $this->logger->info('[SALESFORCE_SERVICE] Using fallback payment mode UPI for book_now', [
            'reason' => 'payment_mode_not_found_in_response',
            'transaction_id' => $transactionDetails['transaction_id'] ?? 'unknown'
        ], 'salesforce_logs.txt');

        return 'UPI';
    }

    /**
     * Get booking amount based on form type
     */
    private function getBookingAmount($formType)
    {
        return $formType === 'book_now' ? '1000' : '';
    }

    // ========================================================================
    // VALUE MAPPING METHODS
    // ========================================================================

    /**
     * Map variant values using configuration-based mappings
     */
    private function mapVariant($variant)
    {
        if (empty($variant)) {
            return '';
        }

        $variant = trim(strtolower($variant));
        $variantMappings = $this->fieldMappings['value_mappings']['variant'] ?? [];

        return $variantMappings[$variant] ?? $variant;
    }

    /**
     * Map concern values using configuration-based mappings
     */
    private function mapConcern($concern)
    {
        if (empty($concern)) {
            return '';
        }

        $concern = trim(strtolower($concern));
        $concernMappings = $this->fieldMappings['value_mappings']['concern'] ?? [];

        return $concernMappings[$concern] ?? $concern;
    }

    /**
     * Map payment status values using configuration-based mappings
     */
    private function mapPaymentStatus($status)
    {
        if (empty($status)) {
            return '';
        }

        $status = trim(strtolower($status));
        $paymentStatusMappings = $this->fieldMappings['value_mappings']['payment_status'] ?? [];

        return $paymentStatusMappings[$status] ?? $status;
    }

    /**
     * Build product variant string combining variant and color
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

    // ========================================================================
    // UTILITY METHODS
    // ========================================================================

    /**
     * Format date to DD-MM-YYYY format for Salesforce
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

        // If parsing fails, return original
        return $date;
    }

    /**
     * Log address field mapping for debugging
     */
    private function logAddressMapping($formData, $addressValue, $salesforceFieldId)
    {
        $this->logger->info('[SALESFORCE_SERVICE] Address field mapping', [
            'address_1' => $formData['address_1'] ?? 'not_set',
            'address' => $formData['address'] ?? 'not_set',
            'final_value' => $addressValue,
            'salesforce_field' => $salesforceFieldId
        ], 'salesforce_logs.txt');
    }

    // ========================================================================
    // DATABASE INTEGRATION METHODS
    // ========================================================================

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
            // Load DatabaseHandler if needed
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

                // Process transaction date
                $transactionDate = $this->processTransactionDate($transaction);

                return [
                    'amount' => $transaction['amount'] ?? '',
                    'variant' => $transaction['variant'] ?? '',
                    'color' => $transaction['color'] ?? '',
                    'transaction_date' => $transactionDate,
                    'payment_details' => $transaction['payment_details'] ?? null,
                    'transaction_id' => $transactionId
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
     * Process transaction date from various possible field names
     */
    private function processTransactionDate($transaction)
    {
        $possibleDateFields = ['created_at', 'transaction_date', 'payment_date', 'date_created'];
        
        foreach ($possibleDateFields as $field) {
            if (!empty($transaction[$field])) {
                return date('d-m-Y', strtotime($transaction[$field]));
            }
        }
        
        // Fallback to current date
        return date('d-m-Y');
    }

    // ========================================================================
    // DUPLICATE PREVENTION METHODS
    // ========================================================================

    /**
     * Check if this is a duplicate submission
     */
    private function isDuplicateSubmission($formData, $formType)
    {
        $transactionId = $formData['txnid'] ?? $formData['transaction_id'] ?? '';
        $paymentStatus = $formData['payment_status'] ?? '';

        // Only check for duplicates if we have transaction info
        if (empty($transactionId) || empty($paymentStatus)) {
            return false;
        }

        return $this->checkDuplicateInDatabase($transactionId, $paymentStatus, $formType);
    }

    /**
     * Check database for existing submission
     */
    private function checkDuplicateInDatabase($transactionId, $paymentStatus, $formType)
    {
        try {
            if (!class_exists('DatabaseHandler')) {
                require_once __DIR__ . '/DatabaseHandler.php';
            }

            $db = new DatabaseHandler();
            $connection = $db->getConnection();

            // Normalize payment status
            $submissionType = $this->normalizeSubmissionType($paymentStatus);

            // Check for existing submission
            $stmt = $connection->prepare("
                SELECT COUNT(*) as count, submitted_at 
                FROM salesforce_submissions 
                WHERE transaction_id = ? AND submission_type = ? AND form_type = ?
            ");

            $stmt->execute([$transactionId, $submissionType, $formType]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result && $result['count'] > 0) {
                $this->logger->info('[SALESFORCE_SERVICE] Found existing submission', [
                    'transaction_id' => $transactionId,
                    'payment_status' => $paymentStatus,
                    'submission_type' => $submissionType,
                    'form_type' => $formType,
                    'existing_submission_date' => $result['submitted_at'],
                    'duplicate_count' => $result['count']
                ], 'salesforce_logs.txt');

                return true; // Duplicate found
            }

            // Record new submission
            $this->recordNewSubmission($connection, $transactionId, $formType, $submissionType, $paymentStatus);

            return false; // Not a duplicate

        } catch (Exception $e) {
            $this->logger->error('[SALESFORCE_SERVICE] Error checking for duplicate submission', [
                'transaction_id' => $transactionId,
                'payment_status' => $paymentStatus,
                'error' => $e->getMessage(),
                'error_code' => $e->getCode()
            ], 'salesforce_logs.txt');

            // On error, allow submission to proceed (safer approach)
            return false;
        }
    }

    /**
     * Normalize payment status to submission type
     */
    private function normalizeSubmissionType($paymentStatus)
    {
        $submissionType = strtolower($paymentStatus);
        
        if ($submissionType === 'completed' || $submissionType === 'success') {
            return 'success';
        } elseif ($submissionType === 'failed' || $submissionType === 'failure') {
            return 'failed';
        } else {
            return 'pending';
        }
    }

    /**
     * Record new submission in database
     */
    private function recordNewSubmission($connection, $transactionId, $formType, $submissionType, $paymentStatus)
    {
        // Get customer info if available
        $customerEmail = '';
        $customerPhone = '';
        
        $transactionDetails = $this->getTransactionDetails(['transaction_id' => $transactionId]);
        if (!empty($transactionDetails)) {
            $db = new DatabaseHandler();
            $transaction = $db->getTransaction($transactionId);
            $customerEmail = $transaction['email'] ?? '';
            $customerPhone = $transaction['phone'] ?? '';
        }

        $insertStmt = $connection->prepare("
            INSERT INTO salesforce_submissions 
            (transaction_id, form_type, submission_type, customer_email, customer_phone, submitted_at) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ");

        $insertStmt->execute([$transactionId, $formType, $submissionType, $customerEmail, $customerPhone]);

        $this->logger->info('[SALESFORCE_SERVICE] New submission recorded', [
            'transaction_id' => $transactionId,
            'payment_status' => $paymentStatus,
            'submission_type' => $submissionType,
            'form_type' => $formType
        ], 'salesforce_logs.txt');
    }

    // ========================================================================
    // SALESFORCE API COMMUNICATION
    // ========================================================================

    /**
     * Submit data to Salesforce Web-to-Lead endpoint
     * 
     * This method handles the actual HTTP communication with Salesforce API
     * including error handling, timing, and response processing.
     */
    private function submitToSalesforce($salesforceData)
    {
        $startTime = microtime(true);

        $this->logger->info('[SALESFORCE_SERVICE] Initiating HTTP request to Salesforce', [
            'endpoint' => $this->salesforceEndpoint,
            'data_fields' => array_keys($salesforceData)
        ], 'salesforce_logs.txt');

        // Initialize cURL with proper configuration
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $this->salesforceEndpoint,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($salesforceData),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_USERAGENT => 'KineticEV-WebToLead/2.0',
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded',
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8'
            ]
        ]);

        // Execute request
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $responseTime = (microtime(true) - $startTime) * 1000; // Convert to milliseconds

        // Handle cURL errors
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

        // Evaluate response
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