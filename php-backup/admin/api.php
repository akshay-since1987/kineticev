<?php
// Suppress PHP errors/warnings to ensure clean JSON output
error_reporting(0);
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');

// Comprehensive cache control headers to prevent CDN caching
header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0, private');
header('Pragma: no-cache');
header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');

// CDN-specific headers
header('CDN-Cache-Control: no-cache');
header('Cloudflare-CDN-Cache-Control: no-cache');
header('X-Accel-Expires: 0');
header('Surrogate-Control: no-store');
header('X-Cache-Control: no-cache');

// Content type and security headers
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// Configure session settings
ini_set('session.cookie_lifetime', 3600); // 1 hour
ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
ini_set('session.cookie_httponly', 1); // Prevent XSS
ini_set('session.use_strict_mode', 1); // Prevent session fixation

session_start();
require_once '../Logger.php';
require_once 'AdminHandler.php';

// Check authentication
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // Check if this is an AJAX request
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        // For AJAX requests, return JSON with redirect instruction
        http_response_code(401);
        echo json_encode([
            'error' => 'Session expired',
            'redirect' => 'login',
            'message' => 'Please log in again'
        ]);
    } else {
        // For direct requests, redirect to login page
        header('Location: login');
    }
    exit;
}

$logger = Logger::getInstance();
$config = include '../config.php';
$adminHandler = new AdminHandler($config);

$action = $_GET['action'] ?? '';

// Capture any unexpected output
ob_start();

try {
    switch ($action) {
        case 'dashboard_stats':
            try {
                $stats = $adminHandler->getDashboardStats();
                echo json_encode($stats);
            } catch (Exception $e) {
                $logger->error('[ADMIN_API] Dashboard stats error', [
                    'error' => $e->getMessage()
                ]);
                echo json_encode(['error' => 'Failed to load dashboard stats']);
            }
            break;

        case 'table_data':
            $table = $_GET['table'] ?? '';
            
            // Handle both our custom format and standard DataTables format
            if (isset($_GET['start']) && isset($_GET['length'])) {
                // Standard DataTables server-side processing format
                $start = intval($_GET['start'] ?? 0);
                $length = intval($_GET['length'] ?? 10);
                $page = floor($start / $length) + 1;
                $perPage = $length;
            } else {
                // Our custom format
                $page = intval($_GET['page'] ?? 1);
                $perPage = intval($_GET['per_page'] ?? 10);
            }
            
            $search = $_GET['search'] ?? '';
            $draw = intval($_GET['draw'] ?? 1);
            
            // Handle sorting
            if (isset($_GET['order'][0]['column']) && isset($_GET['order'][0]['dir'])) {
                $orderColumn = intval($_GET['order'][0]['column']);
                $sortDirection = $_GET['order'][0]['dir'] === 'asc' ? 'ASC' : 'DESC';
                
                // Get column name from column index
                $columns = [];
                if ($table === 'transactions') {
                    $columns = ['created_at', 'transaction_id', 'firstname', 'pincode', 'variant', 'color', 'status'];
                } elseif ($table === 'test_drives') {
                    $columns = ['created_at', 'id', 'full_name', 'email', 'phone', 'pincode', 'date', 'message'];
                } elseif ($table === 'contacts') {
                    $columns = ['created_at', 'id', 'full_name', 'email', 'phone', 'help_type', 'message'];
                }
                
                $sortColumn = $columns[$orderColumn] ?? 'created_at';
            } else {
                // Default sorting - latest first for all tables
                if ($table === 'transactions') {
                    $sortColumn = 'created_at'; // Try created_at first, fallback will be handled in AdminHandler
                } else {
                    $sortColumn = 'created_at';
                }
                $sortDirection = 'DESC';
            }

            // Advanced filtering parameters
            $filters = [];
            $dateRange = $_GET['date_range'] ?? '';
            $startDate = $_GET['start_date'] ?? '';
            $endDate = $_GET['end_date'] ?? '';

            // Collect column-specific filters
            foreach ($_GET as $key => $value) {
                if (strpos($key, 'filter_') === 0 && !empty($value)) {
                    $columnName = substr($key, 7); // Remove 'filter_' prefix
                    $filters[$columnName] = $value;
                }
            }

            $result = $adminHandler->getTableDataForDataTables($table, $page, $perPage, $search, $sortColumn, $sortDirection, $filters, $dateRange, $startDate, $endDate, $draw);
            echo json_encode($result);
            break;

        case 'filter_options':
            try {
                $table = $_GET['table'] ?? '';
                $options = $adminHandler->getFilterOptions($table);
                echo json_encode($options);
            } catch (Exception $e) {
                $logger->error('[ADMIN_API] Filter options error', [
                    'table' => $table ?? 'unknown',
                    'error' => $e->getMessage()
                ]);
                echo json_encode([]);
            }
            break;

        case 'analytics':
            $analytics = $adminHandler->getAnalyticsData();
            echo json_encode($analytics);
            break;

        case 'logs':
            // Set execution time limit for log reading
            set_time_limit(30);
            
            $logFile = $_GET['log_file'] ?? 'debug_logs.txt';
            $lines = intval($_GET['lines'] ?? 100);
            
            // Validate log file name to prevent directory traversal
            $allowedLogFiles = [
                'debug_logs.txt',
                'email_logs.txt',
                'error_logs.txt',
                'info_logs.txt',
                'payment_logs.txt',
                'salesforce_logs.txt',
                'sms_logs.txt',
                'status_logs.txt',
                'thank_you_logs.txt',
                'warning_logs.txt',
                'payment-flow.log'
            ];
            
            if (!in_array($logFile, $allowedLogFiles)) {
                echo json_encode(['error' => 'Invalid log file requested']);
                break;
            }
            
            try {
                $content = $adminHandler->getLogContent($logFile, $lines);
                echo json_encode(['content' => $content, 'status' => 'success']);
            } catch (Exception $e) {
                echo json_encode(['error' => 'Failed to read log file: ' . $e->getMessage()]);
            }
            break;

        case 'export':
            $table = $_GET['table'] ?? '';
            $format = $_GET['format'] ?? 'csv';
            
            // Check if there are filters to apply
            $hasFilters = false;
            $filters = [];
            $search = $_GET['search'] ?? '';
            $dateRange = $_GET['date_range'] ?? '';
            $startDate = $_GET['start_date'] ?? '';
            $endDate = $_GET['end_date'] ?? '';

            // Collect column-specific filters
            foreach ($_GET as $key => $value) {
                if (strpos($key, 'filter_') === 0 && !empty($value)) {
                    $columnName = substr($key, 7);
                    $filters[$columnName] = $value;
                    $hasFilters = true;
                }
            }

            if (!empty($search) || !empty($dateRange) || (!empty($startDate) && !empty($endDate))) {
                $hasFilters = true;
            }

            if ($hasFilters) {
                // Export filtered data
                $result = $adminHandler->getTableDataWithFilters($table, 1, 10000, $search, null, 'DESC', $filters, $dateRange, $startDate, $endDate);
                $data = $adminHandler->exportFilteredData($result['data'], $format);
            } else {
                // Export all data
                $data = $adminHandler->exportTableData($table, $format);
            }
            
            $filename = $table . '_export_' . date('Y-m-d_H-i-s');
            
            if ($format === 'csv') {
                header('Content-Type: text/csv');
                header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
                echo $data;
            } elseif ($format === 'json') {
                header('Content-Type: application/json');
                header('Content-Disposition: attachment; filename="' . $filename . '.json"');
                echo $data;
            } elseif ($format === 'excel') {
                header('Content-Type: application/vnd.ms-excel');
                header('Content-Disposition: attachment; filename="' . $filename . '.xls"');
                echo $data;
            }
            exit;

        // User Management Endpoints
        case 'current_user':
            try {
                $currentUser = $adminHandler->getCurrentUserInfo();
                if ($currentUser) {
                    // Don't send password_hash
                    unset($currentUser['password_hash']);
                    echo json_encode($currentUser);
                } else {
                    echo json_encode(['error' => 'User not found']);
                }
            } catch (Exception $e) {
                $logger->error('[ADMIN_API] Current user error', [
                    'error' => $e->getMessage()
                ]);
                echo json_encode(['error' => 'Failed to load user info']);
            }
            break;

        case 'users':
            $users = $adminHandler->getAllAdminUsers();
            echo json_encode($users);
            break;

        case 'create_user':
            // Check if current user is admin or super admin
            $currentRole = $adminHandler->getCurrentUserRole();
            if (!in_array($currentRole, ['admin', 'super_admin'])) {
                echo json_encode(['success' => false, 'error' => 'Only administrators can create users']);
                break;
            }

            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';
            $email = $_POST['email'] ?? '';
            $fullName = $_POST['full_name'] ?? '';
            $role = $_POST['role'] ?? 'admin';

            if (empty($username) || empty($password)) {
                echo json_encode(['success' => false, 'error' => 'Username and password are required']);
                break;
            }

            $result = $adminHandler->createAdminUser($username, $password, $email, $fullName, $role);
            echo json_encode($result);
            break;

        case 'update_user':
            // Check if current user is super admin
            if (!$adminHandler->isSuperAdmin()) {
                echo json_encode(['success' => false, 'error' => 'Only super administrators can edit users']);
                break;
            }

            $userId = $_POST['user_id'] ?? '';
            $email = $_POST['email'] ?? '';
            $fullName = $_POST['full_name'] ?? '';
            $role = $_POST['role'] ?? 'admin';
            $isActive = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;

            if (empty($userId)) {
                echo json_encode(['success' => false, 'error' => 'User ID is required']);
                break;
            }

            $result = $adminHandler->updateAdminUser($userId, $email, $fullName, $role, $isActive);
            echo json_encode($result);
            break;

        case 'change_password':
            // Check if current user is admin or super admin
            $currentRole = $adminHandler->getCurrentUserRole();
            if (!in_array($currentRole, ['admin', 'super_admin'])) {
                echo json_encode(['success' => false, 'error' => 'Only administrators can change passwords']);
                break;
            }

            $userId = $_POST['user_id'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';

            if (empty($userId) || empty($newPassword)) {
                echo json_encode(['success' => false, 'error' => 'User ID and new password are required']);
                break;
            }

            $result = $adminHandler->changeAdminPassword($userId, $newPassword);
            echo json_encode($result);
            break;

        case 'delete_user':
            $userId = $_POST['user_id'] ?? '';

            if (empty($userId)) {
                echo json_encode(['success' => false, 'error' => 'User ID is required']);
                break;
            }

            $result = $adminHandler->deleteAdminUser($userId);
            echo json_encode($result);
            break;

        case 'get_user':
            $userId = $_GET['user_id'] ?? '';

            if (empty($userId)) {
                echo json_encode(['error' => 'User ID is required']);
                break;
            }

            $user = $adminHandler->getAdminUser($userId);
            if ($user) {
                echo json_encode($user);
            } else {
                echo json_encode(['error' => 'User not found']);
            }
            break;

        case 'get_transaction':
            $id = $_GET['id'] ?? '';

            if (empty($id)) {
                echo json_encode(['error' => 'Transaction ID is required']);
                break;
            }

            $result = $adminHandler->getTransactionById($id);
            echo json_encode($result);
            break;

        case 'get_test_drive':
            $id = $_GET['id'] ?? '';

            if (empty($id)) {
                echo json_encode(['error' => 'Test drive ID is required']);
                break;
            }

            $result = $adminHandler->getTestDriveById($id);
            echo json_encode($result);
            break;

        case 'get_contact':
            $id = $_GET['id'] ?? '';

            if (empty($id)) {
                echo json_encode(['error' => 'Contact ID is required']);
                break;
            }

            $result = $adminHandler->getContactById($id);
            echo json_encode($result);
            break;

        case 'get_dealerships':
            try {
                $dealerships = $adminHandler->getAllDealerships();
                echo json_encode([
                    'success' => true,
                    'data' => $dealerships
                ]);
            } catch (Exception $e) {
                $logger->error('[ADMIN_API] Get dealerships error', [
                    'error' => $e->getMessage()
                ]);
                echo json_encode(['success' => false, 'error' => 'Failed to load dealerships']);
            }
            break;

        case 'get_dealership':
            $id = $_GET['id'] ?? '';
            
            if (empty($id)) {
                echo json_encode(['success' => false, 'error' => 'Dealership ID is required']);
                break;
            }
            
            try {
                $dealership = $adminHandler->getDealership($id);
                if ($dealership) {
                    echo json_encode([
                        'success' => true,
                        'data' => $dealership
                    ]);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Dealership not found']);
                }
            } catch (Exception $e) {
                $logger->error('[ADMIN_API] Get dealership error', [
                    'error' => $e->getMessage(),
                    'id' => $id
                ]);
                echo json_encode(['success' => false, 'error' => 'Failed to load dealership']);
            }
            break;

        case 'update_dealership':
            try {
                $input = json_decode(file_get_contents('php://input'), true);
                
                if (!$input || !isset($input['id'])) {
                    echo json_encode(['success' => false, 'error' => 'Invalid input data']);
                    break;
                }
                
                // Prepare data array including the ID for updateDealership method
                $data = [
                    'id' => $input['id'],
                    'name' => $input['name'] ?? '',
                    'email' => $input['email'] ?? '',
                    'phone' => $input['phone'] ?? '',
                    'address' => $input['address'] ?? '',
                    'city' => $input['city'] ?? '',
                    'state' => $input['state'] ?? '',
                    'pincode' => $input['pincode'] ?? '',
                    'latitude' => $input['latitude'] ?? null,
                    'longitude' => $input['longitude'] ?? null
                ];
                
                // Validate required fields
                if (empty($data['name']) || empty($data['address']) || empty($data['city']) || 
                    empty($data['state']) || empty($data['pincode'])) {
                    echo json_encode(['success' => false, 'error' => 'Required fields are missing']);
                    break;
                }
                
                $result = $adminHandler->updateDealership($data);
                
                if ($result) {
                    echo json_encode(['success' => true, 'message' => 'Dealership updated successfully']);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Failed to update dealership']);
                }
            } catch (Exception $e) {
                $logger->error('[ADMIN_API] Update dealership error', [
                    'error' => $e->getMessage()
                ]);
                echo json_encode(['success' => false, 'error' => 'Failed to update dealership']);
            }
            break;

        case 'create_dealership':
            try {
                $input = json_decode(file_get_contents('php://input'), true);
                
                if (!$input) {
                    echo json_encode(['success' => false, 'error' => 'Invalid input data']);
                    break;
                }
                
                $data = [
                    'name' => $input['name'] ?? '',
                    'email' => $input['email'] ?? '',
                    'phone' => $input['phone'] ?? '',
                    'address' => $input['address'] ?? '',
                    'city' => $input['city'] ?? '',
                    'state' => $input['state'] ?? '',
                    'pincode' => $input['pincode'] ?? '',
                    'latitude' => $input['latitude'] ?? null,
                    'longitude' => $input['longitude'] ?? null
                ];
                
                // Validate required fields
                if (empty($data['name']) || empty($data['address']) || empty($data['city']) || 
                    empty($data['state']) || empty($data['pincode'])) {
                    echo json_encode(['success' => false, 'error' => 'Required fields are missing']);
                    break;
                }
                
                $result = $adminHandler->createDealership($data);
                
                if ($result) {
                    echo json_encode(['success' => true, 'message' => 'Dealership created successfully', 'data' => $result]);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Failed to create dealership']);
                }
            } catch (Exception $e) {
                $logger->error('[ADMIN_API] Create dealership error', [
                    'error' => $e->getMessage()
                ]);
                echo json_encode(['success' => false, 'error' => 'Failed to create dealership']);
            }
            break;

        case 'delete_dealership':
            try {
                $id = $_POST['id'] ?? $_GET['id'] ?? '';
                
                if (empty($id)) {
                    echo json_encode(['success' => false, 'error' => 'Dealership ID is required']);
                    break;
                }
                
                $result = $adminHandler->deleteDealership($id);
                
                if ($result) {
                    echo json_encode(['success' => true, 'message' => 'Dealership deleted successfully']);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Failed to delete dealership']);
                }
            } catch (Exception $e) {
                $logger->error('[ADMIN_API] Delete dealership error', [
                    'error' => $e->getMessage()
                ]);
                echo json_encode(['success' => false, 'error' => 'Failed to delete dealership']);
            }
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
    }
} catch (Exception $e) {
    $logger->error('[ADMIN_API] API error', [
        'action' => $action,
        'error' => $e->getMessage()
    ]);
    
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}

// Clean output buffer before sending response
$bufferContent = ob_get_contents();
if (!empty($bufferContent)) {
    // Check if buffer contains valid JSON
    $decoded = json_decode($bufferContent);
    if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
        // Buffer contains invalid JSON (likely PHP errors), clean it
        ob_clean();
    }
}
ob_end_flush();
?>
