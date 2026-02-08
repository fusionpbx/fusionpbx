<?php
/**
 * PHPUnit Bootstrap File for FusionPBX Performance Benchmarks
 *
 * Sets up test environment, constants, and helper functions for performance testing
 */

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define test constants
define('PERF_PATH', __DIR__);
define('API_BASE_PATH', dirname(dirname(__DIR__)) . '/api');

// Load test configuration
$perfConfigFile = PERF_PATH . '/config.php';
if (file_exists($perfConfigFile)) {
    require_once $perfConfigFile;
}

// Default configuration (override in tests/performance/config.php)
if (!defined('PERF_API_BASE_URL')) {
    define('PERF_API_BASE_URL', 'http://localhost/api');
}
if (!defined('PERF_API_KEY')) {
    define('PERF_API_KEY', 'test-api-key-123');
}
if (!defined('PERF_DOMAIN')) {
    define('PERF_DOMAIN', 'test.example.com');
}
if (!defined('PERF_WARMUP_REQUESTS')) {
    define('PERF_WARMUP_REQUESTS', 5);
}
if (!defined('PERF_ITERATIONS')) {
    define('PERF_ITERATIONS', 100);
}
if (!defined('PERF_CONCURRENT_REQUESTS')) {
    define('PERF_CONCURRENT_REQUESTS', 10);
}
if (!defined('PERF_SMALL_DATASET_SIZE')) {
    define('PERF_SMALL_DATASET_SIZE', 100);
}
if (!defined('PERF_LARGE_DATASET_SIZE')) {
    define('PERF_LARGE_DATASET_SIZE', 1000);
}
if (!defined('PERF_VERBOSE')) {
    define('PERF_VERBOSE', false);
}
if (!defined('PERF_OUTPUT_FORMAT')) {
    define('PERF_OUTPUT_FORMAT', 'text');
}
if (!defined('PERF_RESULTS_DIR')) {
    define('PERF_RESULTS_DIR', __DIR__ . '/results');
}

/**
 * Helper function to make HTTP requests to API with timing
 *
 * @param string $method HTTP method (GET, POST, PUT, DELETE)
 * @param string $endpoint API endpoint path
 * @param array $data Request body data
 * @param array $headers Additional headers
 * @return array Response data with 'status', 'headers', 'body', 'duration_ms'
 */
function perf_api_request($method, $endpoint, $data = [], $headers = []) {
    $url = PERF_API_BASE_URL . $endpoint;

    $startTime = microtime(true);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);

    // Build headers
    $requestHeaders = array_merge([
        'Content-Type: application/json',
        'X-API-Key: ' . PERF_API_KEY,
        'X-Domain: ' . PERF_DOMAIN
    ], $headers);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $requestHeaders);

    // Set method and data
    switch (strtoupper($method)) {
        case 'POST':
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            break;
        case 'PUT':
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            break;
        case 'DELETE':
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
            break;
        case 'GET':
        default:
            if (!empty($data)) {
                $url .= '?' . http_build_query($data);
                curl_setopt($ch, CURLOPT_URL, $url);
            }
            break;
    }

    // Execute request
    $response = curl_exec($ch);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $endTime = microtime(true);
    $duration = ($endTime - $startTime) * 1000; // Convert to milliseconds

    // Parse response
    $responseHeaders = substr($response, 0, $headerSize);
    $responseBody = substr($response, $headerSize);

    return [
        'status' => $statusCode,
        'headers' => $responseHeaders,
        'body' => json_decode($responseBody, true),
        'raw_body' => $responseBody,
        'duration_ms' => $duration
    ];
}

/**
 * Calculate percentile from sorted array of values
 *
 * @param array $sortedValues Sorted array of numeric values
 * @param float $percentile Percentile to calculate (0-100)
 * @return float
 */
function calculate_percentile($sortedValues, $percentile) {
    $count = count($sortedValues);
    if ($count === 0) {
        return 0;
    }

    $index = ($percentile / 100) * ($count - 1);
    $lower = floor($index);
    $upper = ceil($index);

    if ($lower === $upper) {
        return $sortedValues[$lower];
    }

    $fraction = $index - $lower;
    return $sortedValues[$lower] + ($sortedValues[$upper] - $sortedValues[$lower]) * $fraction;
}

/**
 * Calculate statistics from array of latencies
 *
 * @param array $latencies Array of latency measurements in milliseconds
 * @return array Statistics array with min, max, mean, median, p50, p95, p99
 */
function calculate_stats($latencies) {
    if (empty($latencies)) {
        return [
            'min' => 0,
            'max' => 0,
            'mean' => 0,
            'median' => 0,
            'p50' => 0,
            'p95' => 0,
            'p99' => 0,
            'stddev' => 0
        ];
    }

    sort($latencies);
    $count = count($latencies);
    $mean = array_sum($latencies) / $count;

    // Standard deviation
    $variance = 0;
    foreach ($latencies as $latency) {
        $variance += pow($latency - $mean, 2);
    }
    $stddev = sqrt($variance / $count);

    return [
        'min' => $latencies[0],
        'max' => $latencies[$count - 1],
        'mean' => $mean,
        'median' => calculate_percentile($latencies, 50),
        'p50' => calculate_percentile($latencies, 50),
        'p95' => calculate_percentile($latencies, 95),
        'p99' => calculate_percentile($latencies, 99),
        'stddev' => $stddev
    ];
}

/**
 * Format statistics for output
 *
 * @param array $stats Statistics from calculate_stats()
 * @param string $format Output format: 'text', 'json', 'csv'
 * @return string
 */
function format_stats($stats, $format = 'text') {
    switch ($format) {
        case 'json':
            return json_encode($stats, JSON_PRETTY_PRINT);

        case 'csv':
            return implode(',', [
                round($stats['min'], 2),
                round($stats['max'], 2),
                round($stats['mean'], 2),
                round($stats['median'], 2),
                round($stats['p50'], 2),
                round($stats['p95'], 2),
                round($stats['p99'], 2),
                round($stats['stddev'], 2)
            ]);

        case 'text':
        default:
            return sprintf(
                "Min: %.2fms | Max: %.2fms | Mean: %.2fms | Median: %.2fms | P95: %.2fms | P99: %.2fms",
                $stats['min'],
                $stats['max'],
                $stats['mean'],
                $stats['median'],
                $stats['p95'],
                $stats['p99']
            );
    }
}

/**
 * Generate UUID v4
 *
 * @return string
 */
function perf_uuid() {
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // Version 4
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // Variant

    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

/**
 * Save benchmark results to file
 *
 * @param string $testName Test name
 * @param array $results Benchmark results
 */
function save_results($testName, $results) {
    $resultsDir = PERF_RESULTS_DIR;
    if (!is_dir($resultsDir)) {
        mkdir($resultsDir, 0755, true);
    }

    $timestamp = date('Y-m-d_H-i-s');
    $filename = sprintf('%s/%s_%s.json', $resultsDir, $testName, $timestamp);

    file_put_contents($filename, json_encode($results, JSON_PRETTY_PRINT));
}
