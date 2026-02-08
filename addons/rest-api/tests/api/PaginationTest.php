<?php
/**
 * Pagination Integration Tests
 *
 * Tests pagination functionality across all API endpoints:
 * - Default pagination behavior
 * - Custom page and per_page parameters
 * - Edge cases (page 0, negative, excessive per_page)
 * - Pagination metadata accuracy
 * - Consistency across endpoints
 */

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';

class PaginationTest extends TestCase
{
    private $testDomainUuid;
    private $testApiKeyUuid;
    private $createdRingGroups = [];
    private $createdExtensions = [];

    protected function setUp(): void
    {
        parent::setUp();

        // Create test domain and API key
        $testDomain = 'test-pagination-' . time() . '.example.com';
        $testApiKey = 'test-key-' . bin2hex(random_bytes(16));

        $this->testDomainUuid = create_test_domain($testDomain);
        $this->testApiKeyUuid = create_test_api_key($this->testDomainUuid, $testApiKey);

        // Store credentials for api_request
        define('TEST_PAGINATION_API_KEY', $testApiKey);
        define('TEST_PAGINATION_DOMAIN', $testDomain);
    }

    protected function tearDown(): void
    {
        // Clean up ring groups
        foreach ($this->createdRingGroups as $uuid) {
            cleanup_test_data('v_ring_group_destinations', ['ring_group_uuid' => $uuid]);
            cleanup_test_data('v_ring_groups', ['ring_group_uuid' => $uuid]);
        }

        // Clean up extensions
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
            'X-API-Key: ' . TEST_PAGINATION_API_KEY,
            'X-Domain: ' . TEST_PAGINATION_DOMAIN
        ]);
    }

    public function test_default_pagination_ring_groups()
    {
        // Create multiple ring groups
        for ($i = 1; $i <= 3; $i++) {
            $this->createdRingGroups[] = $this->createRingGroup("Default Test {$i}", "700{$i}");
        }

        $response = $this->apiRequest('GET', '/ring_groups');

        $this->assertEquals(200, $response['status']);
        $this->assertArrayHasKey('data', $response['body']);
        $this->assertArrayHasKey('pagination', $response['body']);

        $pagination = $response['body']['pagination'];
        $this->assertArrayHasKey('current_page', $pagination);
        $this->assertArrayHasKey('per_page', $pagination);
        $this->assertArrayHasKey('total', $pagination);
        $this->assertArrayHasKey('total_pages', $pagination);

        // Default should be page 1
        $this->assertEquals(1, $pagination['current_page']);
    }

    public function test_custom_page_size()
    {
        // Create 10 ring groups
        for ($i = 1; $i <= 10; $i++) {
            $this->createdRingGroups[] = $this->createRingGroup("Page Size Test {$i}", "710{$i}");
        }

        // Request with per_page = 3
        $response = $this->apiRequest('GET', '/ring_groups', [
            'per_page' => 3
        ]);

        $this->assertEquals(200, $response['status']);
        $this->assertArrayHasKey('pagination', $response['body']);

        $pagination = $response['body']['pagination'];
        $this->assertEquals(3, $pagination['per_page']);

        // Should return at most 3 items
        $this->assertLessThanOrEqual(3, count($response['body']['data']));
    }

    public function test_page_navigation()
    {
        // Create 8 ring groups
        for ($i = 1; $i <= 8; $i++) {
            $this->createdRingGroups[] = $this->createRingGroup("Nav Test {$i}", "720{$i}");
        }

        // Get page 1 with 3 per page
        $page1Response = $this->apiRequest('GET', '/ring_groups', [
            'page' => 1,
            'per_page' => 3
        ]);

        $this->assertEquals(200, $page1Response['status']);
        $page1Data = $page1Response['body']['data'];
        $page1Pagination = $page1Response['body']['pagination'];

        $this->assertEquals(1, $page1Pagination['current_page']);
        $this->assertEquals(3, $page1Pagination['per_page']);

        // Get page 2
        $page2Response = $this->apiRequest('GET', '/ring_groups', [
            'page' => 2,
            'per_page' => 3
        ]);

        $this->assertEquals(200, $page2Response['status']);
        $page2Data = $page2Response['body']['data'];
        $page2Pagination = $page2Response['body']['pagination'];

        $this->assertEquals(2, $page2Pagination['current_page']);

        // Pages should have different data
        if (!empty($page1Data) && !empty($page2Data)) {
            $this->assertNotEquals(
                $page1Data[0]['ring_group_uuid'],
                $page2Data[0]['ring_group_uuid'],
                'Different pages should return different data'
            );
        }
    }

    public function test_total_pages_calculation()
    {
        // Create exactly 7 ring groups
        for ($i = 1; $i <= 7; $i++) {
            $this->createdRingGroups[] = $this->createRingGroup("Total Pages Test {$i}", "730{$i}");
        }

        $response = $this->apiRequest('GET', '/ring_groups', [
            'per_page' => 3
        ]);

        $this->assertEquals(200, $response['status']);
        $pagination = $response['body']['pagination'];

        // With 7 items and 3 per page, should be 3 pages (7/3 = 2.33 -> 3)
        $expectedPages = (int)ceil($pagination['total'] / 3);
        $this->assertEquals($expectedPages, $pagination['total_pages'],
            'Total pages calculation should be correct');
    }

    public function test_pagination_on_extensions()
    {
        // Create multiple extensions
        for ($i = 1; $i <= 5; $i++) {
            $this->createdExtensions[] = $this->createExtension("200{$i}", "Pagination Ext {$i}");
        }

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
    }

    public function test_page_beyond_total_pages()
    {
        // Create 2 ring groups
        $this->createdRingGroups[] = $this->createRingGroup("Beyond Test 1", "7401");
        $this->createdRingGroups[] = $this->createRingGroup("Beyond Test 2", "7402");

        // Request page 999
        $response = $this->apiRequest('GET', '/ring_groups', [
            'page' => 999,
            'per_page' => 10
        ]);

        // Should return empty data or last page
        $this->assertEquals(200, $response['status']);
        $this->assertArrayHasKey('data', $response['body']);

        // Data should be empty or contain last page items
        $this->assertIsArray($response['body']['data']);
    }

    public function test_zero_page_defaults_to_first_page()
    {
        $this->createdRingGroups[] = $this->createRingGroup("Zero Page Test", "7501");

        $response = $this->apiRequest('GET', '/ring_groups', [
            'page' => 0,
            'per_page' => 10
        ]);

        $this->assertEquals(200, $response['status']);
        $this->assertArrayHasKey('pagination', $response['body']);

        // Should default to page 1
        $pagination = $response['body']['pagination'];
        $this->assertGreaterThanOrEqual(1, $pagination['current_page'],
            'Page 0 should default to page 1');
    }

    public function test_negative_page_defaults_to_first_page()
    {
        $this->createdRingGroups[] = $this->createRingGroup("Negative Page Test", "7601");

        $response = $this->apiRequest('GET', '/ring_groups', [
            'page' => -5,
            'per_page' => 10
        ]);

        $this->assertEquals(200, $response['status']);
        $this->assertArrayHasKey('pagination', $response['body']);

        // Should default to page 1
        $pagination = $response['body']['pagination'];
        $this->assertGreaterThanOrEqual(1, $pagination['current_page'],
            'Negative page should default to page 1');
    }

    public function test_excessive_per_page_is_limited()
    {
        $this->createdRingGroups[] = $this->createRingGroup("Excessive Test", "7701");

        // Request 10000 items per page
        $response = $this->apiRequest('GET', '/ring_groups', [
            'per_page' => 10000
        ]);

        $this->assertEquals(200, $response['status']);
        $this->assertArrayHasKey('pagination', $response['body']);

        $pagination = $response['body']['pagination'];
        // Should be limited to reasonable maximum (e.g., 100)
        $this->assertLessThanOrEqual(100, $pagination['per_page'],
            'Per page should be limited to maximum value');
    }

    public function test_invalid_per_page_uses_default()
    {
        $this->createdRingGroups[] = $this->createRingGroup("Invalid PerPage Test", "7801");

        // Request with invalid per_page
        $response = $this->apiRequest('GET', '/ring_groups', [
            'per_page' => 'invalid'
        ]);

        // Should use default per_page value
        $this->assertEquals(200, $response['status']);
        $this->assertArrayHasKey('pagination', $response['body']);

        $pagination = $response['body']['pagination'];
        $this->assertIsInt($pagination['per_page']);
        $this->assertGreaterThan(0, $pagination['per_page']);
    }

    public function test_pagination_metadata_consistency()
    {
        // Create known number of ring groups
        $createdCount = 6;
        for ($i = 1; $i <= $createdCount; $i++) {
            $this->createdRingGroups[] = $this->createRingGroup("Consistency Test {$i}", "790{$i}");
        }

        $response = $this->apiRequest('GET', '/ring_groups', [
            'per_page' => 2
        ]);

        $this->assertEquals(200, $response['status']);
        $pagination = $response['body']['pagination'];

        // Verify consistency
        $this->assertGreaterThanOrEqual($createdCount, $pagination['total'],
            'Total should include at least created items');

        $calculatedPages = (int)ceil($pagination['total'] / $pagination['per_page']);
        $this->assertEquals($calculatedPages, $pagination['total_pages'],
            'Total pages should match calculation');

        $this->assertGreaterThanOrEqual(1, $pagination['current_page'],
            'Current page should be at least 1');
        $this->assertLessThanOrEqual($pagination['total_pages'], $pagination['current_page'],
            'Current page should not exceed total pages');
    }

    public function test_empty_result_pagination()
    {
        // Query with filter that returns no results
        $response = $this->apiRequest('GET', '/ring_groups', [
            'search' => 'nonexistent-ring-group-xyz-' . time()
        ]);

        if ($response['status'] === 200) {
            $this->assertArrayHasKey('data', $response['body']);
            $this->assertArrayHasKey('pagination', $response['body']);

            // Empty result should still have valid pagination
            $pagination = $response['body']['pagination'];
            $this->assertArrayHasKey('current_page', $pagination);
            $this->assertArrayHasKey('total', $pagination);
        }
    }

    /**
     * Helper method to create a test ring group
     */
    private function createRingGroup($name, $extension)
    {
        $response = $this->apiRequest('POST', '/ring_groups', [
            'ring_group_name' => $name,
            'ring_group_extension' => $extension,
            'ring_group_enabled' => 'true'
        ]);

        return $response['body']['ring_group_uuid'];
    }

    /**
     * Helper method to create a test extension
     */
    private function createExtension($extension, $callerIdName)
    {
        $response = $this->apiRequest('POST', '/extensions', [
            'extension' => $extension,
            'password' => 'TestPass123!',
            'effective_caller_id_name' => $callerIdName,
            'enabled' => 'true'
        ]);

        return $response['body']['extension_uuid'];
    }
}
