<?php
/*
 * EMERGENCY PASSWORD RESET SCRIPT - KINETIC ELECTRIC VEHICLES
 * ========================================================
 * 
 * ⚠️  CRITICAL SECURITY WARNING ⚠️
 * 
 * This script is for EMERGENCY USE ONLY and should be used exclusively
 * within the KineticEV corporate network. This file contains sensitive
 * administrative functions that can compromise system security if misused.
 * 
 * USAGE RESTRICTIONS:
 * - ONLY use when admin password is lost and immediate access is required
 * - ONLY execute from within KineticEV secure network infrastructure  
 * - IMMEDIATELY disable/remove after emergency use
 * - NEVER deploy to production without proper authorization
 * 
 * SECURITY REQUIREMENTS:
 * - Network access must be restricted to KineticEV internal IPs
 * - Script execution should be logged and monitored
 * - File must be removed after emergency situation is resolved
 * 
 * This script resets the 'kineticadmin' user password to 'admin123'
 * 
 * Last Modified: Emergency Reset Protocol v1.0
 * Authorization: System Administrator Only
 */

// SCRIPT IS CURRENTLY DISABLED FOR SECURITY
// Uncomment the following lines ONLY in emergency situations within KineticEV network

/*
// IP Restriction - Only allow KineticEV network ranges
$allowedIPs = [
    '192.168.1.0/24',    // KineticEV Internal Network
    '10.0.0.0/8',        // Private Network Range
    '172.16.0.0/12',     // Private Network Range
    '127.0.0.1'          // Localhost for testing
];

function isIPAllowed($clientIP, $allowedRanges) {
    foreach ($allowedRanges as $range) {
        if (strpos($range, '/') !== false) {
            list($subnet, $mask) = explode('/', $range);
            if ((ip2long($clientIP) & ~((1 << (32 - $mask)) - 1)) == ip2long($subnet)) {
                return true;
            }
        } else {
            if ($clientIP === $range) {
                return true;
            }
        }
    }
    return false;
}

$clientIP = $_SERVER['REMOTE_ADDR'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? 'unknown';

if (!isIPAllowed($clientIP, $allowedIPs)) {
    http_response_code(403);
    die('❌ ACCESS DENIED: This resource is restricted to KineticEV network only. Your IP: ' . $clientIP);
}

// Emergency logging
error_log("EMERGENCY PASSWORD RESET ACCESSED from IP: " . $clientIP . " at " . date('Y-m-d H:i:s'));

try {
    // Load configuration with error handling
    $config = null;
    
    // Try main config first
    if (file_exists('../config.php')) {
        try {
            $config = include '../config.php';
        } catch (Exception $e) {
            $config = null;
        }
    }
    
    // Fallback to local config if main config fails
    if (!$config && file_exists('config-local.php')) {
        try {
            $config = include 'config-local.php';
        } catch (Exception $e) {
            $config = null;
        }
    }
    
    if (!$config || !isset($config['database'])) {
        throw new Exception('Configuration file not found or invalid');
    }
    
    require_once 'AdminHandler.php';
    require_once '../Logger.php';
    
    $logger = Logger::getInstance();
    $adminHandler = new AdminHandler($config);
    
    // Emergency password reset
    $newPassword = 'admin123';
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    
    $dbConfig = $config['database'];
    $conn = new PDO(
        "mysql:host={$dbConfig['host']};dbname={$dbConfig['dbname']};charset=utf8mb4", 
        $dbConfig['username'], 
        $dbConfig['password']
    );
    
    $stmt = $conn->prepare('UPDATE admin_users SET password_hash = ? WHERE username = ?');
    $result = $stmt->execute([$hashedPassword, 'kineticadmin']);
    
    if ($result) {
        echo "✅ EMERGENCY PASSWORD RESET SUCCESSFUL!\n";
        echo "Username: kineticadmin\n";
        echo "New Password: $newPassword\n\n";
        echo "⚠️  SECURITY REMINDER:\n";
        echo "1. Change this password immediately after login\n";
        echo "2. Disable/remove this reset script\n";
        echo "3. Review access logs for security\n";
        
        // Verify the new password works
        $stmt = $conn->prepare('SELECT password_hash FROM admin_users WHERE username = ?');
        $stmt->execute(['kineticadmin']);
        $user = $stmt->fetch();
        
        if (password_verify($newPassword, $user['password_hash'])) {
            echo "✅ Password verification successful!\n";
        } else {
            echo "❌ Password verification failed!\n";
        }
        
        // Log the emergency reset
        $logger->emergency('Emergency password reset executed', [
            'user' => 'kineticadmin',
            'ip' => $clientIP,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        
    } else {
        echo "❌ Failed to update password!\n";
        $logger->error('Emergency password reset failed', [
            'user' => 'kineticadmin',
            'ip' => $clientIP
        ]);
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    error_log("Emergency password reset error: " . $e->getMessage());
}
*/

// END OF COMMENTED EMERGENCY SCRIPT
// Remove this file after emergency use!

echo "This script is disabled. Please contact system administrator to enable this script.\n";
?>
