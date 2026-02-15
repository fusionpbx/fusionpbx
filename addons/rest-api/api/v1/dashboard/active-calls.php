<?php
require_once dirname(__DIR__) . '/auth.php';
validate_api_key();

$calls = [];

if (!class_exists('event_socket')) {
    api_error('ESL_NOT_AVAILABLE', 'Event Socket Library not available', null, 503);
}

$esl = event_socket::create();
if (!$esl || !$esl->is_connected()) {
    api_error('ESL_CONNECTION_FAILED', 'Failed to connect to FreeSWITCH', null, 503);
}

if ($esl->is_connected()) {
    $response = event_socket::api("show calls as json");

    if (!empty($response)) {
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
                    $calls[] = [
                        'uuid' => $call['uuid'] ?? '',
                        'direction' => $call['direction'] ?? '',
                        'caller_id_name' => $call['cid_name'] ?? '',
                        'caller_id_number' => $call['cid_num'] ?? '',
                        'destination' => $call['dest'] ?? '',
                        'state' => $call['callstate'] ?? '',
                        'created' => $call['created'] ?? '',
                        'created_epoch' => $call['created_epoch'] ?? ''
                    ];
                }
            }
        }
    }
}

api_success($calls);
