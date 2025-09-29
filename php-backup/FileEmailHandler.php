<?php

// Prevent direct access
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    http_response_code(404);
    exit();
}

/**
 * File-Based Email Handler
 * Saves emails as HTML files instead of sending via SMTP
 * Perfect for development when you don't want to configure email servers
 */

class FileEmailHandler {
    private $emailDir;
    private $logger;
    
    public function __construct($emailDir = null, $logger = null) {
        $this->emailDir = $emailDir ?: __DIR__ . '/email-files';
        $this->logger = $logger ?: Logger::getInstance();
        
        // Create email directory if it doesn't exist
        if (!is_dir($this->emailDir)) {
            mkdir($this->emailDir, 0755, true);
        }
    }
    
    /**
     * Save email as file instead of sending
     */
    public function sendEmail($recipients, $subject, $htmlMessage, $textMessage = null, $emailType = 'general', $additionalHeaders = []) {
        $startTime = microtime(true);
        $emailId = $this->generateEmailId();
        
        $this->logInfo("File email save initiated", [
            'email_id' => $emailId,
            'type' => $emailType,
            'recipients' => is_array($recipients) ? $recipients : [$recipients],
            'recipient_count' => is_array($recipients) ? count($recipients) : 1,
            'subject' => $subject,
            'has_html' => !empty($htmlMessage),
            'has_text' => !empty($textMessage),
            'message_size_html' => strlen($htmlMessage ?? ''),
            'message_size_text' => strlen($textMessage ?? ''),
            'email_directory' => $this->emailDir
        ]);
        
        try {
            $recipients = is_array($recipients) ? $recipients : [$recipients];
            $filesCreated = [];
            
            foreach ($recipients as $index => $recipient) {
                $filename = sprintf(
                    '%s/%s_%s_%03d_%s.html',
                    $this->emailDir,
                    $emailId,
                    $emailType,
                    $index + 1,
                    $this->sanitizeFilename($recipient)
                );
                
                $emailContent = $this->buildEmailFile($recipient, $subject, $htmlMessage, $textMessage, $emailType, $additionalHeaders, $emailId);
                
                $bytesWritten = file_put_contents($filename, $emailContent);
                
                if ($bytesWritten !== false) {
                    $filesCreated[] = $filename;
                    
                    $this->logInfo("Email file created successfully", [
                        'email_id' => $emailId,
                        'recipient' => $recipient,
                        'filename' => basename($filename),
                        'file_size' => $bytesWritten,
                        'full_path' => $filename
                    ]);
                } else {
                    throw new Exception("Failed to create email file for: $recipient");
                }
            }
            
            $endTime = microtime(true);
            $duration = round(($endTime - $startTime) * 1000, 2);
            
            $this->logInfo("File email save completed", [
                'email_id' => $emailId,
                'total_recipients' => count($recipients),
                'files_created' => count($filesCreated),
                'duration_ms' => $duration,
                'method' => 'file_system',
                'success_rate' => '100%'
            ]);
            
            return [
                'success' => true,
                'email_id' => $emailId,
                'method' => 'file',
                'total_sent' => count($recipients),
                'files_created' => count($filesCreated),
                'file_paths' => $filesCreated,
                'duration_ms' => $duration
            ];
            
        } catch (Exception $e) {
            $endTime = microtime(true);
            $duration = round(($endTime - $startTime) * 1000, 2);
            
            $this->logError("File email save failed", [
                'email_id' => $emailId,
                'error' => $e->getMessage(),
                'duration_ms' => $duration,
                'recipients' => $recipients,
                'subject' => $subject
            ]);
            
            return [
                'success' => false,
                'email_id' => $emailId,
                'method' => 'file',
                'error' => $e->getMessage(),
                'duration_ms' => $duration
            ];
        }
    }
    
    /**
     * Build HTML file content for email preview
     */
    private function buildEmailFile($recipient, $subject, $htmlMessage, $textMessage, $emailType, $headers, $emailId) {
        $timestamp = date('Y-m-d H:i:s');
        
        $html = "<!DOCTYPE html>\n<html lang=\"en\">\n<head>\n";
        $html .= "<meta charset=\"UTF-8\">\n";
        $html .= "<meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\n";
        $html .= "<title>Email Preview: {$subject}</title>\n";
        $html .= "<style>
            body { 
                font-family: Arial, sans-serif; 
                margin: 0; 
                padding: 20px; 
                background-color: #f4f4f4; 
            }
            .container {
                max-width: 800px;
                margin: 0 auto;
                background: white;
                border-radius: 8px;
                overflow: hidden;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            }
            .email-header { 
                background: #2c3e50; 
                color: white;
                padding: 20px; 
            }
            .email-header h1 {
                margin: 0;
                font-size: 24px;
            }
            .meta { 
                color: #ecf0f1; 
                font-size: 14px; 
                margin-top: 10px;
                line-height: 1.5;
            }
            .email-body { 
                padding: 30px; 
                background: white;
            }
            .text-version {
                background: #f8f9fa;
                padding: 20px;
                border-top: 2px solid #e9ecef;
                margin-top: 20px;
            }
            .text-version h3 {
                margin-top: 0;
                color: #495057;
            }
            pre {
                background: #f1f3f4;
                padding: 15px;
                border-radius: 4px;
                overflow-x: auto;
                white-space: pre-wrap;
                font-family: 'Courier New', monospace;
            }
            .badge {
                display: inline-block;
                background: #28a745;
                color: white;
                padding: 4px 8px;
                border-radius: 12px;
                font-size: 12px;
                font-weight: bold;
                text-transform: uppercase;
            }
            .info-grid {
                display: grid;
                grid-template-columns: auto 1fr;
                gap: 8px 15px;
                margin-top: 15px;
            }
            .info-label {
                font-weight: bold;
                color: #bdc3c7;
            }
        </style>\n";
        $html .= "</head>\n<body>\n";
        
        $html .= "<div class='container'>\n";
        
        // Header section
        $html .= "<div class='email-header'>\n";
        $html .= "<h1>📧 Email Preview <span class='badge'>File Mode</span></h1>\n";
        $html .= "<div class='info-grid'>\n";
        $html .= "<div class='info-label'>To:</div><div>{$recipient}</div>\n";
        $html .= "<div class='info-label'>Subject:</div><div>{$subject}</div>\n";
        $html .= "<div class='info-label'>Type:</div><div>{$emailType}</div>\n";
        $html .= "<div class='info-label'>Email ID:</div><div>{$emailId}</div>\n";
        $html .= "<div class='info-label'>Generated:</div><div>{$timestamp}</div>\n";
        $html .= "<div class='info-label'>Method:</div><div>File System (No SMTP)</div>\n";
        $html .= "</div>\n";
        $html .= "</div>\n";
        
        // Email body
        $html .= "<div class='email-body'>\n";
        if ($htmlMessage) {
            $html .= $htmlMessage;
        } else {
            $html .= "<h3>Text Content:</h3>";
            $html .= "<pre>" . htmlspecialchars($textMessage ?: 'No content provided') . "</pre>";
        }
        $html .= "</div>\n";
        
        // Text version if both HTML and text are provided
        if ($textMessage && $htmlMessage) {
            $html .= "<div class='text-version'>\n";
            $html .= "<h3>📄 Plain Text Version</h3>\n";
            $html .= "<pre>" . htmlspecialchars($textMessage) . "</pre>\n";
            $html .= "</div>\n";
        }
        
        $html .= "</div>\n";
        $html .= "</body>\n</html>";
        
        return $html;
    }
    
    /**
     * Generate unique email ID
     */
    private function generateEmailId() {
        return 'EMAIL_' . date('Ymd_His_') . strtoupper(substr(md5(uniqid()), 0, 8));
    }
    
    /**
     * Sanitize filename for filesystem
     */
    private function sanitizeFilename($email) {
        return preg_replace('/[^a-zA-Z0-9@._-]/', '_', $email);
    }
    
    /**
     * Log info message
     */
    private function logInfo($message, $context = []) {
        if ($this->logger) {
            $this->logger->info($message, $context, 'FILE_EMAIL');
        }
    }
    
    /**
     * Log error message
     */
    private function logError($message, $context = []) {
        if ($this->logger) {
            $this->logger->error($message, $context, 'FILE_EMAIL');
        }
    }
    
    /**
     * Get list of saved email files
     */
    public function getSavedEmails($limit = 50) {
        if (!is_dir($this->emailDir)) {
            return [];
        }
        
        $files = glob($this->emailDir . '/*.html');
        
        // Sort by modification time (newest first)
        usort($files, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });
        
        $emails = [];
        $count = 0;
        
        foreach ($files as $file) {
            if ($count >= $limit) break;
            
            $filename = basename($file);
            $parts = explode('_', $filename);
            
            $emails[] = [
                'filename' => $filename,
                'full_path' => $file,
                'size' => filesize($file),
                'created' => date('Y-m-d H:i:s', filemtime($file)),
                'email_id' => $parts[1] ?? 'unknown',
                'type' => $parts[2] ?? 'unknown',
                'url' => 'file://' . str_replace('\\', '/', $file)
            ];
            
            $count++;
        }
        
        return $emails;
    }
}
