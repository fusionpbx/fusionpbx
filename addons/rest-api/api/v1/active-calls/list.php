<?php
/**
 * Active Calls API - List
 * GET /api/v1/active-calls/list.php
 *
 * Lists all active calls for the authenticated domain
 */

require_once __DIR__ . '/../base.php';

// Authenticate and scope domain
validate_api_key();

// Require GET method
api_require_method('GET');

$calls = [];

// Check if event_socket class exists
if (!class_exists('event_socket')) {
    api_error('ESL_NOT_AVAILABLE', 'Event Socket Library not available', null, 503);
}

// Create ESL connection
$esl = event_socket::create();

if (!$esl->is_connected()) {
    api_error('ESL_CONNECTION_FAILED', 'Failed to connect to FreeSWITCH Event Socket', null, 503);
}

// Get active calls as JSON
$response = event_socket::api("show channels as json");

if (empty($response)) {
    // No calls or empty response
    api_success($calls);
}

// Parse JSON response
$data = json_decode($response, true);

if (!empty($data['rows'])) {
    foreach ($data['rows'] as $call) {
        // Filter by domain using presence_id or context
        $include_call = false;

        // Check if call belongs to this domain via presence_id
        if (!empty($call['presence_id']) && strpos($call['presence_id'], '@' . $domain_name) !== false) {
            $include_call = true;
        }

        // Also check context field
        if (!empty($call['context']) && $call['context'] === $domain_name) {
            $include_call = true;
        }

        if ($include_call) {
            // Calculate duration in seconds
            $created_epoch = intval($call['created_epoch'] ?? 0);
            $duration = $created_epoch > 0 ? time() - $created_epoch : 0;

            $calls[] = [
                'call_uuid' => $call['uuid'] ?? '',
                'caller_id_name' => $call['cid_name'] ?? '',
                'caller_id_number' => $call['cid_num'] ?? '',
                'destination_number' => $call['dest'] ?? '',
                'call_direction' => $call['direction'] ?? '',
                'call_state' => $call['callstate'] ?? '',
                'created_time' => $call['created'] ?? '',
                'created_epoch' => $created_epoch,
                'duration' => $duration
            ];
        }
    }
}

api_success($calls);
