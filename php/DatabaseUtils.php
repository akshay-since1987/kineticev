<?php

// Prevent direct access
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    http_response_code(404);
    exit();
}

/**
 * Database Utilities
 * Provides helper functions to ensure consistent database timezone settings
 */
class DatabaseUtils
{
    /**
     * Set database connection timezone to IST
     * @param PDO $connection The PDO connection object
     * @param Logger|null $logger Optional logger instance
     */
    public static function setTimezone($connection, $logger = null)
    {
        if (!$connection instanceof PDO) {
            return false;
        }

        try {
            $connection->exec("SET time_zone = '+05:30'");
            if ($logger) {
                $logger->info('Database timezone set to IST (+05:30)');
            }
            return true;
        } catch (PDOException $e) {
            if ($logger) {
                $logger->warning('Failed to set database timezone', [
                    'error' => $e->getMessage()
                ]);
            }
            return false;
        }
    }

    /**
     * Create a PDO connection with IST timezone automatically set
     * @param string $dsn Data Source Name
     * @param string $username Database username
     * @param string $password Database password
     * @param array $options PDO options
     * @param Logger|null $logger Optional logger instance
     * @return PDO
     */
    public static function createConnection($dsn, $username, $password, $options = [], $logger = null)
    {
        // Default options with error mode and timeout settings
        $defaultOptions = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 30, // 30 second connection timeout
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET time_zone = '+05:30'",
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true
        ];
        
        // Merge with provided options (provided options take precedence)
        $finalOptions = array_merge($defaultOptions, $options);
        
        return self::createConnectionWithRetry($dsn, $username, $password, $finalOptions, $logger);
    }

    /**
     * Create a PDO connection with retry logic for failed connections
     * @param string $dsn Data Source Name
     * @param string $username Database username
     * @param string $password Database password
     * @param array $options PDO options
     * @param Logger|null $logger Optional logger instance
     * @param int $maxRetries Maximum number of retry attempts
     * @param int $retryDelay Delay between retries in seconds
     * @return PDO
     */
    public static function createConnectionWithRetry($dsn, $username, $password, $options = [], $logger = null, $maxRetries = 3, $retryDelay = 2)
    {
        $retryCount = 0;
        $lastException = null;
        
        while ($retryCount <= $maxRetries) {
            try {
                if ($logger && $retryCount > 0) {
                    $logger->info("Database connection retry attempt {$retryCount}/{$maxRetries}");
                }
                
                $connection = new PDO($dsn, $username, $password, $options);
                
                // Double-check timezone is set (in case INIT_COMMAND didn't work)
                self::setTimezone($connection, $logger);
                
                if ($logger && $retryCount > 0) {
                    $logger->info("Database connection successful after {$retryCount} retries");
                }
                
                return $connection;
                
            } catch (PDOException $e) {
                $lastException = $e;
                $retryCount++;
                
                if ($logger) {
                    $logger->warning('Database connection attempt failed', [
                        'attempt' => $retryCount,
                        'max_retries' => $maxRetries,
                        'error' => $e->getMessage(),
                        'error_code' => $e->getCode(),
                        'dsn' => $dsn
                    ]);
                }
                
                // If this is not the last attempt, wait before retrying
                if ($retryCount <= $maxRetries) {
                    if ($logger) {
                        $logger->info("Waiting {$retryDelay} seconds before retry...");
                    }
                    sleep($retryDelay);
                    // Exponential backoff: increase delay for next retry
                    $retryDelay = min($retryDelay * 2, 10); // Max 10 seconds delay
                }
            }
        }
        
        // All retries failed
        if ($logger) {
            $logger->error('Database connection failed after all retries', [
                'total_attempts' => $retryCount,
                'final_error' => $lastException->getMessage(),
                'dsn' => $dsn
            ]);
        }
        
        throw $lastException;
    }

    /**
     * Get database configuration from config.php
     * @return array Database configuration
     */
    public static function getConfig()
    {
        $config = require __DIR__ . '/config.php';
        return $config['database'];
    }

    /**
     * Create a standard database connection using config.php settings
     * @param Logger|null $logger Optional logger instance
     * @return PDO
     */
    public static function createStandardConnection($logger = null)
    {
        $dbConfig = self::getConfig();
        $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['dbname']};charset=utf8mb4";
        
        return self::createConnection(
            $dsn, 
            $dbConfig['username'], 
            $dbConfig['password'], 
            [],
            $logger
        );
    }
}
