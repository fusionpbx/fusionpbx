<?php
/**
 * Fax Queue Retry API
 * POST /api/v1/queues/fax/retry.php
 *
 * Retry a failed fax by resetting retry_count to 0 and status to 'pending'
 *
 * Request Body:
 * {
 *   "fax_queue_uuid": "uuid-here"
 * }
 */

require_once __DIR__ . '/../../base.php';

// Only allow POST method
api_require_method('POST');

// Get request data
$data = get_request_data();

// Validate required fields
$errors = api_validate($data, ['fax_queue_uuid']);
if (!empty($errors)) {
    api_validation_error($errors);
}

// Validate UUID format
$fax_queue_uuid = $data['fax_queue_uuid'];
api_validate_uuid($fax_queue_uuid, 'fax_queue_uuid');

// Check if fax queue item exists
$fax_queue = api_get_record('v_fax_queue', 'fax_queue_uuid', $fax_queue_uuid);
if (!$fax_queue) {
    api_not_found('Fax queue item');
}

// Update fax queue item
$database = new database;
$sql = "UPDATE v_fax_queue
        SET fax_status = :fax_status,
            fax_retry_count = :fax_retry_count
        WHERE fax_queue_uuid = :fax_queue_uuid
        AND domain_uuid = :domain_uuid";

$parameters = [
    'fax_status' => 'pending',
    'fax_retry_count' => 0,
    'fax_queue_uuid' => $fax_queue_uuid,
    'domain_uuid' => $domain_uuid
];

$database->execute($sql, $parameters);

// Get updated record
$updated_fax_queue = api_get_record('v_fax_queue', 'fax_queue_uuid', $fax_queue_uuid);

// Return success response
api_success($updated_fax_queue, 'Fax queue item reset for retry');
