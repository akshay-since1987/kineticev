<?php

// Prevent direct access
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    http_response_code(404);
    exit();
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class MailngxEmailHandler {
    private $config;
    private $logger;
    protected $mailer; // Changed to protected so we can access it in test scripts

    public function __construct() {
        $this->config = include __DIR__ . '/config.php';
        $this->logger = Logger::getInstance();
        $this->initializeMailer();
    }

    /**
     * Enable debug mode for SMTP communication
     */
    public function enableDebug($level = 2) {
        $this->mailer->SMTPDebug = $level;
        $this->mailer->Debugoutput = function($str, $level) {
            error_log("[SMTP DEBUG] [$level] $str");
        };
    }

    private function initializeMailer() {
        $mailConfig = $this->config['mailngx'] ?? null;
        if (!$mailConfig || empty($mailConfig['host']) || empty($mailConfig['username'])) {
            throw new Exception('Mailngx configuration missing in config.php');
        }

        // Initialize PHPMailer
        $this->mailer = new PHPMailer(true);
        
        // Enable debug output by default in development
        if ($this->config['development_mode'] ?? false) {
            $this->mailer->SMTPDebug = 2;
            $this->mailer->Debugoutput = function($str, $level) {
                error_log("[SMTP DEBUG] [$level] $str");
            };
        }

        $this->mailer = new PHPMailer(true);
        
        // Server settings
        $this->mailer->isSMTP();
        $this->mailer->Host = $mailConfig['host'];
        $this->mailer->SMTPAuth = true;
        $this->mailer->Username = $mailConfig['username'];
        $this->mailer->Password = $mailConfig['password'];
        $this->mailer->SMTPSecure = $mailConfig['encryption'] ?? PHPMailer::ENCRYPTION_STARTTLS;
        $this->mailer->Port = $mailConfig['port'] ?? 587;
        
        // Default sender
        $this->mailer->setFrom($mailConfig['from_email'], 'KineticEV');
        
        // Common settings
        $this->mailer->CharSet = 'UTF-8';
        $this->mailer->Encoding = 'base64';
        $this->mailer->isHTML(true);
    }

    /**
     * Send email using Mailngx SMTP
     */
    public function sendEmail($to, $subject, $htmlMessage, $textMessage = '', $isHtml = true, $from = null) {
        try {
            $startTime = microtime(true);
            
            // Reset all recipients
            $this->mailer->clearAllRecipients();
            
            // Set custom from if provided
            if ($from) {
                if (is_array($from) && isset($from['email'], $from['name'])) {
                    $this->mailer->setFrom($from['email'], $from['name']);
                } else if (preg_match('/(.*?)\s*<(.+?)>/', $from, $matches)) {
                    // Handle "Name <email>" format
                    $this->mailer->setFrom($matches[2], trim($matches[1]));
                } else {
                    $this->mailer->setFrom($from);
                }
            }

            // Add recipients (supports single email or array of emails)
            if (is_array($to)) {
                foreach ($to as $recipient) {
                    $this->mailer->addAddress($recipient);
                }
            } else {
                $this->mailer->addAddress($to);
            }

            // Set subject
            $this->mailer->Subject = $subject;

            // Set content
            if ($isHtml) {
                $this->mailer->isHTML(true);
                $this->mailer->Body = $htmlMessage;
                if ($textMessage) {
                    $this->mailer->AltBody = $textMessage;
                }
            } else {
                $this->mailer->isHTML(false);
                $this->mailer->Body = $textMessage ?: strip_tags($htmlMessage);
            }

            // Send the email
            $result = $this->mailer->send();
            $endTime = microtime(true);
            
            // Log success
            $this->logger->info('[MAILNGX_EMAIL] Mail sent successfully', [
                'to' => $to,
                'subject' => $subject,
                'from' => $from ?: $this->config['mailngx']['from_email'],
                'isHtml' => $isHtml,
                'duration' => round($endTime - $startTime, 4),
                'timestamp' => date('Y-m-d H:i:s')
            ], 'email_logs.txt');

            return $result;
        } catch (Exception $e) {
            // Log detailed error information
            $errorDetails = [
                'error' => $e->getMessage(),
                'exception' => (string)$e,
                'trace' => $e->getTraceAsString(),
                'to' => $to,
                'subject' => $subject,
                'from' => $from ?: $this->config['mailngx']['from_email'],
                'isHtml' => $isHtml,
                'smtp_debug' => $this->mailer->SMTPDebug,
                'last_smtp_error' => $this->mailer->ErrorInfo,
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
            $this->logger->error('[MAILNGX_EMAIL] Mail send failed', $errorDetails, 'email_logs.txt');
            error_log(print_r($errorDetails, true));
            
            return false;
        }
    }
}