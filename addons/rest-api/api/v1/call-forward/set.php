<?php
/**
 * Enable/configure call forwarding for an extension
 * POST /api/v1/call-forward/set.php
 *
 * Request Body (JSON):
 * {
 *   "extension_uuid": "uuid",
 *   "forward_type": "all|busy|no_answer|user_not_registered",
 *   "destination": "1234",
 *   "enabled": true
 * }
 */

require_once __DIR__ . '/../base.php';

api_require_method('POST');

// Get request data
$data = get_request_data();

// Validate required fields
$errors = api_validate($data, ['extension_uuid', 'forward_type', 'destination']);
if (!empty($errors)) {
    api_validation_error($errors);
}

// Validate extension_uuid
$extension_uuid = api_validate_uuid($data['extension_uuid'], 'extension_uuid');

// Validate forward_type
$valid_types = ['all', 'busy', 'no_answer', 'user_not_registered'];
$forward_type = $data['forward_type'];
if (!in_array($forward_type, $valid_types)) {
    api_error('VALIDATION_ERROR', 'Invalid forward_type. Must be one of: ' . implode(', ', $valid_types), 'forward_type', 400);
}

// Check if extension exists
if (!api_record_exists('v_extensions', 'extension_uuid', $extension_uuid)) {
    api_not_found('Extension');
}

// Get enabled flag (default to true)
$enabled = isset($data['enabled']) ? ($data['enabled'] === true || $data['enabled'] === 'true') : true;
$enabled_str = $enabled ? 'true' : 'false';

// Prepare update fields based on forward_type
$field_map = [
    'all' => ['forward_all_destination', 'forward_all_enabled'],
    'busy' => ['forward_busy_destination', 'forward_busy_enabled'],
    'no_answer' => ['forward_no_answer_destination', 'forward_no_answer_enabled'],
    'user_not_registered' => ['forward_user_not_registered_destination', 'forward_user_not_registered_enabled']
];

$fields = $field_map[$forward_type];
$destination_field = $fields[0];
$enabled_field = $fields[1];

// Update extension
$database = new database;
$array['extensions'][0]['extension_uuid'] = $extension_uuid;
$array['extensions'][0]['domain_uuid'] = $domain_uuid;
$array['extensions'][0][$destination_field] = $data['destination'];
$array['extensions'][0][$enabled_field] = $enabled_str;

$database->app_name = 'extensions';
$database->app_uuid = 'e68d9571-2566-b51f-2b32-3ec0a3929ce8';
$database->save($array);
unset($array);

// Regenerate extension XML for FreeSWITCH
api_regenerate_xml('extension', $domain_uuid, $domain_name);

// Get updated extension data
$extension = api_get_record(
    'v_extensions',
    'extension_uuid',
    $extension_uuid,
    'extension_uuid, extension, forward_all_destination, forward_all_enabled,
    forward_busy_destination, forward_busy_enabled,
    forward_no_answer_destination, forward_no_answer_enabled,
    forward_user_not_registered_destination, forward_user_not_registered_enabled'
);

// Format response
$response = [
    'extension_uuid' => $extension['extension_uuid'],
    'extension' => $extension['extension'],
    'forward_settings' => [
        'all' => [
            'enabled' => $extension['forward_all_enabled'] === 'true',
            'destination' => $extension['forward_all_destination']
        ],
        'busy' => [
            'enabled' => $extension['forward_busy_enabled'] === 'true',
            'destination' => $extension['forward_busy_destination']
        ],
        'no_answer' => [
            'enabled' => $extension['forward_no_answer_enabled'] === 'true',
            'destination' => $extension['forward_no_answer_destination']
        ],
        'user_not_registered' => [
            'enabled' => $extension['forward_user_not_registered_enabled'] === 'true',
            'destination' => $extension['forward_user_not_registered_destination']
        ]
    ]
];

api_success($response, 'Call forward settings updated successfully');
