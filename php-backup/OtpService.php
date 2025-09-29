<?php

// Initialize production time            // Create OTP verifications table
            $createOtpTable = "
                CREATE TABLE IF NOT EXISTS otp_verifications (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    phone VARCHAR(15) NOT NULL,
                    otp VARCHAR(6) NOT NULL,
                    purpose ENUM('contact_form', 'test_ride', 'booking_form') NOT NULL,
                    verified BOOLEAN DEFAULT FALSE,
                    expires_at TIMESTAMP NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    verified_at TIMESTAMP NULL,
                    attempts INT DEFAULT 0,
                    max_attempts INT DEFAULT 3,
                    
                    INDEX idx_phone_purpose (phone, purpose),
                    INDEX idx_otp_expires (otp, expires_at),
                    INDEX idx_created_at (created_at)
                )";require_once __DIR__ . '/production-timezone-guard.php';

// Prevent direct access
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    http_response_code(404);
    exit();
}

class OtpService
{
    private $db;
    private $logger;
    private $smsService;
    private $config;

    public function __construct()
    {
        require_once __DIR__ . '/DatabaseHandler.php';
        require_once __DIR__ . '/Logger.php';
        require_once __DIR__ . '/SmsService.php';
        
        // Set connection timeout to prevent hanging
        ini_set('default_socket_timeout', 10);
        
        try {
            $this->db = new DatabaseHandler();
        } catch (Exception $e) {
            // Log the database error and re-throw for handling upstream
            error_log("OtpService database connection failed: " . $e->getMessage());
            throw new Exception("Database connection failed: " . $e->getMessage(), 0, $e);
        }
        $this->logger = Logger::getInstance();
        $this->smsService = new SmsService();
        $this->config = include __DIR__ . '/config.php';
        
        // Ensure timezone is set for this specific connection using production guard
        applyTimezoneToConnection($this->db->getConnection(), 'OtpService_connection');
        
        // Run database migrations on construction
        $this->runMigrations();
    }

    /**
     * Run database migrations for OTP verification
     */
    private function runMigrations()
    {
        try {
            $connection = $this->db->getConnection();
            
            // Create OTP verifications table
            $createOtpTable = "
                CREATE TABLE IF NOT EXISTS otp_verifications (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    phone VARCHAR(15) NOT NULL,
                    otp VARCHAR(6) NOT NULL,
                    purpose ENUM('contact_form', 'test_ride') NOT NULL,
                    verified BOOLEAN DEFAULT FALSE,
                    expires_at TIMESTAMP NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    verified_at TIMESTAMP NULL,
                    attempts INT DEFAULT 0,
                    max_attempts INT DEFAULT 3,
                    
                    INDEX idx_phone_purpose (phone, purpose),
                    INDEX idx_otp_expires (otp, expires_at),
                    INDEX idx_created_at (created_at)
                )
            ";
            
            $connection->query($createOtpTable);
            
            // Update ENUM column to include booking_form if table exists
            $this->updatePurposeEnum();
            
            // Add phone_verified columns to existing tables if they don't exist
            $this->addColumnIfNotExists('contacts', 'phone_verified', 'BOOLEAN DEFAULT FALSE AFTER phone');
            $this->addColumnIfNotExists('contacts', 'phone_verified_at', 'TIMESTAMP NULL AFTER phone_verified');
            $this->addColumnIfNotExists('test_drives', 'phone_verified', 'BOOLEAN DEFAULT FALSE AFTER phone');
            $this->addColumnIfNotExists('test_drives', 'phone_verified_at', 'TIMESTAMP NULL AFTER phone_verified');
            
            $this->logger->info('[OTP_SERVICE] Database migrations completed successfully');
            
        } catch (Exception $e) {
            $this->logger->error('[OTP_SERVICE] Migration failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Add column to table if it doesn't exist
     */
    private function addColumnIfNotExists($table, $column, $definition)
    {
        try {
            $connection = $this->db->getConnection();
            
            // Check if column exists
            $checkColumn = "SHOW COLUMNS FROM `{$table}` LIKE ?";
            $stmt = $connection->prepare($checkColumn);
            $stmt->execute([$column]);
            
            if ($stmt->rowCount() == 0) {
                // Column doesn't exist, add it
                $addColumn = "ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}";
                $connection->exec($addColumn);
                $this->logger->info("[OTP_SERVICE] Added column {$column} to table {$table}");
            }
        } catch (Exception $e) {
            $this->logger->warning("[OTP_SERVICE] Failed to add column {$column} to table {$table}", [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Update purpose ENUM to include booking_form
     */
    private function updatePurposeEnum()
    {
        try {
            $connection = $this->db->getConnection();
            
            // Check if the table exists and has the old ENUM structure
            $checkTable = "SHOW COLUMNS FROM otp_verifications LIKE 'purpose'";
            $result = $connection->query($checkTable);
            
            if ($result && $result->rowCount() > 0) {
                $column = $result->fetch(PDO::FETCH_ASSOC);
                $type = $column['Type'];
                
                // Check if booking_form is not already in the ENUM
                if (strpos($type, 'booking_form') === false) {
                    $this->logger->info('[OTP_SERVICE] Updating purpose ENUM to include booking_form');
                    
                    $alterTable = "ALTER TABLE otp_verifications 
                                  MODIFY COLUMN purpose ENUM('contact_form', 'test_ride', 'booking_form') NOT NULL";
                    $connection->exec($alterTable);
                    
                    $this->logger->info('[OTP_SERVICE] Successfully updated purpose ENUM');
                } else {
                    $this->logger->debug('[OTP_SERVICE] Purpose ENUM already includes booking_form');
                }
            }
        } catch (Exception $e) {
            $this->logger->warning("[OTP_SERVICE] Failed to update purpose ENUM", [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Resend existing OTP via SMS
     */
    private function resendExistingOtp($phone, $existingOtp, $purpose)
    {
        try {
            $this->logger->info('[OTP_SERVICE] Resending existing valid OTP', [
                'phone' => $phone,
                'purpose' => $purpose,
                'otp_id' => $existingOtp['id'],
                'expires_at' => $existingOtp['expires_at']
            ]);
            
            // Resend SMS with existing OTP
            $smsResult = $this->smsService->sendOtpSms($phone, $existingOtp['otp']);
            
            // Calculate remaining time
            $expiresAt = new DateTime($existingOtp['expires_at'], new DateTimeZone('Asia/Kolkata'));
            $now = new DateTime('now', new DateTimeZone('Asia/Kolkata'));
            $remainingSeconds = max(0, $expiresAt->getTimestamp() - $now->getTimestamp());
            
            $response = [
                'success' => true,
                'message' => 'OTP resent to your mobile number',
                'expires_in' => $remainingSeconds,
                'existing_otp' => true,
                'resent' => true,
                'sms_result' => $smsResult
            ];
            
            // In development mode, include the existing OTP
            if (isset($this->config['development_mode']) && $this->config['development_mode'] === true) {
                $response['development_otp'] = $existingOtp['otp'];
                $response['development_mode'] = true;
            }
            
            return $response;
            
        } catch (Exception $e) {
            $this->logger->error('[OTP_SERVICE] Failed to resend existing OTP', [
                'phone' => $phone,
                'purpose' => $purpose,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get existing valid OTP for phone and purpose
     */
    private function getValidOtp($phone, $purpose)
    {
        try {
            $connection = $this->db->getConnection();
            $stmt = $connection->prepare("
                SELECT id, otp, expires_at 
                FROM otp_verifications 
                WHERE phone = ? AND purpose = ? 
                AND verified = FALSE AND expires_at > NOW()
                ORDER BY created_at DESC 
                LIMIT 1
            ");
            $stmt->execute([$phone, $purpose]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $this->logger->error('[OTP_SERVICE] Failed to check existing OTP', [
                'phone' => $phone,
                'purpose' => $purpose,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Generate and send OTP
     */
    public function generateAndSendOtp($phone, $purpose = 'contact_form', $forceNew = false)
    {
        try {
            // Clean phone number
            $cleanPhone = $this->cleanPhoneNumber($phone);
            
            // Check if there's already a valid OTP for this phone/purpose
            $existingOtp = $this->getValidOtp($cleanPhone, $purpose);
            if ($existingOtp) {
                if ($forceNew) {
                    // Resend SMS with existing OTP
                    $resendResult = $this->resendExistingOtp($cleanPhone, $existingOtp, $purpose);
                    if ($resendResult) {
                        return $resendResult;
                    }
                } else {
                    // Return existing OTP info without resending SMS
                    $this->logger->info('[OTP_SERVICE] Returning existing valid OTP', [
                        'phone' => $cleanPhone,
                        'purpose' => $purpose,
                        'otp_id' => $existingOtp['id'],
                        'expires_at' => $existingOtp['expires_at']
                    ]);
                    
                    // Calculate remaining time
                    $expiresAt = new DateTime($existingOtp['expires_at'], new DateTimeZone('Asia/Kolkata'));
                    $now = new DateTime('now', new DateTimeZone('Asia/Kolkata'));
                    $remainingSeconds = max(0, $expiresAt->getTimestamp() - $now->getTimestamp());
                    
                    $response = [
                        'success' => true,
                        'message' => 'OTP already sent to your mobile number',
                        'expires_in' => $remainingSeconds,
                        'existing_otp' => true
                    ];
                    
                    // In development mode, include the existing OTP
                    if (isset($this->config['development_mode']) && $this->config['development_mode'] === true) {
                        $response['development_otp'] = $existingOtp['otp'];
                        $response['development_mode'] = true;
                    }
                    
                    return $response;
                }
            }
            
            // Check rate limiting (max 3 OTPs per phone per hour)
            if (!$this->checkRateLimit($cleanPhone)) {
                return [
                    'success' => false,
                    'error' => 'Too many OTP requests. Please try again later.',
                    'rate_limited' => true
                ];
            }
            
            // Generate 6-digit OTP
            $otp = sprintf('%06d', mt_rand(100000, 999999));
            
            // Store OTP in database (use MySQL time functions for consistency)
            $connection = $this->db->getConnection();
            $stmt = $connection->prepare("
                INSERT INTO otp_verifications (phone, otp, purpose, expires_at) 
                VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 5 MINUTE))
            ");
            
            if (!$stmt->execute([$cleanPhone, $otp, $purpose])) {
                throw new Exception('Failed to store OTP in database');
            }
            
            // Send OTP via SMS
            $smsResult = $this->smsService->sendOtpSms($cleanPhone, $otp);
            
            $this->logger->info('[OTP_SERVICE] OTP generated and sent', [
                'phone' => $cleanPhone,
                'purpose' => $purpose,
                'sms_success' => $smsResult['success'] ?? false,
                'expires_in_minutes' => 5
            ]);
            
            // Prepare response
            $response = [
                'success' => true,
                'message' => 'OTP sent successfully to your mobile number',
                'expires_in' => 300, // 5 minutes in seconds
                'sms_result' => $smsResult
            ];
            
            // In development mode, include the OTP in response for testing
            if (isset($this->config['development_mode']) && $this->config['development_mode'] === true) {
                $response['development_otp'] = $otp;
                $response['development_mode'] = true;
                $this->logger->info('[OTP_SERVICE] Development mode - OTP included in response', [
                    'phone' => $cleanPhone,
                    'otp' => $otp
                ]);
            }
            
            return $response;
            
        } catch (Exception $e) {
            $this->logger->error('[OTP_SERVICE] Failed to generate OTP', [
                'phone' => $phone,
                'purpose' => $purpose,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'error' => 'Failed to send OTP. Please try again.',
                'exception' => $e->getMessage()
            ];
        }
    }

    /**
     * Verify OTP
     */
    public function verifyOtp($phone, $otp, $purpose = 'contact_form')
    {
        try {
            $cleanPhone = $this->cleanPhoneNumber($phone);
            
            $connection = $this->db->getConnection();
            
            // Find valid OTP - get fresh data to avoid stale attempts counter
            $stmt = $connection->prepare("
                SELECT id, attempts, max_attempts 
                FROM otp_verifications 
                WHERE phone = ? AND otp = ? AND purpose = ? 
                AND verified = FALSE AND expires_at > NOW()
                ORDER BY created_at DESC 
                LIMIT 1
            ");
            $stmt->execute([$cleanPhone, $otp, $purpose]);
            $otpRecord = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$otpRecord) {
                // For invalid OTP, increment attempts on the most recent OTP for this phone/purpose
                $this->incrementLatestOtpAttempts($cleanPhone, $purpose);
                
                $this->logger->info('[OTP_SERVICE] Invalid OTP attempt', [
                    'phone' => $cleanPhone,
                    'purpose' => $purpose,
                    'attempted_otp' => substr($otp, 0, 2) . '****' // Log only first 2 digits for security
                ]);
                
                return [
                    'success' => false,
                    'error' => 'Invalid or expired OTP',
                    'invalid_otp' => true
                ];
            }
            
            // For valid OTP, check if attempts limit was exceeded 
            // But be lenient - if this is the correct OTP, allow verification even if attempts were incremented by previous invalid attempts
            $this->logger->info('[OTP_SERVICE] Found valid OTP record', [
                'phone' => $cleanPhone,
                'purpose' => $purpose,
                'otp_id' => $otpRecord['id'],
                'current_attempts' => $otpRecord['attempts'],
                'max_attempts' => $otpRecord['max_attempts']
            ]);
            
            // Only reject if attempts significantly exceed limit (allow for race conditions with correct OTP)
            if ($otpRecord['attempts'] > $otpRecord['max_attempts']) {
                $this->logger->warning('[OTP_SERVICE] OTP attempts significantly exceeded', [
                    'phone' => $cleanPhone,
                    'purpose' => $purpose,
                    'otp_id' => $otpRecord['id'],
                    'attempts' => $otpRecord['attempts'],
                    'max_attempts' => $otpRecord['max_attempts']
                ]);
                
                return [
                    'success' => false,
                    'error' => 'Maximum OTP attempts exceeded. Please request a new OTP.',
                    'max_attempts_exceeded' => true
                ];
            }
            
            // Valid OTP found - mark as verified (don't increment attempts for correct OTP)
            $updateStmt = $connection->prepare("
                UPDATE otp_verifications 
                SET verified = TRUE, verified_at = NOW() 
                WHERE id = ?
            ");
            $updateStmt->execute([$otpRecord['id']]);
            
            // Clean up old OTPs for this phone/purpose
            $cleanupStmt = $connection->prepare("
                DELETE FROM otp_verifications 
                WHERE phone = ? AND purpose = ? AND id != ?
            ");
            $cleanupStmt->execute([$cleanPhone, $purpose, $otpRecord['id']]);
            
            $this->logger->info('[OTP_SERVICE] OTP verified successfully', [
                'phone' => $cleanPhone,
                'purpose' => $purpose,
                'otp_id' => $otpRecord['id']
            ]);
            
            return [
                'success' => true,
                'message' => 'OTP verified successfully',
                'verified' => true
            ];
            
        } catch (Exception $e) {
            $this->logger->error('[OTP_SERVICE] OTP verification failed', [
                'phone' => $phone,
                'purpose' => $purpose,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'error' => 'OTP verification failed. Please try again.',
                'exception' => $e->getMessage()
            ];
        }
    }

    /**
     * Check if phone number is verified
     */
    public function isPhoneVerified($phone, $purpose = 'contact_form')
    {
        try {
            $cleanPhone = $this->cleanPhoneNumber($phone);
            
            $connection = $this->db->getConnection();
            $stmt = $connection->prepare("
                SELECT COUNT(*) as count 
                FROM otp_verifications 
                WHERE phone = ? AND purpose = ? AND verified = TRUE 
                AND verified_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
            ");
            $stmt->execute([$cleanPhone, $purpose]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $row['count'] > 0;
            
        } catch (Exception $e) {
            $this->logger->error('[OTP_SERVICE] Failed to check phone verification', [
                'phone' => $phone,
                'purpose' => $purpose,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Check rate limiting
     */
    private function checkRateLimit($phone)
    {
        try {
            $connection = $this->db->getConnection();
            $stmt = $connection->prepare("
                SELECT COUNT(*) as count 
                FROM otp_verifications 
                WHERE phone = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
            ");
            $stmt->execute([$phone]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $row['count'] < 15; // Max 3 OTPs per hour
            
        } catch (Exception $e) {
            $this->logger->error('[OTP_SERVICE] Rate limit check failed', [
                'phone' => $phone,
                'error' => $e->getMessage()
            ]);
            return true; // Allow on error
        }
    }

    /**
     * Increment OTP attempts for the most recent OTP - only for truly invalid OTPs
     */
    private function incrementLatestOtpAttempts($phone, $purpose)
    {
        try {
            $connection = $this->db->getConnection();
            
            // First, find the most recent OTP record for this phone/purpose
            $findStmt = $connection->prepare("
                SELECT id, otp, attempts, max_attempts
                FROM otp_verifications 
                WHERE phone = ? AND purpose = ? AND verified = FALSE AND expires_at > NOW()
                ORDER BY created_at DESC 
                LIMIT 1
            ");
            $findStmt->execute([$phone, $purpose]);
            $otpRecord = $findStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($otpRecord) {
                // Only increment if we haven't exceeded the limit yet
                if ($otpRecord['attempts'] < $otpRecord['max_attempts']) {
                    $updateStmt = $connection->prepare("
                        UPDATE otp_verifications 
                        SET attempts = attempts + 1 
                        WHERE id = ? AND attempts < max_attempts
                    ");
                    $updateStmt->execute([$otpRecord['id']]);
                    
                    $this->logger->info('[OTP_SERVICE] Incremented attempts for latest OTP', [
                        'phone' => $phone,
                        'purpose' => $purpose,
                        'otp_id' => $otpRecord['id'],
                        'new_attempts' => $otpRecord['attempts'] + 1,
                        'max_attempts' => $otpRecord['max_attempts']
                    ]);
                } else {
                    $this->logger->info('[OTP_SERVICE] Skipped incrementing - max attempts already reached', [
                        'phone' => $phone,
                        'purpose' => $purpose,
                        'otp_id' => $otpRecord['id'],
                        'attempts' => $otpRecord['attempts'],
                        'max_attempts' => $otpRecord['max_attempts']
                    ]);
                }
            }
        } catch (Exception $e) {
            $this->logger->error('[OTP_SERVICE] Failed to increment latest OTP attempts', [
                'phone' => $phone,
                'purpose' => $purpose,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Clean phone number
     */
    private function cleanPhoneNumber($phone)
    {
        // Remove all non-numeric characters
        $cleaned = preg_replace('/[^0-9]/', '', $phone);
        
        // Handle Indian numbers
        if (strlen($cleaned) == 10 && substr($cleaned, 0, 1) >= '6') {
            return '+91' . $cleaned;
        } elseif (strlen($cleaned) == 12 && substr($cleaned, 0, 2) == '91') {
            return '+' . $cleaned;
        } elseif (strlen($cleaned) == 13 && substr($cleaned, 0, 3) == '+91') {
            return $cleaned;
        }
        
        return '+91' . substr($cleaned, -10); // Take last 10 digits
    }

    /**
     * Clean up expired OTPs (called by cron or periodically)
     */
    public function cleanupExpiredOtps()
    {
        try {
            $connection = $this->db->getConnection();
            $stmt = $connection->prepare("
                DELETE FROM otp_verifications 
                WHERE expires_at < DATE_SUB(NOW(), INTERVAL 1 HOUR)
            ");
            $stmt->execute();
            
            $deletedCount = $stmt->rowCount();
            
            $this->logger->info('[OTP_SERVICE] Cleaned up expired OTPs', [
                'deleted_count' => $deletedCount
            ]);
            
            return $deletedCount;
            
        } catch (Exception $e) {
            $this->logger->error('[OTP_SERVICE] Cleanup failed', [
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }
}
?>
