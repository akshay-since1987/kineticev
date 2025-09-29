<?php

// Prevent direct access
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    http_response_code(404);
    exit();
}

class SmsService
{
    private $config;
    private $logger;

    public function __construct()
    {
        // Load configuration properly - use include instead of require_once to avoid caching issues
        $configPath = __DIR__ . '/config.php';
        if (file_exists($configPath)) {
            $this->config = include $configPath;
        } else {
            throw new Exception("Configuration file not found: $configPath");
        }
        
        // Validate config structure
        if (!is_array($this->config) || !isset($this->config['sms'])) {
            throw new Exception("Invalid configuration: SMS configuration not found");
        }
        
        // Initialize logger
        require_once 'Logger.php';
        $this->logger = Logger::getInstance();
    }

    /**
     * Send thank you SMS to customer
     */
    public function sendThankYouSms($phoneNumber, $bookingId)
    {
        // Validate SMS configuration exists
        if (!isset($this->config['sms']['thank_you'])) {
            $this->logger->error("Thank you SMS configuration not found", [], 'sms_logs.txt');
            return [
                'success' => false,
                'error' => 'SMS configuration not found',
                'http_code' => 0,
                'response' => null
            ];
        }

        $smsConfig = $this->config['sms']['thank_you'];
        
        // Validate required configuration fields
        $requiredFields = ['username', 'api_key', 'sender_id', 'route', 'template_id', 'base_url', 'template'];
        foreach ($requiredFields as $field) {
            if (!isset($smsConfig[$field])) {
                $this->logger->error("Missing SMS configuration field: $field", [], 'sms_logs.txt');
                return [
                    'success' => false,
                    'error' => "Missing configuration field: $field",
                    'http_code' => 0,
                    'response' => null
                ];
            }
        }
        
        // Clean phone number (remove spaces, dashes, etc.)
        $cleanPhone = preg_replace('/[^0-9+]/', '', $phoneNumber);
        
        // Ensure phone number has country code
        if (substr($cleanPhone, 0, 1) !== '+') {
            if (substr($cleanPhone, 0, 2) === '91') {
                $cleanPhone = '+' . $cleanPhone;
            } elseif (strlen($cleanPhone) === 10) {
                $cleanPhone = '+91' . $cleanPhone;
            }
        }

        // Replace template variable with booking ID
        $message = str_replace('{#var#}', $bookingId, $smsConfig['template']);

        // Prepare mobile number for SMS API (remove + and country code)
        $smsPhone = $cleanPhone;
        if (strpos($smsPhone, '+91') === 0) {
            $smsPhone = substr($smsPhone, 3); // Remove +91
        } elseif (strpos($smsPhone, '+') === 0) {
            $smsPhone = substr($smsPhone, 1); // Remove just +
        }

        // Prepare SMS API request
        $postData = [
            'username' => $smsConfig['username'],
            'apikey' => $smsConfig['api_key'],
            'apirequest' => 'Text',
            'sender' => $smsConfig['sender_id'],
            'route' => $smsConfig['route'],
            'mobile' => $smsPhone,
            'message' => $message,
            'TemplateID' => $smsConfig['template_id'],
            'format' => 'JSON'
        ];

        $this->logger->info("Preparing to send thank you SMS", [
            'phone' => $cleanPhone, // Keep full format for logging
            'sms_phone' => $smsPhone, // Show the actual number sent to API
            'booking_id' => $bookingId,
            'template_id' => $smsConfig['template_id']
        ], 'sms_logs.txt');

        // Send SMS via cURL with dynamic protocol
        $response = $this->sendSmsRequest($smsConfig['base_url'], $postData);
        
        return $response;
    }

    /**
     * Send OTP SMS to customer
     */
    public function sendOtpSms($phoneNumber, $otp)
    {
        // Check if we're in development mode
        if (isset($this->config['development_mode']) && $this->config['development_mode'] === true) {
            $this->logger->info("Development mode: Skipping actual SMS sending", [
                'phone' => $phoneNumber,
                'otp' => $otp
            ], 'sms_logs.txt');
            
            return [
                'success' => true,
                'development_mode' => true,
                'message' => 'OTP sent successfully (development mode)',
                'http_code' => 200,
                'response' => 'Development mode - SMS not actually sent'
            ];
        }
        
        // Validate SMS configuration exists
        if (!isset($this->config['sms']['otp_message'])) {
            $this->logger->error("OTP SMS configuration not found", [], 'sms_logs.txt');
            return [
                'success' => false,
                'error' => 'OTP SMS configuration not found',
                'http_code' => 0,
                'response' => null
            ];
        }

        $smsConfig = $this->config['sms']['otp_message'];
        
        // Validate required configuration fields
        $requiredFields = ['username', 'api_key', 'sender_id', 'route', 'template_id', 'base_url', 'template'];
        foreach ($requiredFields as $field) {
            if (!isset($smsConfig[$field])) {
                $this->logger->error("Missing OTP SMS configuration field: $field", [], 'sms_logs.txt');
                return [
                    'success' => false,
                    'error' => "Missing OTP configuration field: $field",
                    'http_code' => 0,
                    'response' => null
                ];
            }
        }
        
        // Clean phone number
        $cleanPhone = preg_replace('/[^0-9+]/', '', $phoneNumber);
        
        // Ensure phone number has country code
        if (substr($cleanPhone, 0, 1) !== '+') {
            if (substr($cleanPhone, 0, 2) === '91') {
                $cleanPhone = '+' . $cleanPhone;
            } elseif (strlen($cleanPhone) === 10) {
                $cleanPhone = '+91' . $cleanPhone;
            }
        }

        // Replace template variable with OTP
        $message = str_replace('{#var#}', $otp, $smsConfig['template']);

        // Prepare mobile number for SMS API (remove + and country code)
        $smsPhone = $cleanPhone;
        if (strpos($smsPhone, '+91') === 0) {
            $smsPhone = substr($smsPhone, 3); // Remove +91
        } elseif (strpos($smsPhone, '+') === 0) {
            $smsPhone = substr($smsPhone, 1); // Remove just +
        }

        // Prepare SMS API request
        $postData = [
            'username' => $smsConfig['username'],
            'apikey' => $smsConfig['api_key'],
            'apirequest' => 'Text',
            'sender' => $smsConfig['sender_id'],
            'route' => $smsConfig['route'],
            'mobile' => $smsPhone,
            'message' => $message,
            'TemplateID' => $smsConfig['template_id'],
            'format' => 'JSON'
        ];

        $this->logger->info("Preparing to send OTP SMS", [
            'phone' => $cleanPhone, // Keep full format for logging
            'sms_phone' => $smsPhone, // Show the actual number sent to API
            'template_id' => $smsConfig['template_id']
        ], 'sms_logs.txt');

        // Send SMS via cURL with dynamic protocol
        $response = $this->sendSmsRequest($smsConfig['base_url'], $postData);
        
        return $response;
    }

    /**
     * Send SMS request via cURL
     * 
     * FIXES IMPLEMENTED:
     * 1. Force HTTP protocol to avoid SSL certificate mismatch with IP-based SMS API
     * 2. Disable SSL verification as backup measure for any HTTPS fallback
     * 
     * Issue: SMS provider uses IP address (123.108.46.13) instead of domain name,
     * causing SSL certificate "no alternative certificate subject name matches target ipv4 address" error
     */
    private function sendSmsRequest($url, $postData)
    {
        // Force HTTP for SMS API to avoid SSL certificate issues with IP-based endpoints
        // SMS provider uses IP address (123.108.46.13) which causes SSL certificate mismatch
        $originalUrl = $url;
        $url = str_replace('https://', 'http://', $url);
        
        // If no protocol specified, add http protocol for SMS API
        if (strpos($url, 'http://') !== 0 && strpos($url, 'https://') !== 0) {
            $url = 'http://' . $url;
        }
        
        // Build query string for GET request
        $queryString = http_build_query($postData);
        $fullUrl = $url . '?' . $queryString;
        
        $this->logger->info("SMS API URL prepared for GET request", [
            'original_url' => $originalUrl,
            'forced_protocol' => 'http',
            'final_url' => $url,
            'full_url' => $fullUrl
        ], 'sms_logs.txt');

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => $fullUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json'
            ],
            // Disable SSL verification for SMS API only (backup fix for IP-based endpoints)
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        curl_close($curl);

        $success = ($httpCode === 200 && $response && empty($error));

        if ($success) {
            $this->logger->success("SMS API response received successfully", [
                'http_code' => $httpCode,
                'response' => $response
            ], 'sms_logs.txt');
        } else {
            $this->logger->error("SMS API request failed", [
                'http_code' => $httpCode,
                'response' => $response,
                'curl_error' => $error
            ], 'sms_logs.txt');
        }

        return [
            'success' => $success,
            'http_code' => $httpCode,
            'response' => $response,
            'error' => $error
        ];
    }
}
