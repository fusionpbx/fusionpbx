<?php
/**
 * Email Queue Get API
 * GET /api/v1/queues/email/get.php?uuid={email_queue_uuid}
 *
 * Get a single email queue item by UUID
 *
 * Query Parameters:
 * - uuid: Email queue UUID (required)
 */

require_once __DIR__ . '/../../base.php';

// Only allow GET method
api_require_method('GET');

// Get and validate UUID
$email_queue_uuid = $_GET['uuid'] ?? '';
api_validate_uuid($email_queue_uuid, 'email_queue_uuid');

// Get email queue item
$email_queue = api_get_record('v_email_queue', 'email_queue_uuid', $email_queue_uuid);

// Check if record exists
if (!$email_queue) {
    api_not_found('Email queue item');
}

// Return success response
api_success($email_queue);
