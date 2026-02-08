<?php
/**
 * PHPUnit Bootstrap File for FusionPBX API Integration Tests
 *
 * Sets up test environment, constants, and helper functions
 */

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define test constants
define('TESTS_PATH', __DIR__);
define('API_BASE_PATH', dirname(dirname(__DIR__)) . '/api');

// Load test configuration
$testConfigFile = TESTS_PATH . '/config.php';
if (file_exists($testConfigFile)) {
    require_once $testConfigFile;
}

// Default test configuration (override in tests/config.php)
if (!defined('TEST_API_BASE_URL')) {
    define('TEST_API_BASE_URL', 'http://localhost/api');
}
if (!defined('TEST_API_KEY')) {
    define('TEST_API_KEY', 'test-api-key-123');
}
if (!defined('TEST_DOMAIN')) {
    define('TEST_DOMAIN', 'test.example.com');
}
if (!defined('TEST_DATABASE_HOST')) {
    define('TEST_DATABASE_HOST', 'localhost');
}
if (!defined('TEST_DATABASE_NAME')) {
    define('TEST_DATABASE_NAME', 'fusionpbx_test');
}
if (!defined('TEST_DATABASE_USER')) {
    define('TEST_DATABASE_USER', 'fusionpbx');
}
if (!defined('TEST_DATABASE_PASSWORD')) {
    define('TEST_DATABASE_PASSWORD', '');
}

/**
 * Helper function to make HTTP requests to API
 *
 * @param string $method HTTP method (GET, POST, PUT, DELETE)
 * @param string $endpoint API endpoint path
 * @param array $data Request body data
 * @param array $headers Additional headers
 * @return array Response data with 'status', 'headers', 'body'
 */
function api_request($method, $endpoint, $data = [], $headers = []) {
    $url = TEST_API_BASE_URL . $endpoint;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);

    // Build headers
    $requestHeaders = array_merge([
        'Content-Type: application/json',
        'X-API-Key: ' . TEST_API_KEY,
        'X-Domain: ' . TEST_DOMAIN
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

    // Parse response
    $responseHeaders = substr($response, 0, $headerSize);
    $responseBody = substr($response, $headerSize);

    return [
        'status' => $statusCode,
        'headers' => $responseHeaders,
        'body' => json_decode($responseBody, true),
        'raw_body' => $responseBody
    ];
}

/**
 * Get database connection for test setup/teardown
 *
 * @return PDO
 */
function get_test_db_connection() {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf(
            'pgsql:host=%s;dbname=%s',
            TEST_DATABASE_HOST,
            TEST_DATABASE_NAME
        );

        $pdo = new PDO($dsn, TEST_DATABASE_USER, TEST_DATABASE_PASSWORD, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
    }

    return $pdo;
}

/**
 * Clean up test data from database
 *
 * @param string $table Table name
 * @param array $conditions WHERE conditions
 */
function cleanup_test_data($table, $conditions = []) {
    $db = get_test_db_connection();

    if (empty($conditions)) {
        // Don't allow deleting all data without conditions
        return;
    }

    $where = [];
    $params = [];
    foreach ($conditions as $key => $value) {
        $where[] = "$key = ?";
        $params[] = $value;
    }

    $sql = "DELETE FROM $table WHERE " . implode(' AND ', $where);
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
}

/**
 * Create a test domain
 *
 * @param string $domainName
 * @return string Domain UUID
 */
function create_test_domain($domainName = null) {
    $db = get_test_db_connection();

    $domainName = $domainName ?? TEST_DOMAIN;
    $domainUuid = uuid();

    $stmt = $db->prepare("
        INSERT INTO v_domains (domain_uuid, domain_name, domain_enabled)
        VALUES (?, ?, 'true')
    ");
    $stmt->execute([$domainUuid, $domainName]);

    return $domainUuid;
}

/**
 * Create a test API key
 *
 * @param string $domainUuid
 * @param string $key
 * @return string API key UUID
 */
function create_test_api_key($domainUuid, $key = null) {
    $db = get_test_db_connection();

    $key = $key ?? TEST_API_KEY;
    $keyUuid = uuid();

    $stmt = $db->prepare("
        INSERT INTO v_api_keys (api_key_uuid, domain_uuid, api_key, api_key_enabled)
        VALUES (?, ?, ?, 'true')
    ");
    $stmt->execute([$keyUuid, $domainUuid, $key]);

    return $keyUuid;
}

/**
 * Generate UUID v4
 *
 * @return string
 */
function uuid() {
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // Version 4
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // Variant

    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

/**
 * Assert JSON structure matches expected schema
 *
 * @param array $expected Expected keys and types
 * @param array $actual Actual JSON data
 * @param string $message Assertion message
 */
function assert_json_structure($expected, $actual, $message = '') {
    foreach ($expected as $key => $type) {
        if (!isset($actual[$key])) {
            throw new Exception("Missing key: $key. $message");
        }

        $actualType = gettype($actual[$key]);
        if ($actualType !== $type && !($type === 'array' && is_array($actual[$key]))) {
            throw new Exception("Key $key: expected $type, got $actualType. $message");
        }
    }
}
