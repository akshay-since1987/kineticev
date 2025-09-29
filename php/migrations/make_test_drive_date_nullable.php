<?php
/**
 * Database Migration: Make test_drives.date column nullable
 * 
 * This migration modifies the test_drives table to make the date column nullable
 * to support test ride requests without requiring a specific date.
 * 
 * Usage: php make_test_drive_date_nullable.php
 */

require_once __DIR__ . '/../DatabaseHandler.php';
require_once __DIR__ . '/../Logger.php';

// Initialize logger
$logger = Logger::getInstance();
$logger->info('[MIGRATION] Starting test_drives date column migration', [
    'migration' => 'make_test_drive_date_nullable',
    'timestamp' => date('Y-m-d H:i:s')
]);

try {
    // Initialize database connection using config
    $db = new DatabaseHandler();
    $connection = $db->getConnection();
    
    // Get current environment info
    $config = include __DIR__ . '/../config.php';
    $environment = $config['environment'] ?? 'unknown';
    
    $logger->info('[MIGRATION] Connected to database', [
        'environment' => $environment,
        'host' => $config['database']['host'] ?? 'unknown'
    ]);
    
    // Check current column structure
    $checkSql = "SHOW COLUMNS FROM test_drives WHERE Field = 'date'";
    $stmt = $connection->prepare($checkSql);
    $stmt->execute();
    $currentColumn = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$currentColumn) {
        $logger->error('[MIGRATION] Date column not found in test_drives table');
        echo "ERROR: Date column not found in test_drives table\n";
        exit(1);
    }
    
    $logger->info('[MIGRATION] Current column structure', [
        'field' => $currentColumn['Field'],
        'type' => $currentColumn['Type'],
        'null' => $currentColumn['Null'],
        'default' => $currentColumn['Default']
    ]);
    
    echo "Current date column structure:\n";
    echo "Type: {$currentColumn['Type']}\n";
    echo "Null: {$currentColumn['Null']}\n";
    echo "Default: {$currentColumn['Default']}\n\n";
    
    // Check if column is already nullable
    if ($currentColumn['Null'] === 'YES') {
        $logger->info('[MIGRATION] Date column is already nullable - no changes needed');
        echo "SUCCESS: Date column is already nullable. No migration needed.\n";
        exit(0);
    }
    
    // Perform the migration
    echo "Making date column nullable...\n";
    
    $migrationSql = "ALTER TABLE test_drives MODIFY COLUMN date DATE NULL";
    
    $logger->info('[MIGRATION] Executing migration SQL', [
        'sql' => $migrationSql
    ]);
    
    $connection->exec($migrationSql);
    
    // Verify the change
    $stmt = $connection->prepare($checkSql);
    $stmt->execute();
    $updatedColumn = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($updatedColumn['Null'] === 'YES') {
        $logger->info('[MIGRATION] Migration completed successfully', [
            'updated_column' => $updatedColumn,
            'environment' => $environment
        ]);
        
        echo "SUCCESS: Date column is now nullable!\n";
        echo "Updated column structure:\n";
        echo "Type: {$updatedColumn['Type']}\n";
        echo "Null: {$updatedColumn['Null']}\n";
        echo "Default: {$updatedColumn['Default']}\n";
        
        // Log to database migration tracking if table exists
        try {
            $migrationLogSql = "INSERT INTO database_migrations (migration_name, executed_at, environment) VALUES (?, NOW(), ?)";
            $migrationStmt = $connection->prepare($migrationLogSql);
            $migrationStmt->execute(['make_test_drive_date_nullable', $environment]);
            
            $logger->info('[MIGRATION] Migration logged to database_migrations table');
        } catch (Exception $e) {
            $logger->warning('[MIGRATION] Could not log to database_migrations table (table may not exist)', [
                'error' => $e->getMessage()
            ]);
            echo "Note: Migration executed but not logged to tracking table (table may not exist)\n";
        }
        
    } else {
        throw new Exception("Migration verification failed - column is still not nullable");
    }
    
} catch (Exception $e) {
    $logger->error('[MIGRATION] Migration failed', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    
    echo "ERROR: Migration failed - " . $e->getMessage() . "\n";
    exit(1);
}

echo "\nMigration completed successfully on " . ($environment ?? 'unknown environment') . "\n";
echo "Timestamp: " . date('Y-m-d H:i:s') . "\n";
?>