<?php
/**
 * Migration: Create and populate allowed_cities table
 * 
 * This migration script:
 * 1. Creates the allowed_cities table if it doesn't exist
 * 2. Populates it with default cities data
 */

// Initialize production timezone guard
require_once __DIR__ . '/../production-timezone-guard.php';
require_once __DIR__ . '/../DatabaseHandler.php';
require_once __DIR__ . '/../Logger.php';
require_once __DIR__ . '/../DatabaseMigration.php';

class AllowedCitiesMigration {
    private $db;
    private $logger;

    // Default cities data
    private $defaultCities = [
        [
            'city_name' => 'Mumbai',
            'coordinates' => '19.0760,72.8777',
            'is_allowed' => 1,
            'description' => 'Mumbai Metropolitan Region',
            'max_distance_km' => 50,
            'created_at' => 'CURRENT_TIMESTAMP',
            'updated_at' => 'CURRENT_TIMESTAMP'
        ],
        [
            'city_name' => 'Pune',
            'coordinates' => '18.5204,73.8567',
            'is_allowed' => 1,
            'description' => 'Pune Metropolitan Region',
            'max_distance_km' => 50,
            'created_at' => 'CURRENT_TIMESTAMP',
            'updated_at' => 'CURRENT_TIMESTAMP'
        ],
        [
            'city_name' => 'Pimpri-Chinchwad',
            'coordinates' => '18.6298,73.7997',
            'is_allowed' => 1,
            'description' => 'Pimpri-Chinchwad Metropolitan Region',
            'max_distance_km' => 50,
            'created_at' => 'CURRENT_TIMESTAMP',
            'updated_at' => 'CURRENT_TIMESTAMP'
        ]
    ];

    public function __construct() {
        $this->logger = Logger::getInstance();
        $this->db = new DatabaseHandler();
    }

    /**
     * Run the migration
     */
    public function up() {
        try {
            $this->logger->info('Starting AllowedCitiesMigration');

            // Create table if it doesn't exist
            $this->createTable();

            // Check if table is empty
            $conn = $this->db->getConnection();
            $stmt = $conn->query("SELECT COUNT(*) FROM allowed_cities");
            $count = $stmt->fetchColumn();
            
            if ($count == 0) {
                $this->logger->info('Populating allowed_cities table with default data');
                $this->insertDefaultData();
            } else {
                $this->logger->info('Table already has data, skipping population');
            }

            $this->logger->info('AllowedCitiesMigration completed successfully');
            return true;
        } catch (Exception $e) {
            $this->logger->error('Migration failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Rollback the migration
     */
    public function down() {
        try {
            $this->logger->info('Rolling back AllowedCitiesMigration');
            
            // Drop the table
            $sql = "DROP TABLE IF EXISTS allowed_cities";
            $this->db->getConnection()->exec($sql);
            
            $this->logger->info('Rollback completed successfully');
            return true;
        } catch (Exception $e) {
            $this->logger->error('Rollback failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create the allowed_cities table
     */
    private function createTable() {
        $sql = "CREATE TABLE IF NOT EXISTS allowed_cities (
            id INT AUTO_INCREMENT PRIMARY KEY,
            city_name VARCHAR(100) NOT NULL,
            coordinates VARCHAR(50) NOT NULL COMMENT 'Format: lat,lng',
            is_allowed TINYINT(1) DEFAULT 1,
            description TEXT,
            max_distance_km INT DEFAULT 50,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_city (city_name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $this->db->getConnection()->exec($sql);
        $this->logger->info('Created allowed_cities table');
    }

    /**
     * Insert default cities data
     */
    private function insertDefaultData() {
        foreach ($this->defaultCities as $city) {
            $sql = "INSERT INTO allowed_cities 
                   (city_name, coordinates, is_allowed, description, max_distance_km) 
                   VALUES 
                   (:city_name, :coordinates, :is_allowed, :description, :max_distance_km)";

            $params = [
                ':city_name' => $city['city_name'],
                ':coordinates' => $city['coordinates'],
                ':is_allowed' => $city['is_allowed'],
                ':description' => $city['description'],
                ':max_distance_km' => $city['max_distance_km']
            ];

            $stmt = $this->db->getConnection()->prepare($sql);
            $stmt->execute($params);
            $this->logger->info("Inserted city: {$city['city_name']}");
        }
    }
}

// Run migration if file is executed directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    try {
        $migration = new AllowedCitiesMigration();
        
        // Check for rollback flag
        $isRollback = isset($argv[1]) && $argv[1] === '--rollback';
        
        if ($isRollback) {
            $migration->down();
            echo "Migration rolled back successfully\n";
        } else {
            $migration->up();
            echo "Migration completed successfully\n";
        }
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
        exit(1);
    }
}
?>