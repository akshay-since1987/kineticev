<?php

// Prevent direct access
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    http_response_code(404);
    exit();
}

/**
 * Production Timezone Guard
 * 
 * This script ensures timezone consistency between PHP and MySQL in production.
 * It handles multiple scenarios:
 * 1. Server timezone vs Application timezone
 * 2. PHP timezone vs MySQL timezone
 * 3. Multiple database connections
 * 4. Environment-specific configurations
 * 
 * Usage: Include this at the very top of your application bootstrap
 */

// Prevent multiple executions
if (defined('PRODUCTION_TIMEZONE_GUARD_LOADED')) {
    return;
}
define('PRODUCTION_TIMEZONE_GUARD_LOADED', true);

class ProductionTimezoneGuard {
    
    private static $initialized = false;
    private static $targetTimezone = 'Asia/Kolkata';
    private static $connections = [];
    private static $errors = [];
    
    /**
     * Initialize timezone consistency across the entire application
     */
    public static function initialize($timezone = 'Asia/Kolkata') {
        if (self::$initialized) {
            return true;
        }
        
        self::$targetTimezone = $timezone;
        
        try {
            // Step 1: Set PHP timezone
            self::setPHPTimezone();
            
            // Step 2: Log current timezone status for debugging
            self::logTimezoneStatus();
            
            // Step 3: Register database connection hook
            self::registerDatabaseHook();
            
            self::$initialized = true;
            return true;
            
        } catch (Exception $e) {
            self::$errors[] = "Timezone initialization failed: " . $e->getMessage();
            error_log("ProductionTimezoneGuard Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Set PHP timezone with fallback handling
     */
    private static function setPHPTimezone() {
        // Get current PHP timezone
        $currentPHPTimezone = date_default_timezone_get();
        
        // Set the target timezone
        if (!date_default_timezone_set(self::$targetTimezone)) {
            throw new Exception("Failed to set PHP timezone to " . self::$targetTimezone);
        }
        
        // Verify the change
        $newPHPTimezone = date_default_timezone_get();
        if ($newPHPTimezone !== self::$targetTimezone) {
            throw new Exception("PHP timezone verification failed. Expected: " . self::$targetTimezone . ", Got: " . $newPHPTimezone);
        }
        
        // Log the change if it was different
        if ($currentPHPTimezone !== self::$targetTimezone) {
            error_log("ProductionTimezoneGuard: Changed PHP timezone from {$currentPHPTimezone} to {$newPHPTimezone}");
        }
    }
    
    /**
     * Log current timezone status for production debugging
     */
    private static function logTimezoneStatus() {
        $status = [
            'server_timezone' => date_default_timezone_get(),
            'server_time' => date('Y-m-d H:i:s'),
            'server_time_utc' => gmdate('Y-m-d H:i:s'),
            'php_version' => PHP_VERSION,
            'environment' => $_SERVER['SERVER_NAME'] ?? 'CLI'
        ];
        
        error_log("ProductionTimezoneGuard Status: " . json_encode($status));
    }
    
    /**
     * Register hooks to ensure all database connections use correct timezone
     */
    private static function registerDatabaseHook() {
        // This will be called whenever a new PDO connection is made
        // We'll also provide a manual method for existing connections
    }
    
    /**
     * Apply timezone settings to a specific database connection
     */
    public static function applyToConnection($connection, $connectionId = null) {
        if (!$connection) {
            return false;
        }
        
        $connectionId = $connectionId ?? spl_object_hash($connection);
        
        // Skip if already processed
        if (isset(self::$connections[$connectionId])) {
            return true;
        }
        
        try {
            // Set MySQL timezone to match PHP
            $stmt = $connection->prepare("SET time_zone = ?");
            $mysqlTimezone = self::getMySQLTimezoneString();
            $stmt->execute([$mysqlTimezone]);
            
            // Verify the setting
            $stmt = $connection->prepare("SELECT @@session.time_zone as tz, NOW() as mysql_time");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            self::$connections[$connectionId] = [
                'mysql_timezone' => $result['tz'],
                'mysql_time' => $result['mysql_time'],
                'php_time' => date('Y-m-d H:i:s'),
                'applied_at' => time()
            ];
            
            // Log for production debugging
            error_log("ProductionTimezoneGuard: Applied timezone to connection {$connectionId}. MySQL TZ: {$result['tz']}, MySQL Time: {$result['mysql_time']}, PHP Time: " . date('Y-m-d H:i:s'));
            
            return true;
            
        } catch (Exception $e) {
            self::$errors[] = "Failed to apply timezone to connection {$connectionId}: " . $e->getMessage();
            error_log("ProductionTimezoneGuard Error on connection {$connectionId}: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get MySQL timezone string equivalent to PHP timezone
     */
    private static function getMySQLTimezoneString() {
        // For Asia/Kolkata, MySQL equivalent is '+05:30'
        $timezoneMap = [
            'Asia/Kolkata' => '+05:30',
            'UTC' => '+00:00',
            'America/New_York' => '-05:00', // EST (adjust for DST in production)
            'Europe/London' => '+00:00',    // GMT (adjust for DST in production)
        ];
        
        if (isset($timezoneMap[self::$targetTimezone])) {
            return $timezoneMap[self::$targetTimezone];
        }
        
        // Calculate offset dynamically
        $tz = new DateTimeZone(self::$targetTimezone);
        $offset = $tz->getOffset(new DateTime());
        $hours = intval($offset / 3600);
        $minutes = abs(($offset % 3600) / 60);
        
        return sprintf('%+03d:%02d', $hours, $minutes);
    }
    
    /**
     * Check if PHP and MySQL times are synchronized
     */
    public static function verifyTimeSynchronization($connection) {
        try {
            $stmt = $connection->prepare("SELECT NOW() as mysql_time, UNIX_TIMESTAMP(NOW()) as mysql_timestamp");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $phpTime = time();
            $mysqlTime = $result['mysql_timestamp'];
            $timeDiff = abs($phpTime - $mysqlTime);
            
            $status = [
                'php_time' => date('Y-m-d H:i:s', $phpTime),
                'mysql_time' => $result['mysql_time'],
                'time_difference_seconds' => $timeDiff,
                'synchronized' => $timeDiff <= 2 // Allow 2 seconds tolerance
            ];
            
            if (!$status['synchronized']) {
                error_log("ProductionTimezoneGuard WARNING: Time difference detected - " . json_encode($status));
            }
            
            return $status;
            
        } catch (Exception $e) {
            error_log("ProductionTimezoneGuard: Failed to verify time synchronization - " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get comprehensive status report for production monitoring
     */
    public static function getStatusReport() {
        return [
            'initialized' => self::$initialized,
            'target_timezone' => self::$targetTimezone,
            'php_timezone' => date_default_timezone_get(),
            'php_time' => date('Y-m-d H:i:s'),
            'connections_managed' => count(self::$connections),
            'connections' => self::$connections,
            'errors' => self::$errors,
            'server_info' => [
                'php_version' => PHP_VERSION,
                'server_name' => $_SERVER['SERVER_NAME'] ?? 'CLI',
                'server_time' => date('Y-m-d H:i:s'),
                'utc_time' => gmdate('Y-m-d H:i:s')
            ]
        ];
    }
    
    /**
     * Emergency reset function for production issues
     */
    public static function emergencyReset() {
        self::$initialized = false;
        self::$connections = [];
        self::$errors = [];
        return self::initialize(self::$targetTimezone);
    }
}

// Auto-initialize on include
ProductionTimezoneGuard::initialize();

/**
 * Helper function for quick connection timezone application
 */
function applyTimezoneToConnection($connection, $connectionId = null) {
    return ProductionTimezoneGuard::applyToConnection($connection, $connectionId);
}

/**
 * Helper function to get timezone status
 */
function getTimezoneStatus() {
    return ProductionTimezoneGuard::getStatusReport();
}

/**
 * Helper function to verify time sync
 */
function verifyTimeSynchronization($connection) {
    return ProductionTimezoneGuard::verifyTimeSynchronization($connection);
}
