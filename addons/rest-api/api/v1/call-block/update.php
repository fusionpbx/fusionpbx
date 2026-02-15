<?php
/**
 * Update Call Block Rule
 * PUT /api/v1/call-block/update.php?call_block_uuid={uuid}
 *
 * Query Parameters:
 * - call_block_uuid: UUID of the call block rule
 *
 * Request Body (JSON):
 * {
 *   "call_block_number": "1234567890",
 *   "call_block_name": "Updated Name",
 *   "call_block_action": "reject",
 *   "call_block_enabled": false,
 *   "call_block_description": "Updated description"
 * }
 */

require_once __DIR__ . '/../base.php';

api_require_method('PUT');

// Get and validate UUID
$call_block_uuid = $_GET['call_block_uuid'] ?? null;
api_validate_uuid($call_block_uuid, 'call_block_uuid');

// Check if record exists
if (!api_record_exists('v_call_block', 'call_block_uuid', $call_block_uuid)) {
    api_not_found('Call block rule');
}

// Get request data
$data = get_request_data();

// Validate phone number format if provided
if (isset($data['call_block_number'])) {
    if (!preg_match('/^[\d\+\-\*\#\s\(\)]+$/', $data['call_block_number'])) {
        api_error('VALIDATION_ERROR', 'Invalid phone number format', 'call_block_number', 400);
    }

    // Check for duplicate number (excluding current record)
    $database = new database;
    $check_sql = "SELECT COUNT(*) FROM v_call_block
                  WHERE domain_uuid = :domain_uuid
                  AND call_block_number = :number
                  AND call_block_uuid != :uuid";
    $check_params = [
        'domain_uuid' => $domain_uuid,
        'number' => $data['call_block_number'],
        'uuid' => $call_block_uuid
    ];
    $exists = $database->select($check_sql, $check_params, 'column') > 0;

    if ($exists) {
        api_conflict('call_block_number', 'This number is already blocked');
    }
}

// Prepare update data
$update_data = ['call_block_uuid' => $call_block_uuid];
$allowed_fields = [
    'call_block_number',
    'call_block_name',
    'call_block_action',
    'call_block_description'
];

foreach ($allowed_fields as $field) {
    if (isset($data[$field])) {
        $update_data[$field] = $data[$field];
    }
}

// Handle enabled field
if (isset($data['call_block_enabled'])) {
    $update_data['call_block_enabled'] = $data['call_block_enabled'] ? 'true' : 'false';
}

// Update record
if (count($update_data) > 1) { // More than just UUID
    $database = new database;
    $database->update('v_call_block', $update_data, 'call_block_uuid');

    // Clear dialplan cache
    api_clear_dialplan_cache();
}

// Fetch updated record
$record = api_get_record('v_call_block', 'call_block_uuid', $call_block_uuid);
$record['call_block_enabled'] = $record['call_block_enabled'] === 'true';

api_success($record, 'Call block rule updated successfully');
