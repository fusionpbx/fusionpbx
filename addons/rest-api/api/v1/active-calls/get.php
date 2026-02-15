<?php
/**
 * Active Calls API - Get Single Call
 * GET /api/v1/active-calls/get.php?call_uuid={uuid}
 *
 * Gets details of a single active call
 */

require_once __DIR__ . '/../base.php';

// Authenticate and scope domain
validate_api_key();

// Require GET method
api_require_method('GET');

// Get and validate call_uuid parameter
$call_uuid = $_GET['call_uuid'] ?? '';
api_validate_uuid($call_uuid, 'call_uuid');

// Check if event_socket class exists
if (!class_exists('event_socket')) {
    api_error('ESL_NOT_AVAILABLE', 'Event Socket Library not available', null, 503);
}

// Create ESL connection
$esl = event_socket::create();

if (!$esl->is_connected()) {
    api_error('ESL_CONNECTION_FAILED', 'Failed to connect to FreeSWITCH Event Socket', null, 503);
}

// Get all active calls as JSON
$response = event_socket::api("show channels as json");

if (empty($response)) {
    api_not_found('Call');
}

// Parse JSON response
$data = json_decode($response, true);
$call_found = null;

if (!empty($data['rows'])) {
    foreach ($data['rows'] as $call) {
        if (($call['uuid'] ?? '') === $call_uuid) {
            // Verify call belongs to this domain
            $belongs_to_domain = false;

            // Check presence_id
            if (!empty($call['presence_id']) && strpos($call['presence_id'], '@' . $domain_name) !== false) {
                $belongs_to_domain = true;
            }

            // Check context
            if (!empty($call['context']) && $call['context'] === $domain_name) {
                $belongs_to_domain = true;
            }

            if (!$belongs_to_domain) {
                api_forbidden('Call does not belong to your domain');
            }

            // Calculate duration
            $created_epoch = intval($call['created_epoch'] ?? 0);
            $duration = $created_epoch > 0 ? time() - $created_epoch : 0;

            $call_found = [
                'call_uuid' => $call['uuid'] ?? '',
                'caller_id_name' => $call['cid_name'] ?? '',
                'caller_id_number' => $call['cid_num'] ?? '',
                'destination_number' => $call['dest'] ?? '',
                'call_direction' => $call['direction'] ?? '',
                'call_state' => $call['callstate'] ?? '',
                'created_time' => $call['created'] ?? '',
                'created_epoch' => $created_epoch,
                'duration' => $duration,
                'context' => $call['context'] ?? '',
                'application' => $call['application'] ?? '',
                'application_data' => $call['application_data'] ?? '',
                'read_codec' => $call['read_codec'] ?? '',
                'write_codec' => $call['write_codec'] ?? ''
            ];
            break;
        }
    }
}

if ($call_found === null) {
    api_not_found('Call');
}

api_success($call_found);
