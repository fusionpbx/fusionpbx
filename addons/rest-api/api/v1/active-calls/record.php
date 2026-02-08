<?php
/**
 * Active Calls API - Record Call
 * POST /api/v1/active-calls/record.php
 *
 * Starts or stops recording an active call
 * Body: {
 *   "call_uuid": "uuid",
 *   "action": "start|stop",
 *   "filename": "optional-path-to-recording-file" (only for start)
 * }
 */

require_once __DIR__ . '/../base.php';

// Authenticate and scope domain
validate_api_key();

// Require POST method
api_require_method('POST');

// Get request data
$data = get_request_data();

// Validate required fields
$errors = api_validate($data, ['call_uuid', 'action']);
if (!empty($errors)) {
    api_validation_error($errors);
}

$call_uuid = $data['call_uuid'];
$action = strtolower($data['action']);

// Validate call_uuid format
api_validate_uuid($call_uuid, 'call_uuid');

// Validate action
if (!in_array($action, ['start', 'stop'])) {
    api_error('VALIDATION_ERROR', 'Action must be "start" or "stop"', 'action', 400);
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

// Execute recording command
if ($action === 'start') {
    // Always generate filename server-side (never accept from user input)
    $recording_dir = '/var/lib/freeswitch/recordings/' . $domain_name;
    if (!is_dir($recording_dir)) {
        @mkdir($recording_dir, 0770, true);
    }
    $filename = $recording_dir . '/api_' . $call_uuid . '_' . date('Y-m-d_His') . '.wav';

    $record_result = event_socket::api("uuid_record {$call_uuid} start {$filename}");
} else {
    // Stop recording - use "all" to stop all recordings on this call
    $record_result = event_socket::api("uuid_record {$call_uuid} stop all");
}

// Check if record command was successful
if ($record_result === false) {
    api_error('RECORD_FAILED', 'Failed to execute record command', null, 500);
}

if (stripos($record_result, '-ERR') !== false) {
    api_error('RECORD_FAILED', trim($record_result), null, 500);
}

$response_data = [
    'call_uuid' => $call_uuid,
    'action' => $action,
    'result' => trim($record_result)
];

if ($action === 'start') {
    $response_data['filename'] = $filename;
}

api_success($response_data, 'Recording ' . $action . 'ed successfully');
