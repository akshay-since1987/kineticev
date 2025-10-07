<?php
header('Content-Type: application/json');
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/../../DatabaseHandler.php');
require_once(__DIR__ . '/../../Logger.php');

// Check authentication
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

try {
    $db = new DatabaseHandler();
    $logger = Logger::getInstance();
    $action = $_GET['action'] ?? '';

    switch ($action) {
        case 'list':
            $start = isset($_GET['start']) ? intval($_GET['start']) : 0;
            $length = isset($_GET['length']) ? intval($_GET['length']) : 10;
            $draw = isset($_GET['draw']) ? intval($_GET['draw']) : 1;

            // Get total records
            $total = $db->getConnection()->query("SELECT COUNT(*) FROM test_drive_bookings")->fetchColumn();

            // Get filtered records
            $sql = "SELECT b.*, u.name as customer_name, v.model as vehicle_model 
                   FROM test_drive_bookings b 
                   LEFT JOIN users u ON b.user_id = u.id 
                   LEFT JOIN vehicles v ON b.vehicle_id = v.id 
                   ORDER BY b.booking_date DESC 
                   LIMIT $start, $length";
            
            $bookings = $db->getConnection()->query($sql)->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'draw' => $draw,
                'recordsTotal' => $total,
                'recordsFiltered' => $total,
                'data' => $bookings
            ]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    $logger->error('Test Drives API Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to process request',
        'debug' => $config['DEBUG'] ? $e->getMessage() : null
    ]);
}