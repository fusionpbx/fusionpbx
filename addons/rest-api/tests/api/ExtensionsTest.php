<?php
/**
 * Extensions API Integration Tests
 *
 * Tests CRUD operations for extensions endpoint:
 * - Create extension
 * - Get all extensions (with pagination)
 * - Get single extension
 * - Update extension
 * - Delete extension
 * - Extension validation
 */

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';

class ExtensionsTest extends TestCase
{
    private $testDomainUuid;
    private $testApiKeyUuid;
    private $createdExtensions = [];

    protected function setUp(): void
    {
        parent::setUp();

        // Create test domain and API key
        $testDomain = 'test-extensions-' . time() . '.example.com';
        $testApiKey = 'test-key-' . bin2hex(random_bytes(16));

        $this->testDomainUuid = create_test_domain($testDomain);
        $this->testApiKeyUuid = create_test_api_key($this->testDomainUuid, $testApiKey);

        // Store credentials for api_request
        define('TEST_EXTENSIONS_API_KEY', $testApiKey);
        define('TEST_EXTENSIONS_DOMAIN', $testDomain);
    }

    protected function tearDown(): void
    {
        // Clean up created extensions and related data
        foreach ($this->createdExtensions as $uuid) {
            cleanup_test_data('v_extension_users', ['extension_uuid' => $uuid]);
            cleanup_test_data('v_extensions', ['extension_uuid' => $uuid]);
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

    private function apiRequest($method, $endpoint, $data = [])
    {
        return api_request($method, $endpoint, $data, [
            'X-API-Key: ' . TEST_EXTENSIONS_API_KEY,
            'X-Domain: ' . TEST_EXTENSIONS_DOMAIN
        ]);
    }

    public function test_create_extension_success()
    {
        $extensionData = [
            'extension' => '1001',
            'number_alias' => '1001',
            'password' => 'SecurePass123!',
            'effective_caller_id_name' => 'John Doe',
            'effective_caller_id_number' => '1001',
            'outbound_caller_id_name' => 'John Doe',
            'outbound_caller_id_number' => '5551234567',
            'enabled' => 'true',
            'description' => 'Test extension for John Doe'
        ];

        $response = $this->apiRequest('POST', '/extensions', $extensionData);

        $this->assertContains($response['status'], [200, 201],
            'Create extension should return 200 or 201');

        $this->assertIsArray($response['body'], 'Response body should be an array');
        $this->assertArrayHasKey('extension_uuid', $response['body'],
            'Response should contain extension_uuid');

        // Store for cleanup
        $this->createdExtensions[] = $response['body']['extension_uuid'];

        // Verify returned data
        $this->assertEquals($extensionData['extension'],
            $response['body']['extension']);
        $this->assertEquals($extensionData['effective_caller_id_name'],
            $response['body']['effective_caller_id_name']);
    }

    public function test_create_extension_missing_required_fields()
    {
        $response = $this->apiRequest('POST', '/extensions', [
            'extension' => '1002'
            // Missing password and other required fields
        ]);

        $this->assertEquals(400, $response['status'],
            'Missing required fields should return 400');

        $this->assertArrayHasKey('error', $response['body'],
            'Error response should contain error key');
    }

    public function test_get_all_extensions()
    {
        // Create test extensions
        $ext1 = $this->createTestExtension('1010', 'User One');
        $ext2 = $this->createTestExtension('1011', 'User Two');

        $response = $this->apiRequest('GET', '/extensions');

        $this->assertEquals(200, $response['status'],
            'Get all extensions should return 200');

        $this->assertIsArray($response['body'], 'Response body should be an array');
        $this->assertArrayHasKey('data', $response['body'],
            'Response should contain data key');
        $this->assertIsArray($response['body']['data'], 'Data should be an array');

        // Should contain at least our test extensions
        $this->assertGreaterThanOrEqual(2, count($response['body']['data']),
            'Should return at least the created extensions');
    }

    public function test_get_extension_by_uuid()
    {
        // Create test extension
        $extensionUuid = $this->createTestExtension('1020', 'Test User');

        $response = $this->apiRequest('GET', "/extensions/{$extensionUuid}");

        $this->assertEquals(200, $response['status'],
            'Get extension by UUID should return 200');

        $this->assertIsArray($response['body'], 'Response body should be an array');
        $this->assertEquals($extensionUuid, $response['body']['extension_uuid'],
            'Should return correct extension');
        $this->assertEquals('1020', $response['body']['extension']);
    }

    public function test_get_extension_not_found()
    {
        $fakeUuid = uuid();

        $response = $this->apiRequest('GET', "/extensions/{$fakeUuid}");

        $this->assertEquals(404, $response['status'],
            'Non-existent extension should return 404');

        $this->assertArrayHasKey('error', $response['body'],
            'Error response should contain error key');
    }

    public function test_update_extension()
    {
        // Create test extension
        $extensionUuid = $this->createTestExtension('1030', 'Update Test');

        // Update the extension
        $updateData = [
            'effective_caller_id_name' => 'Updated Name',
            'outbound_caller_id_number' => '5559876543',
            'description' => 'Updated description'
        ];

        $response = $this->apiRequest('PUT', "/extensions/{$extensionUuid}", $updateData);

        $this->assertContains($response['status'], [200, 204],
            'Update extension should return 200 or 204');

        // Verify update
        $getResponse = $this->apiRequest('GET', "/extensions/{$extensionUuid}");
        $this->assertEquals('Updated Name',
            $getResponse['body']['effective_caller_id_name']);
        $this->assertEquals('5559876543',
            $getResponse['body']['outbound_caller_id_number']);
    }

    public function test_update_extension_not_found()
    {
        $fakeUuid = uuid();

        $response = $this->apiRequest('PUT', "/extensions/{$fakeUuid}", [
            'effective_caller_id_name' => 'Non-existent'
        ]);

        $this->assertEquals(404, $response['status'],
            'Update non-existent extension should return 404');
    }

    public function test_delete_extension()
    {
        // Create test extension
        $extensionUuid = $this->createTestExtension('1040', 'Delete Test');

        // Delete the extension
        $response = $this->apiRequest('DELETE', "/extensions/{$extensionUuid}");

        $this->assertContains($response['status'], [200, 204],
            'Delete extension should return 200 or 204');

        // Verify deletion
        $getResponse = $this->apiRequest('GET', "/extensions/{$extensionUuid}");
        $this->assertEquals(404, $getResponse['status'],
            'Deleted extension should not be found');

        // Remove from cleanup list since already deleted
        $this->createdExtensions = array_filter($this->createdExtensions,
            fn($uuid) => $uuid !== $extensionUuid);
    }

    public function test_delete_extension_not_found()
    {
        $fakeUuid = uuid();

        $response = $this->apiRequest('DELETE', "/extensions/{$fakeUuid}");

        $this->assertEquals(404, $response['status'],
            'Delete non-existent extension should return 404');
    }

    public function test_get_extensions_with_pagination()
    {
        // Create multiple extensions
        for ($i = 1; $i <= 5; $i++) {
            $this->createTestExtension("105{$i}", "Pagination User {$i}");
        }

        // Test first page
        $response = $this->apiRequest('GET', '/extensions', [
            'page' => 1,
            'per_page' => 2
        ]);

        $this->assertEquals(200, $response['status']);
        $this->assertArrayHasKey('data', $response['body']);
        $this->assertArrayHasKey('pagination', $response['body']);

        $pagination = $response['body']['pagination'];
        $this->assertEquals(1, $pagination['current_page']);
        $this->assertEquals(2, $pagination['per_page']);
        $this->assertArrayHasKey('total', $pagination);
        $this->assertArrayHasKey('total_pages', $pagination);
    }

    public function test_duplicate_extension_returns_error()
    {
        // Create first extension
        $this->createTestExtension('1060', 'First User');

        // Try to create another with same extension number
        $response = $this->apiRequest('POST', '/extensions', [
            'extension' => '1060',
            'password' => 'Pass123!',
            'effective_caller_id_name' => 'Second User'
        ]);

        $this->assertEquals(400, $response['status'],
            'Duplicate extension should return 400');
        $this->assertArrayHasKey('error', $response['body']);
    }

    public function test_extension_enabled_toggle()
    {
        // Create enabled extension
        $extensionUuid = $this->createTestExtension('1070', 'Toggle Test');

        // Disable it
        $response = $this->apiRequest('PUT', "/extensions/{$extensionUuid}", [
            'enabled' => 'false'
        ]);

        $this->assertContains($response['status'], [200, 204]);

        // Verify disabled
        $getResponse = $this->apiRequest('GET', "/extensions/{$extensionUuid}");
        $this->assertEquals('false', $getResponse['body']['enabled']);

        // Enable it again
        $response = $this->apiRequest('PUT', "/extensions/{$extensionUuid}", [
            'enabled' => 'true'
        ]);

        $this->assertContains($response['status'], [200, 204]);

        // Verify enabled
        $getResponse = $this->apiRequest('GET', "/extensions/{$extensionUuid}");
        $this->assertEquals('true', $getResponse['body']['enabled']);
    }

    public function test_extension_password_update()
    {
        // Create extension
        $extensionUuid = $this->createTestExtension('1080', 'Password Test');

        // Update password
        $response = $this->apiRequest('PUT', "/extensions/{$extensionUuid}", [
            'password' => 'NewSecurePass456!'
        ]);

        $this->assertContains($response['status'], [200, 204],
            'Password update should succeed');

        // Note: We can't directly verify the password, but we can check
        // that the update didn't fail
    }

    public function test_extension_with_invalid_number_format()
    {
        $response = $this->apiRequest('POST', '/extensions', [
            'extension' => 'invalid-ext',
            'password' => 'Pass123!',
            'effective_caller_id_name' => 'Invalid Extension'
        ]);

        // Should either reject or accept based on implementation
        // If accepted, it's stored as-is; if rejected, should be 400
        $this->assertContains($response['status'], [200, 201, 400],
            'Response should be success or validation error');

        if (in_array($response['status'], [200, 201])) {
            // If accepted, add to cleanup
            $this->createdExtensions[] = $response['body']['extension_uuid'];
        }
    }

    public function test_search_extensions_by_caller_id()
    {
        // Create extensions with specific caller IDs
        $this->createTestExtension('1090', 'Search Test One', 'SearchUser1');
        $this->createTestExtension('1091', 'Search Test Two', 'SearchUser2');

        $response = $this->apiRequest('GET', '/extensions', [
            'search' => 'SearchUser'
        ]);

        if ($response['status'] === 200 && isset($response['body']['data'])) {
            // If search is supported, verify results
            $this->assertIsArray($response['body']['data']);
            // Should find at least our test extensions
            $found = false;
            foreach ($response['body']['data'] as $ext) {
                if (strpos($ext['effective_caller_id_name'], 'SearchUser') !== false) {
                    $found = true;
                    break;
                }
            }
            $this->assertTrue($found, 'Search should find matching extensions');
        }
    }

    /**
     * Helper method to create a test extension
     */
    private function createTestExtension($extension, $callerIdName, $description = null)
    {
        $response = $this->apiRequest('POST', '/extensions', [
            'extension' => $extension,
            'password' => 'TestPass123!',
            'effective_caller_id_name' => $callerIdName,
            'effective_caller_id_number' => $extension,
            'enabled' => 'true',
            'description' => $description ?? "Test extension {$extension}"
        ]);

        $uuid = $response['body']['extension_uuid'];
        $this->createdExtensions[] = $uuid;

        return $uuid;
    }
}
