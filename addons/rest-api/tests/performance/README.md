# FusionPBX API Performance Benchmarks

This directory contains performance benchmark tests for the FusionPBX API. These tests measure endpoint latency and throughput to ensure the API meets performance targets.

## Table of Contents

- [Quick Start](#quick-start)
- [Setup](#setup)
- [Running Benchmarks](#running-benchmarks)
- [Performance Targets](#performance-targets)
- [Interpreting Results](#interpreting-results)
- [Benchmark Details](#benchmark-details)
- [Configuration](#configuration)
- [Troubleshooting](#troubleshooting)

## Quick Start

```bash
# 1. Copy and configure settings
cd tests/performance
cp config.php.example config.php
nano config.php  # Update API_BASE_URL, API_KEY, DOMAIN

# 2. Run all benchmarks via CLI
php benchmark.php

# 3. Or run via PHPUnit
phpunit --bootstrap bootstrap.php BenchmarkTest.php
```

## Setup

### Prerequisites

- PHP 7.4 or higher
- cURL extension enabled
- PHPUnit (for running test suite)
- FusionPBX API with test database
- Valid API key with domain access

### Configuration

1. **Copy the example configuration:**
   ```bash
   cp config.php.example config.php
   ```

2. **Edit `config.php` with your settings:**
   ```php
   define('PERF_API_BASE_URL', 'http://your-server/api');
   define('PERF_API_KEY', 'your-api-key');
   define('PERF_DOMAIN', 'your-domain.com');
   ```

3. **Adjust benchmark parameters (optional):**
   ```php
   define('PERF_ITERATIONS', 100);        // Number of test iterations
   define('PERF_WARMUP_REQUESTS', 5);     // Warmup requests
   define('PERF_CONCURRENT_REQUESTS', 10); // Concurrent load test size
   ```

4. **Set performance targets (optional):**
   ```php
   define('PERF_TARGET_LIST_100_P50', 50);    // 50ms for listing 100 items
   define('PERF_TARGET_LIST_1000_P95', 100);  // 100ms for listing 1000 items
   define('PERF_TARGET_GET_SINGLE', 20);      // 20ms for single item
   define('PERF_TARGET_CREATE', 100);         // 100ms for creation
   define('PERF_TARGET_UPDATE', 100);         // 100ms for update
   define('PERF_TARGET_DELETE', 50);          // 50ms for deletion
   define('PERF_TARGET_SEARCH', 100);         // 100ms for search
   ```

## Running Benchmarks

### Method 1: CLI Script (Recommended)

The CLI script provides a user-friendly interface with progress indicators and formatted output.

**Run all benchmarks:**
```bash
php benchmark.php
```

**Run specific benchmark:**
```bash
php benchmark.php --test=list100
php benchmark.php --test=create
php benchmark.php --test=search
```

**Available tests:**
- `list100` - List 100 items (P50 latency)
- `list1000` - List 1000 items (P95 latency)
- `get` - Get single item
- `create` - Create item
- `update` - Update item
- `delete` - Delete item
- `search` - Search/filter operations
- `all` - Run all benchmarks (default)

**Customize iterations:**
```bash
php benchmark.php --iterations=200
php benchmark.php --iterations=500 --warmup=10
```

**Change output format:**
```bash
php benchmark.php --format=json > results.json
php benchmark.php --format=csv > results.csv
php benchmark.php --format=text
```

**Enable verbose output:**
```bash
php benchmark.php --verbose
php benchmark.php -v
```

**Show help:**
```bash
php benchmark.php --help
```

### Method 2: PHPUnit Test Suite

Run benchmarks using PHPUnit for integration with CI/CD pipelines.

**Run all benchmarks:**
```bash
phpunit --bootstrap bootstrap.php BenchmarkTest.php
```

**Run specific test groups:**
```bash
phpunit --bootstrap bootstrap.php --group list BenchmarkTest.php
phpunit --bootstrap bootstrap.php --group create BenchmarkTest.php
phpunit --bootstrap bootstrap.php --group concurrent BenchmarkTest.php
```

**Available groups:**
- `benchmark` - All benchmark tests
- `list` - List operation tests
- `get` - Get operation tests
- `create` - Create operation tests
- `update` - Update operation tests
- `delete` - Delete operation tests
- `search` - Search operation tests
- `pagination` - Pagination tests
- `concurrent` - Concurrent request tests

**Run with verbose output:**
```bash
phpunit --bootstrap bootstrap.php --verbose BenchmarkTest.php
```

## Performance Targets

The benchmarks measure against the following performance targets:

| Operation | Metric | Target | Description |
|-----------|--------|--------|-------------|
| List 100 items | P50 | < 50ms | Median latency for listing 100 items |
| List 1000 items | P95 | < 100ms | 95th percentile for listing 1000 items |
| Get single item | P50 | < 20ms | Median latency for retrieving one item |
| Create item | P50 | < 100ms | Median latency for creating an item |
| Update item | P50 | < 100ms | Median latency for updating an item |
| Delete item | P50 | < 50ms | Median latency for deleting an item |
| Search/Filter | P50 | < 100ms | Median latency for search operations |
| Concurrent requests | Avg | < 200ms | Average latency under concurrent load |

### Understanding Percentiles

- **P50 (Median)**: 50% of requests complete faster than this time
- **P95**: 95% of requests complete faster than this time (captures outliers)
- **P99**: 99% of requests complete faster than this time (worst-case scenarios)

## Interpreting Results

### CLI Output Example

```
==================================================================
BENCHMARK: List 100 Items (P50)
==================================================================
Running 100 iterations...

Results:
----------------------------------------------------------------------
  Min:                  12.45 ms
  Max:                 156.32 ms
  Mean:                 35.67 ms
  Median (P50):         32.18 ms
  P95:                  78.91 ms
  P99:                 124.56 ms
  StdDev:               18.42 ms
  Total Time:         3567.23 ms
  Requests/sec:          28.03
----------------------------------------------------------------------
Target P50:              50.00 ms
Actual P50:              32.18 ms
Status:            ✓ PASS (under by -17.82 ms)
```

### Understanding the Metrics

- **Min/Max**: Fastest and slowest request times
- **Mean**: Average latency across all requests
- **Median (P50)**: Middle value when sorted (better than mean for skewed data)
- **P95/P99**: High percentiles show worst-case performance
- **StdDev**: Standard deviation (consistency indicator - lower is better)
- **Requests/sec**: Throughput measurement

### Passing vs Failing

- ✓ **PASS**: The measured metric is at or below the target
- ✗ **FAIL**: The measured metric exceeds the target

### What to Do When Tests Fail

1. **Check server resources**: CPU, memory, disk I/O
2. **Review database performance**: Slow queries, missing indexes
3. **Network latency**: Test from different locations
4. **Concurrency**: Check if other processes are consuming resources
5. **Dataset size**: Performance degrades with larger datasets
6. **Caching**: Ensure appropriate caching is enabled

## Benchmark Details

### Test Data Setup

The benchmarks automatically create test data before running:

- **Extensions**: Up to 1000 test extensions for list operations
- **Ring Groups**: 50 test ring groups for additional coverage

All test data is automatically cleaned up after benchmarks complete.

### Warmup Phase

Each benchmark includes a warmup phase (default: 5 requests) to:
- Establish database connections
- Prime caches
- Stabilize performance
- Eliminate cold start effects

Warmup results are discarded and not included in measurements.

### Measurement Phase

The actual benchmark runs the configured number of iterations (default: 100) and collects:
- Request duration in milliseconds
- HTTP status codes
- Response data

### Statistical Analysis

Results are analyzed using:
- Min/Max values
- Mean and median
- Percentiles (P50, P95, P99)
- Standard deviation
- Throughput (requests per second)

## Configuration

### Environment Variables

You can override configuration using environment variables:

```bash
export PERF_API_BASE_URL="http://localhost/api"
export PERF_API_KEY="your-key"
export PERF_ITERATIONS=200
php benchmark.php
```

### Results Directory

Benchmark results are saved to `results/` directory in JSON format:

```
results/
  list_100_items_(p50)_2024-01-15_14-30-45.json
  create_item_2024-01-15_14-31-12.json
  search_filter_2024-01-15_14-31-45.json
```

Each result file contains:
- Timestamp
- Configuration (iterations, warmup, targets)
- Full statistics
- Raw latency data for all iterations

### Output Formats

**Text (default)**: Human-readable formatted output
```bash
php benchmark.php --format=text
```

**JSON**: Machine-readable for analysis tools
```bash
php benchmark.php --format=json > results.json
```

**CSV**: Spreadsheet-compatible format
```bash
php benchmark.php --format=csv > results.csv
```

## Troubleshooting

### Issue: Connection refused or timeout

**Solution**: Check that API server is running and accessible:
```bash
curl -H "X-API-Key: your-key" -H "X-Domain: domain.com" http://localhost/api/extensions
```

### Issue: Authentication errors

**Solution**: Verify API key and domain in `config.php`:
- Ensure API key exists in database
- Confirm API key is enabled
- Check domain is correctly associated with key

### Issue: Benchmarks are too slow

**Reduce iterations for faster results:**
```bash
php benchmark.php --iterations=50 --warmup=2
```

### Issue: Inconsistent results

**Possible causes:**
- Background processes consuming resources
- Network congestion
- Database maintenance operations
- Insufficient warmup period

**Solutions:**
- Increase warmup: `--warmup=10`
- Increase iterations for better averaging: `--iterations=200`
- Run during off-peak hours
- Ensure dedicated test environment

### Issue: Out of memory

**Solution**: Reduce dataset size in `config.php`:
```php
define('PERF_SMALL_DATASET_SIZE', 50);
define('PERF_LARGE_DATASET_SIZE', 500);
```

### Issue: Database errors

**Solution**: Ensure test database is properly set up:
- Database exists and is accessible
- Tables have proper schema
- Sufficient disk space
- Database user has correct permissions

## Best Practices

### For Accurate Results

1. **Dedicated environment**: Run on isolated test server
2. **Consistent conditions**: Same time of day, similar load
3. **Multiple runs**: Run benchmarks several times and compare
4. **Baseline**: Establish baseline before making changes
5. **Version control**: Track configuration and results over time

### For CI/CD Integration

```bash
# Example CI script
cd tests/performance
cp config.php.example config.php
sed -i 's|http://localhost/api|$API_URL|g' config.php
sed -i 's|test-api-key-123|$API_KEY|g' config.php
phpunit --bootstrap bootstrap.php BenchmarkTest.php --log-junit results.xml
```

### For Performance Regression Detection

Compare results over time:
```bash
# Run and save baseline
php benchmark.php --format=json > baseline.json

# After code changes
php benchmark.php --format=json > after_changes.json

# Compare (using jq or custom script)
jq -s '.[0].list100.statistics.p50 - .[1].list100.statistics.p50' baseline.json after_changes.json
```

## Additional Resources

- [FusionPBX API Documentation](../../api/README.md)
- [API Integration Tests](../api/README.md)
- Performance tuning guides (see main documentation)

## Support

For issues or questions:
1. Check [Troubleshooting](#troubleshooting) section
2. Review configuration in `config.php`
3. Verify API server is running correctly
4. Check server logs for errors
