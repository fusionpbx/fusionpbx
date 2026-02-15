<?php
/**
 * Email Queue Retry API
 * POST /api/v1/queues/email/retry.php
 *
 * Retry a failed email by resetting retry_count to 0 and status to 'pending'
 *
 * Request Body:
 * {
 *   "email_queue_uuid": "uuid-here"
 * }
 */

require_once __DIR__ . '/../../base.php';

// Only allow POST method
api_require_method('POST');

// Get request data
$data = get_request_data();

// Validate required fields
$errors = api_validate($data, ['email_queue_uuid']);
if (!empty($errors)) {
    api_validation_error($errors);
}

// Validate UUID format
$email_queue_uuid = $data['email_queue_uuid'];
api_validate_uuid($email_queue_uuid, 'email_queue_uuid');

// Check if email queue item exists
$email_queue = api_get_record('v_email_queue', 'email_queue_uuid', $email_queue_uuid);
if (!$email_queue) {
    api_not_found('Email queue item');
}

// Update email queue item
$database = new database;
$sql = "UPDATE v_email_queue
        SET email_status = :email_status,
            email_retry_count = :email_retry_count
        WHERE email_queue_uuid = :email_queue_uuid
        AND domain_uuid = :domain_uuid";

$parameters = [
    'email_status' => 'pending',
    'email_retry_count' => 0,
    'email_queue_uuid' => $email_queue_uuid,
    'domain_uuid' => $domain_uuid
];

$database->execute($sql, $parameters);

// Get updated record
$updated_email_queue = api_get_record('v_email_queue', 'email_queue_uuid', $email_queue_uuid);

// Return success response
api_success($updated_email_queue, 'Email queue item reset for retry');
