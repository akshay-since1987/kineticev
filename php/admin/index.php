<?php
// Configure session settings
ini_set('session.cookie_lifetime', 3600); // 1 hour
ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
ini_set('session.cookie_httponly', 1); // Prevent XSS
ini_set('session.use_strict_mode', 1); // Prevent session fixation

session_start();
// Load config once
$config = include '../config.php';
require_once '../Logger.php';
require_once 'AdminHandler.php';

$logger = Logger::getInstance();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Add comprehensive cache control headers to prevent caching (including CDN)
header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0, private');
header('Pragma: no-cache');
header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');

// CDN-specific headers
header('CDN-Cache-Control: no-cache');
header('Cloudflare-CDN-Cache-Control: no-cache');
header('X-Accel-Expires: 0');
header('Surrogate-Control: no-store');
header('X-Cache-Control: no-cache');

// Prevent robots indexing
header('X-Robots-Tag: noindex, nofollow');

// Cache busting function with enhanced timestamp and random component
function getFileVersion($filePath)
{
    $timestamp = file_exists($filePath) ? filemtime($filePath) : time();
    $random = mt_rand(1000, 9999); // Add random component for extra cache busting
    return $timestamp . '.' . date('His') . '.' . $random;
}

// Add database connection error handling
try {
    // Verify config is an array
    if (!is_array($config) || !isset($config['database'])) {
        throw new Exception("Invalid configuration. Database settings are missing.");
    }

    // Make sure database credentials are present
    if (
        !isset($config['database']['host']) || !isset($config['database']['dbname']) ||
        !isset($config['database']['username'])
    ) {
        throw new Exception("Database configuration is incomplete.");
    }

    $adminHandler = new AdminHandler($config);
    $tables = $adminHandler->getAllTables();
} catch (PDOException $e) {
    die("<div class='alert alert-danger'>Database connection failed: " . htmlspecialchars($e->getMessage()) . "</div>");
} catch (Exception $e) {
    die("<div class='alert alert-danger'>Error: " . htmlspecialchars($e->getMessage()) . "</div>");
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>KineticEV Admin Panel</title>
    <link rel="icon" type="image/x-icon" href="/-/images/logo.svg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/admin.css?v=<?php echo getFileVersion('assets/admin.css'); ?>" rel="stylesheet">
</head>

<body class="bg-light">
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0">
                <div class="sidebar">
                    <div class="logo">
                        <i class="fas fa-bolt"></i> KineticEV Admin
                    </div>
                    <nav class="nav flex-column">
                        <a class="nav-link active" href="#dashboard" data-section="dashboard">
                            <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                        </a>
                        <a class="nav-link" href="#transactions" data-section="transactions">
                            <i class="fas fa-credit-card me-2"></i> Transactions
                        </a>
                        <a class="nav-link" href="#test-drives" data-section="test_drives">
                            <i class="fas fa-motorcycle me-2"></i> Test Drives
                        </a>
                        <a class="nav-link" href="#contacts" data-section="contacts">
                            <i class="fas fa-envelope me-2"></i> Contacts
                        </a>
                        <a class="nav-link" href="#dealerships" data-section="dealerships">
                            <i class="fas fa-store me-2"></i> Dealerships
                        </a>
                        <a class="nav-link" href="#analytics" data-section="analytics">
                            <i class="fas fa-chart-bar me-2"></i> Analytics
                        </a>
                        <a class="nav-link" href="#user-management" data-section="user_management">
                            <i class="fas fa-users me-2"></i> User Management
                        </a>
                        <a class="nav-link" href="#email-logs" data-section="email_logs">
                            <i class="fas fa-mail-bulk me-2"></i> Email Logs
                        </a>
                        <a class="nav-link" href="#system-logs" data-section="system_logs">
                            <i class="fas fa-file-alt me-2"></i> System Logs
                        </a>
                        <a class="nav-link" href="logout">
                            <i class="fas fa-sign-out-alt me-2"></i> Logout
                        </a>
                    </nav>
                    <div class="text-center mt-3">
                        <small class="text-light" id="currentUserInfo">
                            <i class="fas fa-user me-1"></i>
                            <span id="currentUsername">Loading...</span>
                            <span class="badge bg-light text-dark ms-2" id="currentUserRole">...</span>
                        </small>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <div class="content-header text-center">
                    <h1><i class="fas fa-bolt"></i> KineticEV Admin Dashboard</h1>
                    <p class="mb-0">Manage your electric vehicle business efficiently</p>
                </div>

                <!-- Dashboard Section -->
                <div id="dashboard-section" class="content-section">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="stat-card text-center">
                                <i class="fas fa-credit-card fa-3x mb-3"></i>
                                <h3 id="total-transactions">Loading...</h3>
                                <p>Total Transactions</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card text-center">
                                <i class="fas fa-motorcycle fa-3x mb-3"></i>
                                <h3 id="total-test-drives">Loading...</h3>
                                <p>Test Drive Requests</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card text-center">
                                <i class="fas fa-envelope fa-3x mb-3"></i>
                                <h3 id="total-contacts">Loading...</h3>
                                <p>Contact Inquiries</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card text-center">
                                <i class="fas fa-rupee-sign fa-3x mb-3"></i>
                                <h3 id="total-revenue">Loading...</h3>
                                <p>Total Revenue</p>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="table-card">
                                <div class="table-header">
                                    <h5><i class="fas fa-chart-line me-2"></i>Recent Activity</h5>
                                </div>
                                <div class="p-4">
                                    <div id="recent-activity">Loading recent activity...</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Dealership Section -->
                <div id="dealerships-section" class="content-section" style="display: none;">
                    <div class="table-card">
                        <div class="table-header d-flex justify-content-between align-items-center">
                            <h5><i class="fas fa-store me-2"></i>Dealerships</h5>
                            <div>
                                <!-- PROMINENT ADD BUTTON -->
                                <button id="create-dealership-btn" class="btn btn-success" data-bs-toggle="modal"
                                    data-bs-target="#addDealershipModal" style="font-weight:bold;">
                                    <i class="fas fa-plus me-1"></i> Create Dealership
                                </button>
                                <button class="btn btn-kinetic btn-sm btn-refresh-dealerships ms-2"
                                    onclick="refreshDealerships()">
                                    <i class="fas fa-sync-alt me-1"></i> Refresh
                                </button>
                            </div>
                        </div>

                        <div class="p-4">
                            <!-- Results Info -->
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div id="dealerships-result-info" class="result-info">
                                    Loading data...
                                </div>
                            </div>

                            <!-- Table -->
                            <table id="dealerships-table" class="table table-striped table-hover" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Address</th>
                                        <th>City</th>
                                        <th>State</th>
                                        <th>Phone</th>
                                        <th>Email</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Data will be loaded via JavaScript -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Dynamic Table Sections -->
                <?php foreach ($tables as $table): ?>
                    <div id="<?php echo $table; ?>-section" class="content-section" style="display: none;">
                        <div class="table-card">
                            <div class="table-header d-flex justify-content-between align-items-center">
                                <h5><i class="fas fa-table me-2"></i><?php echo ucfirst(str_replace('_', ' ', $table)); ?>
                                </h5>
                                <div>
                                    <button class="btn btn-kinetic btn-sm" onclick="refreshTable('<?php echo $table; ?>')">
                                        <i class="fas fa-sync-alt me-1"></i> Refresh
                                    </button>
                                    <button class="btn btn-success btn-sm" onclick="exportTable('<?php echo $table; ?>')">
                                        <i class="fas fa-download me-1"></i> Export
                                    </button>
                                </div>
                            </div>

                            <!-- Advanced Filters -->
                            <div class="filter-section">
                                <div class="filter-header p-3 d-flex justify-content-between align-items-center"
                                    data-bs-toggle="collapse" data-bs-target="#<?php echo $table; ?>-filters-collapse"
                                    aria-expanded="false">
                                    <h6 class="mb-0"><i class="fas fa-filter me-2"></i>Advanced Filters</h6>
                                    <i class="fas fa-chevron-down"></i>
                                </div>
                                <div class="collapse" id="<?php echo $table; ?>-filters-collapse">
                                    <div class="filter-controls p-3" id="<?php echo $table; ?>-filters">
                                        <!-- Filter controls will be dynamically loaded here -->
                                    </div>
                                </div>
                            </div>

                            <div class="p-4">
                                <!-- Results Info -->
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div id="<?php echo $table; ?>-result-info" class="result-info">
                                        Loading data...
                                    </div>
                                    <div class="btn-group" role="group">
                                        <button type="button" class="btn btn-outline-primary btn-sm"
                                            onclick="adminPanel.exportFiltered('<?php echo $table; ?>', 'csv')">
                                            <i class="fas fa-file-csv"></i> Export CSV
                                        </button>
                                        <button type="button" class="btn btn-outline-success btn-sm"
                                            onclick="adminPanel.exportFiltered('<?php echo $table; ?>', 'excel')">
                                            <i class="fas fa-file-excel"></i> Export Excel
                                        </button>
                                    </div>
                                </div>

                                <table id="<?php echo $table; ?>-table" class="table table-striped table-hover"
                                    style="width:100%">
                                    <thead class="table-dark">
                                        <!-- Dynamic headers will be loaded -->
                                    </thead>
                                    <tbody>
                                        <!-- Dynamic data will be loaded -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <!-- Analytics Section -->
                <div id="analytics-section" class="content-section" style="display: none;">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="table-card">
                                <div class="table-header">
                                    <h5><i class="fas fa-chart-pie me-2"></i>Transaction Status Distribution</h5>
                                </div>
                                <div class="p-4">
                                    <canvas id="statusChart"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="table-card">
                                <div class="table-header">
                                    <h5><i class="fas fa-chart-line me-2"></i>Monthly Revenue Trend</h5>
                                </div>
                                <div class="p-4">
                                    <canvas id="revenueChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="table-card">
                                <div class="table-header">
                                    <h5><i class="fas fa-chart-bar me-2"></i>Daily Activity Chart</h5>
                                </div>
                                <div class="p-4">
                                    <canvas id="activityChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- User Management Section -->
                <div id="user_management-section" class="content-section" style="display: none;">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h4><i class="fas fa-users me-2"></i>User Management</h4>
                        </div>
                        <div class="col-md-6 text-end">
                            <button class="btn btn-kinetic" onclick="showCreateUserModal()">
                                <i class="fas fa-plus me-2"></i>Add New User
                            </button>
                        </div>
                    </div>

                    <div class="table-card">
                        <div class="table-header">
                            <h5><i class="fas fa-users-cog me-2"></i>Admin Users</h5>
                        </div>
                        <div class="p-4">
                            <div class="table-responsive">
                                <table id="usersTable" class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Username</th>
                                            <th>Full Name</th>
                                            <th>Email</th>
                                            <th>Role</th>
                                            <th>Status</th>
                                            <th>Last Login</th>
                                            <th>Created</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="usersTableBody">
                                        <!-- Users will be loaded here -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Email Logs Section -->
                <div id="email_logs-section" class="content-section" style="display: none;">
                    <div class="table-card">
                        <div class="table-header">
                            <h5><i class="fas fa-mail-bulk me-2"></i>Email Logs</h5>
                        </div>
                        <div class="p-4">
                            <div id="email-logs-content">Loading email logs...</div>
                        </div>
                    </div>
                </div>

                <!-- System Logs Section -->
                <div id="system_logs-section" class="content-section" style="display: none;">
                    <div class="table-card">
                        <div class="table-header">
                            <h5><i class="fas fa-file-alt me-2"></i>System Logs</h5>
                        </div>
                        <div class="p-4">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="list-group">
                                        <a href="#" class="list-group-item list-group-item-action active"
                                            data-log="debug_logs.txt">
                                            Debug Logs
                                        </a>
                                        <a href="#" class="list-group-item list-group-item-action"
                                            data-log="email_logs.txt">
                                            Email Logs
                                        </a>
                                        <a href="#" class="list-group-item list-group-item-action"
                                            data-log="info_logs.txt">
                                            Info Logs
                                        </a>
                                        <a href="#" class="list-group-item list-group-item-action"
                                            data-log="payment_logs.txt">
                                            Payment Logs
                                        </a>
                                        <a href="#" class="list-group-item list-group-item-action"
                                            data-log="salesforce_logs.txt">
                                            Salesforce Logs
                                        </a>
                                        <a href="#" class="list-group-item list-group-item-action"
                                            data-log="sms_logs.txt">
                                            SMS Logs
                                        </a>
                                    </div>
                                </div>
                                <div class="col-md-9">
                                    <div id="log-content"
                                        style="background: #1e1e1e; color: #00ff00; padding: 20px; border-radius: 5px; font-family: monospace; height: 500px; overflow-y: auto;">
                                        Loading logs...
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- User Management Modals -->
    <!-- Create User Modal -->
    <div class="modal fade" id="createUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Create New Admin User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="createUserForm">
                        <div class="mb-3">
                            <label for="createUsername" class="form-label">Username *</label>
                            <input type="text" class="form-control" id="createUsername" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="createPassword" class="form-label">Password *</label>
                            <input type="password" class="form-control" id="createPassword" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label for="createEmail" class="form-label">Email</label>
                            <input type="email" class="form-control" id="createEmail" name="email">
                        </div>
                        <div class="mb-3">
                            <label for="createFullName" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="createFullName" name="full_name">
                        </div>
                        <div class="mb-3">
                            <label for="createRole" class="form-label">Role</label>
                            <select class="form-select" id="createRole" name="role">
                                <option value="admin">Admin</option>
                                <option value="viewer">Viewer</option>
                                <option value="super_admin">Super Admin</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-kinetic" onclick="createUser()">Create User</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-edit me-2"></i>Edit Admin User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editUserForm">
                        <input type="hidden" id="editUserId" name="user_id">
                        <div class="mb-3">
                            <label for="editUsername" class="form-label">Username</label>
                            <input type="text" class="form-control" id="editUsername" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="editEmail" class="form-label">Email</label>
                            <input type="email" class="form-control" id="editEmail" name="email">
                        </div>
                        <div class="mb-3">
                            <label for="editFullName" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="editFullName" name="full_name">
                        </div>
                        <div class="mb-3">
                            <label for="editRole" class="form-label">Role</label>
                            <select class="form-select" id="editRole" name="role">
                                <option value="admin">Admin</option>
                                <option value="viewer">Viewer</option>
                                <option value="super_admin">Super Admin</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="editIsActive" class="form-label">Status</label>
                            <select class="form-select" id="editIsActive" name="is_active">
                                <option value="1">Active</option>
                                <option value="0">Disabled</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-kinetic" onclick="updateUser()">Update User</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Change Password Modal -->
    <div class="modal fade" id="changePasswordModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-key me-2"></i>Change Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="changePasswordForm">
                        <input type="hidden" id="passwordUserId" name="user_id">
                        <div class="mb-3">
                            <label for="passwordUsername" class="form-label">Username</label>
                            <input type="text" class="form-control" id="passwordUsername" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="newPassword" class="form-label">New Password *</label>
                            <input type="password" class="form-control" id="newPassword" name="new_password" required>
                        </div>
                        <div class="mb-3">
                            <label for="confirmPassword" class="form-label">Confirm Password *</label>
                            <input type="password" class="form-control" id="confirmPassword" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-kinetic" onclick="changePassword()">Change Password</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Force clear any old cached admin panel instance
        if (window.adminPanel) {
            console.log('Clearing old admin panel instance');
            delete window.adminPanel;
        }
        // Add cache-busting timestamp to help with debugging
        window.cacheTimestamp = <?php echo time(); ?>;
        console.log('Cache timestamp:', window.cacheTimestamp);
    </script>
    <script src="assets/admin.js?v=<?php echo getFileVersion('assets/admin.js'); ?>"></script>
    <script src="assets/dealership.js?v=<?php echo getFileVersion('assets/dealership.js'); ?>"></script>
    <div class="modal fade" id="addDealershipModal" tabindex="-1" aria-labelledby="addDealershipModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addDealershipModalLabel">Create New Dealership</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="dealershipFormAlert" class="alert alert-danger" style="display:none;"></div>
                    <form id="dealershipForm">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="dealership_name" class="form-label">Dealership Name <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="dealership_name" name="name" required>
                            </div>
                            <div class="col-md-6">
                                <label for="dealership_email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="dealership_email" name="email">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="dealership_phone" class="form-label">Phone</label>
                                <input type="text" class="form-control" id="dealership_phone" name="phone">
                            </div>
                            <div class="col-md-6">
                                <label for="dealership_pincode" class="form-label">Pincode <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="dealership_pincode" name="pincode" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="dealership_address" class="form-label">Address <span
                                    class="text-danger">*</span></label>
                            <textarea class="form-control" id="dealership_address" name="address" rows="2"
                                required></textarea>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="dealership_city" class="form-label">City <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="dealership_city" name="city" required>
                            </div>
                            <div class="col-md-6">
                                <label for="dealership_state" class="form-label">State <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="dealership_state" name="state" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="dealership_latitude" class="form-label">Latitude</label>
                                <input type="text" class="form-control" id="dealership_latitude" name="latitude"
                                    placeholder="e.g. 18.5204">
                            </div>
                            <div class="col-md-6">
                                <label for="dealership_longitude" class="form-label">Longitude</label>
                                <input type="text" class="form-control" id="dealership_longitude" name="longitude"
                                    placeholder="e.g. 73.8567">
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveDealershipBtn">Save Dealership</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Dealership Modal -->
    <div class="modal fade" id="editDealershipModal" tabindex="-1" aria-labelledby="editDealershipModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editDealershipModalLabel">Edit Dealership</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="editDealershipFormAlert" class="alert alert-danger" style="display:none;"></div>
                    <form id="editDealershipForm">
                        <input type="hidden" id="edit_dealership_id" name="id">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="edit_dealership_name" class="form-label">Dealership Name <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="edit_dealership_name" name="name" required>
                            </div>
                            <div class="col-md-6">
                                <label for="edit_dealership_email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="edit_dealership_email" name="email">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="edit_dealership_phone" class="form-label">Phone</label>
                                <input type="text" class="form-control" id="edit_dealership_phone" name="phone">
                            </div>
                            <div class="col-md-6">
                                <label for="edit_dealership_pincode" class="form-label">Pincode <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="edit_dealership_pincode" name="pincode" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="edit_dealership_address" class="form-label">Address <span
                                    class="text-danger">*</span></label>
                            <textarea class="form-control" id="edit_dealership_address" name="address" rows="2"
                                required></textarea>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="edit_dealership_city" class="form-label">City <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="edit_dealership_city" name="city" required>
                            </div>
                            <div class="col-md-6">
                                <label for="edit_dealership_state" class="form-label">State <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="edit_dealership_state" name="state" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="edit_dealership_latitude" class="form-label">Latitude</label>
                                <input type="text" class="form-control" id="edit_dealership_latitude" name="latitude"
                                    placeholder="e.g. 18.5204">
                            </div>
                            <div class="col-md-6">
                                <label for="edit_dealership_longitude" class="form-label">Longitude</label>
                                <input type="text" class="form-control" id="edit_dealership_longitude" name="longitude"
                                    placeholder="e.g. 73.8567">
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="updateDealershipBtn">Update Dealership</button>
                </div>
            </div>
        </div>
    </div>
</body>

</html>