<?php
/**
 * Bulk Export Call Block Rules
 * GET /api/v1/call-block/bulk-export.php
 *
 * Query Parameters:
 * - format: Export format (json or csv, default: json)
 * - enabled: Filter by enabled status (true/false)
 */

require_once __DIR__ . '/../base.php';

api_require_method('GET');

// Get format parameter
$format = strtolower($_GET['format'] ?? 'json');

if (!in_array($format, ['json', 'csv'])) {
    api_error('VALIDATION_ERROR', 'Invalid format. Supported: json, csv', 'format', 400);
}

// Build WHERE clause
$where_conditions = ['domain_uuid = :domain_uuid'];
$parameters = ['domain_uuid' => $domain_uuid];

// Filter by enabled status
if (isset($_GET['enabled']) && $_GET['enabled'] !== '') {
    $enabled_value = filter_var($_GET['enabled'], FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false';
    $where_conditions[] = "call_block_enabled = :enabled";
    $parameters['enabled'] = $enabled_value;
}

$where_clause = implode(' AND ', $where_conditions);

// Fetch all records
$sql = "SELECT call_block_uuid, call_block_name, call_block_number,
        call_block_count, call_block_action, call_block_enabled, call_block_description
        FROM v_call_block
        WHERE {$where_clause}
        ORDER BY call_block_number ASC";

$database = new database;
$records = $database->select($sql, $parameters, 'all') ?? [];

// Convert enabled values to boolean for export
foreach ($records as &$record) {
    $record['call_block_enabled'] = $record['call_block_enabled'] === 'true';
}

if ($format === 'csv') {
    // Set headers for CSV download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="call-block-export-' . date('Y-m-d') . '.csv"');

    $output = fopen('php://output', 'w');

    // Write CSV header
    fputcsv($output, [
        'UUID',
        'Number',
        'Name',
        'Action',
        'Enabled',
        'Count',
        'Description'
    ]);

    // Write data rows
    foreach ($records as $record) {
        fputcsv($output, [
            $record['call_block_uuid'],
            $record['call_block_number'],
            $record['call_block_name'] ?? '',
            $record['call_block_action'] ?? 'reject',
            $record['call_block_enabled'] ? 'true' : 'false',
            $record['call_block_count'] ?? 0,
            $record['call_block_description'] ?? ''
        ]);
    }

    fclose($output);
    exit;
} else {
    // JSON format
    api_success([
        'rules' => $records,
        'total' => count($records),
        'exported_at' => date('Y-m-d H:i:s')
    ]);
}
