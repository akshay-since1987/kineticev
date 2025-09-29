<?php
/**
 * Migration Runner Script
 * 
 * This script runs database migrations on the current environment
 * based on the config.php file in the php directory.
 * 
 * Usage: 
 * php run_migration.php make_test_drive_date_nullable
 */

if ($argc < 2) {
    echo "Usage: php run_migration.php <migration_name>\n";
    echo "Available migrations:\n";
    echo "  - make_test_drive_date_nullable\n";
    exit(1);
}

$migrationName = $argv[1];
$migrationFile = __DIR__ . "/migrations/{$migrationName}.php";

if (!file_exists($migrationFile)) {
    echo "ERROR: Migration file not found: {$migrationFile}\n";
    exit(1);
}

echo "Running migration: {$migrationName}\n";
echo "Environment will be determined by config.php\n";
echo "==========================================\n\n";

// Include and execute the migration
require_once $migrationFile;
?>