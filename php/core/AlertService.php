<?php

namespace Kinetic\Core;

class AlertService {
    private $config;
    private $logger;
    
    public function __construct() {
        $this->config = Config::getInstance();
        $this->logger = Logger::getInstance();
    }
    
    public function send(string $type, array $data): void {
        if (!$this->config->get('alert.enabled', true)) {
            return;
        }
        
        switch ($type) {
            case 'performance_alert':
                $this->handlePerformanceAlert($data);
                break;
            case 'security_alert':
                $this->handleSecurityAlert($data);
                break;
            case 'error_alert':
                $this->handleErrorAlert($data);
                break;
            default:
                $this->handleGenericAlert($type, $data);
        }
    }
    
    private function handlePerformanceAlert(array $data): void {
        $message = $data['message'];
        $metrics = $data['metrics'];
        
        // Log the alert
        $this->logger->error('Performance Alert', [
            'message' => $message,
            'metrics' => $metrics
        ]);
        
        // Send email notification
        $this->sendEmail(
            $this->config->get('alert.email'),
            'Performance Alert: ' . $message,
            $this->formatMetricsForEmail($metrics)
        );
        
        // Send Slack notification if configured
        if ($webhook = $this->config->get('alert.slack_webhook')) {
            $this->sendSlackNotification($webhook, [
                'text' => "🚨 *Performance Alert*\n{$message}",
                'attachments' => [
                    [
                        'color' => 'danger',
                        'fields' => $this->formatMetricsForSlack($metrics)
                    ]
                ]
            ]);
        }
    }
    
    private function handleSecurityAlert(array $data): void {
        // Implementation for security alerts
        $this->logger->error('Security Alert', $data);
        
        // Send immediate notification
        $this->sendUrgentNotification($data);
    }
    
    private function handleErrorAlert(array $data): void {
        // Implementation for error alerts
        $this->logger->error('Error Alert', $data);
        
        if ($this->isUrgent($data)) {
            $this->sendUrgentNotification($data);
        } else {
            $this->sendNormalNotification($data);
        }
    }
    
    private function handleGenericAlert(string $type, array $data): void {
        $this->logger->warning('Generic Alert', [
            'type' => $type,
            'data' => $data
        ]);
        
        $this->sendNormalNotification([
            'type' => $type,
            'data' => $data
        ]);
    }
    
    private function sendEmail(string $to, string $subject, string $body): void {
        // Email sending implementation
        $headers = [
            'From: alerts@kineticeducation.com',
            'Content-Type: text/html; charset=UTF-8'
        ];
        
        mail($to, $subject, $body, implode("\r\n", $headers));
    }
    
    private function sendSlackNotification(string $webhook, array $payload): void {
        $ch = curl_init($webhook);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if ($httpCode !== 200) {
            $this->logger->error('Failed to send Slack notification', [
                'http_code' => $httpCode,
                'response' => $result
            ]);
        }
        
        curl_close($ch);
    }
    
    private function formatMetricsForEmail(array $metrics): string {
        $html = '<h2>Performance Metrics</h2>';
        $html .= '<table>';
        
        foreach ($metrics as $key => $value) {
            $html .= '<tr>';
            $html .= '<th>' . htmlspecialchars($key) . '</th>';
            $html .= '<td>' . htmlspecialchars(is_array($value) ? json_encode($value) : $value) . '</td>';
            $html .= '</tr>';
        }
        
        $html .= '</table>';
        return $html;
    }
    
    private function formatMetricsForSlack(array $metrics): array {
        $fields = [];
        
        foreach ($metrics as $key => $value) {
            $fields[] = [
                'title' => $key,
                'value' => is_array($value) ? json_encode($value) : (string)$value,
                'short' => true
            ];
        }
        
        return $fields;
    }
    
    private function isUrgent(array $data): bool {
        // Define urgency criteria
        $urgentKeywords = ['critical', 'urgent', 'emergency'];
        $message = strtolower($data['message'] ?? '');
        
        foreach ($urgentKeywords as $keyword) {
            if (strpos($message, $keyword) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    private function sendUrgentNotification(array $data): void {
        // Send to all urgent channels
        $urgentEmails = $this->config->get('alert.urgent_emails', []);
        foreach ($urgentEmails as $email) {
            $this->sendEmail(
                $email,
                'URGENT: ' . ($data['message'] ?? 'Alert'),
                $this->formatAlertForEmail($data)
            );
        }
        
        // Send SMS if configured
        if ($smsConfig = $this->config->get('alert.sms')) {
            $this->sendSMS(
                $smsConfig['number'],
                'URGENT: ' . ($data['message'] ?? 'Alert')
            );
        }
    }
    
    private function sendNormalNotification(array $data): void {
        // Send to normal notification channels
        $this->sendEmail(
            $this->config->get('alert.email'),
            'Alert: ' . ($data['message'] ?? 'Notification'),
            $this->formatAlertForEmail($data)
        );
    }
    
    private function formatAlertForEmail(array $data): string {
        $html = '<h2>Alert Details</h2>';
        $html .= '<table>';
        
        foreach ($data as $key => $value) {
            $html .= '<tr>';
            $html .= '<th>' . htmlspecialchars($key) . '</th>';
            $html .= '<td>' . htmlspecialchars(is_array($value) ? json_encode($value) : $value) . '</td>';
            $html .= '</tr>';
        }
        
        $html .= '</table>';
        return $html;
    }
    
    private function sendSMS(string $number, string $message): void {
        // SMS sending implementation
        // This would typically integrate with an SMS service provider
        // For now, just log the attempt
        $this->logger->info('SMS Alert', [
            'number' => $number,
            'message' => $message
        ]);
    }
}