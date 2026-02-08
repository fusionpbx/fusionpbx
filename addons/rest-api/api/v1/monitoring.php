<?php
/**
 * API Monitoring Helper Functions
 *
 * Provides request tracking, metrics collection, and error logging
 * for FusionPBX API endpoints.
 */

// Metrics storage file
define('METRICS_FILE', '/tmp/fusionpbx_api_metrics.json');

/**
 * Initialize metrics file if it doesn't exist
 */
function init_metrics_file() {
    if (!file_exists(METRICS_FILE)) {
        $initial_data = [
            'total_requests' => 0,
            'error_count' => 0,
            'latency_sum' => 0.0,
            'latency_count' => 0,
            'errors' => [],
            'last_updated' => date('Y-m-d H:i:s')
        ];
        file_put_contents(METRICS_FILE, json_encode($initial_data, JSON_PRETTY_PRINT));
        chmod(METRICS_FILE, 0600);
    }
}

/**
 * Read current metrics from file
 *
 * @return array Current metrics data
 */
function read_metrics() {
    init_metrics_file();
    $data = file_get_contents(METRICS_FILE);
    return json_decode($data, true) ?: [
        'total_requests' => 0,
        'error_count' => 0,
        'latency_sum' => 0.0,
        'latency_count' => 0,
        'errors' => [],
        'last_updated' => date('Y-m-d H:i:s')
    ];
}

/**
 * Write metrics to file with file locking
 *
 * @param array $metrics Metrics data to write
 * @return bool Success status
 */
function write_metrics($metrics) {
    $metrics['last_updated'] = date('Y-m-d H:i:s');
    $fp = fopen(METRICS_FILE, 'c');
    if (!$fp) {
        return false;
    }

    if (flock($fp, LOCK_EX)) {
        ftruncate($fp, 0);
        fwrite($fp, json_encode($metrics, JSON_PRETTY_PRINT));
        fflush($fp);
        flock($fp, LOCK_UN);
    }

    fclose($fp);
    return true;
}

/**
 * Record the start of an API request
 *
 * Sets the request start time in globals for later duration calculation.
 *
 * @return float Request start timestamp
 */
function log_request_start() {
    $start_time = microtime(true);
    $GLOBALS['request_start_time'] = $start_time;

    // Increment total request counter
    $metrics = read_metrics();
    $metrics['total_requests']++;
    write_metrics($metrics);

    return $start_time;
}

/**
 * Record the end of an API request
 *
 * Calculates request duration and updates latency metrics.
 *
 * @param int $status_code HTTP response status code
 * @return array Request timing information
 */
function log_request_end($status_code) {
    $end_time = microtime(true);
    $start_time = $GLOBALS['request_start_time'] ?? $end_time;
    $duration = ($end_time - $start_time) * 1000; // Convert to milliseconds

    // Update latency metrics
    $metrics = read_metrics();
    $metrics['latency_sum'] += $duration;
    $metrics['latency_count']++;

    // Track errors (4xx and 5xx status codes)
    if ($status_code >= 400) {
        $metrics['error_count']++;
    }

    write_metrics($metrics);

    return [
        'start_time' => $start_time,
        'end_time' => $end_time,
        'duration_ms' => round($duration, 2),
        'status_code' => $status_code
    ];
}

/**
 * Get current API metrics
 *
 * Returns calculated metrics including total requests, average latency,
 * and error rate.
 *
 * @return array Metrics summary
 */
function get_metrics() {
    $metrics = read_metrics();

    $avg_latency = 0;
    if ($metrics['latency_count'] > 0) {
        $avg_latency = round($metrics['latency_sum'] / $metrics['latency_count'], 2);
    }

    $error_rate = 0;
    if ($metrics['total_requests'] > 0) {
        $error_rate = round(($metrics['error_count'] / $metrics['total_requests']) * 100, 2);
    }

    return [
        'total_requests' => $metrics['total_requests'],
        'error_count' => $metrics['error_count'],
        'error_rate_percent' => $error_rate,
        'average_latency_ms' => $avg_latency,
        'last_updated' => $metrics['last_updated'],
        'recent_errors' => array_slice($metrics['errors'], -10) // Last 10 errors
    ];
}

/**
 * Log an API error
 *
 * Records error details including endpoint, message, and timestamp.
 * Keeps only the most recent 100 errors to prevent unbounded growth.
 *
 * @param string $endpoint API endpoint where error occurred
 * @param string $error_message Error description
 * @return bool Success status
 */
function log_error($endpoint, $error_message) {
    $metrics = read_metrics();

    $error_entry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'endpoint' => $endpoint,
        'message' => $error_message,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ];

    // Add error to log
    if (!isset($metrics['errors'])) {
        $metrics['errors'] = [];
    }

    $metrics['errors'][] = $error_entry;

    // Keep only last 100 errors to prevent unbounded growth
    if (count($metrics['errors']) > 100) {
        $metrics['errors'] = array_slice($metrics['errors'], -100);
    }

    return write_metrics($metrics);
}

/**
 * Reset all metrics
 *
 * Clears all collected metrics and error logs.
 * Useful for testing or periodic cleanup.
 *
 * @return bool Success status
 */
function reset_metrics() {
    $initial_data = [
        'total_requests' => 0,
        'error_count' => 0,
        'latency_sum' => 0.0,
        'latency_count' => 0,
        'errors' => [],
        'last_updated' => date('Y-m-d H:i:s')
    ];
    return file_put_contents(METRICS_FILE, json_encode($initial_data, JSON_PRETTY_PRINT)) !== false;
}
