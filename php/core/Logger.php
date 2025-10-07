<?php

namespace Kinetic\Core;

class Logger {
    private static $instance = null;
    private $logPath;
    private $logLevel;
    
    private const LEVEL_DEBUG = 100;
    private const LEVEL_INFO = 200;
    private const LEVEL_WARNING = 300;
    private const LEVEL_ERROR = 400;
    
    private function __construct() {
        $config = Config::getInstance();
        $this->logPath = $config->get('log.path', __DIR__ . '/../../logs');
        $this->logLevel = $config->get('log.level', self::LEVEL_INFO);
        
        if (!file_exists($this->logPath)) {
            mkdir($this->logPath, 0755, true);
        }
    }
    
    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function debug(string $message, array $context = []): void {
        $this->log('DEBUG', $message, $context, self::LEVEL_DEBUG);
    }
    
    public function info(string $message, array $context = []): void {
        $this->log('INFO', $message, $context, self::LEVEL_INFO);
    }
    
    public function warning(string $message, array $context = []): void {
        $this->log('WARNING', $message, $context, self::LEVEL_WARNING);
    }
    
    public function error(string $message, array $context = []): void {
        $this->log('ERROR', $message, $context, self::LEVEL_ERROR);
    }
    
    private function log(string $level, string $message, array $context, int $levelNum): void {
        if ($levelNum < $this->logLevel) {
            return;
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $contextJson = empty($context) ? '' : ' ' . json_encode($context);
        $logMessage = "[{$timestamp}] {$level}: {$message}{$contextJson}" . PHP_EOL;
        
        $filename = $this->logPath . '/' . date('Y-m-d') . '.log';
        file_put_contents($filename, $logMessage, FILE_APPEND | LOCK_EX);
    }
}