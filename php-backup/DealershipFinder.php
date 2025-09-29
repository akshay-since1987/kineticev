<?php

// Prevent direct access
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    http_response_code(404);
    exit();
}

require_once 'DatabaseUtils.php';

/**
 * DealershipFinder Class
 * Handles finding dealerships based on PIN code and radius
 */
class DealershipFinder {
    private $pdo;
    private $logger;

    /**
     * Constructor - sets up database connection
     */
    public function __construct() {
        try {
            // Include config from current directory (php directory)
            $configPath = __DIR__ . '/config.php';
            $config = include $configPath;
            
            if (!isset($config['database'])) {
                throw new Exception("Database configuration not found");
            }
            
            $dbConfig = $config['database'];
            
            // Create DSN string for database connection
            $host = $dbConfig['host'];
            $dbname = $dbConfig['dbname'];
            $charset = $dbConfig['charset'] ?? 'utf8mb4';
            
            $dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
            
            // Set up PDO connection with IST timezone
            $this->pdo = DatabaseUtils::createConnection(
                $dsn, 
                $dbConfig['username'], 
                $dbConfig['password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
            
            // Set up logger if available
            if (class_exists('Logger')) {
                $loggerPath = dirname(__DIR__) . '/Logger.php';
                if (file_exists($loggerPath)) {
                    include_once $loggerPath;
                    $this->logger = Logger::getInstance();
                }
            }
        } catch (PDOException $e) {
            // Log the error
            error_log("Database connection error: " . $e->getMessage());
            throw new Exception("Database connection failed. Please try again later.");
        } catch (Exception $e) {
            // Log the error
            error_log("DealershipFinder initialization error: " . $e->getMessage());
            throw $e; // Re-throw the exception
        }
    }

    /**
     * Find dealerships by PIN code with distance calculation
     * 
     * @param string $pincode The PIN code to search for
     * @param int $radius Search radius in kilometers
     * @return array Array of dealerships
     */
    public function findDealershipsByPincode($pincode, $radius = 50) {
        try {
            // First, try to find dealerships with the exact PIN code
            $stmt = $this->pdo->prepare("SELECT * FROM dealerships WHERE pincode = :pincode");
            $stmt->bindParam(':pincode', $pincode, PDO::PARAM_STR);
            $stmt->execute();
            $exactMatches = $stmt->fetchAll();
            
            if (count($exactMatches) > 0) {
                return $exactMatches;
            }
            
            // If no exact matches, use PIN code to get approximate location via API
            $coordinates = $this->getPincodeCoordinates($pincode);
            
            if (!$coordinates) {
                return [];
            }
            
            // Now find dealerships within the given radius using the Haversine formula
            $latitude = $coordinates['latitude'];
            $longitude = $coordinates['longitude'];
            
            $stmt = $this->pdo->prepare("SELECT *, 
                (6371 * acos(
                    cos(radians(:lat1)) * 
                    cos(radians(latitude)) * 
                    cos(radians(longitude) - radians(:long)) + 
                    sin(radians(:lat2)) * 
                    sin(radians(latitude))
                )) AS distance 
                FROM dealerships 
                HAVING distance < :radius 
                ORDER BY distance");
                
            $stmt->bindParam(':lat1', $latitude, PDO::PARAM_STR);
            $stmt->bindParam(':long', $longitude, PDO::PARAM_STR);
            $stmt->bindParam(':lat2', $latitude, PDO::PARAM_STR);
            $stmt->bindParam(':radius', $radius, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Database query error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get coordinates (latitude, longitude) for a PIN code
     * Uses a free API or falls back to a stored mapping
     * 
     * @param string $pincode The PIN code to look up
     * @return array|null Array with "latitude" and "longitude" or null if not found
     */
    public function getPincodeCoordinates($pincode) {
        // Method 1: Try to use a free geocoding API
        $apiUrl = "https://api.postalpincode.in/pincode/" . $pincode;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For development only
        curl_setopt($ch, CURLOPT_TIMEOUT, 5); // 5-second timeout
        $response = curl_exec($ch);
        curl_close($ch);
        
        if ($response) {
            $data = json_decode($response, true);
            
            if (isset($data[0]['Status']) && $data[0]['Status'] === 'Success' && !empty($data[0]['PostOffice'])) {
                // Indian PIN code API returns multiple post offices - use the first one
                $postOffice = $data[0]['PostOffice'][0];
                
                if (isset($postOffice['Latitude']) && isset($postOffice['Longitude'])) {
                    return [
                        'latitude' => (float)$postOffice['Latitude'],
                        'longitude' => (float)$postOffice['Longitude']
                    ];
                }
            }
        }
        
        // Method 2: Fallback to hardcoded mapping for common PIN codes
        $pincodeMap = [
            '411001' => ['latitude' => 18.5289, 'longitude' => 73.8567], // Pune - M.G. Road
            '411004' => ['latitude' => 18.5196, 'longitude' => 73.8427], // Pune - Deccan Gymkhana
            '411005' => ['latitude' => 18.5314, 'longitude' => 73.8446], // Pune - FC Road
            '411009' => ['latitude' => 18.4634, 'longitude' => 73.8565], // Pune - Satara Road
            '411014' => ['latitude' => 18.5679, 'longitude' => 73.9143], // Pune - Viman Nagar
            '411028' => ['latitude' => 18.5158, 'longitude' => 73.9272], // Pune - Magarpatta
            '411045' => ['latitude' => 18.5590, 'longitude' => 73.7868], // Pune - Baner Road
            '411057' => ['latitude' => 18.5908, 'longitude' => 73.7576], // Pune - Wakad
            '400001' => ['latitude' => 18.9256, 'longitude' => 72.8245], // Mumbai
            '400053' => ['latitude' => 19.1190, 'longitude' => 72.8470], // Mumbai Andheri
            '560001' => ['latitude' => 12.9716, 'longitude' => 77.5946], // Bangalore
            '700016' => ['latitude' => 22.5476, 'longitude' => 88.3476], // Kolkata
            '600002' => ['latitude' => 13.0827, 'longitude' => 80.2707], // Chennai
            '110001' => ['latitude' => 28.6139, 'longitude' => 77.2090]  // Delhi
        ];
        
        return isset($pincodeMap[$pincode]) ? $pincodeMap[$pincode] : null;
    }
    
    /**
     * Create dealerships table if it doesn't exist
     * 
     * @return bool True on success, false on failure
     */
    public function createDealershipsTable() {
        try {
            $sql = "CREATE TABLE IF NOT EXISTS dealerships (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                address TEXT NOT NULL,
                city VARCHAR(100) NOT NULL,
                state VARCHAR(100) NOT NULL,
                pincode VARCHAR(20) NOT NULL,
                phone VARCHAR(50),
                email VARCHAR(100),
                latitude DECIMAL(10, 8) NULL,
                longitude DECIMAL(11, 8) NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )";
            
            $this->pdo->exec($sql);
            
            // Check if the table is empty and insert sample data if needed
            $countStmt = $this->pdo->query("SELECT COUNT(*) as count FROM dealerships");
            $result = $countStmt->fetch();
            
            return true;
        } catch (PDOException $e) {
            error_log("Error creating dealerships table: " . $e->getMessage());
            return false;
        }
    }
    
    
    /**
     * Insert a dealership into the database
     * 
     * @param array $dealershipData Associative array with dealership data
     * @return bool True on success, false on failure
     */
    public function insertDealership($dealershipData) {
        try {
            $sql = "INSERT INTO dealerships 
                   (name, address, city, state, pincode, phone, email, latitude, longitude) 
                   VALUES 
                   (:name, :address, :city, :state, :pincode, :phone, :email, :latitude, :longitude)";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($dealershipData);
            
            return true;
        } catch (PDOException $e) {
            error_log("Error inserting dealership: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get all dealerships for map display
     * 
     * @return array An array of all dealerships
     */
    public function getAllDealerships() {
        try {
            $sql = "SELECT * FROM dealerships";
            error_log("Executing SQL: " . $sql);
            
            $stmt = $this->pdo->query($sql);
            $results = $stmt->fetchAll();
            
            error_log("Found " . count($results) . " dealerships in database");
            
            return $results;
        } catch (PDOException $e) {
            error_log("Error fetching dealerships: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get dealership by ID
     * 
     * @param int $id Dealership ID
     * @return array|null Dealership data or null if not found
     */
    public function getDealershipById($id) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM dealerships WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            $result = $stmt->fetch();
            return $result ?: null;
        } catch (PDOException $e) {
            error_log("Error fetching dealership by ID: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Search dealerships by city or state
     * 
     * @param string $term Search term
     * @return array Array of matching dealerships
     */
    public function searchDealerships($term) {
        try {
            $searchTerm = "%{$term}%";
            
            $stmt = $this->pdo->prepare("SELECT * FROM dealerships 
                WHERE city LIKE :term 
                OR state LIKE :term 
                OR name LIKE :term
                ORDER BY name ASC");
                
            $stmt->bindParam(':term', $searchTerm, PDO::PARAM_STR);
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error searching dealerships: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get dealership variables for direct use in PHP templates
     * 
     * @param int|null $dealershipId Optional dealership ID to get specific dealership
     * @return array Array of variables for template use
     */
    public function getDealershipVariables($dealershipId = null) {
        try {
            $variables = [];
            
            if ($dealershipId) {
                $dealership = $this->getDealershipById($dealershipId);
                if ($dealership) {
                    $variables['dealership'] = $dealership;
                    $variables['dealership_name'] = $dealership['name'];
                    $variables['dealership_address'] = $dealership['address'];
                    $variables['dealership_city'] = $dealership['city'];
                    $variables['dealership_state'] = $dealership['state'];
                    $variables['dealership_pincode'] = $dealership['pincode'];
                    $variables['dealership_phone'] = $dealership['phone'] ?? '';
                    $variables['dealership_email'] = $dealership['email'] ?? '';
                    $variables['dealership_latitude'] = $dealership['latitude'];
                    $variables['dealership_longitude'] = $dealership['longitude'];
                }
            }
            
            $allDealerships = $this->getAllDealerships();
            $variables['dealerships'] = $allDealerships;
            $variables['dealerships_count'] = count($allDealerships);
            $variables['dealerships_json'] = json_encode($allDealerships);
            
            // We don't need to group by state anymore since we're not displaying the listing
            $variables['dealerships_by_state'] = [];
            
            return $variables;
        } catch (Exception $e) {
            error_log("Error getting dealership variables: " . $e->getMessage());
            return [
                'dealerships' => [],
                'dealerships_count' => 0,
                'dealerships_json' => '[]',
                'dealerships_by_state' => []
            ];
        }
    }
}
