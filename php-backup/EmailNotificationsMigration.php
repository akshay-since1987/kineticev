<?php

// Prevent direct access
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    http_response_code(404);
    exit();
}

/**
 * Database Migration: Email Notifications Tracking
 * Purpose: Prevent duplicate email sending for the same transaction status
 * Created: September 8, 2025
 */

require_once __DIR__ . '/DatabaseHandler.php';

class EmailNotificationsMigration 
{
    private $db;
    
    public function __construct() 
    {
        $this->db = new DatabaseHandler();
    }
    
    /**
     * Automatically ensure the email_notifications table exists
     * This method will create the table only if it doesn't exist
     * Safe to call multiple times - won't overwrite existing data
     */
    public static function ensureTableExists() 
    {
        try {
            $db = new DatabaseHandler();
            $conn = $db->getConnection();
            
            // Check if table exists
            $checkSql = "SHOW TABLES LIKE 'email_notifications'";
            $stmt = $conn->prepare($checkSql);
            $stmt->execute();
            $tableExists = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$tableExists) {
                // Table doesn't exist, create it
                $migration = new self();
                $result = $migration->up(true); // Pass true for silent mode
                
                if ($result) {
                    error_log("[EMAIL_NOTIFICATIONS] Table created automatically on app load");
                    return true;
                } else {
                    error_log("[EMAIL_NOTIFICATIONS] Failed to create table automatically");
                    return false;
                }
            } else {
                // Table already exists, no action needed
                error_log("[EMAIL_NOTIFICATIONS] Table already exists, skipping auto-creation");
                return true;
            }
        } catch (Exception $e) {
            error_log("[EMAIL_NOTIFICATIONS] Auto-migration error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Create email_notifications table
     * @param bool $silent Whether to suppress console output
     */
    public function up($silent = false) 
    {
        $sql = "
        CREATE TABLE IF NOT EXISTS email_notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            transaction_id VARCHAR(50) NOT NULL,
            status ENUM('success', 'failure', 'pending') NOT NULL,
            email_type ENUM('admin', 'customer', 'both') NOT NULL DEFAULT 'both',
            recipients TEXT NULL COMMENT 'JSON array of email addresses',
            sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_notification (transaction_id, status),
            INDEX idx_transaction_id (transaction_id),
            INDEX idx_status (status),
            INDEX idx_sent_at (sent_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
        COMMENT='Track email notifications to prevent duplicates';
        ";
        
        try {
            $conn = $this->db->getConnection();
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            
            if (!$silent) {
                echo "✅ Email notifications table created successfully\n";
            }
            return true;
        } catch (Exception $e) {
            if (!$silent) {
                echo "❌ Error creating email notifications table: " . $e->getMessage() . "\n";
            }
            error_log("EmailNotificationsMigration: Failed to create table - " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Drop email_notifications table
     */
    public function down() 
    {
        $sql = "DROP TABLE IF EXISTS email_notifications";
        
        try {
            $conn = $this->db->getConnection();
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            echo "✅ Email notifications table dropped successfully\n";
            return true;
        } catch (Exception $e) {
            echo "❌ Error dropping email notifications table: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    /**
     * Check if email was already sent for transaction and status
     */
    public static function isEmailSent($transaction_id, $status) 
    {
        $db = new DatabaseHandler();
        $sql = "SELECT id FROM email_notifications WHERE transaction_id = :transaction_id AND status = :status LIMIT 1";
        
        $params = [
            ':transaction_id' => $transaction_id,
            ':status' => $status
        ];
        
        $conn = $db->getConnection();
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return !empty($result);
    }
    
    /**
     * Record email notification sent
     */
    public static function recordEmailSent($transaction_id, $status, $recipients = null) 
    {
        $db = new DatabaseHandler();
        $sql = "
        INSERT INTO email_notifications (transaction_id, status, recipients) 
        VALUES (:transaction_id, :status, :recipients)
        ON DUPLICATE KEY UPDATE 
            recipients = VALUES(recipients),
            sent_at = CURRENT_TIMESTAMP
        ";
        
        $params = [
            ':transaction_id' => $transaction_id,
            ':status' => $status,
            ':recipients' => $recipients ? json_encode($recipients) : null
        ];
        
        try {
            $conn = $db->getConnection();
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            return true;
        } catch (Exception $e) {
            error_log("Error recording email notification: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get email sending statistics
     */
    public static function getEmailStats($days = 7) 
    {
        $db = new DatabaseHandler();
        $sql = "
        SELECT 
            status,
            COUNT(*) as total_emails,
            DATE(sent_at) as date
        FROM email_notifications 
        WHERE sent_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
        GROUP BY status, DATE(sent_at)
        ORDER BY date DESC, status
        ";
        
        $params = [':days' => $days];
        $conn = $db->getConnection();
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// CLI execution
if (php_sapi_name() === 'cli') {
    $migration = new EmailNotificationsMigration();
    
    $action = isset($argv[1]) ? $argv[1] : 'up';
    
    switch ($action) {
        case 'up':
            echo "🚀 Running email notifications migration...\n";
            $migration->up();
            break;
            
        case 'down':
            echo "⬇️ Rolling back email notifications migration...\n";
            $migration->down();
            break;
            
        case 'status':
            echo "📊 Email notification statistics (last 7 days):\n";
            $stats = EmailNotificationsMigration::getEmailStats();
            if (empty($stats)) {
                echo "No email notifications found.\n";
            } else {
                foreach ($stats as $stat) {
                    echo sprintf("Date: %s | Status: %s | Count: %d\n", 
                        $stat['date'], 
                        $stat['status'], 
                        $stat['total_emails']
                    );
                }
            }
            break;
            
        default:
            echo "Usage: php EmailNotificationsMigration.php [up|down|status]\n";
            echo "  up     - Create email notifications table\n";
            echo "  down   - Drop email notifications table\n";
            echo "  status - Show email sending statistics\n";
    }
}
