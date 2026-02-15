# FusionPBX API Integration Tests

This directory contains PHPUnit integration tests for the FusionPBX REST API.

## Test Structure

```
tests/api/
├── bootstrap.php              # Test setup, helper functions, constants
├── phpunit.xml               # PHPUnit configuration
├── config.php.example        # Configuration template
├── AuthenticationTest.php    # API authentication tests
├── RingGroupsTest.php        # Ring groups CRUD tests
├── ExtensionsTest.php        # Extensions CRUD tests
├── PaginationTest.php        # Pagination functionality tests
└── ErrorHandlingTest.php     # Error response tests
```

## Prerequisites

- PHP 7.4 or higher
- PHPUnit 9.5 or higher
- PostgreSQL with FusionPBX database
- cURL extension enabled
- Running FusionPBX API server

## Setup

### 1. Install PHPUnit

```bash
# Using Composer (recommended)
composer require --dev phpunit/phpunit ^9.5

# Or download PHAR
wget https://phar.phpunit.de/phpunit-9.phar
chmod +x phpunit-9.phar
mv phpunit-9.phar /usr/local/bin/phpunit
```

### 2. Configure Test Environment

Create `tests/api/config.php` from the example:

```bash
cp tests/api/config.php.example tests/api/config.php
```

Edit `config.php` with your test environment settings:

```php
<?php
// API Configuration
define('TEST_API_BASE_URL', 'http://localhost/api');

// Test credentials (create these in your test database)
define('TEST_API_KEY', 'your-test-api-key');
define('TEST_DOMAIN', 'test.example.com');

// Database Configuration
define('TEST_DATABASE_HOST', 'localhost');
define('TEST_DATABASE_NAME', 'fusionpbx_test');
define('TEST_DATABASE_USER', 'fusionpbx');
define('TEST_DATABASE_PASSWORD', 'your-password');
```

### 3. Prepare Test Database

It's recommended to use a separate test database to avoid affecting production data:

```bash
# Create test database (as postgres user)
createdb fusionpbx_test

# Import schema from production
pg_dump -s fusionpbx | psql fusionpbx_test

# Or restore from backup
psql fusionpbx_test < fusionpbx_schema.sql
```

## Running Tests

### Run All Tests

```bash
cd /path/to/fusionpbx/tests/api
phpunit
```

### Run Specific Test Suite

```bash
# Authentication tests only
phpunit AuthenticationTest.php

# Ring groups tests only
phpunit RingGroupsTest.php

# Extensions tests only
phpunit ExtensionsTest.php

# Pagination tests only
phpunit PaginationTest.php

# Error handling tests only
phpunit ErrorHandlingTest.php
```

### Run Specific Test Method

```bash
phpunit --filter test_valid_api_key_authentication AuthenticationTest.php
```

### Run with Verbose Output

```bash
phpunit --verbose
```

### Run with Coverage Report

```bash
phpunit --coverage-html coverage/
```

## Test Suites

### AuthenticationTest
- Valid API key authentication
- Invalid API key handling
- Missing API key handling
- Missing domain header handling
- Invalid domain handling
- Disabled API key/domain handling
- Case-insensitive header names

### RingGroupsTest
- Create ring group
- Get all ring groups (with pagination)
- Get single ring group by UUID
- Update ring group
- Delete ring group
- Ring group not found (404)
- Ring group with destinations (relationships)
- Duplicate extension validation
- Enable/disable toggle

### ExtensionsTest
- Create extension
- Get all extensions (with pagination)
- Get single extension by UUID
- Update extension
- Delete extension
- Extension not found (404)
- Duplicate extension validation
- Password updates
- Enable/disable toggle
- Search functionality

### PaginationTest
- Default pagination behavior
- Custom page size
- Page navigation
- Total pages calculation
- Pagination across different endpoints
- Edge cases (page 0, negative, excessive per_page)
- Empty result pagination
- Pagination metadata consistency

### ErrorHandlingTest
- 400 Bad Request responses
- 401 Unauthorized responses
- 404 Not Found responses
- 405 Method Not Allowed responses
- Error response format consistency
- Helpful error messages
- Content-Type headers on errors
- No sensitive data in errors

## Test Data Management

The tests automatically:
- Create test data in `setUp()` methods
- Clean up test data in `tearDown()` methods
- Use unique identifiers (timestamps, random strings) to avoid conflicts
- Isolate tests from each other

### Manual Cleanup

If tests are interrupted, you may need to manually clean up:

```sql
-- Find test data
SELECT * FROM v_domains WHERE domain_name LIKE 'test-%';
SELECT * FROM v_api_keys WHERE api_key LIKE 'test-key-%';

-- Clean up test domains
DELETE FROM v_api_keys WHERE api_key LIKE 'test-key-%';
DELETE FROM v_domains WHERE domain_name LIKE 'test-%';

-- Clean up test ring groups
DELETE FROM v_ring_groups WHERE ring_group_name LIKE '%Test%';

-- Clean up test extensions
DELETE FROM v_extensions WHERE extension >= '1000' AND extension < '2100';
```

## Troubleshooting

### Connection Refused
- Ensure API server is running
- Check `TEST_API_BASE_URL` in config.php
- Verify firewall settings

### Database Connection Failed
- Check PostgreSQL is running
- Verify database credentials in config.php
- Ensure test database exists

### Tests Failing Due to Existing Data
- Use a clean test database
- Run manual cleanup SQL queries
- Check for unique constraint violations

### Authentication Failures
- Verify test API key exists in database
- Check domain is enabled
- Ensure API key is enabled

## Writing New Tests

Follow these patterns when adding new tests:

```php
class NewFeatureTest extends TestCase
{
    private $testDomainUuid;
    private $testApiKeyUuid;

    protected function setUp(): void
    {
        parent::setUp();
        // Create test domain and API key
        $testDomain = 'test-feature-' . time() . '.example.com';
        $testApiKey = 'test-key-' . bin2hex(random_bytes(16));

        $this->testDomainUuid = create_test_domain($testDomain);
        $this->testApiKeyUuid = create_test_api_key($this->testDomainUuid, $testApiKey);
    }

    protected function tearDown(): void
    {
        // Clean up test data
        cleanup_test_data('v_api_keys', ['api_key_uuid' => $this->testApiKeyUuid]);
        cleanup_test_data('v_domains', ['domain_uuid' => $this->testDomainUuid]);
        parent::tearDown();
    }

    public function test_descriptive_name()
    {
        // Arrange
        $data = ['key' => 'value'];

        // Act
        $response = api_request('GET', '/endpoint', $data);

        // Assert
        $this->assertEquals(200, $response['status']);
        $this->assertArrayHasKey('expected_key', $response['body']);
    }
}
```

## Best Practices

1. **Isolation**: Each test should be independent and not rely on other tests
2. **Cleanup**: Always clean up test data in tearDown()
3. **Naming**: Use descriptive test method names (test_what_it_does)
4. **Assertions**: Use specific assertions with helpful messages
5. **Test Data**: Use unique identifiers to avoid conflicts
6. **Documentation**: Comment complex test scenarios
7. **Error Cases**: Test both success and failure paths

## Continuous Integration

To integrate with CI/CD pipelines:

```yaml
# Example GitHub Actions workflow
name: API Tests
on: [push, pull_request]
jobs:
  test:
    runs-on: ubuntu-latest
    services:
      postgres:
        image: postgres:13
        env:
          POSTGRES_DB: fusionpbx_test
          POSTGRES_USER: fusionpbx
          POSTGRES_PASSWORD: test
    steps:
      - uses: actions/checkout@v2
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '7.4'
      - name: Install dependencies
        run: composer install
      - name: Run tests
        run: cd tests/api && phpunit
```

## Contributing

When contributing new tests:
1. Follow the existing test structure
2. Add test documentation
3. Ensure all tests pass
4. Update this README if adding new test suites
5. Use meaningful assertions with messages

## License

Same as FusionPBX project license.
