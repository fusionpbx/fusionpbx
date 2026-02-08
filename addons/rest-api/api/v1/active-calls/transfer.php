<?php
/**
 * Active Calls API - Transfer Call
 * POST /api/v1/active-calls/transfer.php
 *
 * Transfers an active call to another destination
 * Body: {"call_uuid": "uuid", "destination": "extension or number"}
 */

require_once __DIR__ . '/../base.php';

// Authenticate and scope domain
validate_api_key();

// Require POST method
api_require_method('POST');

// Get request data
$data = get_request_data();

// Validate required fields
$errors = api_validate($data, ['call_uuid', 'destination']);
if (!empty($errors)) {
    api_validation_error($errors);
}

$call_uuid = $data['call_uuid'];
$destination = $data['destination'];

// Validate call_uuid format
api_validate_uuid($call_uuid, 'call_uuid');

// Sanitize destination - allow only digits, +, *, # (no spaces to prevent ESL injection)
if (!preg_match('/^[0-9\+\*\#]{1,50}$/', $destination)) {
    api_error('VALIDATION_ERROR', 'Invalid destination format. Only digits, +, *, # allowed.', 'destination', 400);
}

// Check if event_socket class exists
if (!class_exists('event_socket')) {
    api_error('ESL_NOT_AVAILABLE', 'Event Socket Library not available', null, 503);
}

// Create ESL connection
$esl = event_socket::create();

if (!$esl->is_connected()) {
    api_error('ESL_CONNECTION_FAILED', 'Failed to connect to FreeSWITCH Event Socket', null, 503);
}

// First verify the call exists and belongs to this domain
$response = event_socket::api("show channels as json");

if (!empty($response)) {
    $channels = json_decode($response, true);
    $call_found = false;

    if (!empty($channels['rows'])) {
        foreach ($channels['rows'] as $call) {
            if (($call['uuid'] ?? '') === $call_uuid) {
                // Verify domain ownership
                $belongs_to_domain = false;

                if (!empty($call['presence_id']) && strpos($call['presence_id'], '@' . $domain_name) !== false) {
                    $belongs_to_domain = true;
                }

                if (!empty($call['context']) && $call['context'] === $domain_name) {
                    $belongs_to_domain = true;
                }

                if (!$belongs_to_domain) {
                    api_forbidden('Call does not belong to your domain');
                }

                $call_found = true;
                break;
            }
        }
    }

    if (!$call_found) {
        api_not_found('Call');
    }
}

// Execute transfer command
$transfer_result = event_socket::api("uuid_transfer {$call_uuid} {$destination}");

// Check if transfer was successful
if ($transfer_result === false) {
    api_error('TRANSFER_FAILED', 'Failed to execute transfer command', null, 500);
}

if (stripos($transfer_result, '-ERR') !== false) {
    api_error('TRANSFER_FAILED', trim($transfer_result), null, 500);
}

api_success([
    'call_uuid' => $call_uuid,
    'destination' => $destination,
    'result' => trim($transfer_result)
], 'Call transferred successfully');
