<?php
/**
 * Get call forward settings for a specific extension
 * GET /api/v1/call-forward/get.php?extension_uuid={uuid}
 *
 * Query Parameters:
 * - extension_uuid: UUID of the extension (required)
 */

require_once __DIR__ . '/../base.php';

api_require_method('GET');

// Validate extension_uuid parameter
$extension_uuid = $_GET['extension_uuid'] ?? null;
api_validate_uuid($extension_uuid, 'extension_uuid');

// Get extension record
$extension = api_get_record(
    'v_extensions',
    'extension_uuid',
    $extension_uuid,
    'extension_uuid, extension, number_alias, effective_caller_id_name,
    forward_all_destination, forward_all_enabled,
    forward_busy_destination, forward_busy_enabled,
    forward_no_answer_destination, forward_no_answer_enabled,
    forward_user_not_registered_destination, forward_user_not_registered_enabled,
    enabled, description'
);

if (!$extension) {
    api_not_found('Extension');
}

// Format response
$response = [
    'extension_uuid' => $extension['extension_uuid'],
    'extension' => $extension['extension'],
    'number_alias' => $extension['number_alias'],
    'caller_id_name' => $extension['effective_caller_id_name'],
    'enabled' => $extension['enabled'] === 'true',
    'description' => $extension['description'],
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

api_success($response);
