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

    // Default cities and pincodes data
    private $defaultCities = array(
        array(
            'city_name' => 'Mumbai',
            'state' => 'Maharashtra',
            'coordinates' => '19.0760,72.8777',
            'is_allowed' => 1,
            'description' => 'Mumbai Metropolitan Region',
            'max_distance_km' => 50,
            'created_by' => 1,
            'updated_by' => 1,
            'pincodes' => array(
                array(
                    'pincode' => '400001',
                    'area_name' => 'Fort',
                    'coordinates' => '18.9345,72.8346',
                    'is_allowed' => 1,
                    'is_enabled' => 1,
                    'description' => 'Fort Area'
                )
            )
        ),
        array(
            'city_name' => 'Pune',
            'state' => 'Maharashtra',
            'coordinates' => '18.5204,73.8567',
            'is_allowed' => 1,
            'description' => 'Pune Metropolitan Region',
            'max_distance_km' => 50,
            'created_by' => 1,
            'updated_by' => 1,
            'pincodes' => array(
                array(
                    'pincode' => '411001',
                    'area_name' => 'Shivajinagar',
                    'coordinates' => '18.5314,73.8446',
                    'is_allowed' => 1,
                    'is_enabled' => 1,
                    'description' => 'Shivajinagar Area'
                )
            )
        ),
        array(
            'city_name' => 'Pimpri-Chinchwad',
            'state' => 'Maharashtra',
            'coordinates' => '18.6298,73.7997',
            'is_allowed' => 1,
            'description' => 'Pimpri-Chinchwad Metropolitan Region',
            'max_distance_km' => 50,
            'created_by' => 1,
            'updated_by' => 1,
            'pincodes' => array(
                array(
                    'pincode' => '411044',
                    'area_name' => 'Pimpri',
                    'coordinates' => '18.6287,73.8007',
                    'is_allowed' => 1,
                    'is_enabled' => 1,
                    'description' => 'Pimpri Industrial Area'
                )
            )
        )
    );

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
            
            // Drop the tables (pincodes first due to foreign key)
            $sql = "DROP TABLE IF EXISTS allowed_pincodes";
            $this->db->getConnection()->exec($sql);
            
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
            state VARCHAR(100) NOT NULL,
            coordinates VARCHAR(50) NOT NULL COMMENT 'Format: lat,lng',
            is_allowed TINYINT(1) DEFAULT 1,
            description TEXT,
            max_distance_km INT DEFAULT 50,
            created_by INT,
            updated_by INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_city (city_name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $this->db->getConnection()->exec($sql);
        $this->logger->info('Created allowed_cities table');

        // Create allowed_pincodes table
        $sql = "CREATE TABLE IF NOT EXISTS allowed_pincodes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            pincode VARCHAR(10) NOT NULL,
            area_name VARCHAR(200) NOT NULL,
            city_id INT NOT NULL,
            coordinates VARCHAR(50) NOT NULL COMMENT 'Format: lat,lng',
            is_allowed TINYINT(1) DEFAULT 1,
            is_enabled TINYINT(1) DEFAULT 1,
            description TEXT,
            created_by INT,
            updated_by INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_pincode (pincode),
            FOREIGN KEY (city_id) REFERENCES allowed_cities(id) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $this->db->getConnection()->exec($sql);
        $this->logger->info('Created allowed_pincodes table');
    }

    /**
     * Insert default cities data
     */
    private function insertDefaultData() {
        $conn = $this->db->getConnection();

        foreach ($this->defaultCities as $city) {
            $sql = "INSERT INTO allowed_cities 
                   (city_name, state, coordinates, is_allowed, description, max_distance_km, created_by, updated_by) 
                   VALUES 
                   (:city_name, :state, :coordinates, :is_allowed, :description, :max_distance_km, :created_by, :updated_by)";

            $params = [
                ':city_name' => $city['city_name'],
                ':state' => $city['state'],
                ':coordinates' => $city['coordinates'],
                ':is_allowed' => $city['is_allowed'],
                ':description' => $city['description'],
                ':max_distance_km' => $city['max_distance_km'],
                ':created_by' => $city['created_by'],
                ':updated_by' => $city['updated_by']
            ];

            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            $cityId = $conn->lastInsertId();
            $this->logger->info("Inserted city: {$city['city_name']}");

            // If city has default pincodes, insert them
            if (isset($city['pincodes'])) {
                foreach ($city['pincodes'] as $pincode) {
                    $sql = "INSERT INTO allowed_pincodes 
                           (pincode, area_name, city_id, coordinates, is_allowed, is_enabled, description, created_by, updated_by) 
                           VALUES 
                           (:pincode, :area_name, :city_id, :coordinates, :is_allowed, :is_enabled, :description, :created_by, :updated_by)";

                    $params = [
                        ':pincode' => $pincode['pincode'],
                        ':area_name' => $pincode['area_name'],
                        ':city_id' => $cityId,
                        ':coordinates' => $pincode['coordinates'],
                        ':is_allowed' => $pincode['is_allowed'] ?? 1,
                        ':is_enabled' => $pincode['is_enabled'] ?? 1,
                        ':description' => $pincode['description'] ?? null,
                        ':created_by' => 1, // Default admin user
                        ':updated_by' => 1  // Default admin user
                    ];

                    $stmt = $conn->prepare($sql);
                    $stmt->execute($params);
                    $this->logger->info("Inserted pincode: {$pincode['pincode']} for city: {$city['city_name']}");
                }
            }
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