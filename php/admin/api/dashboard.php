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

    // Get users count
    $total_users = $db->getConnection()->query("SELECT COUNT(*) FROM admin_users")->fetchColumn();
    $active_users = $total_users; // Assuming all users are active for now
    
    // Get cities count
    $total_cities = $db->getConnection()->query("SELECT COUNT(*) FROM allowed_cities")->fetchColumn();
    $active_cities = $db->getConnection()->query("SELECT COUNT(*) FROM allowed_cities WHERE is_allowed = 1")->fetchColumn();
    
    echo json_encode([
        'success' => true,
        'stats' => [
            'total_users' => (int)$total_users,
            'active_users' => (int)$active_users,
            'total_cities' => (int)$total_cities,
            'active_cities' => (int)$active_cities
        ]
    ]);

} catch (Exception $e) {
    $logger->error('Dashboard API Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to load dashboard data'
    ]);
}