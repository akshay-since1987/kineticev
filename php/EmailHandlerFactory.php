<?php

// Prevent direct access
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    http_response_code(404);
    exit();
}

require_once __DIR__ . '/EmailHandler.php';
require_once __DIR__ . '/GodaddyEmailHandler.php';

class EmailHandlerFactory {
    public static function getEmailHandler() {
        $config = include __DIR__ . '/config.php';
        $emailerService = $config['emailerService'] ?? 'AWS';
        
        switch (strtoupper($emailerService)) {
            case 'GD':
                return new GodaddyEmailHandler();
            case 'AWS':
            default:
                return new EmailHandler();
        }
    }
}