<?php
/**
 * Ring Groups API Integration Tests
 *
 * Tests CRUD operations for ring groups endpoint:
 * - Create ring group
 * - Get all ring groups (with pagination)
 * - Get single ring group
 * - Update ring group
 * - Delete ring group
 * - Relationships (destinations, users)
 */

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';

class RingGroupsTest extends TestCase
{
    private $testDomainUuid;
    private $testApiKeyUuid;
    private $createdRingGroups = [];

    protected function setUp(): void
    {
        parent::setUp();

        // Create test domain and API key
        $testDomain = 'test-ringgroups-' . time() . '.example.com';
        $testApiKey = 'test-key-' . bin2hex(random_bytes(16));

        $this->testDomainUuid = create_test_domain($testDomain);
        $this->testApiKeyUuid = create_test_api_key($this->testDomainUuid, $testApiKey);

        // Store credentials for api_request
        define('TEST_RING_GROUPS_API_KEY', $testApiKey);
        define('TEST_RING_GROUPS_DOMAIN', $testDomain);
    }

    protected function tearDown(): void
    {
        // Clean up created ring groups
        foreach ($this->createdRingGroups as $uuid) {
            cleanup_test_data('v_ring_group_destinations', ['ring_group_uuid' => $uuid]);
            cleanup_test_data('v_ring_group_users', ['ring_group_uuid' => $uuid]);
            cleanup_test_data('v_ring_groups', ['ring_group_uuid' => $uuid]);
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
            'X-API-Key: ' . TEST_RING_GROUPS_API_KEY,
            'X-Domain: ' . TEST_RING_GROUPS_DOMAIN
        ]);
    }

    public function test_create_ring_group_success()
    {
        $ringGroupData = [
            'ring_group_name' => 'Test Sales Team',
            'ring_group_extension' => '6000',
            'ring_group_strategy' => 'simultaneous',
            'ring_group_timeout_sec' => 30,
            'ring_group_enabled' => 'true',
            'ring_group_description' => 'Test sales team ring group'
        ];

        $response = $this->apiRequest('POST', '/ring_groups', $ringGroupData);

        $this->assertContains($response['status'], [200, 201],
            'Create ring group should return 200 or 201');

        $this->assertIsArray($response['body'], 'Response body should be an array');
        $this->assertArrayHasKey('ring_group_uuid', $response['body'],
            'Response should contain ring_group_uuid');

        // Store for cleanup
        $this->createdRingGroups[] = $response['body']['ring_group_uuid'];

        // Verify returned data
        $this->assertEquals($ringGroupData['ring_group_name'],
            $response['body']['ring_group_name']);
        $this->assertEquals($ringGroupData['ring_group_extension'],
            $response['body']['ring_group_extension']);
    }

    public function test_create_ring_group_missing_required_fields()
    {
        $response = $this->apiRequest('POST', '/ring_groups', [
            'ring_group_name' => 'Incomplete Ring Group'
            // Missing ring_group_extension
        ]);

        $this->assertEquals(400, $response['status'],
            'Missing required fields should return 400');

        $this->assertArrayHasKey('error', $response['body'],
            'Error response should contain error key');
    }

    public function test_get_all_ring_groups()
    {
        // Create test ring groups
        $ringGroup1 = $this->createTestRingGroup('Test Group 1', '6001');
        $ringGroup2 = $this->createTestRingGroup('Test Group 2', '6002');

        $response = $this->apiRequest('GET', '/ring_groups');

        $this->assertEquals(200, $response['status'],
            'Get all ring groups should return 200');

        $this->assertIsArray($response['body'], 'Response body should be an array');
        $this->assertArrayHasKey('data', $response['body'],
            'Response should contain data key');
        $this->assertIsArray($response['body']['data'], 'Data should be an array');

        // Should contain at least our test ring groups
        $this->assertGreaterThanOrEqual(2, count($response['body']['data']),
            'Should return at least the created ring groups');
    }

    public function test_get_ring_group_by_uuid()
    {
        // Create test ring group
        $ringGroupUuid = $this->createTestRingGroup('Test Get Group', '6003');

        $response = $this->apiRequest('GET', "/ring_groups/{$ringGroupUuid}");

        $this->assertEquals(200, $response['status'],
            'Get ring group by UUID should return 200');

        $this->assertIsArray($response['body'], 'Response body should be an array');
        $this->assertEquals($ringGroupUuid, $response['body']['ring_group_uuid'],
            'Should return correct ring group');
        $this->assertEquals('Test Get Group', $response['body']['ring_group_name']);
    }

    public function test_get_ring_group_not_found()
    {
        $fakeUuid = uuid();

        $response = $this->apiRequest('GET', "/ring_groups/{$fakeUuid}");

        $this->assertEquals(404, $response['status'],
            'Non-existent ring group should return 404');

        $this->assertArrayHasKey('error', $response['body'],
            'Error response should contain error key');
    }

    public function test_update_ring_group()
    {
        // Create test ring group
        $ringGroupUuid = $this->createTestRingGroup('Test Update Group', '6004');

        // Update the ring group
        $updateData = [
            'ring_group_name' => 'Updated Group Name',
            'ring_group_timeout_sec' => 45,
            'ring_group_description' => 'Updated description'
        ];

        $response = $this->apiRequest('PUT', "/ring_groups/{$ringGroupUuid}", $updateData);

        $this->assertContains($response['status'], [200, 204],
            'Update ring group should return 200 or 204');

        // Verify update
        $getResponse = $this->apiRequest('GET', "/ring_groups/{$ringGroupUuid}");
        $this->assertEquals('Updated Group Name',
            $getResponse['body']['ring_group_name']);
        $this->assertEquals(45, $getResponse['body']['ring_group_timeout_sec']);
    }

    public function test_update_ring_group_not_found()
    {
        $fakeUuid = uuid();

        $response = $this->apiRequest('PUT', "/ring_groups/{$fakeUuid}", [
            'ring_group_name' => 'Non-existent'
        ]);

        $this->assertEquals(404, $response['status'],
            'Update non-existent ring group should return 404');
    }

    public function test_delete_ring_group()
    {
        // Create test ring group
        $ringGroupUuid = $this->createTestRingGroup('Test Delete Group', '6005');

        // Delete the ring group
        $response = $this->apiRequest('DELETE', "/ring_groups/{$ringGroupUuid}");

        $this->assertContains($response['status'], [200, 204],
            'Delete ring group should return 200 or 204');

        // Verify deletion
        $getResponse = $this->apiRequest('GET', "/ring_groups/{$ringGroupUuid}");
        $this->assertEquals(404, $getResponse['status'],
            'Deleted ring group should not be found');

        // Remove from cleanup list since already deleted
        $this->createdRingGroups = array_filter($this->createdRingGroups,
            fn($uuid) => $uuid !== $ringGroupUuid);
    }

    public function test_delete_ring_group_not_found()
    {
        $fakeUuid = uuid();

        $response = $this->apiRequest('DELETE', "/ring_groups/{$fakeUuid}");

        $this->assertEquals(404, $response['status'],
            'Delete non-existent ring group should return 404');
    }

    public function test_get_ring_groups_with_pagination()
    {
        // Create multiple ring groups
        for ($i = 1; $i <= 5; $i++) {
            $this->createTestRingGroup("Pagination Test {$i}", "60{$i}0");
        }

        // Test first page
        $response = $this->apiRequest('GET', '/ring_groups', [
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

    public function test_get_ring_group_with_destinations()
    {
        // Create ring group with destination
        $ringGroupUuid = $this->createTestRingGroup('Test With Dest', '6020');

        // Add destination
        $db = get_test_db_connection();
        $destUuid = uuid();
        $stmt = $db->prepare("
            INSERT INTO v_ring_group_destinations
            (ring_group_destination_uuid, ring_group_uuid, domain_uuid,
             destination_number, destination_delay)
            VALUES (?, ?, ?, '1001', 0)
        ");
        $stmt->execute([$destUuid, $ringGroupUuid, $this->testDomainUuid]);

        // Get ring group with relationships
        $response = $this->apiRequest('GET', "/ring_groups/{$ringGroupUuid}", [
            'include' => 'destinations'
        ]);

        $this->assertEquals(200, $response['status']);
        $this->assertArrayHasKey('destinations', $response['body'],
            'Response should include destinations');
        $this->assertIsArray($response['body']['destinations']);
        $this->assertGreaterThan(0, count($response['body']['destinations']));

        // Cleanup destination
        cleanup_test_data('v_ring_group_destinations', [
            'ring_group_destination_uuid' => $destUuid
        ]);
    }

    public function test_duplicate_extension_returns_error()
    {
        // Create first ring group
        $this->createTestRingGroup('First Group', '6030');

        // Try to create another with same extension
        $response = $this->apiRequest('POST', '/ring_groups', [
            'ring_group_name' => 'Second Group',
            'ring_group_extension' => '6030'
        ]);

        $this->assertEquals(400, $response['status'],
            'Duplicate extension should return 400');
        $this->assertArrayHasKey('error', $response['body']);
    }

    public function test_ring_group_enabled_toggle()
    {
        // Create enabled ring group
        $ringGroupUuid = $this->createTestRingGroup('Toggle Test', '6040');

        // Disable it
        $response = $this->apiRequest('PUT', "/ring_groups/{$ringGroupUuid}", [
            'ring_group_enabled' => 'false'
        ]);

        $this->assertContains($response['status'], [200, 204]);

        // Verify disabled
        $getResponse = $this->apiRequest('GET', "/ring_groups/{$ringGroupUuid}");
        $this->assertEquals('false', $getResponse['body']['ring_group_enabled']);

        // Enable it again
        $response = $this->apiRequest('PUT', "/ring_groups/{$ringGroupUuid}", [
            'ring_group_enabled' => 'true'
        ]);

        $this->assertContains($response['status'], [200, 204]);

        // Verify enabled
        $getResponse = $this->apiRequest('GET', "/ring_groups/{$ringGroupUuid}");
        $this->assertEquals('true', $getResponse['body']['ring_group_enabled']);
    }

    /**
     * Helper method to create a test ring group
     */
    private function createTestRingGroup($name, $extension)
    {
        $response = $this->apiRequest('POST', '/ring_groups', [
            'ring_group_name' => $name,
            'ring_group_extension' => $extension,
            'ring_group_enabled' => 'true'
        ]);

        $uuid = $response['body']['ring_group_uuid'];
        $this->createdRingGroups[] = $uuid;

        return $uuid;
    }
}
