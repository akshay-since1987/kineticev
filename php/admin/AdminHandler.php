<?php

// Prevent direct access
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    http_response_code(404);
    exit();
}

require_once '../Logger.php';
require_once '../DatabaseMigration.php';
require_once '../DatabaseUtils.php';

class AdminHandler
{
    private $conn;
    private $logger;
    private $config;

    public function __construct($config)
    {
        $this->config = $config;
        $this->logger = Logger::getInstance();
        $this->connectDatabase();
        $this->createAdminTable();
    }

    private function connectDatabase()
    {
        try {
            // Validate config data
            if (!is_array($this->config)) {
                throw new Exception("Invalid configuration: Configuration is not an array");
            }
            
            if (!isset($this->config['database'])) {
                throw new Exception("Invalid configuration: Database settings are missing");
            }
            
            $dbConfig = $this->config['database'];
            
            // Validate database settings
            if (!isset($dbConfig['host']) || !isset($dbConfig['dbname']) || !isset($dbConfig['username'])) {
                throw new Exception("Invalid configuration: Database connection parameters are incomplete");
            }
            
            // Connect to database
            $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['dbname']}";
            if (isset($dbConfig['charset'])) {
                $dsn .= ";charset={$dbConfig['charset']}";
            }
            
            $password = $dbConfig['password'] ?? ''; // Use empty string if password is not set
            
            $this->conn = DatabaseUtils::createConnection(
                $dsn, 
                $dbConfig['username'], 
                $password, 
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ],
                $this->logger
            );
        } catch (PDOException $e) {
            $this->logger->error('[ADMIN_HANDLER] Database connection failed', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        } catch (Exception $e) {
            $this->logger->error('[ADMIN_HANDLER] Configuration error', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    private function createAdminTable()
    {
        try {
            $sql = "CREATE TABLE IF NOT EXISTS admin_users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) NOT NULL UNIQUE,
                password_hash VARCHAR(255) NOT NULL,
                email VARCHAR(100),
                full_name VARCHAR(100),
                role ENUM('super_admin', 'admin', 'viewer') DEFAULT 'admin',
                is_active TINYINT(1) DEFAULT 1,
                last_login DATETIME NULL,
                last_login_ip VARCHAR(45) NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )";
            $this->conn->exec($sql);

            // Create default admin user if none exists
            $checkSql = "SELECT COUNT(*) FROM admin_users";
            $stmt = $this->conn->query($checkSql);
            if ($stmt->fetchColumn() == 0) {
                $this->createDefaultAdmin();
            }
            
            // Run UUID migration for admin_users table
            $this->runUuidMigration();
            
        } catch (PDOException $e) {
            $this->logger->error('[ADMIN_HANDLER] Failed to create admin table', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Run UUID migration for admin_users table
     */
    private function runUuidMigration()
    {
        try {
            $this->logger->info('[UUID_MIGRATION] Starting UUID migration for admin_users');
            
            $migration = new DatabaseMigration($this->conn);
            
            // Add UUID to admin_users table
            $migration->addUuidColumn('admin_users', 'uuid');
            
            $this->logger->info('[UUID_MIGRATION] UUID migration completed for admin_users');
        } catch (Exception $e) {
            $this->logger->error('[UUID_MIGRATION] UUID migration failed for admin_users', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            // Don't throw - allow system to continue working
        }
    }

    private function createDefaultAdmin()
    {
        $defaultUsername = 'kineticadmin';
        $defaultPassword = 'Kinetic@2025!';
        $passwordHash = password_hash($defaultPassword, PASSWORD_DEFAULT);
        
        // Generate UUID for new admin user
        $uuid = DatabaseMigration::generateUuid();

        $sql = "INSERT INTO admin_users (uuid, username, password_hash, email, full_name, role) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            $uuid,
            $defaultUsername,
            $passwordHash,
            'admin@kineticev.in',
            'KineticEV Administrator',
            'super_admin'
        ]);

        $this->logger->info('[ADMIN_HANDLER] Default admin user created', [
            'uuid' => $uuid,
            'username' => $defaultUsername,
            'default_password' => $defaultPassword
        ]);
    }

    public function authenticateAdmin($username, $password)
    {
        try {
            $sql = "SELECT id, username, password_hash, role, is_active FROM admin_users 
                    WHERE username = ? AND is_active = 1";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$username]);
            $admin = $stmt->fetch();

            if ($admin && password_verify($password, $admin['password_hash'])) {
                // Update last login
                $updateSql = "UPDATE admin_users SET last_login = NOW(), last_login_ip = ? WHERE id = ?";
                $updateStmt = $this->conn->prepare($updateSql);
                $updateStmt->execute([$_SERVER['REMOTE_ADDR'] ?? 'unknown', $admin['id']]);

                // Store admin info in session
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['admin_role'] = $admin['role'];

                return true;
            }
            return false;
        } catch (PDOException $e) {
            $this->logger->error('[ADMIN_HANDLER] Authentication error', [
                'error' => $e->getMessage(),
                'username' => $username
            ]);
            return false;
        }
    }

    public function getAllTables()
    {
        try {
            $sql = "SHOW TABLES";
            $stmt = $this->conn->query($sql);
            $tables = [];
            while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
                if ($row[0] !== 'admin_users') { // Exclude admin table from public view
                    $tables[] = $row[0];
                }
            }
            return $tables;
        } catch (PDOException $e) {
            $this->logger->error('[ADMIN_HANDLER] Failed to get tables', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    public function getTableData($tableName, $page = 1, $perPage = 25, $search = '', $orderBy = null, $orderDir = 'DESC')
    {
        try {
            // Validate table name to prevent SQL injection
            $allowedTables = $this->getAllTables();
            if (!in_array($tableName, $allowedTables)) {
                throw new Exception("Invalid table name");
            }

            // Get table columns
            $columnsSql = "SHOW COLUMNS FROM `$tableName`";
            $columnsStmt = $this->conn->query($columnsSql);
            $columns = [];
            $primaryKey = null;
            while ($column = $columnsStmt->fetch()) {
                $columns[] = $column['Field'];
                if ($column['Key'] === 'PRI') {
                    $primaryKey = $column['Field'];
                }
            }

            // Set default order by primary key or first column
            if (!$orderBy || !in_array($orderBy, $columns)) {
                $orderBy = $primaryKey ?: $columns[0];
            }

            // Build search conditions
            $searchCondition = '';
            $searchParams = [];
            if (!empty($search)) {
                $searchClauses = [];
                foreach ($columns as $column) {
                    $searchClauses[] = "`$column` LIKE ?";
                    $searchParams[] = "%$search%";
                }
                $searchCondition = "WHERE " . implode(" OR ", $searchClauses);
            }

            // Get total count
            $countSql = "SELECT COUNT(*) FROM `$tableName` $searchCondition";
            $countStmt = $this->conn->prepare($countSql);
            $countStmt->execute($searchParams);
            $totalRecords = $countStmt->fetchColumn();

            // Calculate pagination
            $offset = ($page - 1) * $perPage;
            $totalPages = ceil($totalRecords / $perPage);

            // Get data
            $dataSql = "SELECT * FROM `$tableName` $searchCondition 
                       ORDER BY `$orderBy` $orderDir 
                       LIMIT $perPage OFFSET $offset";
            $dataStmt = $this->conn->prepare($dataSql);
            $dataStmt->execute($searchParams);
            $data = $dataStmt->fetchAll();

            return [
                'data' => $data,
                'columns' => $columns,
                'totalRecords' => $totalRecords,
                'totalPages' => $totalPages,
                'currentPage' => $page,
                'perPage' => $perPage,
                'search' => $search,
                'orderBy' => $orderBy,
                'orderDir' => $orderDir
            ];
        } catch (Exception $e) {
            $this->logger->error('[ADMIN_HANDLER] Failed to get table data', [
                'error' => $e->getMessage(),
                'table' => $tableName
            ]);
            return [
                'data' => [],
                'columns' => [],
                'totalRecords' => 0,
                'totalPages' => 0,
                'currentPage' => 1,
                'perPage' => $perPage,
                'error' => $e->getMessage()
            ];
        }
    }

    public function getTableDataWithFilters($tableName, $page = 1, $perPage = 25, $search = '', $orderBy = null, $orderDir = 'DESC', $filters = [], $dateRange = '', $startDate = '', $endDate = '')
    {
        try {
            // Validate table name to prevent SQL injection
            $allowedTables = $this->getAllTables();
            if (!in_array($tableName, $allowedTables)) {
                throw new Exception("Invalid table name");
            }

            // Get table columns
            $columnsSql = "SHOW COLUMNS FROM `$tableName`";
            $columnsStmt = $this->conn->query($columnsSql);
            $columns = [];
            $primaryKey = null;
            while ($column = $columnsStmt->fetch()) {
                $columns[] = $column['Field'];
                if ($column['Key'] === 'PRI') {
                    $primaryKey = $column['Field'];
                }
            }

            // Set default order by primary key or first column
            if (!$orderBy || !in_array($orderBy, $columns)) {
                $orderBy = $primaryKey ?: $columns[0];
            }

            // Build WHERE conditions
            $whereConditions = [];
            $searchParams = [];

            // Global search condition
            if (!empty($search)) {
                $searchClauses = [];
                foreach ($columns as $column) {
                    $searchClauses[] = "`$column` LIKE ?";
                    $searchParams[] = "%$search%";
                }
                $whereConditions[] = "(" . implode(" OR ", $searchClauses) . ")";
            }

            // Date range filtering
            if (!empty($dateRange) || (!empty($startDate) && !empty($endDate))) {
                $dateColumn = $this->getDateColumn($tableName);
                if ($dateColumn) {
                    if (!empty($dateRange)) {
                        $dateCondition = $this->buildDateRangeCondition($dateColumn, $dateRange);
                        if ($dateCondition) {
                            $whereConditions[] = $dateCondition;
                        }
                    } elseif (!empty($startDate) && !empty($endDate)) {
                        $whereConditions[] = "`$dateColumn` BETWEEN ? AND ?";
                        $searchParams[] = $startDate . ' 00:00:00';
                        $searchParams[] = $endDate . ' 23:59:59';
                    }
                }
            }

            // Column-specific filters
            foreach ($filters as $column => $value) {
                if (in_array($column, $columns) && !empty($value)) {
                    if (strpos($value, '%') !== false) {
                        $whereConditions[] = "`$column` LIKE ?";
                        $searchParams[] = $value;
                    } else {
                        $whereConditions[] = "`$column` = ?";
                        $searchParams[] = $value;
                    }
                }
            }

            // Combine WHERE conditions
            $whereClause = empty($whereConditions) ? '' : 'WHERE ' . implode(' AND ', $whereConditions);

            // Get total count
            $countSql = "SELECT COUNT(*) FROM `$tableName` $whereClause";
            $countStmt = $this->conn->prepare($countSql);
            $countStmt->execute($searchParams);
            $totalRecords = $countStmt->fetchColumn();

            // Calculate pagination
            $offset = ($page - 1) * $perPage;
            $totalPages = ceil($totalRecords / $perPage);

            // Get data
            $dataSql = "SELECT * FROM `$tableName` $whereClause 
                       ORDER BY `$orderBy` $orderDir 
                       LIMIT $perPage OFFSET $offset";
            $dataStmt = $this->conn->prepare($dataSql);
            $dataStmt->execute($searchParams);
            $data = $dataStmt->fetchAll();

            return [
                'data' => $data,
                'columns' => $columns,
                'totalRecords' => $totalRecords,
                'totalPages' => $totalPages,
                'currentPage' => $page,
                'perPage' => $perPage,
                'search' => $search,
                'orderBy' => $orderBy,
                'orderDir' => $orderDir,
                'filters' => $filters,
                'dateRange' => $dateRange,
                'appliedFilters' => count($filters) + (!empty($search) ? 1 : 0) + (!empty($dateRange) || (!empty($startDate) && !empty($endDate)) ? 1 : 0)
            ];
        } catch (Exception $e) {
            $this->logger->error('[ADMIN_HANDLER] Failed to get filtered table data', [
                'error' => $e->getMessage(),
                'table' => $tableName,
                'filters' => $filters
            ]);
            return [
                'data' => [],
                'columns' => [],
                'totalRecords' => 0,
                'totalPages' => 0,
                'currentPage' => 1,
                'perPage' => $perPage,
                'error' => $e->getMessage()
            ];
        }
    }

    public function getFilterOptions($tableName)
    {
        try {
            $allowedTables = $this->getAllTables();
            if (!in_array($tableName, $allowedTables)) {
                throw new Exception("Invalid table name");
            }

            $options = [];

            // Get columns that should have dropdown filters
            $filterColumns = $this->getFilterableColumns($tableName);

            foreach ($filterColumns as $column) {
                try {
                    $sql = "SELECT DISTINCT `$column` FROM `$tableName` WHERE `$column` IS NOT NULL AND `$column` != '' ORDER BY `$column`";
                    $stmt = $this->conn->query($sql);
                    $values = [];
                    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
                        $values[] = $row[0];
                    }
                    $options[$column] = $values;
                } catch (PDOException $e) {
                    // Column might not exist, log and skip
                    $this->logger->warning('[ADMIN_HANDLER] Filter column not found', [
                        'table' => $tableName,
                        'column' => $column,
                        'error' => $e->getMessage()
                    ]);
                    continue;
                }
            }

            return $options;
        } catch (Exception $e) {
            $this->logger->error('[ADMIN_HANDLER] Failed to get filter options', [
                'error' => $e->getMessage(),
                'table' => $tableName
            ]);
            return [];
        }
    }

    private function getFilterableColumns($tableName)
    {
        // Define which columns should have dropdown filters for each table
        $filterableColumns = [
            'transactions' => ['status'],
            'test_drives' => [], // Disable filters for now to avoid errors
            'contacts' => [], // Disable filters for now to avoid errors  
            'dealerships' => ['city', 'state']
        ];

        $requestedColumns = $filterableColumns[$tableName] ?? [];
        
        // Verify columns exist before returning them
        $existingColumns = $this->getTableColumns($tableName);
        $validColumns = [];
        
        foreach ($requestedColumns as $column) {
            if (in_array($column, $existingColumns)) {
                $validColumns[] = $column;
            } else {
                $this->logger->warning('[ADMIN_HANDLER] Filterable column does not exist', [
                    'table' => $tableName,
                    'column' => $column
                ]);
            }
        }
        
        return $validColumns;
    }
    
    private function getTableColumns($tableName)
    {
        try {
            $sql = "SHOW COLUMNS FROM `$tableName`";
            $stmt = $this->conn->query($sql);
            $columns = [];
            while ($row = $stmt->fetch()) {
                $columns[] = $row['Field'];
            }
            return $columns;
        } catch (Exception $e) {
            $this->logger->error('[ADMIN_HANDLER] Failed to get table columns', [
                'table' => $tableName,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    private function getDateColumn($tableName)
    {
        // Try to find a suitable date column for filtering
        $dateColumns = ['created_at', 'updated_at', 'date', 'timestamp'];
        
        $columnsSql = "SHOW COLUMNS FROM `$tableName`";
        $columnsStmt = $this->conn->query($columnsSql);
        $availableColumns = [];
        
        while ($column = $columnsStmt->fetch()) {
            $availableColumns[] = $column['Field'];
        }

        foreach ($dateColumns as $dateCol) {
            if (in_array($dateCol, $availableColumns)) {
                return $dateCol;
            }
        }

        return null;
    }

    private function buildDateRangeCondition($dateColumn, $dateRange)
    {
        switch ($dateRange) {
            case 'today':
                return "`$dateColumn` >= CURDATE() AND `$dateColumn` < CURDATE() + INTERVAL 1 DAY";
            case 'yesterday':
                return "`$dateColumn` >= CURDATE() - INTERVAL 1 DAY AND `$dateColumn` < CURDATE()";
            case 'last7days':
                return "`$dateColumn` >= CURDATE() - INTERVAL 7 DAY";
            case 'last30days':
                return "`$dateColumn` >= CURDATE() - INTERVAL 30 DAY";
            case 'thismonth':
                return "`$dateColumn` >= DATE_FORMAT(CURDATE(), '%Y-%m-01')";
            case 'lastmonth':
                return "`$dateColumn` >= DATE_FORMAT(CURDATE() - INTERVAL 1 MONTH, '%Y-%m-01') AND `$dateColumn` < DATE_FORMAT(CURDATE(), '%Y-%m-01')";
            default:
                return null;
        }
    }

    public function getDashboardStats()
    {
        try {
            $stats = [];

            // Total transactions
            $stmt = $this->conn->query("SELECT COUNT(*) FROM transactions");
            $stats['total_transactions'] = $stmt->fetchColumn();

            // Total test drives
            $stmt = $this->conn->query("SELECT COUNT(*) FROM test_drives");
            $stats['total_test_drives'] = $stmt->fetchColumn();

            // Total contacts
            $stmt = $this->conn->query("SELECT COUNT(*) FROM contacts");
            $stats['total_contacts'] = $stmt->fetchColumn();

            // Total revenue - case insensitive check for success status
            $stmt = $this->conn->query("SELECT SUM(amount) FROM transactions WHERE LOWER(status) = 'success' OR LOWER(status) = 'completed' OR LOWER(status) = 'paid'");
            $stats['total_revenue'] = $stmt->fetchColumn() ?: 0;

            // Recent activity
            $recentSql = "
                (SELECT 'transaction' as type, transaction_id as reference, firstname as name, created_at 
                 FROM transactions ORDER BY created_at DESC LIMIT 5)
                UNION ALL
                (SELECT 'test_drive' as type, CONCAT('TD-', id) as reference, full_name as name, created_at 
                 FROM test_drives ORDER BY created_at DESC LIMIT 5)
                UNION ALL
                (SELECT 'contact' as type, CONCAT('CT-', id) as reference, full_name as name, created_at 
                 FROM contacts ORDER BY created_at DESC LIMIT 5)
                ORDER BY created_at DESC LIMIT 10
            ";
            $stmt = $this->conn->query($recentSql);
            $stats['recent_activity'] = $stmt->fetchAll();

            return $stats;
        } catch (Exception $e) {
            $this->logger->error('[ADMIN_HANDLER] Failed to get dashboard stats', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    public function getAnalyticsData()
    {
        try {
            $analytics = [];

            // Transaction status distribution
            $statusSql = "SELECT status, COUNT(*) as count FROM transactions GROUP BY status";
            $stmt = $this->conn->query($statusSql);
            $analytics['status_distribution'] = $stmt->fetchAll();

            // Monthly revenue with zero values for months without data
            $revenueSql = "WITH RECURSIVE months AS (
                SELECT DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 11 MONTH), '%Y-%m') as month
                UNION ALL
                SELECT DATE_FORMAT(DATE_ADD(STR_TO_DATE(CONCAT(month, '-01'), '%Y-%m-%d'), INTERVAL 1 MONTH), '%Y-%m')
                FROM months 
                WHERE month < DATE_FORMAT(NOW(), '%Y-%m')
            )
            SELECT 
                m.month,
                COALESCE(SUM(t.amount), 0) as revenue
            FROM months m
            LEFT JOIN transactions t ON DATE_FORMAT(t.created_at, '%Y-%m') = m.month 
                AND (LOWER(t.status) = 'success' OR LOWER(t.status) = 'completed' OR LOWER(t.status) = 'paid')
            GROUP BY m.month
            ORDER BY m.month ASC";
            $stmt = $this->conn->query($revenueSql);
            $analytics['monthly_revenue'] = $stmt->fetchAll();

            // Daily activity counts
            $activitySql = "SELECT 
                DATE(created_at) as date,
                COUNT(*) as count,
                'transactions' as type
                FROM transactions 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY DATE(created_at)
                UNION ALL
                SELECT 
                DATE(created_at) as date,
                COUNT(*) as count,
                'test_drives' as type
                FROM test_drives 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY DATE(created_at)
                UNION ALL
                SELECT 
                DATE(created_at) as date,
                COUNT(*) as count,
                'contacts' as type
                FROM contacts 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY DATE(created_at)
                ORDER BY date DESC";
            $stmt = $this->conn->query($activitySql);
            $analytics['daily_activity'] = $stmt->fetchAll();

            return $analytics;
        } catch (Exception $e) {
            $this->logger->error('[ADMIN_HANDLER] Failed to get analytics data', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    public function getLogContent($logFile, $lines = 100)
    {
        try {
            $logPath = dirname(__DIR__) . "/logs/$logFile";
            if (!file_exists($logPath)) {
                return "Log file not found: $logFile";
            }

            // Check file size to prevent memory issues
            $fileSize = filesize($logPath);
            if ($fileSize > 50 * 1024 * 1024) { // 50MB
                return "Log file too large to display. File size: " . round($fileSize / 1024 / 1024, 2) . "MB\nPlease download the file for viewing.";
            }

            // For large files, use more efficient reading
            if ($fileSize > 5 * 1024 * 1024) { // 5MB
                return $this->readLogFileEfficiently($logPath, $lines);
            }

            $content = file_get_contents($logPath);
            if ($content === false) {
                return "Error: Could not read log file";
            }

            $logLines = explode("\n", $content);
            $logLines = array_filter($logLines, function($line) {
                return trim($line) !== '';
            });
            $logLines = array_reverse($logLines); // Show newest first
            $logLines = array_slice($logLines, 0, $lines);
            
            return implode("\n", $logLines);
        } catch (Exception $e) {
            return "Error reading log file: " . $e->getMessage();
        }
    }

    private function readLogFileEfficiently($logPath, $lines = 100)
    {
        try {
            $file = new SplFileObject($logPath);
            $file->seek(PHP_INT_MAX);
            $totalLines = $file->key();
            
            $startLine = max(0, $totalLines - $lines);
            $logLines = [];
            
            $file->seek($startLine);
            while (!$file->eof() && count($logLines) < $lines) {
                $line = trim($file->fgets());
                if (!empty($line)) {
                    $logLines[] = $line;
                }
            }
            
            return implode("\n", array_reverse($logLines));
        } catch (Exception $e) {
            return "Error reading large log file: " . $e->getMessage();
        }
    }

    public function exportTableData($tableName, $format = 'csv')
    {
        try {
            $allowedTables = $this->getAllTables();
            if (!in_array($tableName, $allowedTables)) {
                throw new Exception("Invalid table name");
            }

            $sql = "SELECT * FROM `$tableName` ORDER BY id DESC";
            $stmt = $this->conn->query($sql);
            $data = $stmt->fetchAll();

            return $this->formatExportData($data, $format);
        } catch (Exception $e) {
            $this->logger->error('[ADMIN_HANDLER] Export failed', [
                'error' => $e->getMessage(),
                'table' => $tableName,
                'format' => $format
            ]);
            throw $e;
        }
    }

    public function exportFilteredData($data, $format = 'csv')
    {
        try {
            return $this->formatExportData($data, $format);
        } catch (Exception $e) {
            $this->logger->error('[ADMIN_HANDLER] Filtered export failed', [
                'error' => $e->getMessage(),
                'format' => $format
            ]);
            throw $e;
        }
    }

    private function formatExportData($data, $format)
    {
        if ($format === 'csv') {
            return $this->arrayToCsv($data);
        } elseif ($format === 'json') {
            return json_encode($data, JSON_PRETTY_PRINT);
        } elseif ($format === 'excel') {
            return $this->arrayToExcel($data);
        }

        throw new Exception("Unsupported export format");
    }

    private function arrayToExcel($data)
    {
        if (empty($data)) {
            return '';
        }

        $output = "<?xml version=\"1.0\"?>\n";
        $output .= "<Workbook xmlns=\"urn:schemas-microsoft-com:office:spreadsheet\"\n";
        $output .= " xmlns:o=\"urn:schemas-microsoft-com:office:office\"\n";
        $output .= " xmlns:x=\"urn:schemas-microsoft-com:office:excel\"\n";
        $output .= " xmlns:ss=\"urn:schemas-microsoft-com:office:spreadsheet\"\n";
        $output .= " xmlns:html=\"http://www.w3.org/TR/REC-html40\">\n";
        $output .= "<Worksheet ss:Name=\"Export\">\n";
        $output .= "<Table>\n";
        
        // Add headers
        $output .= "<Row>\n";
        foreach (array_keys($data[0]) as $header) {
            $output .= "<Cell><Data ss:Type=\"String\">" . htmlspecialchars($header) . "</Data></Cell>\n";
        }
        $output .= "</Row>\n";
        
        // Add data
        foreach ($data as $row) {
            $output .= "<Row>\n";
            foreach ($row as $value) {
                $type = is_numeric($value) ? "Number" : "String";
                $output .= "<Cell><Data ss:Type=\"$type\">" . htmlspecialchars($value) . "</Data></Cell>\n";
            }
            $output .= "</Row>\n";
        }
        
        $output .= "</Table>\n";
        $output .= "</Worksheet>\n";
        $output .= "</Workbook>\n";
        
        return $output;
    }

    private function arrayToCsv($data)
    {
        if (empty($data)) {
            return '';
        }

        $output = fopen('php://temp', 'r+');
        
        // Add headers
        fputcsv($output, array_keys($data[0]));
        
        // Add data
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return $csv;
    }

    public function changePassword($username, $currentPassword, $newPassword)
    {
        try {
            // Verify current password
            $sql = "SELECT password_hash FROM admin_users WHERE username = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$username]);
            $hash = $stmt->fetchColumn();

            if (!$hash || !password_verify($currentPassword, $hash)) {
                return false;
            }

            // Update password
            $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $updateSql = "UPDATE admin_users SET password_hash = ?, updated_at = NOW() WHERE username = ?";
            $updateStmt = $this->conn->prepare($updateSql);
            return $updateStmt->execute([$newHash, $username]);
        } catch (Exception $e) {
            $this->logger->error('[ADMIN_HANDLER] Failed to change password', [
                'error' => $e->getMessage(),
                'username' => $username
            ]);
            return false;
        }
    }

    public function updateAdminStatus($adminId, $isActive)
    {
        try {
            $sql = "UPDATE admin_users SET is_active = ?, updated_at = NOW() WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([$isActive, $adminId]);
        } catch (Exception $e) {
            $this->logger->error('[ADMIN_HANDLER] Failed to update admin status', [
                'error' => $e->getMessage(),
                'admin_id' => $adminId
            ]);
            throw $e;
        }
    }

    public function isValidTable($tableName)
    {
        $allowedTables = $this->getAllTables();
        return in_array($tableName, $allowedTables);
    }

    public function deleteRecord($tableName, $recordId)
    {
        try {
            if (!$this->isValidTable($tableName)) {
                throw new Exception("Invalid table name");
            }

            // Get primary key column
            $columnsSql = "SHOW COLUMNS FROM `$tableName`";
            $columnsStmt = $this->conn->query($columnsSql);
            $primaryKey = null;
            while ($column = $columnsStmt->fetch()) {
                if ($column['Key'] === 'PRI') {
                    $primaryKey = $column['Field'];
                    break;
                }
            }

            if (!$primaryKey) {
                throw new Exception("No primary key found for table");
            }

            $sql = "DELETE FROM `$tableName` WHERE `$primaryKey` = ?";
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([$recordId]);
        } catch (Exception $e) {
            $this->logger->error('[ADMIN_HANDLER] Failed to delete record', [
                'error' => $e->getMessage(),
                'table' => $tableName,
                'record_id' => $recordId
            ]);
            throw $e;
        }
    }

    public function bulkDelete($tableName, $recordIds)
    {
        try {
            if (!$this->isValidTable($tableName) || empty($recordIds)) {
                throw new Exception("Invalid parameters");
            }

            // Get primary key column
            $columnsSql = "SHOW COLUMNS FROM `$tableName`";
            $columnsStmt = $this->conn->query($columnsSql);
            $primaryKey = null;
            while ($column = $columnsStmt->fetch()) {
                if ($column['Key'] === 'PRI') {
                    $primaryKey = $column['Field'];
                    break;
                }
            }

            if (!$primaryKey) {
                throw new Exception("No primary key found for table");
            }

            $placeholders = str_repeat('?,', count($recordIds) - 1) . '?';
            $sql = "DELETE FROM `$tableName` WHERE `$primaryKey` IN ($placeholders)";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($recordIds);
            return $stmt->rowCount();
        } catch (Exception $e) {
            $this->logger->error('[ADMIN_HANDLER] Failed to bulk delete', [
                'error' => $e->getMessage(),
                'table' => $tableName,
                'record_count' => count($recordIds)
            ]);
            throw $e;
        }
    }

    public function bulkExport($tableName, $recordIds)
    {
        try {
            if (!$this->isValidTable($tableName) || empty($recordIds)) {
                throw new Exception("Invalid parameters");
            }

            // Get primary key column
            $columnsSql = "SHOW COLUMNS FROM `$tableName`";
            $columnsStmt = $this->conn->query($columnsSql);
            $primaryKey = null;
            while ($column = $columnsStmt->fetch()) {
                if ($column['Key'] === 'PRI') {
                    $primaryKey = $column['Field'];
                    break;
                }
            }

            if (!$primaryKey) {
                throw new Exception("No primary key found for table");
            }

            $placeholders = str_repeat('?,', count($recordIds) - 1) . '?';
            $sql = "SELECT * FROM `$tableName` WHERE `$primaryKey` IN ($placeholders)";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($recordIds);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            $this->logger->error('[ADMIN_HANDLER] Failed to bulk export', [
                'error' => $e->getMessage(),
                'table' => $tableName,
                'record_count' => count($recordIds)
            ]);
            throw $e;
        }
    }

    public function getAdminUsers()
    {
        try {
            $sql = "SELECT id, username, email, full_name, role, is_active, last_login, created_at FROM admin_users ORDER BY created_at DESC";
            $stmt = $this->conn->query($sql);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            $this->logger->error('[ADMIN_HANDLER] Failed to get admin users', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    // User Management Methods
    public function getAllAdminUsers()
    {
        try {
            $sql = "SELECT id, username, email, full_name, role, is_active, last_login, created_at 
                    FROM admin_users ORDER BY created_at DESC";
            $stmt = $this->conn->query($sql);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            $this->logger->error('[ADMIN_HANDLER] Failed to get all admin users', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    public function createAdminUser($username, $password, $email, $fullName, $role = 'admin')
    {
        try {
            // Check if username already exists
            $checkSql = "SELECT COUNT(*) FROM admin_users WHERE username = ?";
            $stmt = $this->conn->prepare($checkSql);
            $stmt->execute([$username]);
            if ($stmt->fetchColumn() > 0) {
                return ['success' => false, 'error' => 'Username already exists'];
            }

            // Check if email already exists
            if (!empty($email)) {
                $checkEmailSql = "SELECT COUNT(*) FROM admin_users WHERE email = ?";
                $stmt = $this->conn->prepare($checkEmailSql);
                $stmt->execute([$email]);
                if ($stmt->fetchColumn() > 0) {
                    return ['success' => false, 'error' => 'Email already exists'];
                }
            }

            // Create user
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            
            // Generate UUID for new admin user
            $uuid = DatabaseMigration::generateUuid();
            
            $sql = "INSERT INTO admin_users (uuid, username, password_hash, email, full_name, role) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $this->conn->prepare($sql);
            $result = $stmt->execute([$uuid, $username, $passwordHash, $email, $fullName, $role]);

            if ($result) {
                $userId = $this->conn->lastInsertId();
                $this->logger->info('[ADMIN_HANDLER] New admin user created', [
                    'user_id' => $userId,
                    'uuid' => $uuid,
                    'username' => $username,
                    'role' => $role
                ]);
                return ['success' => true, 'user_id' => $userId, 'uuid' => $uuid];
            } else {
                return ['success' => false, 'error' => 'Failed to create user'];
            }
        } catch (Exception $e) {
            $this->logger->error('[ADMIN_HANDLER] Failed to create admin user', [
                'error' => $e->getMessage(),
                'username' => $username
            ]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function updateAdminUser($userId, $email, $fullName, $role, $isActive)
    {
        try {
            // Check if email already exists for another user
            if (!empty($email)) {
                $checkEmailSql = "SELECT COUNT(*) FROM admin_users WHERE email = ? AND id != ?";
                $stmt = $this->conn->prepare($checkEmailSql);
                $stmt->execute([$email, $userId]);
                if ($stmt->fetchColumn() > 0) {
                    return ['success' => false, 'error' => 'Email already exists'];
                }
            }

            $sql = "UPDATE admin_users SET email = ?, full_name = ?, role = ?, is_active = ? WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $result = $stmt->execute([$email, $fullName, $role, $isActive, $userId]);

            if ($result) {
                $this->logger->info('[ADMIN_HANDLER] Admin user updated', [
                    'user_id' => $userId,
                    'role' => $role,
                    'is_active' => $isActive
                ]);
                return ['success' => true];
            } else {
                return ['success' => false, 'error' => 'Failed to update user'];
            }
        } catch (Exception $e) {
            $this->logger->error('[ADMIN_HANDLER] Failed to update admin user', [
                'error' => $e->getMessage(),
                'user_id' => $userId
            ]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function changeAdminPassword($userId, $newPassword)
    {
        try {
            $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            
            $sql = "UPDATE admin_users SET password_hash = ? WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $result = $stmt->execute([$passwordHash, $userId]);

            if ($result && $stmt->rowCount() > 0) {
                $this->logger->info('[ADMIN_HANDLER] Admin password changed', [
                    'user_id' => $userId
                ]);
                return ['success' => true];
            } else {
                return ['success' => false, 'error' => 'Failed to change password - user not found or no changes made'];
            }
        } catch (Exception $e) {
            $this->logger->error('[ADMIN_HANDLER] Failed to change admin password', [
                'error' => $e->getMessage(),
                'user_id' => $userId
            ]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function deleteAdminUser($userId)
    {
        try {
            // Check if current user is super admin
            if (!$this->isSuperAdmin()) {
                return ['success' => false, 'error' => 'Only super administrators can delete users'];
            }

            // Get user details first
            $userSql = "SELECT username, role FROM admin_users WHERE id = ?";
            $stmt = $this->conn->prepare($userSql);
            $stmt->execute([$userId]);
            $user = $stmt->fetch();

            if (!$user) {
                return ['success' => false, 'error' => 'User not found'];
            }

            // Protect default admin user - can only be disabled, not deleted
            if ($user['username'] === 'kineticadmin') {
                return ['success' => false, 'error' => 'Default admin user cannot be deleted. You can only disable it.'];
            }

            // Don't allow deletion if it's the only active super_admin
            if ($user['role'] === 'super_admin') {
                $checkSql = "SELECT COUNT(*) FROM admin_users WHERE role = 'super_admin' AND is_active = 1 AND id != ?";
                $stmt = $this->conn->prepare($checkSql);
                $stmt->execute([$userId]);
                $otherSuperAdminCount = $stmt->fetchColumn();

                if ($otherSuperAdminCount === 0) {
                    return ['success' => false, 'error' => 'Cannot delete the only active super admin user'];
                }
            }

            $sql = "DELETE FROM admin_users WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $result = $stmt->execute([$userId]);

            if ($result) {
                $this->logger->info('[ADMIN_HANDLER] Admin user deleted', [
                    'user_id' => $userId,
                    'username' => $user['username']
                ]);
                return ['success' => true];
            } else {
                return ['success' => false, 'error' => 'Failed to delete user'];
            }
        } catch (Exception $e) {
            $this->logger->error('[ADMIN_HANDLER] Failed to delete admin user', [
                'error' => $e->getMessage(),
                'user_id' => $userId
            ]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function canDeleteUser($userId)
    {
        try {
            // Only super admins can delete users
            if (!$this->isSuperAdmin()) {
                return false;
            }

            $userSql = "SELECT username, role FROM admin_users WHERE id = ?";
            $stmt = $this->conn->prepare($userSql);
            $stmt->execute([$userId]);
            $user = $stmt->fetch();

            if (!$user) {
                return false;
            }

            // Default admin user cannot be deleted
            if ($user['username'] === 'kineticadmin') {
                return false;
            }

            // Check if it's the only super admin
            if ($user['role'] === 'super_admin') {
                $checkSql = "SELECT COUNT(*) FROM admin_users WHERE role = 'super_admin' AND is_active = 1 AND id != ?";
                $stmt = $this->conn->prepare($checkSql);
                $stmt->execute([$userId]);
                return $stmt->fetchColumn() > 0;
            }

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function getAdminUser($userId)
    {
        try {
            $sql = "SELECT id, username, email, full_name, role, is_active, last_login, created_at 
                    FROM admin_users WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$userId]);
            return $stmt->fetch();
        } catch (Exception $e) {
            $this->logger->error('[ADMIN_HANDLER] Failed to get admin user', [
                'error' => $e->getMessage(),
                'user_id' => $userId
            ]);
            return null;
        }
    }

    public function getCurrentUserRole()
    {
        if (!isset($_SESSION['admin_id'])) {
            return null;
        }

        try {
            $sql = "SELECT role FROM admin_users WHERE id = ? AND is_active = 1";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$_SESSION['admin_id']]);
            return $stmt->fetchColumn();
        } catch (Exception $e) {
            $this->logger->error('[ADMIN_HANDLER] Failed to get current user role', [
                'error' => $e->getMessage(),
                'admin_id' => $_SESSION['admin_id']
            ]);
            return null;
        }
    }

    public function getCurrentUserInfo()
    {
        if (!isset($_SESSION['admin_id'])) {
            return null;
        }

        try {
            $sql = "SELECT id, username, email, full_name, role, is_active FROM admin_users WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$_SESSION['admin_id']]);
            return $stmt->fetch();
        } catch (Exception $e) {
            $this->logger->error('[ADMIN_HANDLER] Failed to get current user info', [
                'error' => $e->getMessage(),
                'admin_id' => $_SESSION['admin_id']
            ]);
            return null;
        }
    }

    /**
     * Get table data formatted for DataTables server-side processing
     */
    public function getTableDataForDataTables($tableName, $page = 1, $perPage = 10, $search = '', $sortColumn = 'created_at', $sortDirection = 'DESC', $filters = [], $dateRange = '', $startDate = '', $endDate = '', $draw = 1)
    {
        try {
            // Validate table name to prevent SQL injection
            $allowedTables = $this->getAllTables();
            if (!in_array($tableName, $allowedTables)) {
                throw new Exception("Invalid table name");
            }

            // Get table columns
            $columnsSql = "SHOW COLUMNS FROM `$tableName`";
            $columnsStmt = $this->conn->query($columnsSql);
            $columns = [];
            $primaryKey = null;
            $dateColumn = null;
            
            while ($column = $columnsStmt->fetch()) {
                $columns[] = $column['Field'];
                if ($column['Key'] === 'PRI') {
                    $primaryKey = $column['Field'];
                }
                // Look for timestamp columns for better default ordering
                if (in_array($column['Field'], ['created_at', 'updated_at', 'timestamp', 'date'])) {
                    $dateColumn = $column['Field'];
                }
            }

            // Set intelligent default sorting - latest first for transactions
            if ($tableName === 'transactions') {
                // For transactions, always prefer ordering by timestamp, default to created_at
                if ($sortColumn === 'id' || $sortColumn === 'created_at') {
                    $sortColumn = $dateColumn ?: 'created_at'; // Prefer detected timestamp or fallback to created_at
                    $sortDirection = 'DESC'; // Always descending for latest first
                }
            }

            // Validate sort column
            if (!in_array($sortColumn, $columns)) {
                $sortColumn = $primaryKey ?: $columns[0];
            }

            // Validate sort direction
            $sortDirection = strtoupper($sortDirection) === 'ASC' ? 'ASC' : 'DESC';

            // Build WHERE conditions
            $whereConditions = [];
            $searchParams = [];

            // Global search condition
            if (!empty($search)) {
                $searchClauses = [];
                foreach ($columns as $column) {
                    $searchClauses[] = "`$column` LIKE ?";
                    $searchParams[] = "%$search%";
                }
                $whereConditions[] = "(" . implode(" OR ", $searchClauses) . ")";
            }

            // Date range filtering
            if (!empty($dateRange) || (!empty($startDate) && !empty($endDate))) {
                $dateColumn = $this->getDateColumn($tableName);
                if ($dateColumn) {
                    if (!empty($dateRange)) {
                        $dateCondition = $this->buildDateRangeCondition($dateColumn, $dateRange);
                        if ($dateCondition) {
                            $whereConditions[] = $dateCondition;
                        }
                    } elseif (!empty($startDate) && !empty($endDate)) {
                        $whereConditions[] = "`$dateColumn` BETWEEN ? AND ?";
                        $searchParams[] = $startDate . ' 00:00:00';
                        $searchParams[] = $endDate . ' 23:59:59';
                    }
                }
            }

            // Column-specific filters
            foreach ($filters as $column => $value) {
                if (in_array($column, $columns) && !empty($value)) {
                    $whereConditions[] = "`$column` LIKE ?";
                    $searchParams[] = "%$value%";
                }
            }

            // Build WHERE clause
            $whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

            // Get total records (without filtering)
            $totalSql = "SELECT COUNT(*) FROM `$tableName`";
            $totalStmt = $this->conn->query($totalSql);
            $recordsTotal = $totalStmt->fetchColumn();

            // Get filtered records count
            $filteredSql = "SELECT COUNT(*) FROM `$tableName` $whereClause";
            $filteredStmt = $this->conn->prepare($filteredSql);
            $filteredStmt->execute($searchParams);
            $recordsFiltered = $filteredStmt->fetchColumn();

            // Calculate pagination
            $offset = ($page - 1) * $perPage;

            // Get data
            $dataSql = "SELECT * FROM `$tableName` $whereClause 
                       ORDER BY `$sortColumn` $sortDirection 
                       LIMIT $perPage OFFSET $offset";

            $this->logger->info('[ADMIN_HANDLER] Datatable query', [
                'query' => $dataSql,
                'params' => $searchParams,
                "where_clause" => $whereClause,
                "sort_column" => $sortColumn,
            ]);
            $dataStmt = $this->conn->prepare($dataSql);
            $dataStmt->execute($searchParams);
            $data = $dataStmt->fetchAll();

            // Return DataTables format
            return [
                'data' => $data,
                'recordsTotal' => intval($recordsTotal),
                'recordsFiltered' => intval($recordsFiltered),
                'draw' => intval($draw),
                'start' => ($page - 1) * $perPage, // Add start position for frontend calculation
                'length' => $perPage // Add page length for frontend calculation
            ];

        } catch (Exception $e) {
            $this->logger->error('[ADMIN_HANDLER] Failed to get DataTables data', [
                'error' => $e->getMessage(),
                'table' => $tableName,
                'filters' => $filters
            ]);
            return [
                'data' => [],
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'draw' => intval($draw),
                'start' => 0,
                'length' => $perPage,
                'error' => $e->getMessage()
            ];
        }
    }

    public function isSuperAdmin()
    {
        return $this->getCurrentUserRole() === 'super_admin';
    }

    public function isAdminOrAbove()
    {
        $role = $this->getCurrentUserRole();
        return in_array($role, ['admin', 'super_admin']);
    }

    // Get individual record methods for detail views
    public function getTransactionById($id)
    {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM transactions WHERE id = ? OR transaction_id = ?");
            $stmt->execute([$id, $id]);
            $result = $stmt->fetch();
            
            if (!$result) {
                return ['error' => 'Transaction not found'];
            }
            
            return ['success' => true, 'data' => $result];
            
        } catch (Exception $e) {
            $this->logger->error('[ADMIN_HANDLER] Failed to get transaction by ID', [
                'error' => $e->getMessage(),
                'id' => $id
            ]);
            return ['error' => 'Database error: ' . $e->getMessage()];
        }
    }

    public function getTestDriveById($id)
    {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM test_drives WHERE id = ?");
            $stmt->execute([$id]);
            $result = $stmt->fetch();
            
            if (!$result) {
                return ['error' => 'Test drive not found'];
            }
            
            return ['success' => true, 'data' => $result];
            
        } catch (Exception $e) {
            $this->logger->error('[ADMIN_HANDLER] Failed to get test drive by ID', [
                'error' => $e->getMessage(),
                'id' => $id
            ]);
            return ['error' => 'Database error: ' . $e->getMessage()];
        }
    }

    public function getContactById($id)
    {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM contacts WHERE id = ?");
            $stmt->execute([$id]);
            $result = $stmt->fetch();
            
            if (!$result) {
                return ['error' => 'Contact not found'];
            }
            
            return ['success' => true, 'data' => $result];
            
        } catch (Exception $e) {
            $this->logger->error('[ADMIN_HANDLER] Failed to get contact by ID', [
                'error' => $e->getMessage(),
                'id' => $id
            ]);
            return ['error' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Executes a SQL statement directly
     * 
     * @param string $sql SQL statement to execute
     * @return bool True if successful
     */
    public function executeSql($sql)
    {
        try {
            $result = $this->conn->exec($sql);
            return $result !== false;
        } catch (PDOException $e) {
            $this->logger->error('[ADMIN_HANDLER] SQL execution error', [
                'error' => $e->getMessage(),
                'sql' => substr($sql, 0, 100) . (strlen($sql) > 100 ? '...' : '')
            ]);
            throw $e;
        }
    }
    
    /**
     * Creates a new dealership
     * 
     * @param array $data Dealership data
     * @return array The created dealership with ID
     */
    public function createDealership($data)
    {
        try {
            $requiredFields = ['name', 'address', 'city', 'state', 'pincode'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    throw new Exception("$field is required");
                }
            }
            
            $fields = [
                'name', 'address', 'city', 'state', 'pincode', 
                'phone', 'email', 'latitude', 'longitude'
            ];
            
            $insertFields = [];
            $insertValues = [];
            $params = [];
            
            foreach ($fields as $field) {
                if (isset($data[$field]) && $data[$field] !== '') {
                    $insertFields[] = $field;
                    $insertValues[] = ":$field";
                    $params[":$field"] = $data[$field];
                }
            }
            
            $insertFieldsStr = implode(', ', $insertFields);
            $insertValuesStr = implode(', ', $insertValues);
            
            $sql = "INSERT INTO dealerships ($insertFieldsStr) VALUES ($insertValuesStr)";
            $stmt = $this->conn->prepare($sql);
            
            if ($stmt->execute($params)) {
                $id = $this->conn->lastInsertId();
                return $this->getDealership($id);
            } else {
                throw new Exception("Failed to create dealership");
            }
        } catch (Exception $e) {
            $this->logger->error('[ADMIN_HANDLER] Failed to create dealership', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            throw $e;
        }
    }

    /**
     * Updates an existing dealership
     * 
     * @param array $data Dealership data with ID
     * @return array The updated dealership
     */
    public function updateDealership($data)
    {
        try {
            if (empty($data['id'])) {
                throw new Exception("Dealership ID is required");
            }
            
            $id = $data['id'];
            unset($data['id']); // Remove ID from update fields
            
            $updateParts = [];
            $params = [":id" => $id];
            
            foreach ($data as $field => $value) {
                $updateParts[] = "$field = :$field";
                $params[":$field"] = $value;
            }
            
            $updateStr = implode(', ', $updateParts);
            
            $sql = "UPDATE dealerships SET $updateStr WHERE id = :id";
            $stmt = $this->conn->prepare($sql);
            
            if ($stmt->execute($params)) {
                return $this->getDealership($id);
            } else {
                throw new Exception("Failed to update dealership");
            }
        } catch (Exception $e) {
            $this->logger->error('[ADMIN_HANDLER] Failed to update dealership', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            throw $e;
        }
    }

    /**
     * Gets a dealership by ID
     * 
     * @param int $id Dealership ID
     * @return array Dealership data
     */
    public function getDealership($id)
    {
        try {
            $sql = "SELECT * FROM dealerships WHERE id = :id";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            
            if ($stmt->execute() && $stmt->rowCount() > 0) {
                return $stmt->fetch(PDO::FETCH_ASSOC);
            } else {
                throw new Exception("Dealership not found");
            }
        } catch (Exception $e) {
            $this->logger->error('[ADMIN_HANDLER] Failed to get dealership', [
                'error' => $e->getMessage(),
                'id' => $id
            ]);
            throw $e;
        }
    }

    /**
     * Gets all dealerships
     * 
     * @return array Array of dealerships
     */
    public function getAllDealerships()
    {
        try {
            $sql = "SELECT * FROM dealerships ORDER BY name ASC";
            $stmt = $this->conn->query($sql);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $this->logger->error('[ADMIN_HANDLER] Failed to get dealerships', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Deletes a dealership
     * 
     * @param int $id Dealership ID
     * @return bool True if successful
     */
    public function deleteDealership($id)
    {
        try {
            $sql = "DELETE FROM dealerships WHERE id = :id";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            
            if ($stmt->execute() && $stmt->rowCount() > 0) {
                return true;
            } else {
                throw new Exception("Dealership not found or could not be deleted");
            }
        } catch (Exception $e) {
            $this->logger->error('[ADMIN_HANDLER] Failed to delete dealership', [
                'error' => $e->getMessage(),
                'id' => $id
            ]);
            throw $e;
        }
    }
}
