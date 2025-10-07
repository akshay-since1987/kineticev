<?php

namespace Kinetic\Core\Performance;

class PerformanceProfiler {
    private static $instance = null;
    private $metrics = [];
    private $startTimes = [];
    private $logger;
    private $config;
    private $dbQueries = [];
    private $cacheOperations = [];
    
    private function __construct() {
        $this->logger = \Kinetic\Core\Logger::getInstance();
        $this->config = \Kinetic\Core\Config::getInstance();
        $this->initializeMetrics();
    }
    
    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function initializeMetrics(): void {
        $this->metrics = [
            'requests' => [
                'total' => 0,
                'successful' => 0,
                'failed' => 0,
                'response_times' => []
            ],
            'database' => [
                'queries' => 0,
                'total_time' => 0,
                'slow_queries' => []
            ],
            'cache' => [
                'hits' => 0,
                'misses' => 0,
                'total_operations' => 0
            ],
            'memory' => [
                'peak' => 0,
                'current' => 0
            ],
            'system' => [
                'cpu_usage' => 0,
                'disk_io' => [
                    'reads' => 0,
                    'writes' => 0
                ]
            ]
        ];
    }
    
    public function startMeasurement(string $key): void {
        $this->startTimes[$key] = [
            'time' => microtime(true),
            'memory' => memory_get_usage(true)
        ];
    }
    
    public function endMeasurement(string $key): array {
        if (!isset($this->startTimes[$key])) {
            throw new \RuntimeException("No measurement started for key: {$key}");
        }
        
        $end = microtime(true);
        $endMemory = memory_get_usage(true);
        
        $duration = $end - $this->startTimes[$key]['time'];
        $memoryUsed = $endMemory - $this->startTimes[$key]['memory'];
        
        $metrics = [
            'duration' => $duration,
            'memory_used' => $memoryUsed,
            'peak_memory' => memory_get_peak_usage(true),
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        $this->logMetrics($key, $metrics);
        
        unset($this->startTimes[$key]);
        
        return $metrics;
    }
    
    public function recordDatabaseQuery(string $query, float $duration): void {
        $this->metrics['database']['queries']++;
        $this->metrics['database']['total_time'] += $duration;
        
        // Record slow queries
        if ($duration > $this->config->get('performance.slow_query_threshold', 1.0)) {
            $this->metrics['database']['slow_queries'][] = [
                'query' => $query,
                'duration' => $duration,
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
            $this->logger->warning('Slow query detected', [
                'query' => $query,
                'duration' => $duration
            ]);
        }
        
        $this->dbQueries[] = [
            'query' => $query,
            'duration' => $duration,
            'timestamp' => microtime(true)
        ];
    }
    
    public function recordCacheOperation(string $operation, string $key, bool $success): void {
        $this->metrics['cache']['total_operations']++;
        
        if ($operation === 'get') {
            if ($success) {
                $this->metrics['cache']['hits']++;
            } else {
                $this->metrics['cache']['misses']++;
            }
        }
        
        $this->cacheOperations[] = [
            'operation' => $operation,
            'key' => $key,
            'success' => $success,
            'timestamp' => microtime(true)
        ];
    }
    
    public function recordRequest(string $path, int $statusCode, float $duration): void {
        $this->metrics['requests']['total']++;
        
        if ($statusCode >= 200 && $statusCode < 400) {
            $this->metrics['requests']['successful']++;
        } else {
            $this->metrics['requests']['failed']++;
        }
        
        $this->metrics['requests']['response_times'][] = [
            'path' => $path,
            'status' => $statusCode,
            'duration' => $duration
        ];
    }
    
    public function updateSystemMetrics(): void {
        // Update memory metrics
        $this->metrics['memory']['current'] = memory_get_usage(true);
        $this->metrics['memory']['peak'] = memory_get_peak_usage(true);
        
        // Update CPU usage if available
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            $this->metrics['system']['cpu_usage'] = $load[0];
        }
    }
    
    public function getMetrics(): array {
        $this->updateSystemMetrics();
        
        return [
            'metrics' => $this->metrics,
            'detailed' => [
                'database_queries' => $this->dbQueries,
                'cache_operations' => $this->cacheOperations
            ]
        ];
    }
    
    public function getCacheHitRate(): float {
        $total = $this->metrics['cache']['hits'] + $this->metrics['cache']['misses'];
        if ($total === 0) {
            return 0.0;
        }
        return ($this->metrics['cache']['hits'] / $total) * 100;
    }
    
    public function getAverageResponseTime(): float {
        $times = array_column($this->metrics['requests']['response_times'], 'duration');
        if (empty($times)) {
            return 0.0;
        }
        return array_sum($times) / count($times);
    }
    
    private function logMetrics(string $key, array $metrics): void {
        // Log if duration exceeds threshold
        if ($metrics['duration'] > $this->config->get('performance.log_threshold', 1.0)) {
            $this->logger->info('Performance measurement', [
                'key' => $key,
                'metrics' => $metrics
            ]);
        }
        
        // Alert if duration exceeds critical threshold
        if ($metrics['duration'] > $this->config->get('performance.alert_threshold', 5.0)) {
            $this->logger->error('Performance critical', [
                'key' => $key,
                'metrics' => $metrics
            ]);
            
            // Send alert to monitoring system
            $this->sendAlert($key, $metrics);
        }
    }
    
    private function sendAlert(string $key, array $metrics): void {
        // Implementation for sending alerts (email, Slack, etc.)
        $alertService = new \Kinetic\Core\AlertService();
        $alertService->send('performance_alert', [
            'message' => "Performance threshold exceeded for {$key}",
            'metrics' => $metrics
        ]);
    }
    
    public function reset(): void {
        $this->initializeMetrics();
        $this->startTimes = [];
        $this->dbQueries = [];
        $this->cacheOperations = [];
    }
    
    public function generateReport(): array {
        return [
            'summary' => [
                'total_requests' => $this->metrics['requests']['total'],
                'success_rate' => ($this->metrics['requests']['successful'] / max(1, $this->metrics['requests']['total'])) * 100,
                'average_response_time' => $this->getAverageResponseTime(),
                'cache_hit_rate' => $this->getCacheHitRate(),
                'memory_usage' => [
                    'current' => $this->formatBytes($this->metrics['memory']['current']),
                    'peak' => $this->formatBytes($this->metrics['memory']['peak'])
                ]
            ],
            'database' => [
                'total_queries' => $this->metrics['database']['queries'],
                'average_query_time' => $this->metrics['database']['queries'] > 0 
                    ? $this->metrics['database']['total_time'] / $this->metrics['database']['queries'] 
                    : 0,
                'slow_queries_count' => count($this->metrics['database']['slow_queries'])
            ],
            'cache' => [
                'hit_rate' => $this->getCacheHitRate(),
                'total_operations' => $this->metrics['cache']['total_operations']
            ],
            'system' => [
                'cpu_usage' => $this->metrics['system']['cpu_usage'],
                'memory_usage_percentage' => ($this->metrics['memory']['current'] / PHP_INT_MAX) * 100
            ]
        ];
    }
    
    private function formatBytes($bytes): string {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        return round($bytes / pow(1024, $pow), 2) . ' ' . $units[$pow];
    }
}