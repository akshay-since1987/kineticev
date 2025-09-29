<?php

// Prevent direct access
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    http_response_code(404);
    exit();
}

/**
 * Common header for admin pages
 */

// Additional security checks
if (!defined('ADMIN_ACCESS')) {
    define('ADMIN_ACCESS', true);
    
    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Redirect to login if not logged in - check both old and new session variables for compatibility
    if (!isset($_SESSION['admin_id']) || !isset($_SESSION['admin_username'])) {
        header('Location: login.php');
        exit;
    }
    
    // Ensure admin_logged_in is set for backward compatibility
    if (!isset($_SESSION['admin_logged_in'])) {
        $_SESSION['admin_logged_in'] = true;
    }
}

// Get admin role for permission checks
$adminRole = $_SESSION['admin_role'] ?? 'admin';
$isSuperAdmin = ($adminRole === 'superadmin');

// Function to check if current page is active
function isActivePage($pageName) {
    $currentPage = basename($_SERVER['PHP_SELF']);
    return ($currentPage === $pageName) ? 'active' : '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Admin Portal'; ?> - Kinetic Green</title>
    
    <!-- No index, no follow for admin pages -->
    <meta name="robots" content="noindex, nofollow">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom admin styles -->
    <style>
        .sidebar {
            min-height: 100vh;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            z-index: 1000;
        }
        
        .sidebar .nav-link {
            color: #333;
            padding: 0.75rem 1rem;
            border-radius: 0.25rem;
            margin-bottom: 0.25rem;
        }
        
        .sidebar .nav-link:hover {
            background-color: #f8f9fa;
        }
        
        .sidebar .nav-link.active {
            color: #fff;
            background-color: #0d6efd;
        }
        
        .sidebar .nav-link .fa {
            margin-right: 0.5rem;
        }
        
        .content-wrapper {
            min-height: 100vh;
            background-color: #f8f9fa;
        }
        
        .admin-header {
            background-color: #fff;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            padding: 0.75rem 1.5rem;
        }
        
        .admin-header .dropdown-toggle::after {
            display: none;
        }
        
        .badge-role {
            font-size: 0.7rem;
            padding: 0.25rem 0.5rem;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 d-md-block bg-white sidebar collapse">
                <div class="position-sticky pt-3">
                    <div class="text-center mb-4">
                        <a href="/admin">
                            <img src="/-/images/logo.png" alt="Kinetic Green Admin" class="img-fluid" style="max-width: 150px;">
                        </a>
                        <h6 class="mt-2">Admin Portal</h6>
                    </div>
                    
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link <?php echo isActivePage('index.php'); ?>" href="index.php">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link <?php echo isActivePage('manage-dealerships.php'); ?>" href="manage-dealerships.php">
                                <i class="fas fa-store"></i> Dealerships
                            </a>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link <?php echo isActivePage('users.php'); ?>" href="users.php">
                                <i class="fas fa-users"></i> Users
                            </a>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link <?php echo isActivePage('bookings.php'); ?>" href="bookings.php">
                                <i class="fas fa-calendar-check"></i> Bookings
                            </a>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link <?php echo isActivePage('inquiries.php'); ?>" href="inquiries.php">
                                <i class="fas fa-question-circle"></i> Inquiries
                            </a>
                        </li>
                        
                        <?php if ($isSuperAdmin): ?>
                        <li class="nav-header mt-3 mb-2 px-3">
                            <small class="text-uppercase text-muted">Advanced Settings</small>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link <?php echo isActivePage('system-settings.php'); ?>" href="system-settings.php">
                                <i class="fas fa-cogs"></i> System Settings
                            </a>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link <?php echo isActivePage('logs.php'); ?>" href="logs.php">
                                <i class="fas fa-file-alt"></i> System Logs
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
            
            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 content-wrapper">
                <!-- Admin Header -->
                <header class="d-flex justify-content-between align-items-center admin-header mb-4">
                    <button class="btn d-md-none" type="button" data-bs-toggle="collapse" data-bs-target=".sidebar">
                        <i class="fas fa-bars"></i>
                    </button>
                    
                    <div class="dropdown">
                        <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user-circle me-2 fs-4"></i>
                            <div>
                                <span><?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'Admin User'); ?></span>
                                <span class="badge <?php echo $isSuperAdmin ? 'bg-danger' : 'bg-secondary'; ?> badge-role d-block"><?php echo ucfirst($adminRole); ?></span>
                            </div>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i> Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                        </ul>
                    </div>
                </header>
                
                <!-- Content starts here -->
