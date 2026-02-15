<?php
/**
 * API Rate Limiting Middleware
 *
 * Implements rate limiting for API requests:
 * - 60 requests per minute
 * - 1000 requests per hour
 *
 * Uses file-based storage for simplicity
 */

// Rate limit configuration
define('RATE_LIMIT_PER_MINUTE', 60);
define('RATE_LIMIT_PER_HOUR', 1000);
define('RATE_LIMIT_STORAGE_PATH', '/tmp/fusionpbx_rate_limits/');

/**
 * Initialize rate limit storage directory
 */
function init_rate_limit_storage() {
    if (!file_exists(RATE_LIMIT_STORAGE_PATH)) {
        mkdir(RATE_LIMIT_STORAGE_PATH, 0755, true);
    }
}

/**
 * Get rate limit data for an API key
 *
 * @param string $api_key The API key to check
 * @return array Rate limit data with minute and hour counters
 */
function get_rate_limit_data($api_key) {
    init_rate_limit_storage();

    $file_path = RATE_LIMIT_STORAGE_PATH . md5($api_key) . '.json';

    if (!file_exists($file_path)) {
        return [
            'minute' => [
                'count' => 0,
                'reset' => time() + 60
            ],
            'hour' => [
                'count' => 0,
                'reset' => time() + 3600
            ]
        ];
    }

    $data = json_decode(file_get_contents($file_path), true);

    // Reset minute counter if expired
    if ($data['minute']['reset'] <= time()) {
        $data['minute'] = [
            'count' => 0,
            'reset' => time() + 60
        ];
    }

    // Reset hour counter if expired
    if ($data['hour']['reset'] <= time()) {
        $data['hour'] = [
            'count' => 0,
            'reset' => time() + 3600
        ];
    }

    return $data;
}

/**
 * Save rate limit data for an API key
 *
 * @param string $api_key The API key
 * @param array $data Rate limit data to save
 */
function save_rate_limit_data($api_key, $data) {
    init_rate_limit_storage();

    $file_path = RATE_LIMIT_STORAGE_PATH . md5($api_key) . '.json';
    file_put_contents($file_path, json_encode($data), LOCK_EX);
}

/**
 * Check if API key has exceeded rate limits
 *
 * @param string $api_key The API key to check
 * @return array ['allowed' => bool, 'data' => array, 'limit_type' => string|null]
 */
function check_rate_limit($api_key) {
    $data = get_rate_limit_data($api_key);

    // Check minute limit
    if ($data['minute']['count'] >= RATE_LIMIT_PER_MINUTE) {
        return [
            'allowed' => false,
            'data' => $data,
            'limit_type' => 'minute'
        ];
    }

    // Check hour limit
    if ($data['hour']['count'] >= RATE_LIMIT_PER_HOUR) {
        return [
            'allowed' => false,
            'data' => $data,
            'limit_type' => 'hour'
        ];
    }

    return [
        'allowed' => true,
        'data' => $data,
        'limit_type' => null
    ];
}

/**
 * Increment rate limit counters for an API key
 *
 * @param string $api_key The API key
 * @return array Updated rate limit data
 */
function increment_rate_limit_counter($api_key) {
    $data = get_rate_limit_data($api_key);

    $data['minute']['count']++;
    $data['hour']['count']++;

    save_rate_limit_data($api_key, $data);

    return $data;
}

/**
 * Set rate limit headers in the response
 *
 * @param array $data Rate limit data
 * @param string $limit_type The limit type ('minute' or 'hour')
 */
function set_rate_limit_headers($data, $limit_type = 'minute') {
    if ($limit_type === 'minute') {
        header('X-RateLimit-Limit: ' . RATE_LIMIT_PER_MINUTE);
        header('X-RateLimit-Remaining: ' . max(0, RATE_LIMIT_PER_MINUTE - $data['minute']['count']));
        header('X-RateLimit-Reset: ' . $data['minute']['reset']);
    } else {
        header('X-RateLimit-Limit: ' . RATE_LIMIT_PER_HOUR);
        header('X-RateLimit-Remaining: ' . max(0, RATE_LIMIT_PER_HOUR - $data['hour']['count']));
        header('X-RateLimit-Reset: ' . $data['hour']['reset']);
    }
}

/**
 * Send rate limit exceeded response
 *
 * @param array $data Rate limit data
 * @param string $limit_type The limit type that was exceeded
 */
function send_rate_limit_exceeded_response($data, $limit_type) {
    http_response_code(429);

    set_rate_limit_headers($data, $limit_type);

    $reset_time = $limit_type === 'minute' ? $data['minute']['reset'] : $data['hour']['reset'];
    $retry_after = max(1, $reset_time - time());

    header('Retry-After: ' . $retry_after);
    header('Content-Type: application/json');

    echo json_encode([
        'error' => 'Rate limit exceeded',
        'message' => 'Too many requests. Please try again later.',
        'limit_type' => $limit_type,
        'retry_after' => $retry_after,
        'reset' => $reset_time
    ]);

    exit;
}

/**
 * Main rate limiting middleware function
 *
 * Call this function at the beginning of your API endpoint
 *
 * @param string $api_key The API key from the request
 */
function apply_rate_limit($api_key) {
    // Check rate limit
    $check = check_rate_limit($api_key);

    if (!$check['allowed']) {
        send_rate_limit_exceeded_response($check['data'], $check['limit_type']);
    }

    // Increment counter
    $data = increment_rate_limit_counter($api_key);

    // Set rate limit headers for successful request
    set_rate_limit_headers($data, 'minute');
}
