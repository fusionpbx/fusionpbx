<?php
/**
 * Delete Call Block Rule
 * DELETE /api/v1/call-block/delete.php?call_block_uuid={uuid}
 *
 * Query Parameters:
 * - call_block_uuid: UUID of the call block rule
 */

require_once __DIR__ . '/../base.php';

api_require_method('DELETE');

// Get and validate UUID
$call_block_uuid = $_GET['call_block_uuid'] ?? null;
api_validate_uuid($call_block_uuid, 'call_block_uuid');

// Check if record exists
if (!api_record_exists('v_call_block', 'call_block_uuid', $call_block_uuid)) {
    api_not_found('Call block rule');
}

// Delete record
$database = new database;
$delete_data = [
    'call_block_uuid' => $call_block_uuid
];
$database->delete('v_call_block', $delete_data, 'call_block_uuid');

// Clear dialplan cache
api_clear_dialplan_cache();

api_no_content();
