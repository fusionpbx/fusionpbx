<?php
/**
 * Authentication Integration Tests
 *
 * Tests API authentication mechanisms:
 * - Valid API key authentication
 * - Invalid API key handling
 * - Missing API key handling
 * - Missing domain header handling
 * - Invalid domain handling
 */

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';

class AuthenticationTest extends TestCase
{
    private $testDomainUuid;
    private $testApiKeyUuid;
    private $validApiKey;
    private $validDomain;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test domain and API key
        $this->validDomain = 'test-auth-' . time() . '.example.com';
        $this->validApiKey = 'test-key-' . bin2hex(random_bytes(16));

        $this->testDomainUuid = create_test_domain($this->validDomain);
        $this->testApiKeyUuid = create_test_api_key($this->testDomainUuid, $this->validApiKey);
    }

    protected function tearDown(): void
    {
        // Clean up test data
        if ($this->testApiKeyUuid) {
            cleanup_test_data('v_api_keys', ['api_key_uuid' => $this->testApiKeyUuid]);
        }
        if ($this->testDomainUuid) {
            cleanup_test_data('v_domains', ['domain_uuid' => $this->testDomainUuid]);
        }

        parent::tearDown();
    }

    public function test_valid_api_key_authentication()
    {
        $response = api_request('GET', '/ring_groups', [], [
            'X-API-Key: ' . $this->validApiKey,
            'X-Domain: ' . $this->validDomain
        ]);

        $this->assertContains($response['status'], [200, 404],
            'Valid authentication should return 200 or 404 (if no ring groups exist)');

        if ($response['status'] === 200) {
            $this->assertIsArray($response['body'], 'Response body should be an array');
            $this->assertArrayHasKey('data', $response['body'], 'Response should contain data key');
            $this->assertIsArray($response['body']['data'], 'Data should be an array');
        }
    }

    public function test_invalid_api_key_returns_401()
    {
        $response = api_request('GET', '/ring_groups', [], [
            'X-API-Key: invalid-key-12345',
            'X-Domain: ' . $this->validDomain
        ]);

        $this->assertEquals(401, $response['status'],
            'Invalid API key should return 401 Unauthorized');

        $this->assertIsArray($response['body'], 'Response body should be an array');
        $this->assertArrayHasKey('error', $response['body'],
            'Error response should contain error key');
        $this->assertStringContainsString('Unauthorized', $response['body']['error'],
            'Error message should mention unauthorized');
    }

    public function test_missing_api_key_returns_401()
    {
        $response = api_request('GET', '/ring_groups', [], [
            'X-Domain: ' . $this->validDomain
        ]);

        $this->assertEquals(401, $response['status'],
            'Missing API key should return 401 Unauthorized');

        $this->assertIsArray($response['body'], 'Response body should be an array');
        $this->assertArrayHasKey('error', $response['body'],
            'Error response should contain error key');
    }

    public function test_missing_domain_header_returns_400()
    {
        $response = api_request('GET', '/ring_groups', [], [
            'X-API-Key: ' . $this->validApiKey
        ]);

        $this->assertEquals(400, $response['status'],
            'Missing domain header should return 400 Bad Request');

        $this->assertIsArray($response['body'], 'Response body should be an array');
        $this->assertArrayHasKey('error', $response['body'],
            'Error response should contain error key');
        $this->assertStringContainsString('domain', strtolower($response['body']['error']),
            'Error message should mention domain');
    }

    public function test_invalid_domain_returns_401()
    {
        $response = api_request('GET', '/ring_groups', [], [
            'X-API-Key: ' . $this->validApiKey,
            'X-Domain: invalid-domain.example.com'
        ]);

        $this->assertEquals(401, $response['status'],
            'Invalid domain should return 401 Unauthorized');

        $this->assertIsArray($response['body'], 'Response body should be an array');
        $this->assertArrayHasKey('error', $response['body'],
            'Error response should contain error key');
    }

    public function test_valid_authentication_with_post_request()
    {
        $response = api_request('POST', '/ring_groups', [
            'ring_group_name' => 'Test Auth Ring Group',
            'ring_group_extension' => '7000'
        ], [
            'X-API-Key: ' . $this->validApiKey,
            'X-Domain: ' . $this->validDomain
        ]);

        $this->assertContains($response['status'], [200, 201, 400],
            'Authenticated POST should not return 401');
        $this->assertNotEquals(401, $response['status'],
            'Valid authentication should not return 401');

        // Clean up if created successfully
        if (isset($response['body']['ring_group_uuid'])) {
            cleanup_test_data('v_ring_groups', [
                'ring_group_uuid' => $response['body']['ring_group_uuid']
            ]);
        }
    }

    public function test_disabled_api_key_returns_401()
    {
        // Disable the API key
        $db = get_test_db_connection();
        $stmt = $db->prepare("
            UPDATE v_api_keys
            SET api_key_enabled = 'false'
            WHERE api_key_uuid = ?
        ");
        $stmt->execute([$this->testApiKeyUuid]);

        $response = api_request('GET', '/ring_groups', [], [
            'X-API-Key: ' . $this->validApiKey,
            'X-Domain: ' . $this->validDomain
        ]);

        $this->assertEquals(401, $response['status'],
            'Disabled API key should return 401 Unauthorized');

        // Re-enable for tearDown
        $stmt = $db->prepare("
            UPDATE v_api_keys
            SET api_key_enabled = 'true'
            WHERE api_key_uuid = ?
        ");
        $stmt->execute([$this->testApiKeyUuid]);
    }

    public function test_disabled_domain_returns_401()
    {
        // Disable the domain
        $db = get_test_db_connection();
        $stmt = $db->prepare("
            UPDATE v_domains
            SET domain_enabled = 'false'
            WHERE domain_uuid = ?
        ");
        $stmt->execute([$this->testDomainUuid]);

        $response = api_request('GET', '/ring_groups', [], [
            'X-API-Key: ' . $this->validApiKey,
            'X-Domain: ' . $this->validDomain
        ]);

        $this->assertEquals(401, $response['status'],
            'Disabled domain should return 401 Unauthorized');

        // Re-enable for tearDown
        $stmt = $db->prepare("
            UPDATE v_domains
            SET domain_enabled = 'true'
            WHERE domain_uuid = ?
        ");
        $stmt->execute([$this->testDomainUuid]);
    }

    public function test_case_insensitive_header_names()
    {
        // Test lowercase headers
        $response = api_request('GET', '/ring_groups', [], [
            'x-api-key: ' . $this->validApiKey,
            'x-domain: ' . $this->validDomain
        ]);

        $this->assertNotEquals(401, $response['status'],
            'Lowercase headers should be accepted');
    }
}
