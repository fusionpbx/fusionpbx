<?php
/**
 * Fax Queue Get API
 * GET /api/v1/queues/fax/get.php?uuid={fax_queue_uuid}
 *
 * Get a single fax queue item by UUID
 *
 * Query Parameters:
 * - uuid: Fax queue UUID (required)
 */

require_once __DIR__ . '/../../base.php';

// Only allow GET method
api_require_method('GET');

// Get and validate UUID
$fax_queue_uuid = $_GET['uuid'] ?? '';
api_validate_uuid($fax_queue_uuid, 'fax_queue_uuid');

// Get fax queue item
$fax_queue = api_get_record('v_fax_queue', 'fax_queue_uuid', $fax_queue_uuid);

// Check if record exists
if (!$fax_queue) {
    api_not_found('Fax queue item');
}

// Return success response
api_success($fax_queue);
