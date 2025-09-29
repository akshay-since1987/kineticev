<?php

// Prevent direct access
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    http_response_code(404);
    exit();
}

// EmailHandler.php
// Sends emails using Amazon AWS SES

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config.php';

use Aws\Ses\SesClient;
use Aws\Exception\AwsException;

class EmailHandler {
    private $sesClient;
    private $config;

    public function __construct() {
        $this->config = include __DIR__ . '/config.php';
        $awsConfig = $this->config['aws_ses'] ?? null;
        if (!$awsConfig || empty($awsConfig['access_key']) || empty($awsConfig['secret_key']) || empty($awsConfig['region'])) {
            throw new Exception('AWS SES configuration missing in config.php');
        }
        $this->sesClient = new SesClient([
            'version' => '2010-12-01',
            'region'  => $awsConfig['region'],
            'credentials' => [
                'key'    => $awsConfig['access_key'],
                'secret' => $awsConfig['secret_key'],
            ],
            'suppress_php_deprecation_warning' => true,
        ]);
    }

    /**
     * Log system info for email operations
     * @param string|int $referenceId Optional reference ID (e.g., contact_id, test_ride_id)
     */
    public function logSystemInfo($referenceId = null) {
        if (!class_exists('Logger')) {
            require_once __DIR__ . '/Logger.php';
        }
        $logger = Logger::getInstance();
        $info = [
            'php_version' => PHP_VERSION,
            'os' => PHP_OS,
            'memory_usage' => memory_get_usage(true),
            'timestamp' => date('Y-m-d H:i:s'),
            'reference_id' => $referenceId,
            'server' => $_SERVER['SERVER_NAME'] ?? 'cli',
            'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
        ];
        $logger->info('[EMAIL_HANDLER] System info', $info, 'email_logs.txt');
    }

    /**
     * Send an email using AWS SES
     * @param string $to Recipient email address
     * @param string $subject Email subject
     * @param string $body Email body (HTML or plain text)
     * @param string $from Sender email address (must be verified in SES)
     * @param bool $isHtml Whether the body is HTML (default: true)
     * @return bool
     */
    public function sendEmail($to, $subject, $body, $from, $isHtml = true) {
        if (!class_exists('Logger')) {
            require_once __DIR__ . '/Logger.php';
        }
        $logger = Logger::getInstance();
        $logger->info('[SES_EMAIL] sendEmail called', [
            'to' => $to,
            'subject' => $subject,
            'from' => $from,
            'isHtml' => $isHtml,
            'timestamp' => date('Y-m-d H:i:s')
        ], 'email_logs.txt');
        try {
            $logger->info('[SES_EMAIL] About to call SES sendEmail', [
                'to' => $to,
                'subject' => $subject,
                'from' => $from,
                'isHtml' => $isHtml,
                'timestamp' => date('Y-m-d H:i:s')
            ], 'email_logs.txt');
            $result = $this->sesClient->sendEmail([
                'Source' => $from,
                'Destination' => [
                    'ToAddresses' => [$to],
                ],
                'Message' => [
                    'Subject' => [
                        'Data' => $subject,
                        'Charset' => 'UTF-8',
                    ],
                    'Body' => $isHtml ? [
                        'Html' => [
                            'Data' => $body,
                            'Charset' => 'UTF-8',
                        ],
                    ] : [
                        'Text' => [
                            'Data' => $body,
                            'Charset' => 'UTF-8',
                        ],
                    ],
                ],
            ]);
            $logger->info('[SES_EMAIL] SES sendEmail call completed', [
                'to' => $to,
                'subject' => $subject,
                'from' => $from,
                'isHtml' => $isHtml,
                'result' => $result,
                'timestamp' => date('Y-m-d H:i:s')
            ], 'email_logs.txt');
            return isset($result['MessageId']);
        } catch (AwsException $e) {
            $logger->error('[SES_EMAIL] Email send failed', [
                'error' => $e->getMessage(),
                'exception' => (string)$e,
                'to' => $to,
                'subject' => $subject,
                'from' => $from,
                'isHtml' => $isHtml,
                'timestamp' => date('Y-m-d H:i:s')
            ], 'email_logs.txt');
            return false;
        }
    }
}
