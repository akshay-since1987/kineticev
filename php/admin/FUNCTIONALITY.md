# Admin Portal Functionality Documentation

## Table of Contents
1. [Authentication System](#authentication-system)
2. [User Management](#user-management)
3. [Dashboard Operations](#dashboard-operations)
4. [Dealership Management](#dealership-management)
5. [Data Table Operations](#data-table-operations)
6. [Analytics System](#analytics-system)
7. [Logging System](#logging-system)
8. [Error Handling](#error-handling)

## Authentication System

### Login Process
```php
// AdminHandler.php
public function authenticateAdmin($username, $password) {
    try {
        $sql = "SELECT id, username, password_hash, role, is_active FROM admin_users 
                WHERE username = ? AND is_active = 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$username]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password_hash'])) {
            // Update last login
            $updateSql = "UPDATE admin_users SET last_login = NOW(), 
                         last_login_ip = ? WHERE id = ?";
            $updateStmt = $this->conn->prepare($updateSql);
            $updateStmt->execute([$_SERVER['REMOTE_ADDR'], $admin['id']]);

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
```

### Session Management
```php
// Session security configuration in index.php
ini_set('session.cookie_lifetime', 3600); // 1 hour
ini_set('session.cookie_secure', 0); // Set to 1 for HTTPS
ini_set('session.cookie_httponly', 1); // Prevent XSS
ini_set('session.use_strict_mode', 1); // Prevent session fixation

// Session validation
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}
```

## User Management

### Create New Admin User
```php
public function createAdminUser($userData) {
    try {
        // Validate required fields
        $requiredFields = ['username', 'password', 'email', 'role'];
        foreach ($requiredFields as $field) {
            if (empty($userData[$field])) {
                throw new Exception("Missing required field: $field");
            }
        }

        // Generate UUID
        $uuid = DatabaseMigration::generateUuid();
        
        // Hash password
        $passwordHash = password_hash($userData['password'], PASSWORD_DEFAULT);

        $sql = "INSERT INTO admin_users (uuid, username, password_hash, email, 
                full_name, role) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            $uuid,
            $userData['username'],
            $passwordHash,
            $userData['email'],
            $userData['full_name'] ?? '',
            $userData['role']
        ]);

        return [
            'success' => true,
            'user_id' => $this->conn->lastInsertId(),
            'message' => 'User created successfully'
        ];
    } catch (Exception $e) {
        $this->logger->error('[ADMIN_HANDLER] Create user error', [
            'error' => $e->getMessage(),
            'data' => $userData
        ]);
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}
```

### Update User Profile
```php
public function updateAdminUser($userId, $userData) {
    try {
        $updateFields = [];
        $params = [];

        // Build dynamic update query
        if (!empty($userData['email'])) {
            $updateFields[] = "email = ?";
            $params[] = $userData['email'];
        }
        if (!empty($userData['full_name'])) {
            $updateFields[] = "full_name = ?";
            $params[] = $userData['full_name'];
        }
        if (!empty($userData['role'])) {
            $updateFields[] = "role = ?";
            $params[] = $userData['role'];
        }
        if (isset($userData['is_active'])) {
            $updateFields[] = "is_active = ?";
            $params[] = $userData['is_active'];
        }

        if (empty($updateFields)) {
            throw new Exception("No fields to update");
        }

        $params[] = $userId;
        $sql = "UPDATE admin_users SET " . implode(", ", $updateFields) . 
               " WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        return [
            'success' => true,
            'message' => 'User updated successfully'
        ];
    } catch (Exception $e) {
        $this->logger->error('[ADMIN_HANDLER] Update user error', [
            'error' => $e->getMessage(),
            'user_id' => $userId,
            'data' => $userData
        ]);
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}
```

## Dashboard Operations

### Get Dashboard Statistics
```php
public function getDashboardStats() {
    try {
        $stats = [
            'users' => $this->getActiveUsersCount(),
            'dealerships' => $this->getActiveDealershipsCount(),
            'transactions' => $this->getRecentTransactionsCount(),
            'revenue' => $this->getTotalRevenue(),
            'system' => $this->getSystemStats()
        ];

        // Add trend analysis
        $stats['trends'] = [
            'daily_users' => $this->getUserTrend('daily'),
            'weekly_revenue' => $this->getRevenueTrend('weekly'),
            'monthly_dealerships' => $this->getDealershipTrend('monthly')
        ];

        return $stats;
    } catch (Exception $e) {
        $this->logger->error('[ADMIN_HANDLER] Dashboard stats error', [
            'error' => $e->getMessage()
        ]);
        return [
            'error' => 'Failed to load dashboard statistics'
        ];
    }
}

private function getSystemStats() {
    return [
        'uptime' => shell_exec('uptime'),
        'memory_usage' => memory_get_usage(true),
        'cpu_load' => sys_getloadavg(),
        'disk_space' => disk_free_space('/')
    ];
}
```

### Frontend Dashboard Implementation
```javascript
class DashboardManager {
    constructor() {
        this.statsContainer = document.getElementById('dashboard-stats');
        this.chartContainer = document.getElementById('dashboard-charts');
        this.init();
    }

    async init() {
        await this.loadStats();
        this.setupRefreshTimer();
        this.initializeCharts();
    }

    async loadStats() {
        try {
            const response = await fetch('/admin/api.php?action=dashboard_stats');
            const data = await response.json();
            
            if (data.error) {
                throw new Error(data.error);
            }

            this.updateStatsDisplay(data);
            this.updateCharts(data.trends);
        } catch (error) {
            console.error('Failed to load dashboard stats:', error);
            this.showError('Failed to load dashboard statistics');
        }
    }

    updateStatsDisplay(stats) {
        const cards = {
            users: {
                icon: 'users',
                label: 'Active Users',
                value: stats.users
            },
            dealerships: {
                icon: 'store',
                label: 'Active Dealerships',
                value: stats.dealerships
            },
            transactions: {
                icon: 'credit-card',
                label: 'Recent Transactions',
                value: stats.transactions
            },
            revenue: {
                icon: 'dollar-sign',
                label: 'Total Revenue',
                value: this.formatCurrency(stats.revenue)
            }
        };

        this.statsContainer.innerHTML = Object.entries(cards)
            .map(([key, data]) => this.createStatCard(key, data))
            .join('');
    }

    createStatCard(key, data) {
        return `
            <div class="stat-card" id="${key}-card">
                <div class="stat-icon">
                    <i class="fas fa-${data.icon}"></i>
                </div>
                <div class="stat-content">
                    <h3>${data.label}</h3>
                    <p class="stat-value">${data.value}</p>
                </div>
            </div>
        `;
    }
}
```

## Dealership Management

### Create/Update Dealership
```php
public function updateDealership($data) {
    try {
        // Validate required fields
        $requiredFields = ['name', 'address', 'city', 'state', 'pincode'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                throw new Exception("Missing required field: $field");
            }
        }

        // Update or Insert based on ID presence
        if (isset($data['id']) && !empty($data['id'])) {
            $sql = "UPDATE dealerships SET 
                    name = ?, address = ?, city = ?, 
                    state = ?, pincode = ?, status = ?,
                    updated_at = NOW()
                    WHERE id = ?";
            $params = [
                $data['name'],
                $data['address'],
                $data['city'],
                $data['state'],
                $data['pincode'],
                $data['status'] ?? 'active',
                $data['id']
            ];
        } else {
            $sql = "INSERT INTO dealerships 
                    (name, address, city, state, pincode, status, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, NOW())";
            $params = [
                $data['name'],
                $data['address'],
                $data['city'],
                $data['state'],
                $data['pincode'],
                $data['status'] ?? 'active'
            ];
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        return [
            'success' => true,
            'dealership_id' => $data['id'] ?? $this->conn->lastInsertId(),
            'message' => isset($data['id']) ? 
                        'Dealership updated successfully' : 
                        'Dealership created successfully'
        ];
    } catch (Exception $e) {
        $this->logger->error('[ADMIN_HANDLER] Dealership update error', [
            'error' => $e->getMessage(),
            'data' => $data
        ]);
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}
```

## Data Table Operations

### Dynamic Table Data Loading
```php
public function getTableDataForDataTables($table, $page, $perPage, $search, 
    $sortColumn, $sortDirection, $filters = [], $dateRange = false, 
    $startDate = null, $endDate = null, $draw = null) {
    try {
        // Validate table name
        if (!$this->isValidTable($table)) {
            throw new Exception("Invalid table name");
        }

        // Build base query
        $sql = "SELECT * FROM " . $table;
        $params = [];
        $whereConditions = [];

        // Add search condition
        if (!empty($search)) {
            $searchConditions = [];
            $columns = $this->getTableColumns($table);
            foreach ($columns as $column) {
                $searchConditions[] = "$column LIKE ?";
                $params[] = "%$search%";
            }
            if (!empty($searchConditions)) {
                $whereConditions[] = "(" . implode(" OR ", $searchConditions) . ")";
            }
        }

        // Add filters
        if (!empty($filters)) {
            foreach ($filters as $field => $value) {
                if ($value !== '') {
                    $whereConditions[] = "$field = ?";
                    $params[] = $value;
                }
            }
        }

        // Add date range filter
        if ($dateRange && $startDate && $endDate) {
            $whereConditions[] = "created_at BETWEEN ? AND ?";
            $params[] = $startDate;
            $params[] = $endDate;
        }

        // Combine where conditions
        if (!empty($whereConditions)) {
            $sql .= " WHERE " . implode(" AND ", $whereConditions);
        }

        // Add sorting
        if ($sortColumn && $sortDirection) {
            $sql .= " ORDER BY $sortColumn $sortDirection";
        }

        // Get total count (before pagination)
        $countSql = "SELECT COUNT(*) as total FROM ($sql) as filtered_table";
        $countStmt = $this->conn->prepare($countSql);
        $countStmt->execute($params);
        $totalFiltered = $countStmt->fetch()['total'];

        // Add pagination
        $sql .= " LIMIT ? OFFSET ?";
        $params[] = $perPage;
        $params[] = ($page - 1) * $perPage;

        // Execute final query
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'draw' => $draw,
            'recordsTotal' => $this->getTableCount($table),
            'recordsFiltered' => $totalFiltered,
            'data' => $data
        ];
    } catch (Exception $e) {
        $this->logger->error('[ADMIN_HANDLER] Table data error', [
            'error' => $e->getMessage(),
            'table' => $table
        ]);
        return [
            'error' => $e->getMessage()
        ];
    }
}
```

### Frontend DataTable Implementation
```javascript
class DataTableManager {
    constructor(tableName, options = {}) {
        this.tableName = tableName;
        this.options = {
            pageLength: options.pageLength || 10,
            dom: options.dom || 'Bfrtip',
            buttons: options.buttons || ['copy', 'csv', 'excel', 'pdf', 'print'],
            ...options
        };
        this.init();
    }

    async init() {
        try {
            const columns = await this.fetchColumns();
            this.initializeDataTable(columns);
            this.setupFilters();
            this.setupExport();
        } catch (error) {
            console.error('Failed to initialize DataTable:', error);
            this.showError('Failed to initialize table');
        }
    }

    async fetchColumns() {
        const response = await fetch(
            `/admin/api.php?action=table_columns&table=${this.tableName}`
        );
        return await response.json();
    }

    initializeDataTable(columns) {
        this.table = $(`#${this.tableName}-table`).DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '/admin/api.php?action=table_data',
                data: (d) => {
                    return {
                        ...d,
                        table: this.tableName,
                        filters: this.getActiveFilters()
                    };
                }
            },
            columns: columns.map(col => ({
                data: col,
                name: col,
                title: this.formatColumnTitle(col)
            })),
            ...this.options
        });
    }
}
```

## Analytics System

### Data Collection
```php
public function getAnalyticsData($startDate = null, $endDate = null) {
    try {
        // Set default date range if not provided
        $endDate = $endDate ?? date('Y-m-d');
        $startDate = $startDate ?? date('Y-m-d', strtotime('-30 days'));

        $data = [
            'summary' => $this->getAnalyticsSummary($startDate, $endDate),
            'trends' => $this->getAnalyticsTrends($startDate, $endDate),
            'demographics' => $this->getUserDemographics(),
            'performance' => $this->getSystemPerformance()
        ];

        // Cache the results
        $this->cacheAnalyticsData($data);

        return $data;
    } catch (Exception $e) {
        $this->logger->error('[ADMIN_HANDLER] Analytics error', [
            'error' => $e->getMessage(),
            'start_date' => $startDate,
            'end_date' => $endDate
        ]);
        return [
            'error' => 'Failed to retrieve analytics data'
        ];
    }
}
```

### Performance Monitoring
```php
private function getSystemPerformance() {
    return [
        'response_times' => $this->getAverageResponseTimes(),
        'error_rates' => $this->getErrorRates(),
        'server_stats' => [
            'cpu_usage' => sys_getloadavg(),
            'memory_usage' => memory_get_usage(true),
            'disk_usage' => disk_free_space('/'),
            'network_stats' => $this->getNetworkStats()
        ]
    ];
}
```

## Error Handling

### Global Error Handler
```php
class ErrorHandler {
    private $logger;

    public function __construct() {
        $this->logger = Logger::getInstance();
        $this->registerHandlers();
    }

    private function registerHandlers() {
        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);
        register_shutdown_function([$this, 'handleShutdown']);
    }

    public function handleError($errno, $errstr, $errfile, $errline) {
        $this->logger->error('[ERROR_HANDLER] PHP Error', [
            'error_number' => $errno,
            'message' => $errstr,
            'file' => $errfile,
            'line' => $errline
        ]);

        // Return false to allow PHP's internal error handler to run
        return false;
    }

    public function handleException($exception) {
        $this->logger->error('[ERROR_HANDLER] Uncaught Exception', [
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ]);

        $this->displayError($exception);
    }

    private function displayError($error) {
        if (headers_sent()) {
            echo json_encode([
                'success' => false,
                'error' => 'An unexpected error occurred',
                'details' => DEBUG_MODE ? $error->getMessage() : null
            ]);
        } else {
            header('HTTP/1.1 500 Internal Server Error');
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'An unexpected error occurred',
                'details' => DEBUG_MODE ? $error->getMessage() : null
            ]);
        }
    }
}
```