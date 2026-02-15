# API Monitoring

This module provides request tracking, metrics collection, and error logging for FusionPBX API endpoints.

## Features

- **Request Tracking**: Log request start/end times and calculate duration
- **Metrics Collection**: Track total requests, error rates, and latency statistics
- **Error Logging**: Record API errors with endpoint, timestamp, and IP address
- **Metrics Endpoint**: Retrieve current metrics via HTTP API

## Usage

### In Your API Endpoints

```php
<?php
require_once(__DIR__ . '/monitoring.php');

// Start request tracking
log_request_start();

try {
    // Your API logic here
    $result = process_request();

    // Log successful completion
    log_request_end(200);

    echo json_encode(['success' => true, 'data' => $result]);

} catch (Exception $e) {
    // Log error
    log_error($_SERVER['REQUEST_URI'], $e->getMessage());
    log_request_end(500);

    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
```

### Available Functions

#### `log_request_start()`
Records the start time of an API request. Call this at the beginning of your endpoint.

**Returns**: `float` - Request start timestamp

#### `log_request_end($status_code)`
Records the end time and calculates request duration. Call this before sending response.

**Parameters**:
- `$status_code` (int) - HTTP response status code

**Returns**: `array` - Request timing information
```php
[
    'start_time' => 1234567890.123,
    'end_time' => 1234567891.456,
    'duration_ms' => 1333.45,
    'status_code' => 200
]
```

#### `get_metrics()`
Retrieves current API metrics.

**Returns**: `array` - Metrics summary
```php
[
    'total_requests' => 1000,
    'error_count' => 50,
    'error_rate_percent' => 5.0,
    'average_latency_ms' => 145.67,
    'last_updated' => '2024-01-15 10:30:45',
    'recent_errors' => [...]  // Last 10 errors
]
```

#### `log_error($endpoint, $error_message)`
Logs an API error with details.

**Parameters**:
- `$endpoint` (string) - API endpoint where error occurred
- `$error_message` (string) - Error description

**Returns**: `bool` - Success status

#### `reset_metrics()`
Clears all collected metrics and error logs.

**Returns**: `bool` - Success status

## Metrics Endpoint

### GET /api/v1/monitoring/metrics.php

Retrieve current metrics.

**Response**:
```json
{
    "success": true,
    "data": {
        "total_requests": 1000,
        "error_count": 50,
        "error_rate_percent": 5.0,
        "average_latency_ms": 145.67,
        "last_updated": "2024-01-15 10:30:45",
        "recent_errors": [
            {
                "timestamp": "2024-01-15 10:25:30",
                "endpoint": "/api/v1/users",
                "message": "Database connection failed",
                "ip": "192.168.1.100"
            }
        ]
    }
}
```

### DELETE /api/v1/monitoring/metrics.php

Reset all metrics (admin only - add authentication as needed).

**Response**:
```json
{
    "success": true,
    "message": "Metrics reset successfully"
}
```

## Data Storage

Metrics are stored in: `/tmp/fusionpbx_api_metrics.json`

**Structure**:
```json
{
    "total_requests": 1000,
    "error_count": 50,
    "latency_sum": 145670.5,
    "latency_count": 1000,
    "errors": [
        {
            "timestamp": "2024-01-15 10:25:30",
            "endpoint": "/api/v1/users",
            "message": "Database connection failed",
            "ip": "192.168.1.100"
        }
    ],
    "last_updated": "2024-01-15 10:30:45"
}
```

## Notes

- File locking is used to prevent race conditions in concurrent requests
- Only the last 100 errors are retained to prevent unbounded file growth
- Metrics file is created automatically with proper permissions (0666)
- All latency measurements are in milliseconds
- Error rate is calculated as percentage of total requests

## Security Considerations

- The metrics endpoint currently has no authentication
- Consider adding authentication/authorization before production use
- The DELETE endpoint should be restricted to admin users
- Metrics file location in `/tmp` may not persist across reboots
- Consider moving to a more permanent location for production use
