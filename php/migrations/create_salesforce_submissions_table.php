<?php
/**
 * Migration: Create salesforce_submissions table for duplicate tracking
 */

require_once __DIR__ . '/../DatabaseHandler.php';
require_once __DIR__ . '/../Logger.php';

$logger = Logger::getInstance();
$logger->info('[MIGRATION] Starting salesforce_submissions table creation');

try {
    $db = new DatabaseHandler();
    $connection = $db->getConnection();
    
    echo "=== SALESFORCE SUBMISSIONS TABLE MIGRATION ===\n\n";
    
    // Check if table exists
    $checkSql = "SHOW TABLES LIKE 'salesforce_submissions'";
    $stmt = $connection->query($checkSql);
    $exists = $stmt->rowCount() > 0;
    
    if ($exists) {
        echo "✅ Table 'salesforce_submissions' already exists\n";
        
        // Show current structure
        echo "\nCurrent table structure:\n";
        $descStmt = $connection->query("DESCRIBE salesforce_submissions");
        $columns = $descStmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($columns as $col) {
            echo "- {$col['Field']} ({$col['Type']}) {$col['Null']} {$col['Default']}\n";
        }
    } else {
        echo "Creating 'salesforce_submissions' table...\n";
        
        $createSql = "
            CREATE TABLE `salesforce_submissions` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `transaction_id` VARCHAR(255) NOT NULL,
                `payment_status` VARCHAR(50) NOT NULL,
                `form_type` VARCHAR(50) NOT NULL,
                `submission_hash` VARCHAR(32) NOT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX `idx_transaction_status` (`transaction_id`, `payment_status`),
                INDEX `idx_submission_hash` (`submission_hash`),
                UNIQUE KEY `unique_submission` (`transaction_id`, `payment_status`, `form_type`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        
        $connection->exec($createSql);
        
        echo "✅ Successfully created 'salesforce_submissions' table\n";
        
        $logger->info('[MIGRATION] salesforce_submissions table created successfully');
    }
    
    echo "\n=== MIGRATION COMPLETED ===\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    $logger->error('[MIGRATION] Failed to create salesforce_submissions table', [
        'error' => $e->getMessage()
    ]);
}
?>