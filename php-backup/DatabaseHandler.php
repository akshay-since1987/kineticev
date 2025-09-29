<?php

// Prevent direct access
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    http_response_code(404);
    exit();
}

// Initialize production timezone guard first
require_once __DIR__ . '/production-timezone-guard.php';

require_once 'Logger.php';
require_once 'DatabaseMigration.php';
require_once 'DatabaseUtils.php';

class DatabaseHandler
{
    private $conn;
    private $logger;

    public function __construct()
    {
        $this->logger = Logger::getInstance();
        $this->logger->info('=== DatabaseHandler Constructor Started ===');
        $this->logger->info('Initializing DatabaseHandler');
        
        $this->logger->debug('Loading configuration file');
        $config = require 'config.php';
        $dbConfig = $config['database'];
        $this->logger->info('Configuration loaded', [
            'host' => $dbConfig['host'],
            'database' => $dbConfig['dbname'],
            'username' => $dbConfig['username']
        ]);
        
        try {
            // First try to connect without specifying database
            $this->logger->info('Step 1: Connecting to MySQL server without database', ['host' => $dbConfig['host']]);
            $dsn = "mysql:host={$dbConfig['host']}";
            $this->logger->debug('DSN for initial connection', ['dsn' => $dsn]);
            
            $this->conn = DatabaseUtils::createConnection(
                $dsn,
                $dbConfig['username'],
                $dbConfig['password'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
                $this->logger
            );
            $this->logger->info('Initial MySQL connection successful');
            
            // Set timezone to IST immediately after connection
            $this->setDatabaseTimezone();
            
            // Check if database exists
            $checkQuery = "SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '{$dbConfig['dbname']}'";
            $this->logger->debug('Checking if database exists', ['query' => $checkQuery]);
            $stmt = $this->conn->query($checkQuery);
            $dbExists = $stmt->fetchColumn();
            $this->logger->info('Database existence check completed', ['exists' => $dbExists ? 'true' : 'false']);
            
            if (!$dbExists) {
                $this->logger->warning('Database does not exist, attempting to create it', ['database' => $dbConfig['dbname']]);
                $createQuery = "CREATE DATABASE IF NOT EXISTS `{$dbConfig['dbname']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
                $this->logger->debug('Executing database creation query', ['query' => $createQuery]);
                $this->conn->exec($createQuery);
                $this->logger->info('Database created successfully', ['database' => $dbConfig['dbname']]);
            } else {
                $this->logger->info('Database already exists', ['database' => $dbConfig['dbname']]);
            }
            
            // Now connect to the specific database
            $this->logger->info('Step 2: Connecting to specific database', ['database' => $dbConfig['dbname']]);
            $fullDsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['dbname']}";
            $this->logger->debug('DSN for database connection', ['dsn' => $fullDsn]);
            
            $this->conn = DatabaseUtils::createConnection(
                $fullDsn,
                $dbConfig['username'],
                $dbConfig['password'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
                $this->logger
            );
            $this->logger->info('Database connection established successfully');
            
            // Set database timezone to IST
            $this->setDatabaseTimezone();
            
            // Always ensure tables exist
            $this->logger->info('Step 3: Ensuring all tables exist');
            $this->createTables();
            $this->logger->info('=== DatabaseHandler Constructor Completed Successfully ===');
        } catch (PDOException $e) {
            $this->logger->error('Database connection failed', [
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            $errorMessage = "Database connection failed: " . $e->getMessage();
            if (strpos($e->getMessage(), "Unknown database") !== false) {
                $errorMessage = "Database '{$dbConfig['dbname']}' does not exist. Please run setup-database.php first or refresh this page to attempt automatic database creation.";
            }
            $this->logger->error('Fatal error - terminating execution', ['error_message' => $errorMessage]);
            die($errorMessage);
        }
    }

    /**
     * Ensure database connection timezone is set using production guard
     * Call this method after any new database connection
     */
    private function setDatabaseTimezone()
    {
        if ($this->conn) {
            $success = applyTimezoneToConnection($this->conn, 'DatabaseHandler_main');
            if ($success) {
                $this->logger->info('Database timezone applied via ProductionTimezoneGuard');
            } else {
                $this->logger->warning('Failed to apply timezone via ProductionTimezoneGuard');
            }
        }
    }

    /**
     * Get a database connection with timezone consistency guaranteed
     * Use this method whenever you need a fresh database connection
     */
    public function getConnection()
    {
        // Ensure timezone is set on current connection
        $this->setDatabaseTimezone();
        
        // Verify time synchronization in production
        $syncStatus = verifyTimeSynchronization($this->conn);
        if ($syncStatus && !$syncStatus['synchronized']) {
            $this->logger->warning('Time synchronization issue detected', $syncStatus);
        }
        
        return $this->conn;
    }

    /**
     * Create necessary database tables
     */
    private function createTables()
    {
        $this->logger->info('=== Starting Table Creation Process ===');
        try {
            // Create transactions table
            $this->logger->info('Creating transactions table');
            $transactionsSql = "CREATE TABLE IF NOT EXISTS transactions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                transaction_id VARCHAR(100) NOT NULL UNIQUE,
                firstname VARCHAR(255) NOT NULL,
                phone VARCHAR(20) NOT NULL,
                email VARCHAR(255) NOT NULL,
                address VARCHAR(255),
                city VARCHAR(100),
                state VARCHAR(100),
                pincode VARCHAR(20),
                ownedBefore TINYINT(1),
                variant VARCHAR(50),
                color VARCHAR(50),
                terms TINYINT(1),
                productinfo VARCHAR(255),
                merchant_id VARCHAR(100),
                amount DECIMAL(10,2) NOT NULL,
                status VARCHAR(50) NOT NULL,
                payment_details TEXT,
                created_at DATETIME NOT NULL,
                updated_at DATETIME
            )";
            $this->logger->debug('Executing transactions table creation query', ['sql' => $transactionsSql]);
            $this->conn->exec($transactionsSql);
            $this->logger->info('Transactions table created successfully');

            // Create test_drives table
            $this->logger->info('Creating test_drives table');
            $testDrivesSql = "CREATE TABLE IF NOT EXISTS test_drives (
                id INT AUTO_INCREMENT PRIMARY KEY,
                full_name VARCHAR(255) NOT NULL,
                phone VARCHAR(20) NOT NULL,
                date DATE NOT NULL,
                pincode VARCHAR(20) NOT NULL,
                message TEXT NOT NULL,
                email VARCHAR(255) NOT NULL,
                status VARCHAR(50) DEFAULT 'pending' COMMENT 'Test drive request status',
                created_at DATETIME NOT NULL,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )";
            $this->logger->debug('Executing test_drives table creation query', ['sql' => $testDrivesSql]);
            $this->conn->exec($testDrivesSql);
            $this->logger->info('Test drives table created successfully');

            // Create contacts table
            $this->logger->info('Creating contacts table');
            $contactsSql = "CREATE TABLE IF NOT EXISTS contacts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                full_name VARCHAR(255) NOT NULL,
                phone VARCHAR(20) NOT NULL,
                email VARCHAR(255) NOT NULL,
                subject VARCHAR(255) DEFAULT NULL COMMENT 'Contact form subject/title',
                help_type ENUM('support', 'enquiry', 'dealership', 'others') NOT NULL,
                message TEXT NOT NULL,
                ip_address VARCHAR(45),
                user_agent TEXT,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )";
            $this->logger->debug('Executing contacts table creation query', ['sql' => $contactsSql]);
            $this->conn->exec($contactsSql);
            $this->logger->info('Contacts table created successfully');
            
            // Create salesforce_submissions table for deduplication tracking
            $this->logger->info('Creating salesforce_submissions table');
            $salesforceSql = "CREATE TABLE IF NOT EXISTS salesforce_submissions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                transaction_id VARCHAR(100) NOT NULL,
                form_type ENUM('book_now', 'test_ride', 'contact') NOT NULL,
                help_type VARCHAR(50) DEFAULT NULL COMMENT 'Contact form help type if applicable',
                submission_type ENUM('success', 'pending', 'failed') NOT NULL DEFAULT 'success',
                customer_email VARCHAR(255) NOT NULL,
                customer_phone VARCHAR(20) NOT NULL,
                salesforce_response TEXT DEFAULT NULL COMMENT 'Store Salesforce API response',
                submitted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_submission (transaction_id, form_type, submission_type),
                INDEX idx_transaction_id (transaction_id),
                INDEX idx_form_type (form_type),
                INDEX idx_submitted_at (submitted_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            $this->logger->debug('Executing salesforce_submissions table creation query', ['sql' => $salesforceSql]);
            $this->conn->exec($salesforceSql);
            $this->logger->info('Salesforce submissions table created successfully');
            
            // Run UUID migrations for tables
            $this->runUuidMigrations();
            
            $this->logger->info('=== Table Creation Process Completed Successfully ===');
        } catch (PDOException $e) {
            $this->logger->error('Failed to create tables', [
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            throw $e;
        }
    }

    /**
     * Run UUID migrations for contacts and test_drives tables
     */
    private function runUuidMigrations()
    {
        try {
            $this->logger->info('[UUID_MIGRATION] Starting UUID migrations and schema fixes');

            $migration = new DatabaseMigration($this->conn);

            // Add UUID to contacts table
            $migration->addUuidColumn('contacts', 'uuid');

            // Add UUID to test_drives table
            $migration->addUuidColumn('test_drives', 'uuid');
            
            // Add phone_verified column to contacts table
            $migration->addColumn('contacts', 'phone_verified', 'ENUM("0", "1") DEFAULT "0" COMMENT "Phone verification status from booking process"');

            // Add dealership option to help_type ENUM
            $migration->addDealershipToHelpType();

            // Run critical schema fixes for missing columns
            $this->logger->info('[SCHEMA_FIX] Running critical schema fixes');
            $schemaResults = $migration->runCriticalSchemaFixes();
            
            if ($schemaResults['test_drives_status'] && $schemaResults['contacts_subject']) {
                $this->logger->info('[SCHEMA_FIX] All critical schema fixes completed successfully');
            } else {
                $this->logger->warning('[SCHEMA_FIX] Some schema fixes failed', $schemaResults);
            }

            $this->logger->info('[UUID_MIGRATION] UUID migrations and schema fixes completed');
        } catch (Exception $e) {
            $this->logger->error('[UUID_MIGRATION] UUID migration or schema fix failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            // Don't throw - allow system to continue working
        }
    }

    public function requestTestDrive($full_name, $phone, $date, $pincode, $message, $email)
    {
        $this->logger->info('=== Test Drive Request Started ===');
        $this->logger->info('Requesting test drive');
        $this->logger->info('Creating new test drive Request', [
            'full_name' => $full_name,
            'phone' => $phone,
            'date' => $date,
            'pincode' => $pincode,
            'message'=> $message,
            'email'=> $email,
        ]);

        $this->logger->debug('Preparing test drive insertion SQL');
        
        // Generate UUID for new test drive
        $uuid = DatabaseMigration::generateUuid();
        
        $sql = 'INSERT INTO test_drives (uuid, full_name, phone, date, pincode, message, email, created_at) VALUES (:uuid, :full_name, :phone, :date, :pincode, :message, :email, NOW())';
        $this->logger->debug('SQL query prepared', ['sql' => $sql]);
        
        $this->logger->debug('Preparing PDO statement');
        $stmt = $this->conn->prepare($sql);
        
        $params = [
            ':uuid' => $uuid,
            ':full_name' => $full_name,
            ':phone' => $phone,
            ':date' => $date,
            ':pincode' => $pincode,
            ':message' => $message,
            ':email' => $email
        ];
        $this->logger->debug('Parameters for execution', $params);
        
        $this->logger->info('Executing test drive insertion');
        $result = $stmt->execute($params);
        
        if ($result) {
            $insertId = $this->conn->lastInsertId();
            $this->logger->info('Test drive request created successfully', [
                'insert_id' => $insertId,
                'uuid' => $uuid,
                'affected_rows' => $stmt->rowCount()
            ]);
            $this->logger->info('=== Test Drive Request Completed ===', ['success' => true]);
            return $insertId;
        } else {
            $errorInfo = $stmt->errorInfo();
            $this->logger->error('Failed to create test drive request', [
                'error_info' => $errorInfo,
                'sql_state' => $errorInfo[0] ?? 'unknown',
                'error_code' => $errorInfo[1] ?? 'unknown',
                'error_message' => $errorInfo[2] ?? 'unknown'
            ]);
            $this->logger->info('=== Test Drive Request Completed ===', ['success' => false]);
            return false;
        }
    }

    /**
     * Create a new transaction record
     */
    public function createTransaction($transactionId, $customerDetails, $amount)
    {
        $this->logger->info('=== Transaction Creation Started ===');
        $this->logger->info('Creating new transaction record', [
            'transactionId' => $transactionId,
            'amount' => $amount
        ]);
        $this->logger->debug('Customer details received', $customerDetails);
        
        $this->logger->debug('Preparing transaction insertion SQL');
        $sql = "INSERT INTO transactions 
            (transaction_id, firstname, phone, email, address, city, state, pincode, ownedBefore, variant, color, terms, productinfo, merchant_id, amount, status, created_at) 
            VALUES (:transaction_id, :firstname, :phone, :email, :address, :city, :state, :pincode, :ownedBefore, :variant, :color, :terms, :productinfo, :merchant_id, :amount, :status, NOW())";
        $this->logger->debug('SQL query prepared', ['sql' => $sql]);
        
        $this->logger->debug('Preparing PDO statement for transaction');
        $stmt = $this->conn->prepare($sql);
        
        $params = [
            ':transaction_id' => $transactionId,
            ':firstname' => $customerDetails['firstname'],
            ':phone' => $customerDetails['phone'],
            ':email' => $customerDetails['email'],
            ':address' => $customerDetails['address'],
            ':city' => $customerDetails['city'],
            ':state' => $customerDetails['state'],
            ':pincode' => $customerDetails['pincode'],
            ':ownedBefore' => $customerDetails['ownedBefore'],
            ':variant' => $customerDetails['variant'],
            ':color' => $customerDetails['color'],
            ':terms' => $customerDetails['terms'],
            ':productinfo' => $customerDetails['productinfo'],
            ':merchant_id' => $customerDetails['merchant_id'],
            ':amount' => $amount,
            ':status' => 'PENDING'
        ];
        $this->logger->debug('Parameters for transaction execution', $params);
        
        $this->logger->info('Executing transaction insertion');
        $result = $stmt->execute($params);
        
        if ($result) {
            $insertId = $this->conn->lastInsertId();
            $this->logger->info('Transaction record created successfully', [
                'transactionId' => $transactionId,
                'insert_id' => $insertId,
                'affected_rows' => $stmt->rowCount()
            ]);
        } else {
            $errorInfo = $stmt->errorInfo();
            $this->logger->error('Failed to create transaction record', [
                'transactionId' => $transactionId,
                'error_info' => $errorInfo,
                'sql_state' => $errorInfo[0] ?? 'unknown',
                'error_code' => $errorInfo[1] ?? 'unknown',
                'error_message' => $errorInfo[2] ?? 'unknown'
            ]);
        }
        
        $this->logger->info('=== Transaction Creation Completed ===', ['success' => $result]);
        return $result;
    }

    /**
     * Update transaction status
     */
    public function updateTransactionStatus($transactionId, $status, $paymentDetails = null)
    {
        $this->logger->info('=== Transaction Status Update Started ===');
        $this->logger->info('Updating transaction status', [
            'transactionId' => $transactionId,
            'status' => $status,
            'has_payment_details' => $paymentDetails !== null
        ]);
        $this->logger->debug('Payment details for update', ['payment_details' => $paymentDetails]);

        $this->logger->debug('Preparing transaction status update SQL');
        $sql = "UPDATE transactions 
            SET status = :status, payment_details = :payment_details, updated_at = NOW() 
            WHERE transaction_id = :transaction_id";
        $this->logger->debug('SQL query prepared', ['sql' => $sql]);
        
        $this->logger->debug('Preparing PDO statement for status update');
        $stmt = $this->conn->prepare($sql);

        $params = [
            ':transaction_id' => $transactionId,
            ':status' => $status,
            ':payment_details' => $paymentDetails ? json_encode($paymentDetails) : null
        ];
        $this->logger->debug('Parameters for status update execution', $params);
        
        $this->logger->info('Executing transaction status update');
        $result = $stmt->execute($params);

        if ($result) {
            $affectedRows = $stmt->rowCount();
            $this->logger->info('Transaction status updated successfully', [
                'transactionId' => $transactionId,
                'status' => $status,
                'affected_rows' => $affectedRows
            ]);
            
            if ($affectedRows === 0) {
                $this->logger->warning('No rows were affected by the update - transaction may not exist', [
                    'transactionId' => $transactionId
                ]);
            }
        } else {
            $errorInfo = $stmt->errorInfo();
            $this->logger->error('Failed to update transaction status', [
                'transactionId' => $transactionId,
                'status' => $status,
                'error_info' => $errorInfo,
                'sql_state' => $errorInfo[0] ?? 'unknown',
                'error_code' => $errorInfo[1] ?? 'unknown',
                'error_message' => $errorInfo[2] ?? 'unknown'
            ]);
        }

        $this->logger->info('=== Transaction Status Update Completed ===', ['success' => $result]);
        return $result;
    }

    /**
     * Get transaction by ID
     */
    public function getTransaction($transactionId)
    {
        $this->logger->info('=== Transaction Fetch Started ===');
        $this->logger->info('Fetching transaction', ['transactionId' => $transactionId]);

        $this->logger->debug('Preparing transaction fetch SQL');
        $sql = "SELECT * FROM transactions WHERE transaction_id = :transaction_id";
        $this->logger->debug('SQL query prepared', ['sql' => $sql]);
        
        $this->logger->debug('Preparing PDO statement for transaction fetch');
        $stmt = $this->conn->prepare($sql);
        
        $params = [':transaction_id' => $transactionId];
        $this->logger->debug('Parameters for transaction fetch', $params);
        
        $this->logger->info('Executing transaction fetch query');
        $stmt->execute($params);
        $transaction = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($transaction) {
            $this->logger->info('Transaction fetched successfully', [
                'transactionId' => $transactionId,
                'found_fields' => array_keys($transaction),
                'status' => $transaction['status'] ?? 'unknown'
            ]);
            $this->logger->debug('Transaction data retrieved', $transaction);
        } else {
            $this->logger->warning('Transaction not found', [
                'transactionId' => $transactionId,
                'affected_rows' => $stmt->rowCount()
            ]);
        }

        $this->logger->info('=== Transaction Fetch Completed ===', ['found' => $transaction !== false]);
        return $transaction;
    }

    /**
     * Delete a transaction (for testing purposes)
     */
    public function deleteTransaction($transactionId)
    {
        $this->logger->info('=== Transaction Deletion Started ===');
        $this->logger->info('Deleting transaction', ['transactionId' => $transactionId]);

        $this->logger->debug('Preparing transaction deletion SQL');
        $sql = "DELETE FROM transactions WHERE transaction_id = :transaction_id";
        $this->logger->debug('SQL query prepared', ['sql' => $sql]);
        
        $this->logger->debug('Preparing PDO statement for transaction deletion');
        $stmt = $this->conn->prepare($sql);
        
        $params = [':transaction_id' => $transactionId];
        $this->logger->debug('Parameters for transaction deletion', $params);
        
        $this->logger->info('Executing transaction deletion');
        $result = $stmt->execute($params);

        if ($result) {
            $affectedRows = $stmt->rowCount();
            $this->logger->info('Transaction deletion executed', [
                'transactionId' => $transactionId,
                'affected_rows' => $affectedRows
            ]);
            
            if ($affectedRows > 0) {
                $this->logger->info('Transaction deleted successfully', ['transactionId' => $transactionId]);
            } else {
                $this->logger->warning('No transaction was deleted - may not have existed', [
                    'transactionId' => $transactionId
                ]);
            }
        } else {
            $errorInfo = $stmt->errorInfo();
            $this->logger->error('Failed to delete transaction', [
                'transactionId' => $transactionId,
                'error_info' => $errorInfo,
                'sql_state' => $errorInfo[0] ?? 'unknown',
                'error_code' => $errorInfo[1] ?? 'unknown',
                'error_message' => $errorInfo[2] ?? 'unknown'
            ]);
        }

        $this->logger->info('=== Transaction Deletion Completed ===', ['success' => $result]);
        return $result;
    }

    /**
     * Create contacts table if it doesn't exist
     */
    public function createContactsTable()
    {
        $this->logger->info('=== Contacts Table Creation Started ===');
        try {
            $this->logger->info('Creating contacts table');
            $sql = "CREATE TABLE IF NOT EXISTS contacts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                full_name VARCHAR(255) NOT NULL,
                phone VARCHAR(20) NOT NULL,
                email VARCHAR(255) NOT NULL,
                help_type ENUM('support', 'enquiry', 'others') NOT NULL,
                message TEXT NOT NULL,
                ip_address VARCHAR(45),
                user_agent TEXT,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )";
            $this->logger->debug('Executing contacts table creation query', ['sql' => $sql]);
            $this->conn->exec($sql);
            $this->logger->info('Contacts table created successfully');
            $this->logger->info('=== Contacts Table Creation Completed ===');
            return true;
        } catch (PDOException $e) {
            $this->logger->error('Failed to create contacts table', [
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            throw $e;
        }
    }

    /**
     * Save contact form submission
     */
    public function saveContact($contactData)
    {
        $this->logger->info('=== Contact Save Started ===');
        $this->logger->info('Saving contact submission', [
            'name' => $contactData['full_name'],
            'email' => $contactData['email'],
            'help_type' => $contactData['help_type']
        ]);
        $this->logger->debug('Complete contact data received', $contactData);

        try {
            $this->logger->debug('Preparing contact insertion SQL');
            
            // Generate UUID for new contact
            $uuid = DatabaseMigration::generateUuid();
            
            $sql = "INSERT INTO contacts (uuid, full_name, phone, email, help_type, message, phone_verified, ip_address, user_agent, created_at) 
                VALUES (:uuid, :full_name, :phone, :email, :help_type, :message, :phone_verified, :ip_address, :user_agent, NOW())";
            $this->logger->debug('SQL query prepared', ['sql' => $sql]);
            
            $this->logger->debug('Preparing PDO statement for contact save');
            $stmt = $this->conn->prepare($sql);

            $params = [
                ':uuid' => $uuid,
                ':full_name' => $contactData['full_name'],
                ':phone' => $contactData['phone'],
                ':email' => $contactData['email'],
                ':help_type' => $contactData['help_type'],
                ':message' => $contactData['message'],
                ':phone_verified' => $contactData['phone_verified'] ?? '0',
                ':ip_address' => $contactData['ip_address'],
                ':user_agent' => $contactData['user_agent']
            ];
            $this->logger->debug('Parameters for contact execution', $params);
            
            $this->logger->info('Executing contact insertion');
            $result = $stmt->execute($params);

            if ($result) {
                $contact_id = $this->conn->lastInsertId();
                $this->logger->info('Contact saved successfully', [
                    'contact_id' => $contact_id,
                    'uuid' => $uuid,
                    'name' => $contactData['full_name'],
                    'email' => $contactData['email'],
                    'affected_rows' => $stmt->rowCount()
                ]);
                $this->logger->info('=== Contact Save Completed Successfully ===');
                return $contact_id;
            } else {
                $errorInfo = $stmt->errorInfo();
                $this->logger->error('Failed to save contact', [
                    'error_info' => $errorInfo,
                    'sql_state' => $errorInfo[0] ?? 'unknown',
                    'error_code' => $errorInfo[1] ?? 'unknown',
                    'error_message' => $errorInfo[2] ?? 'unknown'
                ]);
                $this->logger->info('=== Contact Save Completed with Failure ===');
                return false;
            }
        } catch (PDOException $e) {
            $this->logger->error('Database error while saving contact', [
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            throw $e;
        }
    }

    /**
     * Get contact by ID
     */
    public function getContact($contactId)
    {
        $this->logger->info('=== Contact Fetch Started ===');
        $this->logger->info('Fetching contact', ['contactId' => $contactId]);

        $this->logger->debug('Preparing contact fetch SQL');
        $sql = "SELECT * FROM contacts WHERE id = :contact_id";
        $this->logger->debug('SQL query prepared', ['sql' => $sql]);
        
        $this->logger->debug('Preparing PDO statement for contact fetch');
        $stmt = $this->conn->prepare($sql);
        
        $params = [':contact_id' => $contactId];
        $this->logger->debug('Parameters for contact fetch', $params);
        
        $this->logger->info('Executing contact fetch query');
        $stmt->execute($params);
        $contact = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($contact) {
            $this->logger->info('Contact fetched successfully', [
                'contactId' => $contactId,
                'found_fields' => array_keys($contact),
                'help_type' => $contact['help_type'] ?? 'unknown'
            ]);
            $this->logger->debug('Contact data retrieved', $contact);
        } else {
            $this->logger->warning('Contact not found', [
                'contactId' => $contactId,
                'affected_rows' => $stmt->rowCount()
            ]);
        }

        $this->logger->info('=== Contact Fetch Completed ===', ['found' => $contact !== false]);
        return $contact;
    }

    /**
     * Get all contacts with pagination
     */
    public function getContacts($limit = 50, $offset = 0)
    {
        $this->logger->info('=== Contacts List Fetch Started ===');
        $this->logger->info('Fetching contacts list', ['limit' => $limit, 'offset' => $offset]);

        $this->logger->debug('Preparing contacts list fetch SQL');
        $sql = "SELECT * FROM contacts ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
        $this->logger->debug('SQL query prepared', ['sql' => $sql]);
        
        $this->logger->debug('Preparing PDO statement for contacts list fetch');
        $stmt = $this->conn->prepare($sql);
        
        $this->logger->debug('Binding limit and offset parameters');
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $this->logger->debug('Parameters bound', ['limit' => $limit, 'offset' => $offset]);
        
        $this->logger->info('Executing contacts list fetch query');
        $stmt->execute();
        
        $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $contactCount = count($contacts);
        
        $this->logger->info('Contacts list fetched successfully', [
            'count' => $contactCount,
            'limit' => $limit,
            'offset' => $offset
        ]);
        
        if ($contactCount > 0) {
            $this->logger->debug('Sample contact data', ['first_contact' => $contacts[0]]);
        } else {
            $this->logger->info('No contacts found with current pagination settings');
        }
        
        $this->logger->info('=== Contacts List Fetch Completed ===', ['contacts_found' => $contactCount]);
        return $contacts;
    }

    /**
     * Get UUID by ID for a specific table
     */
    public function getUuidById($table, $id)
    {
        $this->logger->info("=== UUID Fetch Started ===");
        $this->logger->info("Fetching UUID for $table", ['id' => $id]);

        $allowedTables = ['contacts', 'test_drives', 'admin_users'];
        if (!in_array($table, $allowedTables)) {
            $this->logger->error("Invalid table name", ['table' => $table]);
            return false;
        }

        $sql = "SELECT uuid FROM $table WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id' => $id]);
        $uuid = $stmt->fetchColumn();

        $this->logger->info("UUID fetch completed", [
            'table' => $table,
            'id' => $id,
            'uuid' => $uuid ?: 'not found'
        ]);
        $this->logger->info("=== UUID Fetch Completed ===");

        return $uuid;
    }

    /**
     * Check if a Salesforce submission already exists
     * @param string $transactionId Transaction ID or unique identifier
     * @param string $formType Form type (book_now, test_ride, contact)
     * @param string $submissionType Submission type (success, pending, failed)
     * @return bool True if already submitted, false otherwise
     */
    public function hasSalesforceSubmission($transactionId, $formType, $submissionType = 'success')
    {
        $this->logger->info('=== Checking Salesforce Submission Status ===', [
            'transaction_id' => $transactionId,
            'form_type' => $formType,
            'submission_type' => $submissionType
        ]);

        try {
            $query = "SELECT id FROM salesforce_submissions 
                     WHERE transaction_id = ? AND form_type = ? AND submission_type = ?";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$transactionId, $formType, $submissionType]);
            $exists = $stmt->fetchColumn() !== false;
            
            $this->logger->info('Salesforce submission check completed', [
                'exists' => $exists ? 'true' : 'false'
            ]);
            
            return $exists;
        } catch (PDOException $e) {
            $this->logger->error('Failed to check Salesforce submission status', [
                'error' => $e->getMessage(),
                'transaction_id' => $transactionId,
                'form_type' => $formType
            ]);
            return false; // Default to allow submission on error
        }
    }

    /**
     * Record a successful Salesforce submission
     * @param string $transactionId Transaction ID or unique identifier
     * @param string $formType Form type (book_now, test_ride, contact)
     * @param string $submissionType Submission type (success, pending, failed)
     * @param string $customerEmail Customer email
     * @param string $customerPhone Customer phone
     * @param string $helpType Contact form help type (optional)
     * @param array $salesforceResponse Salesforce API response (optional)
     * @return bool True on success, false on failure
     */
    public function recordSalesforceSubmission($transactionId, $formType, $submissionType, $customerEmail, $customerPhone, $helpType = null, $salesforceResponse = null)
    {
        $this->logger->info('=== Recording Salesforce Submission ===', [
            'transaction_id' => $transactionId,
            'form_type' => $formType,
            'submission_type' => $submissionType,
            'customer_email' => $customerEmail,
            'help_type' => $helpType
        ]);

        try {
            $query = "INSERT INTO salesforce_submissions 
                     (transaction_id, form_type, submission_type, customer_email, customer_phone, help_type, salesforce_response) 
                     VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([
                $transactionId,
                $formType,
                $submissionType,
                $customerEmail,
                $customerPhone,
                $helpType,
                $salesforceResponse ? json_encode($salesforceResponse) : null
            ]);
            
            if ($result) {
                $this->logger->info('Salesforce submission recorded successfully', [
                    'record_id' => $this->conn->lastInsertId()
                ]);
                return true;
            } else {
                $this->logger->error('Failed to record Salesforce submission - no rows affected');
                return false;
            }
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) { // Duplicate entry
                $this->logger->warning('Salesforce submission already exists (duplicate prevented)', [
                    'transaction_id' => $transactionId,
                    'form_type' => $formType,
                    'submission_type' => $submissionType
                ]);
                return true; // Consider duplicate as success
            } else {
                $this->logger->error('Failed to record Salesforce submission', [
                    'error' => $e->getMessage(),
                    'code' => $e->getCode(),
                    'transaction_id' => $transactionId
                ]);
                return false;
            }
        }
    }

    /**
     * Get Salesforce submission history for a transaction
     * @param string $transactionId Transaction ID
     * @return array Array of submission records
     */
    public function getSalesforceSubmissionHistory($transactionId)
    {
        $this->logger->info('=== Fetching Salesforce Submission History ===', [
            'transaction_id' => $transactionId
        ]);

        try {
            $query = "SELECT * FROM salesforce_submissions WHERE transaction_id = ? ORDER BY submitted_at DESC";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$transactionId]);
            $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $this->logger->info('Salesforce submission history retrieved', [
                'record_count' => count($history)
            ]);
            
            return $history;
        } catch (PDOException $e) {
            $this->logger->error('Failed to fetch Salesforce submission history', [
                'error' => $e->getMessage(),
                'transaction_id' => $transactionId
            ]);
            return [];
        }
    }
}
