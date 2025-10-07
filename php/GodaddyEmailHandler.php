<?php

// Prevent direct access
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    http_response_code(404);
    exit();
}

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config.php';

class GodaddyEmailHandler {
    private $host;
    private $username;
    private $password;
    private $port;
    private $from_email;
    private $config;

    public function __construct() {
        $this->config = include __DIR__ . '/config.php';
        $gdConfig = $this->config['gd_email'] ?? null;
        if (!$gdConfig || empty($gdConfig['host']) || empty($gdConfig['username']) || empty($gdConfig['password'])) {
            throw new Exception('Godaddy Email configuration missing in config.php');
        }
        
        $this->host = $gdConfig['host'];
        $this->username = $gdConfig['username'];
        $this->password = $gdConfig['password'];
        $this->port = $gdConfig['port'] ?? 587;
        $this->from_email = $gdConfig['from_email'] ?? 'info@kineticev.in';
    }

    /**
     * Log system info for email operations
     * @param string|int $referenceId Optional reference ID (e.g., contact_id, test_ride_id)
     * @param string $message Optional message
     * @param array $context Optional additional context
     */
    public function logSystemInfo($referenceId = null, $message = '', $context = []) {
        if (!class_exists('Logger')) {
            require_once __DIR__ . '/Logger.php';
        }
        $logger = Logger::getInstance();
        $info = array_merge([
            'php_version' => PHP_VERSION,
            'os' => PHP_OS,
            'email_service' => 'GD',
            'memory_usage' => memory_get_usage(true),
            'timestamp' => date('Y-m-d H:i:s'),
            'reference_id' => $referenceId,
            'server' => $_SERVER['SERVER_NAME'] ?? 'cli',
            'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
        ], $context);
        
        $logger->info('[GODADDY_EMAIL_HANDLER] ' . $message, $info, 'email_logs.txt');
    }

    /**
     * Send an email using SMTP via Godaddy
     * @param string $to Recipient email
     * @param string $subject Email subject
     * @param string $message Email body (HTML)
     * @param array $headers Optional additional headers
     * @param array $attachments Optional attachments array
     * @return bool Success status
     */
    public function sendEmail($to, $subject, $message, $headers = [], $attachments = []) {
        require_once __DIR__ . '/vendor/phpmailer/phpmailer/src/PHPMailer.php';
        require_once __DIR__ . '/vendor/phpmailer/phpmailer/src/SMTP.php';
        require_once __DIR__ . '/vendor/phpmailer/phpmailer/src/Exception.php';

        $mail = new PHPMailer\PHPMailer\PHPMailer(true);

        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = $this->host;
            
            // Different settings for local XAMPP testing
            if ($this->config['gd_email']['is_local'] ?? false) {
                $mail->SMTPAuth = false;
                $mail->SMTPAutoTLS = false;
            } else {
                $mail->SMTPAuth = true;
                $mail->Username = $this->username;
                $mail->Password = $this->password;
                $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            }
            $mail->Port = $this->port;

            // Recipients
            $mail->setFrom($this->from_email);
            $mail->addAddress($to);

            // Add custom headers
            foreach ($headers as $name => $value) {
                $mail->addCustomHeader($name, $value);
            }

            // Add attachments
            foreach ($attachments as $attachment) {
                if (isset($attachment['path'])) {
                    $mail->addAttachment(
                        $attachment['path'],
                        $attachment['name'] ?? '',
                        $attachment['encoding'] ?? 'base64',
                        $attachment['type'] ?? ''
                    );
                }
            }

            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $message;
            $mail->AltBody = strip_tags($message);

            $mail->send();
            $this->logSystemInfo(null, 'Email sent successfully', [
                'to' => $to,
                'subject' => $subject,
                'status' => 'success'
            ]);
            return true;
        } catch (Exception $e) {
            $errorMsg = $mail->ErrorInfo ?? $e->getMessage();
            error_log("Email sending failed: {$errorMsg}");
            $this->logSystemInfo(null, 'Email sending failed', [
                'to' => $to,
                'subject' => $subject,
                'status' => 'error',
                'error_message' => $errorMsg,
                'error_code' => $e->getCode(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine()
            ]);
            return false;
        }
    }
}