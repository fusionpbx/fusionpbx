<?php
/**
 * Disable call forwarding for an extension
 * POST /api/v1/call-forward/disable.php
 *
 * Request Body (JSON):
 * {
 *   "extension_uuid": "uuid",
 *   "forward_type": "all|busy|no_answer|user_not_registered"
 * }
 *
 * Note: If forward_type is omitted, all forward types will be disabled
 */

require_once __DIR__ . '/../base.php';

api_require_method('POST');

// Get request data
$data = get_request_data();

// Validate required fields
$errors = api_validate($data, ['extension_uuid']);
if (!empty($errors)) {
    api_validation_error($errors);
}

// Validate extension_uuid
$extension_uuid = api_validate_uuid($data['extension_uuid'], 'extension_uuid');

// Check if extension exists
if (!api_record_exists('v_extensions', 'extension_uuid', $extension_uuid)) {
    api_not_found('Extension');
}

// Determine which forward types to disable
$forward_type = $data['forward_type'] ?? null;
$valid_types = ['all', 'busy', 'no_answer', 'user_not_registered'];

if ($forward_type !== null && !in_array($forward_type, $valid_types)) {
    api_error('VALIDATION_ERROR', 'Invalid forward_type. Must be one of: ' . implode(', ', $valid_types), 'forward_type', 400);
}

// Prepare update fields
$database = new database;
$array['extensions'][0]['extension_uuid'] = $extension_uuid;
$array['extensions'][0]['domain_uuid'] = $domain_uuid;

if ($forward_type === null) {
    // Disable all forward types
    $array['extensions'][0]['forward_all_enabled'] = 'false';
    $array['extensions'][0]['forward_busy_enabled'] = 'false';
    $array['extensions'][0]['forward_no_answer_enabled'] = 'false';
    $array['extensions'][0]['forward_user_not_registered_enabled'] = 'false';
    $message = 'All call forward settings disabled successfully';
} else {
    // Disable specific forward type
    $field_map = [
        'all' => 'forward_all_enabled',
        'busy' => 'forward_busy_enabled',
        'no_answer' => 'forward_no_answer_enabled',
        'user_not_registered' => 'forward_user_not_registered_enabled'
    ];
    $array['extensions'][0][$field_map[$forward_type]] = 'false';
    $message = "Call forward '{$forward_type}' disabled successfully";
}

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

api_success($response, $message);
