<?php
/**
 * Error Handling Integration Tests
 *
 * Tests proper error responses across all API endpoints:
 * - 400 Bad Request (invalid input, missing fields)
 * - 401 Unauthorized (authentication failures)
 * - 404 Not Found (non-existent resources)
 * - 405 Method Not Allowed (wrong HTTP method)
 * - 500 Internal Server Error (server errors)
 * - Error response format consistency
 */

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';

class ErrorHandlingTest extends TestCase
{
    private $testDomainUuid;
    private $testApiKeyUuid;
    private $validApiKey;
    private $validDomain;
    private $createdResources = [];

    protected function setUp(): void
    {
        parent::setUp();

        // Create test domain and API key
        $this->validDomain = 'test-errors-' . time() . '.example.com';
        $this->validApiKey = 'test-key-' . bin2hex(random_bytes(16));

        $this->testDomainUuid = create_test_domain($this->validDomain);
        $this->testApiKeyUuid = create_test_api_key($this->testDomainUuid, $this->validApiKey);
    }

    protected function tearDown(): void
    {
        // Clean up any created resources
        foreach ($this->createdResources as $resource) {
            if ($resource['type'] === 'ring_group') {
                cleanup_test_data('v_ring_groups', ['ring_group_uuid' => $resource['uuid']]);
            } elseif ($resource['type'] === 'extension') {
                cleanup_test_data('v_extensions', ['extension_uuid' => $resource['uuid']]);
            }
        }

        // Clean up test API key and domain
        if ($this->testApiKeyUuid) {
            cleanup_test_data('v_api_keys', ['api_key_uuid' => $this->testApiKeyUuid]);
        }
        if ($this->testDomainUuid) {
            cleanup_test_data('v_domains', ['domain_uuid' => $this->testDomainUuid]);
        }

        parent::tearDown();
    }

    private function apiRequest($method, $endpoint, $data = [], $headers = null)
    {
        if ($headers === null) {
            $headers = [
                'X-API-Key: ' . $this->validApiKey,
                'X-Domain: ' . $this->validDomain
            ];
        }
        return api_request($method, $endpoint, $data, $headers);
    }

    // ===== 400 Bad Request Tests =====

    public function test_400_missing_required_field_ring_group()
    {
        $response = $this->apiRequest('POST', '/ring_groups', [
            'ring_group_name' => 'Incomplete Ring Group'
            // Missing ring_group_extension
        ]);

        $this->assertEquals(400, $response['status'],
            'Missing required field should return 400');

        $this->assertArrayHasKey('error', $response['body'],
            'Error response should contain error key');
        $this->assertIsString($response['body']['error'],
            'Error should be a string message');
    }

    public function test_400_missing_required_field_extension()
    {
        $response = $this->apiRequest('POST', '/extensions', [
            'extension' => '2001'
            // Missing password
        ]);

        $this->assertEquals(400, $response['status'],
            'Missing required field should return 400');

        $this->assertArrayHasKey('error', $response['body']);
    }

    public function test_400_invalid_data_type()
    {
        $response = $this->apiRequest('POST', '/ring_groups', [
            'ring_group_name' => 'Test Ring Group',
            'ring_group_extension' => '8000',
            'ring_group_timeout_sec' => 'not-a-number' // Should be integer
        ]);

        // Should either accept (convert) or reject with 400
        $this->assertContains($response['status'], [200, 201, 400],
            'Invalid data type should be handled gracefully');

        if ($response['status'] === 400) {
            $this->assertArrayHasKey('error', $response['body']);
        }
    }

    public function test_400_malformed_json()
    {
        $url = TEST_API_BASE_URL . '/ring_groups';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, '{invalid json}');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'X-API-Key: ' . $this->validApiKey,
            'X-Domain: ' . $this->validDomain
        ]);

        $response = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);

        $responseBody = substr($response, $headerSize);
        $body = json_decode($responseBody, true);

        $this->assertEquals(400, $statusCode,
            'Malformed JSON should return 400');

        if (is_array($body)) {
            $this->assertArrayHasKey('error', $body);
        }
    }

    public function test_400_missing_domain_header()
    {
        $response = $this->apiRequest('GET', '/ring_groups', [], [
            'X-API-Key: ' . $this->validApiKey
            // Missing X-Domain header
        ]);

        $this->assertEquals(400, $response['status'],
            'Missing domain header should return 400');

        $this->assertArrayHasKey('error', $response['body']);
    }

    // ===== 401 Unauthorized Tests =====

    public function test_401_missing_api_key()
    {
        $response = $this->apiRequest('GET', '/ring_groups', [], [
            'X-Domain: ' . $this->validDomain
            // Missing X-API-Key header
        ]);

        $this->assertEquals(401, $response['status'],
            'Missing API key should return 401');

        $this->assertArrayHasKey('error', $response['body']);
    }

    public function test_401_invalid_api_key()
    {
        $response = $this->apiRequest('GET', '/ring_groups', [], [
            'X-API-Key: invalid-key-' . time(),
            'X-Domain: ' . $this->validDomain
        ]);

        $this->assertEquals(401, $response['status'],
            'Invalid API key should return 401');

        $this->assertArrayHasKey('error', $response['body']);
    }

    public function test_401_invalid_domain()
    {
        $response = $this->apiRequest('GET', '/ring_groups', [], [
            'X-API-Key: ' . $this->validApiKey,
            'X-Domain: invalid-domain.example.com'
        ]);

        $this->assertEquals(401, $response['status'],
            'Invalid domain should return 401');

        $this->assertArrayHasKey('error', $response['body']);
    }

    public function test_401_disabled_api_key()
    {
        // Disable the API key
        $db = get_test_db_connection();
        $stmt = $db->prepare("
            UPDATE v_api_keys
            SET api_key_enabled = 'false'
            WHERE api_key_uuid = ?
        ");
        $stmt->execute([$this->testApiKeyUuid]);

        $response = $this->apiRequest('GET', '/ring_groups');

        $this->assertEquals(401, $response['status'],
            'Disabled API key should return 401');

        // Re-enable for tearDown
        $stmt = $db->prepare("
            UPDATE v_api_keys
            SET api_key_enabled = 'true'
            WHERE api_key_uuid = ?
        ");
        $stmt->execute([$this->testApiKeyUuid]);
    }

    // ===== 404 Not Found Tests =====

    public function test_404_ring_group_not_found()
    {
        $fakeUuid = uuid();

        $response = $this->apiRequest('GET', "/ring_groups/{$fakeUuid}");

        $this->assertEquals(404, $response['status'],
            'Non-existent ring group should return 404');

        $this->assertArrayHasKey('error', $response['body']);
    }

    public function test_404_extension_not_found()
    {
        $fakeUuid = uuid();

        $response = $this->apiRequest('GET', "/extensions/{$fakeUuid}");

        $this->assertEquals(404, $response['status'],
            'Non-existent extension should return 404');

        $this->assertArrayHasKey('error', $response['body']);
    }

    public function test_404_update_non_existent_resource()
    {
        $fakeUuid = uuid();

        $response = $this->apiRequest('PUT', "/ring_groups/{$fakeUuid}", [
            'ring_group_name' => 'Updated Name'
        ]);

        $this->assertEquals(404, $response['status'],
            'Update non-existent resource should return 404');

        $this->assertArrayHasKey('error', $response['body']);
    }

    public function test_404_delete_non_existent_resource()
    {
        $fakeUuid = uuid();

        $response = $this->apiRequest('DELETE', "/ring_groups/{$fakeUuid}");

        $this->assertEquals(404, $response['status'],
            'Delete non-existent resource should return 404');

        $this->assertArrayHasKey('error', $response['body']);
    }

    public function test_404_invalid_endpoint()
    {
        $response = $this->apiRequest('GET', '/nonexistent-endpoint');

        $this->assertEquals(404, $response['status'],
            'Invalid endpoint should return 404');
    }

    // ===== 405 Method Not Allowed Tests =====

    public function test_405_wrong_http_method()
    {
        // Try to POST to a GET-only endpoint (if applicable)
        // This depends on API design - adjust as needed
        $response = $this->apiRequest('POST', '/ring_groups/' . uuid());

        // Should return 404 (not found) or 405 (method not allowed)
        $this->assertContains($response['status'], [404, 405],
            'Wrong HTTP method should return 404 or 405');
    }

    // ===== Error Response Format Tests =====

    public function test_error_response_has_consistent_format()
    {
        // Test various error scenarios
        $errorScenarios = [
            ['method' => 'POST', 'endpoint' => '/ring_groups', 'data' => []],
            ['method' => 'GET', 'endpoint' => '/ring_groups/' . uuid(), 'data' => []],
        ];

        foreach ($errorScenarios as $scenario) {
            $response = $this->apiRequest(
                $scenario['method'],
                $scenario['endpoint'],
                $scenario['data']
            );

            if ($response['status'] >= 400) {
                $this->assertIsArray($response['body'],
                    'Error response body should be an array');
                $this->assertArrayHasKey('error', $response['body'],
                    'Error response should contain error key');
                $this->assertIsString($response['body']['error'],
                    'Error message should be a string');
                $this->assertNotEmpty($response['body']['error'],
                    'Error message should not be empty');
            }
        }
    }

    public function test_error_response_includes_helpful_message()
    {
        $response = $this->apiRequest('POST', '/ring_groups', [
            'ring_group_name' => 'Test'
            // Missing ring_group_extension
        ]);

        if ($response['status'] === 400) {
            $this->assertArrayHasKey('error', $response['body']);
            $errorMessage = strtolower($response['body']['error']);

            // Error message should mention the problem
            $this->assertTrue(
                strpos($errorMessage, 'extension') !== false ||
                strpos($errorMessage, 'required') !== false ||
                strpos($errorMessage, 'missing') !== false,
                'Error message should be helpful and descriptive'
            );
        }
    }

    public function test_multiple_validation_errors()
    {
        $response = $this->apiRequest('POST', '/extensions', [
            // Missing multiple required fields
            'description' => 'Incomplete extension'
        ]);

        $this->assertEquals(400, $response['status']);
        $this->assertArrayHasKey('error', $response['body']);

        // Check if error lists multiple issues or at least mentions validation
        $this->assertNotEmpty($response['body']['error']);
    }

    public function test_404_error_message_specificity()
    {
        $fakeUuid = uuid();

        $response = $this->apiRequest('GET', "/ring_groups/{$fakeUuid}");

        $this->assertEquals(404, $response['status']);
        $this->assertArrayHasKey('error', $response['body']);

        $errorMessage = strtolower($response['body']['error']);

        // Error should mention the resource that wasn't found
        $this->assertTrue(
            strpos($errorMessage, 'not found') !== false ||
            strpos($errorMessage, 'ring group') !== false ||
            strpos($errorMessage, 'does not exist') !== false,
            'Error message should indicate resource was not found'
        );
    }

    public function test_content_type_header_on_errors()
    {
        $response = $this->apiRequest('POST', '/ring_groups', []);

        if ($response['status'] >= 400) {
            // Check that response includes proper Content-Type header
            $this->assertStringContainsString('Content-Type',
                $response['headers'],
                'Error response should include Content-Type header');
            $this->assertStringContainsString('application/json',
                $response['headers'],
                'Error response should be JSON');
        }
    }

    public function test_error_response_without_sensitive_data()
    {
        // Try various error scenarios
        $response = $this->apiRequest('GET', '/ring_groups/' . uuid());

        if ($response['status'] >= 400) {
            $errorMessage = strtolower(json_encode($response['body']));

            // Error should not contain sensitive information
            $this->assertStringNotContainsString('password',
                $errorMessage,
                'Error should not expose passwords');
            $this->assertStringNotContainsString('database',
                $errorMessage,
                'Error should not expose database details');
            $this->assertStringNotContainsString('query',
                $errorMessage,
                'Error should not expose SQL queries');
        }
    }
}
