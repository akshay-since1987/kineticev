<?php
/**
 * Class to handle all allowed cities operations
 */
class AllowedCitiesHandler {
    private $conn;
    private $logger;

    public function __construct($config) {
        $this->logger = Logger::getInstance();
        $this->initializeDatabase($config);
    }

    /**
     * Safely escape an identifier (table or column name)
     */
    private function escapeIdentifier($identifier) {
        // List of allowed column names
        $allowedColumns = [
            'id', 'city_name', 'state', 'status', 'coordinates', 
            'max_distance_km', 'is_allowed', 'created_at', 'updated_at'
        ];
        
        // Only allow known column names
        if (!in_array($identifier, $allowedColumns)) {
            return 'city_name'; // Default to city_name if invalid column
        }
        
        return "`" . str_replace("`", "``", $identifier) . "`";
    }

    private function initializeDatabase($config) {
        try {
            $this->conn = new PDO(
                "mysql:host={$config['database']['host']};dbname={$config['database']['dbname']}",
                $config['database']['username'],
                $config['database']['password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
                ]
            );
        } catch (PDOException $e) {
            $this->logger->error('[ALLOWED_CITIES] Database connection failed', [
                'error' => $e->getMessage()
            ]);
            throw new Exception("Database connection failed");
        }
    }

    /**
     * Get allowed cities data for DataTables
     */
    public function getAllowedCitiesData($start = 0, $length = 10, $search = '', $order = []) {
        try {
            $params = [];
            $whereClause = '';
            
            // Search functionality
            if (!empty($search)) {
                $whereClause = " WHERE city_name LIKE ? OR state LIKE ? OR description LIKE ?";
                $params = ["%$search%", "%$search%", "%$search%"];
            }

            // Base query with joins
            $sql = "SELECT SQL_CALC_FOUND_ROWS ac.*, 
                    creator.username as creator_name,
                    updater.username as updater_name
                    FROM allowed_cities ac
                    LEFT JOIN admin_users creator ON ac.created_by = creator.id
                    LEFT JOIN admin_users updater ON ac.updated_by = updater.id" . 
                    $whereClause;
            
            // Add ordering
            if (!empty($order) && isset($order['column']) && isset($order['dir'])) {
                $sql .= " ORDER BY " . $this->escapeIdentifier($order['column']) . " " . 
                        (strtoupper($order['dir']) === 'DESC' ? 'DESC' : 'ASC');
            } else {
                $sql .= " ORDER BY city_name ASC";
            }
            
            // Add limit and offset
            if ($length > 0) {
                $sql .= " LIMIT " . (int)$length;
                if ($start > 0) {
                    $sql .= " OFFSET " . (int)$start;
                }
            }

            // Log the query for debugging
            $this->logger->debug('[ALLOWED_CITIES] Query', [
                'sql' => $sql,
                'params' => $params
            ]);

            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            $cities = $stmt->fetchAll();

            // Get filtered count
            $stmt = $this->conn->query("SELECT FOUND_ROWS() as total");
            $filteredTotal = (int)$stmt->fetch()['total'];

            // Get total count
            $stmt = $this->conn->query("SELECT COUNT(*) as total FROM allowed_cities");
            $totalRecords = (int)$stmt->fetch()['total'];

            return [
                'data' => $cities,
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $filteredTotal
            ];
        } catch (Exception $e) {
            $this->logger->error('[ALLOWED_CITIES] Get cities error', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get total count of cities
     */
    private function getTotalCitiesCount() {
        $stmt = $this->conn->query("SELECT COUNT(*) as total FROM allowed_cities");
        return $stmt->fetch()['total'];
    }

    /**
     * Get city details by ID
     */
    public function getCityDetails($id) {
        try {
            $sql = "SELECT ac.*, 
                    creator.username as created_by_name,
                    updater.username as updated_by_name
                    FROM allowed_cities ac
                    LEFT JOIN admin_users creator ON ac.created_by = creator.id
                    LEFT JOIN admin_users updater ON ac.updated_by = updater.id
                    WHERE ac.id = ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$id]);
            $city = $stmt->fetch();

            if (!$city) {
                return [
                    'success' => false,
                    'error' => 'City not found'
                ];
            }

            return [
                'success' => true,
                'data' => $city
            ];
        } catch (Exception $e) {
            $this->logger->error('[ALLOWED_CITIES] Get city details error', [
                'error' => $e->getMessage(),
                'id' => $id
            ]);
            throw $e;
        }
    }

    /**
     * Add new allowed city
     */
    public function addAllowedCity($data) {
        try {
            if (!isset($_SESSION['admin_id'])) {
                throw new Exception("Admin ID not found in session");
            }

            // Validate city data
            $errors = $this->validateCityData($data);
            
            // Check if city already exists
            $existingCity = $this->checkCityExists($data['city_name'], $data['state']);
            if ($existingCity) {
                $errors[] = "City already exists in this state";
            }
            
            if (!empty($errors)) {
                return [
                    'success' => false,
                    'errors' => $errors
                ];
            }

            $sql = "INSERT INTO allowed_cities (
                        city_name, state, coordinates, is_allowed,
                        description, max_distance_km, created_by, updated_by
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                $data['city_name'],
                $data['state'],
                $data['coordinates'],
                $data['is_allowed'] ?? 1,
                $data['description'] ?? null,
                $data['max_distance_km'] ?? 50,
                $_SESSION['admin_id'],
                $_SESSION['admin_id']
            ]);

            $this->logger->info('[ALLOWED_CITIES] City added', [
                'city_id' => $this->conn->lastInsertId(),
                'city_name' => $data['city_name'],
                'admin_id' => $_SESSION['admin_id']
            ]);

            return [
                'success' => true,
                'message' => 'City added successfully',
                'id' => $this->conn->lastInsertId()
            ];
        } catch (PDOException $e) {
            $this->logger->error('[ALLOWED_CITIES] Add city error', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            throw $e;
        }
    }

    /**
     * Update allowed city
     */
    public function updateAllowedCity($id, $data) {
        try {
            if (!isset($_SESSION['admin_id'])) {
                throw new Exception("Admin ID not found in session");
            }

            // Validate data
            $errors = $this->validateCityData($data, true);
            
            // Check if city exists (excluding current ID)
            if (isset($data['city_name']) && isset($data['state'])) {
                $existingCity = $this->checkCityExists($data['city_name'], $data['state'], $id);
                if ($existingCity) {
                    $errors[] = "City already exists in this state";
                }
            }

            if (!empty($errors)) {
                return [
                    'success' => false,
                    'errors' => $errors
                ];
            }

            $updateFields = [];
            $params = [];

            $allowedFields = [
                'city_name', 'state', 'coordinates', 'is_allowed',
                'description', 'max_distance_km'
            ];

            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updateFields[] = "$field = ?";
                    $params[] = $data[$field];
                }
            }

            if (empty($updateFields)) {
                throw new Exception("No fields to update");
            }

            // Add updated_by and id parameters
            $params[] = $_SESSION['admin_id'];
            $params[] = $id;

            $sql = "UPDATE allowed_cities SET " . 
                   implode(", ", $updateFields) . 
                   ", updated_by = ? WHERE id = ?";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);

            $this->logger->info('[ALLOWED_CITIES] City updated', [
                'city_id' => $id,
                'admin_id' => $_SESSION['admin_id']
            ]);

            return [
                'success' => true,
                'message' => 'City updated successfully'
            ];
        } catch (Exception $e) {
            $this->logger->error('[ALLOWED_CITIES] Update city error', [
                'error' => $e->getMessage(),
                'id' => $id,
                'data' => $data
            ]);
            throw $e;
        }
    }

    /**
     * Validate city data
     */
    private function validateCityData($data, $isUpdate = false) {
        $errors = [];

        // Required fields
        $requiredFields = ['city_name', 'state', 'coordinates'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                $errors[] = ucfirst(str_replace('_', ' ', $field)) . " is required";
            }
        }

        // Validate coordinates format (latitude,longitude)
        if (!empty($data['coordinates'])) {
            if (!preg_match('/^-?\d+(\.\d+)?,-?\d+(\.\d+)?$/', $data['coordinates'])) {
                $errors[] = "Coordinates must be in format: latitude,longitude";
            } else {
                list($lat, $lng) = explode(',', $data['coordinates']);
                if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
                    $errors[] = "Invalid coordinates range";
                }
            }
        }

        // Validate max_distance_km
        if (isset($data['max_distance_km'])) {
            if (!is_numeric($data['max_distance_km']) || $data['max_distance_km'] < 1) {
                $errors[] = "Maximum distance must be a positive number";
            }
        }

        return $errors;
    }

    /**
     * Check if city already exists
     */
    private function checkCityExists($cityName, $state, $excludeId = null) {
        try {
            $sql = "SELECT id FROM allowed_cities WHERE city_name = ? AND state = ?";
            $params = [$cityName, $state];

            if ($excludeId) {
                $sql .= " AND id != ?";
                $params[] = $excludeId;
            }

            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch();
        } catch (PDOException $e) {
            $this->logger->error('[ALLOWED_CITIES] Check city exists error', [
                'error' => $e->getMessage(),
                'city' => $cityName,
                'state' => $state
            ]);
            throw $e;
        }
    }

    /**
     * Get allowed cities by state
     */
    public function getAllowedCitiesByState($state) {
        try {
            $sql = "SELECT id, city_name, coordinates, max_distance_km 
                    FROM allowed_cities 
                    WHERE state = ? AND is_allowed = 1 
                    ORDER BY city_name";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$state]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            $this->logger->error('[ALLOWED_CITIES] Get cities by state error', [
                'error' => $e->getMessage(),
                'state' => $state
            ]);
            return [];
        }
    }

    /**
     * Get all unique states
     */
    public function getAllowedStates() {
        try {
            $sql = "SELECT DISTINCT state FROM allowed_cities 
                    WHERE is_allowed = 1 
                    ORDER BY state";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (Exception $e) {
            $this->logger->error('[ALLOWED_CITIES] Get states error', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Check if coordinates are within allowed city range
     */
    public function isLocationAllowed($latitude, $longitude) {
        try {
            $sql = "SELECT city_name, coordinates, max_distance_km 
                    FROM allowed_cities 
                    WHERE is_allowed = 1";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            $cities = $stmt->fetchAll();

            foreach ($cities as $city) {
                list($cityLat, $cityLng) = explode(',', $city['coordinates']);
                $distance = $this->calculateDistance(
                    $latitude, $longitude,
                    floatval($cityLat), floatval($cityLng)
                );
                if ($distance <= $city['max_distance_km']) {
                    return [
                        'allowed' => true,
                        'city' => $city['city_name'],
                        'distance' => round($distance, 2)
                    ];
                }
            }
            return ['allowed' => false];
        } catch (Exception $e) {
            $this->logger->error('[ALLOWED_CITIES] Location check error', [
                'error' => $e->getMessage(),
                'coordinates' => "$latitude,$longitude"
            ]);
            return ['allowed' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Calculate distance between two points using Haversine formula
     */
    private function calculateDistance($lat1, $lon1, $lat2, $lon2) {
        $earthRadius = 6371; // Earth's radius in kilometers

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat/2) * sin($dLat/2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon/2) * sin($dLon/2);

        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        return $earthRadius * $c;
    }

    /**
     * Delete an allowed city
     */
    public function deleteAllowedCity($id) {
        try {
            if (!isset($_SESSION['admin_id'])) {
                throw new Exception("Admin ID not found in session");
            }

            // Check if city exists
            $city = $this->getCityDetails($id);
            if (!$city['success']) {
                return false;
            }

            $sql = "DELETE FROM allowed_cities WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $success = $stmt->execute([$id]);

            if ($success) {
                $this->logger->info('[ALLOWED_CITIES] City deleted', [
                    'city_id' => $id,
                    'admin_id' => $_SESSION['admin_id']
                ]);
            }

            return $success;
        } catch (Exception $e) {
            $this->logger->error('[ALLOWED_CITIES] Delete city error', [
                'error' => $e->getMessage(),
                'id' => $id
            ]);
            throw $e;
        }
    }
}