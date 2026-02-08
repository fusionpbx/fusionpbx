<?php
/**
 * FusionPBX API Performance Benchmark Tests
 *
 * Measures API endpoint performance and compares against target metrics.
 * Run with: phpunit --bootstrap bootstrap.php BenchmarkTest.php
 */

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';

class BenchmarkTest extends TestCase
{
    private static $testExtensions = [];
    private static $testRingGroups = [];

    /**
     * Setup test data before running benchmarks
     */
    public static function setUpBeforeClass(): void
    {
        // Create test data for benchmarks
        self::createTestData();
    }

    /**
     * Cleanup test data after all benchmarks complete
     */
    public static function tearDownAfterClass(): void
    {
        // Cleanup created test data
        self::cleanupTestData();
    }

    /**
     * Create test data for benchmarks
     */
    private static function createTestData()
    {
        echo "\nCreating test data for benchmarks...\n";

        // Create extensions for list tests
        $extensionsToCreate = PERF_LARGE_DATASET_SIZE;
        for ($i = 0; $i < $extensionsToCreate; $i++) {
            $extension = sprintf('9%04d', $i);
            $response = perf_api_request('POST', '/extensions', [
                'extension' => $extension,
                'effective_caller_id_name' => "Benchmark User $i",
                'effective_caller_id_number' => $extension,
                'outbound_caller_id_name' => "Benchmark User $i",
                'outbound_caller_id_number' => $extension,
                'enabled' => 'true'
            ]);

            if ($response['status'] === 201 && isset($response['body']['data']['extension_uuid'])) {
                self::$testExtensions[] = $response['body']['data']['extension_uuid'];
            }

            if (($i + 1) % 100 === 0) {
                echo "Created " . ($i + 1) . " extensions\n";
            }
        }

        // Create ring groups for additional tests
        for ($i = 0; $i < 50; $i++) {
            $response = perf_api_request('POST', '/ring_groups', [
                'ring_group_name' => "Benchmark Ring Group $i",
                'ring_group_extension' => sprintf('7%03d', $i),
                'ring_group_strategy' => 'simultaneous',
                'ring_group_enabled' => 'true'
            ]);

            if ($response['status'] === 201 && isset($response['body']['data']['ring_group_uuid'])) {
                self::$testRingGroups[] = $response['body']['data']['ring_group_uuid'];
            }
        }

        echo "Test data creation complete.\n";
    }

    /**
     * Cleanup test data
     */
    private static function cleanupTestData()
    {
        echo "\nCleaning up test data...\n";

        foreach (self::$testExtensions as $uuid) {
            perf_api_request('DELETE', "/extensions/$uuid");
        }

        foreach (self::$testRingGroups as $uuid) {
            perf_api_request('DELETE', "/ring_groups/$uuid");
        }

        echo "Cleanup complete.\n";
    }

    /**
     * Benchmark: List 100 items - P50 latency should be < 50ms
     *
     * @group benchmark
     * @group list
     */
    public function testList100ItemsP50Latency()
    {
        $this->runBenchmark(
            'List 100 Items (P50)',
            function () {
                return perf_api_request('GET', '/extensions', ['per_page' => 100]);
            },
            PERF_TARGET_LIST_100_P50,
            'p50'
        );
    }

    /**
     * Benchmark: List 1000 items - P95 latency should be < 100ms
     *
     * @group benchmark
     * @group list
     */
    public function testList1000ItemsP95Latency()
    {
        $this->runBenchmark(
            'List 1000 Items (P95)',
            function () {
                return perf_api_request('GET', '/extensions', ['per_page' => 100, 'page' => 1]);
            },
            PERF_TARGET_LIST_1000_P95,
            'p95'
        );
    }

    /**
     * Benchmark: Get single item - should be < 20ms
     *
     * @group benchmark
     * @group get
     */
    public function testGetSingleItemLatency()
    {
        if (empty(self::$testExtensions)) {
            $this->markTestSkipped('No test extensions available');
        }

        $extensionUuid = self::$testExtensions[0];

        $this->runBenchmark(
            'Get Single Item',
            function () use ($extensionUuid) {
                return perf_api_request('GET', "/extensions/$extensionUuid");
            },
            PERF_TARGET_GET_SINGLE,
            'p50'
        );
    }

    /**
     * Benchmark: Create item - should be < 100ms
     *
     * @group benchmark
     * @group create
     */
    public function testCreateItemLatency()
    {
        $createdUuids = [];

        $result = $this->runBenchmark(
            'Create Item',
            function () use (&$createdUuids) {
                $extension = sprintf('8%04d', rand(1000, 9999));
                $response = perf_api_request('POST', '/extensions', [
                    'extension' => $extension,
                    'effective_caller_id_name' => 'Benchmark Create',
                    'effective_caller_id_number' => $extension,
                    'enabled' => 'true'
                ]);

                if ($response['status'] === 201 && isset($response['body']['data']['extension_uuid'])) {
                    $createdUuids[] = $response['body']['data']['extension_uuid'];
                }

                return $response;
            },
            PERF_TARGET_CREATE,
            'p50'
        );

        // Cleanup created items
        foreach ($createdUuids as $uuid) {
            perf_api_request('DELETE', "/extensions/$uuid");
        }

        return $result;
    }

    /**
     * Benchmark: Update item - should be < 100ms
     *
     * @group benchmark
     * @group update
     */
    public function testUpdateItemLatency()
    {
        if (empty(self::$testExtensions)) {
            $this->markTestSkipped('No test extensions available');
        }

        $extensionUuid = self::$testExtensions[0];

        $this->runBenchmark(
            'Update Item',
            function () use ($extensionUuid) {
                return perf_api_request('PUT', "/extensions/$extensionUuid", [
                    'effective_caller_id_name' => 'Updated Benchmark User ' . time()
                ]);
            },
            PERF_TARGET_UPDATE,
            'p50'
        );
    }

    /**
     * Benchmark: Delete item - should be < 50ms
     *
     * @group benchmark
     * @group delete
     */
    public function testDeleteItemLatency()
    {
        // Create items to delete
        $itemsToDelete = [];
        for ($i = 0; $i < PERF_ITERATIONS; $i++) {
            $extension = sprintf('8%04d', rand(1000, 9999));
            $response = perf_api_request('POST', '/extensions', [
                'extension' => $extension,
                'effective_caller_id_name' => 'Benchmark Delete',
                'effective_caller_id_number' => $extension,
                'enabled' => 'true'
            ]);

            if ($response['status'] === 201 && isset($response['body']['data']['extension_uuid'])) {
                $itemsToDelete[] = $response['body']['data']['extension_uuid'];
            }
        }

        $index = 0;
        $this->runBenchmark(
            'Delete Item',
            function () use ($itemsToDelete, &$index) {
                if (!isset($itemsToDelete[$index])) {
                    return ['duration_ms' => 0, 'status' => 404];
                }
                $uuid = $itemsToDelete[$index++];
                return perf_api_request('DELETE', "/extensions/$uuid");
            },
            PERF_TARGET_DELETE,
            'p50'
        );
    }

    /**
     * Benchmark: Search/filter operations - should be < 100ms
     *
     * @group benchmark
     * @group search
     */
    public function testSearchFilterLatency()
    {
        $this->runBenchmark(
            'Search/Filter',
            function () {
                return perf_api_request('GET', '/extensions', [
                    'search' => 'Benchmark',
                    'per_page' => 50
                ]);
            },
            PERF_TARGET_SEARCH,
            'p50'
        );
    }

    /**
     * Benchmark: Pagination performance
     *
     * @group benchmark
     * @group pagination
     */
    public function testPaginationLatency()
    {
        $this->runBenchmark(
            'Pagination (Page 5)',
            function () {
                return perf_api_request('GET', '/extensions', [
                    'per_page' => 20,
                    'page' => 5
                ]);
            },
            50, // Target: 50ms for paginated results
            'p50'
        );
    }

    /**
     * Benchmark: Concurrent requests handling
     *
     * @group benchmark
     * @group concurrent
     */
    public function testConcurrentRequestsLatency()
    {
        if (empty(self::$testExtensions)) {
            $this->markTestSkipped('No test extensions available');
        }

        echo "\nRunning concurrent requests benchmark...\n";

        $mh = curl_multi_init();
        $handles = [];
        $startTime = microtime(true);

        // Create concurrent requests
        for ($i = 0; $i < PERF_CONCURRENT_REQUESTS; $i++) {
            $url = PERF_API_BASE_URL . '/extensions?per_page=20';
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'X-API-Key: ' . PERF_API_KEY,
                'X-Domain: ' . PERF_DOMAIN
            ]);
            curl_multi_add_handle($mh, $ch);
            $handles[] = $ch;
        }

        // Execute all requests
        $running = null;
        do {
            curl_multi_exec($mh, $running);
            curl_multi_select($mh);
        } while ($running > 0);

        $endTime = microtime(true);
        $totalDuration = ($endTime - $startTime) * 1000;

        // Cleanup
        foreach ($handles as $ch) {
            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
        }
        curl_multi_close($mh);

        $avgLatency = $totalDuration / PERF_CONCURRENT_REQUESTS;

        echo sprintf(
            "Concurrent Requests: %d requests in %.2fms (avg: %.2fms per request)\n",
            PERF_CONCURRENT_REQUESTS,
            $totalDuration,
            $avgLatency
        );

        // Target: average latency should be reasonable under concurrent load
        $this->assertLessThan(
            200,
            $avgLatency,
            "Average latency under concurrent load should be < 200ms"
        );
    }

    /**
     * Run a benchmark test
     *
     * @param string $name Benchmark name
     * @param callable $operation Operation to benchmark
     * @param float $target Target latency in milliseconds
     * @param string $metric Metric to compare against target (p50, p95, p99, mean)
     * @return array Benchmark results
     */
    private function runBenchmark($name, callable $operation, $target, $metric = 'p50')
    {
        echo "\nRunning benchmark: $name\n";

        // Warmup
        if (PERF_VERBOSE) {
            echo "Warmup phase...\n";
        }
        for ($i = 0; $i < PERF_WARMUP_REQUESTS; $i++) {
            $operation();
        }

        // Actual measurements
        if (PERF_VERBOSE) {
            echo "Measurement phase...\n";
        }
        $latencies = [];
        for ($i = 0; $i < PERF_ITERATIONS; $i++) {
            $response = $operation();
            $latencies[] = $response['duration_ms'];

            if (PERF_VERBOSE && ($i + 1) % 10 === 0) {
                echo "Completed " . ($i + 1) . " iterations\n";
            }
        }

        // Calculate statistics
        $stats = calculate_stats($latencies);

        // Output results
        echo "Results: " . format_stats($stats, PERF_OUTPUT_FORMAT) . "\n";
        echo sprintf("Target %s: %.2fms | Actual %s: %.2fms | ",
            strtoupper($metric),
            $target,
            strtoupper($metric),
            $stats[$metric]
        );

        if ($stats[$metric] <= $target) {
            echo "✓ PASS\n";
        } else {
            echo "✗ FAIL (%.2fms over target)\n";
        }

        // Save results
        $results = [
            'name' => $name,
            'timestamp' => date('Y-m-d H:i:s'),
            'iterations' => PERF_ITERATIONS,
            'warmup' => PERF_WARMUP_REQUESTS,
            'target' => $target,
            'metric' => $metric,
            'statistics' => $stats,
            'latencies' => $latencies
        ];
        save_results(str_replace(' ', '_', strtolower($name)), $results);

        // Assert against target
        $this->assertLessThanOrEqual(
            $target,
            $stats[$metric],
            sprintf(
                "%s %s latency (%.2fms) exceeds target (%.2fms)",
                $name,
                strtoupper($metric),
                $stats[$metric],
                $target
            )
        );

        return $results;
    }
}
