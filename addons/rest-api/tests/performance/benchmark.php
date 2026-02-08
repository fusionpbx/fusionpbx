#!/usr/bin/env php
<?php
/**
 * FusionPBX Performance Benchmark CLI Script
 *
 * Run performance benchmarks from command line and output formatted results.
 *
 * Usage:
 *   php benchmark.php [options]
 *
 * Options:
 *   --test=<name>     Run specific test (list100, list1000, get, create, update, delete, search, all)
 *   --iterations=<n>  Number of iterations (default: 100)
 *   --warmup=<n>      Number of warmup requests (default: 5)
 *   --format=<fmt>    Output format: text, json, csv (default: text)
 *   --verbose         Enable verbose output
 *   --help            Show this help message
 *
 * Examples:
 *   php benchmark.php --test=list100
 *   php benchmark.php --test=all --iterations=200 --format=json
 *   php benchmark.php --test=create --verbose
 */

require_once __DIR__ . '/bootstrap.php';

// Parse command line arguments
$options = parseCliArguments($argv);

if (isset($options['help'])) {
    showHelp();
    exit(0);
}

// Override configuration with CLI options
if (isset($options['iterations'])) {
    define('CLI_ITERATIONS', (int)$options['iterations']);
} else {
    define('CLI_ITERATIONS', PERF_ITERATIONS);
}

if (isset($options['warmup'])) {
    define('CLI_WARMUP', (int)$options['warmup']);
} else {
    define('CLI_WARMUP', PERF_WARMUP_REQUESTS);
}

if (isset($options['format'])) {
    define('CLI_FORMAT', $options['format']);
} else {
    define('CLI_FORMAT', PERF_OUTPUT_FORMAT);
}

if (isset($options['verbose'])) {
    define('CLI_VERBOSE', true);
} else {
    define('CLI_VERBOSE', PERF_VERBOSE);
}

// Determine which test to run
$testName = $options['test'] ?? 'all';

// Banner
printBanner();

// Setup test data
echo "Setting up test data...\n";
$testExtensions = setupTestData();
echo "Test data ready.\n\n";

// Run benchmarks
$allResults = [];

if ($testName === 'all' || $testName === 'list100') {
    $allResults['list100'] = runList100Benchmark();
}

if ($testName === 'all' || $testName === 'list1000') {
    $allResults['list1000'] = runList1000Benchmark();
}

if ($testName === 'all' || $testName === 'get') {
    if (!empty($testExtensions)) {
        $allResults['get'] = runGetBenchmark($testExtensions[0]);
    }
}

if ($testName === 'all' || $testName === 'create') {
    $allResults['create'] = runCreateBenchmark();
}

if ($testName === 'all' || $testName === 'update') {
    if (!empty($testExtensions)) {
        $allResults['update'] = runUpdateBenchmark($testExtensions[0]);
    }
}

if ($testName === 'all' || $testName === 'delete') {
    $allResults['delete'] = runDeleteBenchmark();
}

if ($testName === 'all' || $testName === 'search') {
    $allResults['search'] = runSearchBenchmark();
}

// Cleanup
echo "\nCleaning up test data...\n";
cleanupTestData($testExtensions);
echo "Cleanup complete.\n\n";

// Summary
printSummary($allResults);

// Save results
if (CLI_FORMAT === 'json') {
    echo json_encode($allResults, JSON_PRETTY_PRINT) . "\n";
}

exit(0);

// ============================================================================
// Benchmark Functions
// ============================================================================

function runList100Benchmark() {
    return runBenchmark(
        'List 100 Items (P50)',
        function () {
            return perf_api_request('GET', '/extensions', ['per_page' => 100]);
        },
        PERF_TARGET_LIST_100_P50,
        'p50'
    );
}

function runList1000Benchmark() {
    return runBenchmark(
        'List 1000 Items (P95)',
        function () {
            return perf_api_request('GET', '/extensions', ['per_page' => 100, 'page' => 1]);
        },
        PERF_TARGET_LIST_1000_P95,
        'p95'
    );
}

function runGetBenchmark($extensionUuid) {
    return runBenchmark(
        'Get Single Item',
        function () use ($extensionUuid) {
            return perf_api_request('GET', "/extensions/$extensionUuid");
        },
        PERF_TARGET_GET_SINGLE,
        'p50'
    );
}

function runCreateBenchmark() {
    $createdUuids = [];

    $result = runBenchmark(
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

function runUpdateBenchmark($extensionUuid) {
    return runBenchmark(
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

function runDeleteBenchmark() {
    // Create items to delete
    $itemsToDelete = [];
    echo "Creating items for delete benchmark...\n";
    for ($i = 0; $i < CLI_ITERATIONS; $i++) {
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
    return runBenchmark(
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

function runSearchBenchmark() {
    return runBenchmark(
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

function runBenchmark($name, callable $operation, $target, $metric = 'p50') {
    echo str_repeat('=', 70) . "\n";
    echo "BENCHMARK: $name\n";
    echo str_repeat('=', 70) . "\n";

    // Warmup
    if (CLI_VERBOSE) {
        echo "Warmup phase (" . CLI_WARMUP . " requests)...\n";
    }
    for ($i = 0; $i < CLI_WARMUP; $i++) {
        $operation();
        if (CLI_VERBOSE) {
            echo ".";
        }
    }
    if (CLI_VERBOSE) {
        echo "\n";
    }

    // Actual measurements
    echo "Running " . CLI_ITERATIONS . " iterations...\n";
    $latencies = [];
    $startTime = microtime(true);

    for ($i = 0; $i < CLI_ITERATIONS; $i++) {
        $response = $operation();
        $latencies[] = $response['duration_ms'];

        if (CLI_VERBOSE && ($i + 1) % 10 === 0) {
            echo "Progress: " . ($i + 1) . "/" . CLI_ITERATIONS . "\n";
        }
    }

    $endTime = microtime(true);
    $totalTime = ($endTime - $startTime) * 1000;

    // Calculate statistics
    $stats = calculate_stats($latencies);
    $stats['total_time_ms'] = $totalTime;
    $stats['requests_per_second'] = (CLI_ITERATIONS / ($totalTime / 1000));

    // Output results
    echo "\n";
    echo "Results:\n";
    echo str_repeat('-', 70) . "\n";

    if (CLI_FORMAT === 'csv') {
        echo "Metric,Value\n";
        echo "Min," . round($stats['min'], 2) . "\n";
        echo "Max," . round($stats['max'], 2) . "\n";
        echo "Mean," . round($stats['mean'], 2) . "\n";
        echo "Median," . round($stats['median'], 2) . "\n";
        echo "P50," . round($stats['p50'], 2) . "\n";
        echo "P95," . round($stats['p95'], 2) . "\n";
        echo "P99," . round($stats['p99'], 2) . "\n";
        echo "StdDev," . round($stats['stddev'], 2) . "\n";
    } else {
        printf("  Min:              %8.2f ms\n", $stats['min']);
        printf("  Max:              %8.2f ms\n", $stats['max']);
        printf("  Mean:             %8.2f ms\n", $stats['mean']);
        printf("  Median (P50):     %8.2f ms\n", $stats['median']);
        printf("  P95:              %8.2f ms\n", $stats['p95']);
        printf("  P99:              %8.2f ms\n", $stats['p99']);
        printf("  StdDev:           %8.2f ms\n", $stats['stddev']);
        printf("  Total Time:       %8.2f ms\n", $totalTime);
        printf("  Requests/sec:     %8.2f\n", $stats['requests_per_second']);
    }

    echo str_repeat('-', 70) . "\n";
    printf("Target %s:         %8.2f ms\n", strtoupper($metric), $target);
    printf("Actual %s:         %8.2f ms\n", strtoupper($metric), $stats[$metric]);

    $passed = $stats[$metric] <= $target;
    $status = $passed ? '✓ PASS' : '✗ FAIL';
    $diff = $stats[$metric] - $target;

    if ($passed) {
        echo "Status:            $status (under by " . abs($diff) . " ms)\n";
    } else {
        echo "Status:            $status (over by " . abs($diff) . " ms)\n";
    }

    echo "\n";

    return [
        'name' => $name,
        'timestamp' => date('Y-m-d H:i:s'),
        'iterations' => CLI_ITERATIONS,
        'warmup' => CLI_WARMUP,
        'target' => $target,
        'metric' => $metric,
        'passed' => $passed,
        'statistics' => $stats
    ];
}

// ============================================================================
// Helper Functions
// ============================================================================

function setupTestData() {
    $extensions = [];

    // Create a small set of test extensions
    for ($i = 0; $i < 100; $i++) {
        $extension = sprintf('9%04d', $i);
        $response = perf_api_request('POST', '/extensions', [
            'extension' => $extension,
            'effective_caller_id_name' => "Benchmark User $i",
            'effective_caller_id_number' => $extension,
            'enabled' => 'true'
        ]);

        if ($response['status'] === 201 && isset($response['body']['data']['extension_uuid'])) {
            $extensions[] = $response['body']['data']['extension_uuid'];
        }

        if (($i + 1) % 25 === 0) {
            echo "Created " . ($i + 1) . " test extensions\n";
        }
    }

    return $extensions;
}

function cleanupTestData($extensions) {
    foreach ($extensions as $uuid) {
        perf_api_request('DELETE', "/extensions/$uuid");
    }
}

function printBanner() {
    echo "\n";
    echo str_repeat('=', 70) . "\n";
    echo "  FusionPBX API Performance Benchmark Suite\n";
    echo str_repeat('=', 70) . "\n";
    echo "  Configuration:\n";
    echo "    API URL:       " . PERF_API_BASE_URL . "\n";
    echo "    Domain:        " . PERF_DOMAIN . "\n";
    echo "    Iterations:    " . CLI_ITERATIONS . "\n";
    echo "    Warmup:        " . CLI_WARMUP . "\n";
    echo "    Format:        " . CLI_FORMAT . "\n";
    echo str_repeat('=', 70) . "\n";
    echo "\n";
}

function printSummary($results) {
    echo str_repeat('=', 70) . "\n";
    echo "SUMMARY\n";
    echo str_repeat('=', 70) . "\n";

    $totalTests = count($results);
    $passedTests = 0;

    foreach ($results as $test) {
        if ($test['passed']) {
            $passedTests++;
        }
    }

    echo "Total Tests:   $totalTests\n";
    echo "Passed:        $passedTests\n";
    echo "Failed:        " . ($totalTests - $passedTests) . "\n";
    echo "\n";

    foreach ($results as $key => $result) {
        $status = $result['passed'] ? '✓' : '✗';
        $metric = strtoupper($result['metric']);
        printf(
            "%s %-30s %s: %.2f ms (target: %.2f ms)\n",
            $status,
            $result['name'],
            $metric,
            $result['statistics'][$result['metric']],
            $result['target']
        );
    }

    echo str_repeat('=', 70) . "\n";
}

function parseCliArguments($argv) {
    $options = [];

    for ($i = 1; $i < count($argv); $i++) {
        $arg = $argv[$i];

        if ($arg === '--help' || $arg === '-h') {
            $options['help'] = true;
        } elseif ($arg === '--verbose' || $arg === '-v') {
            $options['verbose'] = true;
        } elseif (strpos($arg, '--') === 0) {
            $parts = explode('=', substr($arg, 2), 2);
            if (count($parts) === 2) {
                $options[$parts[0]] = $parts[1];
            }
        }
    }

    return $options;
}

function showHelp() {
    echo <<<HELP

FusionPBX Performance Benchmark CLI

Usage:
  php benchmark.php [options]

Options:
  --test=<name>     Run specific test (list100, list1000, get, create,
                    update, delete, search, all)
                    Default: all

  --iterations=<n>  Number of iterations per benchmark
                    Default: 100

  --warmup=<n>      Number of warmup requests before measurement
                    Default: 5

  --format=<fmt>    Output format: text, json, csv
                    Default: text

  --verbose, -v     Enable verbose output

  --help, -h        Show this help message

Examples:
  php benchmark.php --test=list100
  php benchmark.php --test=all --iterations=200 --format=json
  php benchmark.php --test=create --verbose

Available Tests:
  list100    - List 100 items (P50 target: <50ms)
  list1000   - List 1000 items (P95 target: <100ms)
  get        - Get single item (P50 target: <20ms)
  create     - Create item (P50 target: <100ms)
  update     - Update item (P50 target: <100ms)
  delete     - Delete item (P50 target: <50ms)
  search     - Search/filter (P50 target: <100ms)
  all        - Run all benchmarks

HELP;
}
