<?php
/**
 * Enhanced Logout Script
 * Handles complete session destruction and proper redirection
 * Last updated: August 25, 2025
 */

// Configure session settings (match with index.php)
ini_set('session.cookie_lifetime', 3600); // 1 hour
ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
ini_set('session.cookie_httponly', 1); // Prevent XSS
ini_set('session.use_strict_mode', 1); // Prevent session fixation

// Start session to access session data
session_start();

// Log the logout action if Logger is available
if (file_exists('../Logger.php')) {
    require_once '../Logger.php';
    try {
        $logger = Logger::getInstance();
        if (isset($_SESSION['admin_username'])) {
            $logger->info('Admin logout: ' . $_SESSION['admin_username']);
        } else {
            $logger->info('Admin logout: Unknown user');
        }
    } catch (Exception $e) {
        // Silently ignore logger errors during logout
    }
}

// Force complete session destruction
$_SESSION = array();
    
// Delete session cookie
if (isset($_COOKIE[session_name()])) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), 
        '', 
        time() - 86400, 
        $params['path'], 
        $params['domain'], 
        $params['secure'], 
        $params['httponly']
    );
}

// Clear admin-specific session variables
unset($_SESSION['admin_logged_in']);
unset($_SESSION['admin_user_id']);
unset($_SESSION['admin_username']);
unset($_SESSION['admin_role']);

// Destroy the session
session_destroy();

// Clear any existing output buffers
while (ob_get_level()) {
    ob_end_clean();
}

// Prevent any caching
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0, private');
header('Pragma: no-cache');
header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
header('CDN-Cache-Control: no-cache');
header('Cloudflare-CDN-Cache-Control: no-cache');

// Force redirect to login page with logout parameter
header('Location: login.php?logout=1', true, 302);
exit();
?>
