<?php
/**
 * Database Connection Health Check Script
 * 
 * Tests database connectivity and reports on potential issues
 * Helps diagnose AWS RDS connection problems
 * 
 * Usage: php check-database-health.php
 */

require_once __DIR__ . '/Logger.php';
require_once __DIR__ . '/DatabaseUtils.php';

$logger = Logger::getInstance();
$logger->info('=== DATABASE HEALTH CHECK STARTED ===');

echo "🔍 DATABASE CONNECTION HEALTH CHECK\n";
echo "==================================\n\n";

try {
    // Load configuration
    $config = require __DIR__ . '/config.php';
    $dbConfig = $config['database'];
    
    echo "📋 Configuration:\n";
    echo "   Host: {$dbConfig['host']}\n";
    echo "   Database: {$dbConfig['dbname']}\n";
    echo "   Username: {$dbConfig['username']}\n\n";
    
    // Test 1: Basic connectivity
    echo "🧪 TEST 1: Basic Database Connectivity\n";
    echo "   Attempting connection with 30-second timeout...\n";
    
    $startTime = microtime(true);
    
    try {
        $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['dbname']};charset=utf8mb4";
        $connection = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 30
        ]);
        
        $endTime = microtime(true);
        $connectionTime = round(($endTime - $startTime) * 1000, 2);
        
        echo "   ✅ Connection successful! ({$connectionTime}ms)\n\n";
        
        // Test 2: Query execution
        echo "🧪 TEST 2: Query Execution\n";
        $stmt = $connection->query("SELECT 1 as test, NOW() as server_time");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "   ✅ Query successful! Server time: {$result['server_time']}\n\n";
        
        // Test 3: Check missing columns
        echo "🧪 TEST 3: Schema Validation\n";
        
        // Check test_drives table for status column
        echo "   Checking test_drives.status column...\n";
        try {
            $stmt = $connection->query("SHOW COLUMNS FROM test_drives LIKE 'status'");
            $statusColumn = $stmt->fetch();
            if ($statusColumn) {
                echo "   ✅ test_drives.status column EXISTS\n";
            } else {
                echo "   ❌ test_drives.status column MISSING\n";
            }
        } catch (Exception $e) {
            echo "   ❌ Error checking test_drives table: " . $e->getMessage() . "\n";
        }
        
        // Check contacts table for subject column
        echo "   Checking contacts.subject column...\n";
        try {
            $stmt = $connection->query("SHOW COLUMNS FROM contacts LIKE 'subject'");
            $subjectColumn = $stmt->fetch();
            if ($subjectColumn) {
                echo "   ✅ contacts.subject column EXISTS\n";
            } else {
                echo "   ❌ contacts.subject column MISSING\n";
            }
        } catch (Exception $e) {
            echo "   ❌ Error checking contacts table: " . $e->getMessage() . "\n";
        }
        
        echo "\n";
        
        // Test 4: Connection with retry logic
        echo "🧪 TEST 4: Connection Retry Logic\n";
        echo "   Testing enhanced connection method...\n";
        
        $retryStartTime = microtime(true);
        $retryConnection = DatabaseUtils::createConnectionWithRetry(
            $dsn,
            $dbConfig['username'],
            $dbConfig['password'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
            $logger,
            3, // max retries
            1  // delay
        );
        $retryEndTime = microtime(true);
        $retryTime = round(($retryEndTime - $retryStartTime) * 1000, 2);
        
        echo "   ✅ Retry-enabled connection successful! ({$retryTime}ms)\n\n";
        
        // Performance metrics
        echo "📊 PERFORMANCE METRICS:\n";
        echo "   Basic connection: {$connectionTime}ms\n";
        echo "   Retry connection: {$retryTime}ms\n";
        
        if ($connectionTime > 5000) {
            echo "   ⚠️  Connection time > 5 seconds - potential network issues\n";
        } elseif ($connectionTime > 1000) {
            echo "   ⚠️  Connection time > 1 second - monitor performance\n";
        } else {
            echo "   ✅ Connection time is good\n";
        }
        
        echo "\n🎉 OVERALL STATUS: HEALTHY\n";
        echo "   Database is accessible and responsive.\n";
        
    } catch (PDOException $e) {
        $endTime = microtime(true);
        $attemptTime = round(($endTime - $startTime) * 1000, 2);
        
        echo "   ❌ Connection failed after {$attemptTime}ms\n";
        echo "   Error: " . $e->getMessage() . "\n\n";
        
        // Diagnose the error
        echo "🔍 ERROR DIAGNOSIS:\n";
        
        if (strpos($e->getMessage(), 'Connection timed out') !== false) {
            echo "   ISSUE: Connection Timeout\n";
            echo "   LIKELY CAUSES:\n";
            echo "   - AWS RDS instance is down or restarting\n";
            echo "   - Security group not allowing connections\n";
            echo "   - Network connectivity issues\n";
            echo "   - RDS instance at capacity\n\n";
            
            echo "   RECOMMENDED ACTIONS:\n";
            echo "   1. Check AWS RDS Console for instance status\n";
            echo "   2. Verify security group rules\n";
            echo "   3. Check CloudWatch metrics for RDS performance\n";
            echo "   4. Test from different network/server\n";
            
        } elseif (strpos($e->getMessage(), 'Access denied') !== false) {
            echo "   ISSUE: Access Denied\n";
            echo "   LIKELY CAUSES:\n";
            echo "   - Incorrect username/password\n";
            echo "   - User doesn't have access to database\n";
            echo "   - IP not whitelisted\n\n";
            
            echo "   RECOMMENDED ACTIONS:\n";
            echo "   1. Verify credentials in config.php\n";
            echo "   2. Check user permissions in RDS\n";
            echo "   3. Verify IP whitelist settings\n";
            
        } elseif (strpos($e->getMessage(), 'Unknown database') !== false) {
            echo "   ISSUE: Database Not Found\n";
            echo "   RECOMMENDED ACTIONS:\n";
            echo "   1. Verify database name in config.php\n";
            echo "   2. Create database if it doesn't exist\n";
            
        } else {
            echo "   ISSUE: Unknown Error\n";
            echo "   Error Code: " . $e->getCode() . "\n";
            echo "   Full Message: " . $e->getMessage() . "\n";
        }
        
        echo "\n❌ OVERALL STATUS: UNHEALTHY\n";
        echo "   Database connection is failing.\n";
    }
    
} catch (Exception $e) {
    echo "❌ HEALTH CHECK FAILED: " . $e->getMessage() . "\n";
    $logger->error('Health check script failed', [
        'error' => $e->getMessage()
    ]);
}

echo "\n=== HEALTH CHECK COMPLETED ===\n";
$logger->info('=== DATABASE HEALTH CHECK COMPLETED ===');
?>
