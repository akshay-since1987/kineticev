<?php

// Prevent direct access
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    http_response_code(404);
    exit();
}

/**
 * Database Migration Handler
 * Manages database schema changes and migrations
 */

require_once __DIR__ . '/Logger.php';

class DatabaseMigration 
{
    private $conn;
    private $logger;
    
    public function __construct($connection) 
    {
        $this->conn = $connection;
        $this->logger = Logger::getInstance();
        
        // Create migrations table if it doesn't exist
        $this->createMigrationsTable();
    }
    
    /**
     * Create migrations table to track completed migrations
     */
    private function createMigrationsTable() 
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS `migrations` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `migration_name` VARCHAR(255) NOT NULL UNIQUE,
                `executed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX `idx_migration_name` (`migration_name`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        
        try {
            $this->conn->exec($sql);
            $this->logger->debug('[MIGRATION] Migrations table created or verified');
        } catch (PDOException $e) {
            $this->logger->error('[MIGRATION] Failed to create migrations table', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Check if a migration has already been run
     */
    public function hasMigrationRun($migrationName) 
    {
        try {
            $stmt = $this->conn->prepare("SELECT COUNT(*) FROM migrations WHERE migration_name = ?");
            $stmt->execute([$migrationName]);
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            $this->logger->error('[MIGRATION] Failed to check migration status', [
                'migration' => $migrationName,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Mark a migration as completed
     */
    public function markMigrationAsRun($migrationName) 
    {
        try {
            $stmt = $this->conn->prepare("INSERT IGNORE INTO migrations (migration_name) VALUES (?)");
            $stmt->execute([$migrationName]);
            $this->logger->info('[MIGRATION] Marked migration as completed', [
                'migration' => $migrationName
            ]);
            return true;
        } catch (PDOException $e) {
            $this->logger->error('[MIGRATION] Failed to mark migration as completed', [
                'migration' => $migrationName,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Add UUID column to table and generate UUIDs for existing records
     */
    public function addUuidColumn($tableName, $columnName = 'uuid')
    {
        $migrationName = "add_uuid_to_{$tableName}";
        
        // Check if migration already completed
        if ($this->hasMigrationRun($migrationName)) {
            $this->logger->info("[MIGRATION] Migration {$migrationName} already executed, skipping");
            return true;
        }
        
        // Check if column already exists
        try {
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) 
                FROM INFORMATION_SCHEMA.COLUMNS 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = ? 
                AND COLUMN_NAME = ?
            ");
            $stmt->execute([$tableName, $columnName]);
            $columnExists = $stmt->fetchColumn() > 0;
            
            if ($columnExists) {
                $this->logger->debug('[MIGRATION] UUID column already exists', [
                    'table' => $tableName,
                    'column' => $columnName
                ]);
                
                // Mark migration as completed
                $this->markMigrationAsRun($migrationName);
                return true;
            }
            
        } catch (PDOException $e) {
            $this->logger->error('[MIGRATION] Failed to check if UUID column exists', [
                'table' => $tableName,
                'column' => $columnName,
                'error' => $e->getMessage()
            ]);
            return false;
        }
        
        // Step-by-step migration to avoid constraint violations
        try {
            // Step 1: Add the UUID column without constraints
            $addColumnSql = "ALTER TABLE `{$tableName}` ADD COLUMN `{$columnName}` VARCHAR(36) NULL AFTER `id`";
            $this->conn->exec($addColumnSql);
            $this->logger->info("[MIGRATION] Added UUID column to {$tableName}");
            
            // Step 2: Generate UUIDs for all existing records
            $stmt = $this->conn->query("SELECT id FROM `{$tableName}`");
            $records = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            foreach ($records as $id) {
                $uuid = self::generateUuid();
                $updateStmt = $this->conn->prepare("UPDATE `{$tableName}` SET `{$columnName}` = ? WHERE id = ?");
                $updateStmt->execute([$uuid, $id]);
            }
            $this->logger->info("[MIGRATION] Generated UUIDs for " . count($records) . " existing records in {$tableName}");
            
            // Step 3: Make the column NOT NULL and add unique constraint
            $constraintSql = "ALTER TABLE `{$tableName}` MODIFY COLUMN `{$columnName}` VARCHAR(36) NOT NULL";
            $this->conn->exec($constraintSql);
            
            $uniqueIndexSql = "ALTER TABLE `{$tableName}` ADD UNIQUE INDEX `idx_{$tableName}_{$columnName}` (`{$columnName}`)";
            $this->conn->exec($uniqueIndexSql);
            
            $this->logger->info("[MIGRATION] Added unique constraint to {$tableName}.{$columnName}");
            
            // Record the migration as completed
            $this->markMigrationAsRun($migrationName);
            
            return true;
            
        } catch (Exception $e) {
            $this->logger->error("[MIGRATION] UUID migration failed for {$tableName}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }
    
    /**
     * Add a column to a table if it doesn't exist
     */
    public function addColumn($tableName, $columnName, $columnDefinition)
    {
        $migrationName = "add_{$columnName}_to_{$tableName}";
        
        if ($this->hasMigrationRun($migrationName)) {
            $this->logger->debug("[MIGRATION] Column {$columnName} already exists in {$tableName}");
            return true;
        }
        
        try {
            $this->logger->info("[MIGRATION] Adding column {$columnName} to table {$tableName}");
            
            // Check if column already exists
            $checkSql = "SHOW COLUMNS FROM `{$tableName}` LIKE '{$columnName}'";
            $stmt = $this->conn->prepare($checkSql);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $this->logger->debug("[MIGRATION] Column {$columnName} already exists in {$tableName}");
                $this->markMigrationAsRun($migrationName);
                return true;
            }
            
            // Add column
            $addColumnSql = "ALTER TABLE `{$tableName}` ADD COLUMN `{$columnName}` {$columnDefinition}";
            $this->conn->exec($addColumnSql);
            
            $this->logger->info("[MIGRATION] Successfully added column {$columnName} to {$tableName}");
            $this->markMigrationAsRun($migrationName);
            
            return true;
        } catch (PDOException $e) {
            $this->logger->error("[MIGRATION] Failed to add column {$columnName} to {$tableName}", [
                'error' => $e->getMessage(),
                'sql' => $addColumnSql ?? 'N/A'
            ]);
            return false;
        }
    }
    
    /**
     * Generate a new UUID (UUID v4 format)
     */
    public static function generateUuid()
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
    
    /**
     * Update ENUM column to add new values
     */
    public function updateEnumColumn($tableName, $columnName, $newEnumValues, $migrationName) 
    {
        if ($this->hasMigrationRun($migrationName)) {
            $this->logger->debug('[MIGRATION] ENUM update migration already completed', [
                'migration' => $migrationName
            ]);
            return true;
        }
        
        try {
            // Convert array to SQL ENUM format
            $enumValues = "'" . implode("','", $newEnumValues) . "'";
            $alterSql = "ALTER TABLE `{$tableName}` MODIFY COLUMN `{$columnName}` ENUM({$enumValues}) NOT NULL";
            
            $this->logger->info("[MIGRATION] Updating ENUM column {$columnName} in {$tableName}", [
                'new_values' => $newEnumValues,
                'sql' => $alterSql
            ]);
            
            $this->conn->exec($alterSql);
            
            $this->logger->info("[MIGRATION] Successfully updated ENUM column {$columnName} in {$tableName}");
            $this->markMigrationAsRun($migrationName);
            
            return true;
        } catch (PDOException $e) {
            $this->logger->error("[MIGRATION] Failed to update ENUM column {$columnName} in {$tableName}", [
                'error' => $e->getMessage(),
                'sql' => $alterSql ?? 'N/A'
            ]);
            return false;
        }
    }
    
    /**
     * Add dealership option to help_type ENUM
     */
    public function addDealershipToHelpType() 
    {
        $migrationName = 'add_dealership_to_help_type_enum_20250902';
        $newEnumValues = ['support', 'enquiry', 'dealership', 'others'];
        
        return $this->updateEnumColumn('contacts', 'help_type', $newEnumValues, $migrationName);
    }

    /**
     * Fix missing status column in test_drives table
     */
    public function fixTestDrivesStatusColumn()
    {
        $migrationName = 'add_status_column_to_test_drives_20250904';
        
        if ($this->hasMigrationRun($migrationName)) {
            $this->logger->info("[MIGRATION] Test drives status column migration already completed");
            return true;
        }

        try {
            // Check if column already exists
            $checkSql = "SHOW COLUMNS FROM test_drives LIKE 'status'";
            $stmt = $this->conn->query($checkSql);
            $columnExists = $stmt->fetch();

            if (!$columnExists) {
                $this->logger->info("[MIGRATION] Adding status column to test_drives table");
                $alterSql = "ALTER TABLE test_drives ADD COLUMN status VARCHAR(50) DEFAULT 'pending' COMMENT 'Test drive request status'";
                $this->conn->exec($alterSql);
                $this->logger->info("[MIGRATION] Successfully added status column to test_drives table");
            } else {
                $this->logger->info("[MIGRATION] Status column already exists in test_drives table");
            }

            $this->markMigrationAsRun($migrationName);
            return true;

        } catch (PDOException $e) {
            $this->logger->error("[MIGRATION] Failed to add status column to test_drives", [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Fix missing subject column in contacts table
     */
    public function fixContactsSubjectColumn()
    {
        $migrationName = 'add_subject_column_to_contacts_20250904';
        
        if ($this->hasMigrationRun($migrationName)) {
            $this->logger->info("[MIGRATION] Contacts subject column migration already completed");
            return true;
        }

        try {
            // Check if column already exists
            $checkSql = "SHOW COLUMNS FROM contacts LIKE 'subject'";
            $stmt = $this->conn->query($checkSql);
            $columnExists = $stmt->fetch();

            if (!$columnExists) {
                $this->logger->info("[MIGRATION] Adding subject column to contacts table");
                $alterSql = "ALTER TABLE contacts ADD COLUMN subject VARCHAR(255) DEFAULT NULL COMMENT 'Contact form subject/title'";
                $this->conn->exec($alterSql);
                $this->logger->info("[MIGRATION] Successfully added subject column to contacts table");
            } else {
                $this->logger->info("[MIGRATION] Subject column already exists in contacts table");
            }

            $this->markMigrationAsRun($migrationName);
            return true;

        } catch (PDOException $e) {
            $this->logger->error("[MIGRATION] Failed to add subject column to contacts", [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Run all critical schema fixes
     */
    public function runCriticalSchemaFixes()
    {
        $this->logger->info("[MIGRATION] Starting critical schema fixes");
        
        $results = [
            'test_drives_status' => $this->fixTestDrivesStatusColumn(),
            'contacts_subject' => $this->fixContactsSubjectColumn()
        ];
        
        $success = array_reduce($results, function($carry, $result) {
            return $carry && $result;
        }, true);
        
        if ($success) {
            $this->logger->info("[MIGRATION] All critical schema fixes completed successfully");
        } else {
            $this->logger->warning("[MIGRATION] Some schema fixes failed", $results);
        }
        
        return $results;
    }
}
