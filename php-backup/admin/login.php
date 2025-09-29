<?php
// Comprehensive cache control headers to prevent CDN caching
header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0, private');
header('Pragma: no-cache');
header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');

// CDN-specific headers
header('CDN-Cache-Control: no-cache');
header('Cloudflare-CDN-Cache-Control: no-cache');
header('X-Accel-Expires: 0');
header('Surrogate-Control: no-store');

// Configure session settings
ini_set('session.cookie_lifetime', 3600); // 1 hour
ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
ini_set('session.cookie_httponly', 1); // Prevent XSS
ini_set('session.use_strict_mode', 1); // Prevent session fixation

session_start();
require_once '../Logger.php';
require_once 'AdminHandler.php';

$logger = Logger::getInstance();
$error = '';
$success = '';

// Check if user was logged out
if (isset($_GET['logout']) && $_GET['logout'] == '1') {
    // Clear any remaining session data for extra security
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
    
    // Start a fresh session
    session_start();
    
    $success = 'You have been successfully logged out.';
    
    // Log the successful redirect after logout
    $logger->info('[ADMIN_LOGOUT] Successfully redirected to login page');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password';
    } else {
        try {
            // Try to load config files in order of preference
            $config = null;
            
            // First try main config
            if (file_exists('../config.php')) {
                try {
                    $config = include '../config.php';
                } catch (Exception $e) {
                    $config = null;
                }
            }
            
            // If main config failed, try local config
            if (!$config && file_exists('config-local.php')) {
                try {
                    $config = include 'config-local.php';
                } catch (Exception $e) {
                    $config = null;
                }
            }
            
            if (!$config) {
                throw new Exception('No valid configuration found');
            }
            
            $adminHandler = new AdminHandler($config);
            
            if ($adminHandler->authenticateAdmin($username, $password)) {
                // Regenerate session ID for security
                session_regenerate_id(true);
                
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_login_time'] = time();
                
                $logger->info('[ADMIN_LOGIN] Successful login', [
                    'username' => $username,
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
                ]);
                
                header('Location: index');
                exit;
            } else {
                $error = 'Invalid username or password';
                $logger->warning('[ADMIN_LOGIN] Failed login attempt', [
                    'username' => $username,
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
                ]);
            }
        } catch (Exception $e) {
            $error = 'Login system error. Please try again.';
            $logger->error('[ADMIN_LOGIN] Login system error', [
                'error' => $e->getMessage(),
                'username' => $username
            ]);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KineticEV Admin Login</title>
    <link rel="icon" type="image/x-icon" href="/-/images/logo.svg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/admin.css" rel="stylesheet">
</head>
<body class="login-body">
    <div class="login-card">
        <div class="login-header">
            <i class="fas fa-bolt fa-3x mb-3"></i>
            <h2>KineticEV Admin</h2>
            <p class="mb-0">Secure Admin Portal</p>
        </div>
        <div class="login-form-body">
            <?php if ($error): ?>
                <div class="alert alert-danger login-alert">
                    <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success login-alert">
                    <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="login-input-group">
                    <label for="username" class="sr-only">Username</label>
                    <span class="input-group-text login-input-group-text">
                        <i class="fas fa-user"></i>
                    </span>
                    <input type="text" id="username" class="form-control login-form-control" name="username" placeholder="Username" 
                           value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
                </div>

                <div class="login-input-group">
                    <label for="password" class="sr-only">Password</label>
                    <span class="input-group-text login-input-group-text">
                        <i class="fas fa-lock"></i>
                    </span>
                    <input type="password" id="password" class="form-control login-form-control" name="password" placeholder="Password" required>
                </div>

                <button type="submit" class="btn btn-login">
                    <i class="fas fa-sign-in-alt me-2"></i>Login to Admin Panel
                </button>
            </form>

            <div class="text-center mt-4">
                <small class="text-muted">
                    <i class="fas fa-shield-alt me-1"></i>
                    Secure access to KineticEV management system
                </small>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
