<?php
/**
 * API Metrics Endpoint
 *
 * Returns current API monitoring metrics including request counts,
 * latency statistics, and error rates.
 *
 * GET /api/v1/monitoring/metrics.php
 */

require_once __DIR__ . '/../base.php';
validate_api_key();

// Include monitoring helpers
require_once(__DIR__ . '/../monitoring.php');

// Set JSON response header
header('Content-Type: application/json');

// Handle request method
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Return current metrics
        $metrics = get_metrics();
        echo json_encode([
            'success' => true,
            'data' => $metrics
        ], JSON_PRETTY_PRINT);
        break;

    case 'DELETE':
        // Reset metrics (optional admin feature)
        // You may want to add authentication here
        $result = reset_metrics();
        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'Metrics reset successfully'
            ], JSON_PRETTY_PRINT);
        } else {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Failed to reset metrics'
            ], JSON_PRETTY_PRINT);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'message' => 'Method not allowed. Use GET to retrieve metrics or DELETE to reset.'
        ], JSON_PRETTY_PRINT);
        break;
}
