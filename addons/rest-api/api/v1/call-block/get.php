<?php
/**
 * Get Call Block Rule
 * GET /api/v1/call-block/get.php?call_block_uuid={uuid}
 *
 * Query Parameters:
 * - call_block_uuid: UUID of the call block rule
 */

require_once __DIR__ . '/../base.php';

api_require_method('GET');

// Get and validate UUID
$call_block_uuid = $_GET['call_block_uuid'] ?? null;
api_validate_uuid($call_block_uuid, 'call_block_uuid');

// Fetch record
$record = api_get_record('v_call_block', 'call_block_uuid', $call_block_uuid);

if (!$record) {
    api_not_found('Call block rule');
}

// Convert enabled to boolean
$record['call_block_enabled'] = $record['call_block_enabled'] === 'true';

api_success($record);
