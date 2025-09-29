<?php

// Prevent direct access
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    http_response_code(404);
    exit();
}

class Logger
{
    private $logDir;
    private static $instance = null;
    private $canWrite = null; // Cache the write permission check

    private function __construct()
    {
        // Use centralized logs directory at the root of the PHP application
        $this->logDir = __DIR__ . '/logs';

        // Create logs directory if it doesn't exist and we have permissions
        $this->initializeLogDirectory();
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new Logger();
        }

        return self::$instance;
    }

    /**
     * Initialize log directory with permission checks
     */
    private function initializeLogDirectory()
    {
        // Check if directory exists
        if (!file_exists($this->logDir)) {
            // Try to create the directory
            if (@mkdir($this->logDir, 0755, true)) {
                $this->canWrite = true;
            } else {
                // Cannot create directory, disable logging
                $this->canWrite = false;
                return;
            }
        }

        // Check if we can write to the directory
        $this->canWrite = $this->checkWritePermissions();
    }

    /**
     * Check if we have write permissions to the log directory
     */
    private function checkWritePermissions()
    {
        // Check if directory is writable
        if (!is_writable($this->logDir)) {
            return false;
        }

        // Try to create a test file to verify write access
        $testFile = $this->logDir . '/test_write_' . uniqid() . '.tmp';
        $testResult = @file_put_contents($testFile, 'test');

        if ($testResult !== false) {
            // Clean up test file
            @unlink($testFile);
            return true;
        }

        return false;
    }

    /**
     * Check if logging is available
     */
    public function isWritable()
    {
        return $this->canWrite === true;
    }

    /**
     * Re-check write permissions (useful if permissions change during runtime)
     */
    public function recheckPermissions()
    {
        $this->canWrite = $this->checkWritePermissions();
        return $this->canWrite;
    }

    /**
     * Get the log directory path
     */
    public function getLogDirectory()
    {
        return $this->logDir;
    }

    /**
     * Log a success message
     */
    public function success($message, $context = [], $logFile = 'success_logs.txt')
    {
        $this->log('SUCCESS', $message, $context, $logFile);
    }

    /**
     * Log an information message
     */
    public function info($message, $context = [], $logFile = 'info_logs.txt')
    {
        $this->log('INFO', $message, $context, $logFile);
    }

    /**
     * Log an error message
     */
    public function error($message, $context = [], $logFile = 'error_logs.txt')
    {
        $this->log('ERROR', $message, $context, $logFile);
    }

    /**
     * Log a warning message
     */
    public function warning($message, $context = [], $logFile = 'warning_logs.txt')
    {
        $this->log('WARNING', $message, $context, $logFile);
    }

    /**
     * Log a debug message
     */
    public function debug($message, $context = [], $logFile = 'debug_logs.txt')
    {
        $this->log('DEBUG', $message, $context, $logFile);
    }

    /**
     * Log database operations (for backward compatibility)
     */
    public function database($level, $message, $context = [])
    {
        $this->log($level, $message, $context, 'database_logs.txt');
    }

    /**
     * Log SMS operations
     */
    public function sms($level, $message, $context = [])
    {
        $this->log($level, $message, $context, 'sms_logs.txt');
    }

    /**
     * Log payment operations
     */
    public function payment($level, $message, $context = [])
    {
        $this->log($level, $message, $context, 'payment_logs.txt');
    }

    /**
     * Log status check operations
     */
    public function status($level, $message, $context = [])
    {
        $this->log($level, $message, $context, 'status_logs.txt');
    }

    /**
     * Write log entry to file
     */
    private function log($level, $message, $context = [], $logFile = 'application_logs.txt')
    {
        // Check if we can write logs
        if ($this->canWrite === false) {
            // Silently ignore if we cannot write
            return;
        }

        // If canWrite is null, we haven't checked yet, so check now
        if ($this->canWrite === null) {
            $this->canWrite = $this->checkWritePermissions();
            if ($this->canWrite === false) {
                return; // Silently ignore
            }
        }

        // Create DateTime with explicit IST timezone
        $date = new DateTime('now', new DateTimeZone('Asia/Kolkata'));
        $fullLogPath = $this->logDir . '/' . $logFile;

        // Format the log entry
        $logEntry = sprintf(
            "[%s] [%s]: %s %s\n",
            $date->format('Y-m-d H:i:s'),
            $level,
            "\n" . $message,
            !empty($context) ? json_encode($context) : ''
        );

        // Try to write to log file with file locking for concurrent access
        $result = @file_put_contents($fullLogPath, $logEntry, FILE_APPEND | LOCK_EX);

        // If write failed, disable logging to prevent further attempts
        if ($result === false) {
            $this->canWrite = false;
            // Optionally log to PHP error log, but only once to avoid spam
            if (!defined('LOGGER_WRITE_ERROR_LOGGED')) {
                define('LOGGER_WRITE_ERROR_LOGGED', true);
                @error_log('[LOGGER] Write access lost to log directory. Logging disabled.');
            }
        }
    }
}
