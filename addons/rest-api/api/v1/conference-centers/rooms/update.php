<?php
require_once __DIR__ . '/../../base.php';
validate_api_key();
api_require_method('PUT');

$conference_room_uuid = get_uuid_from_path();
api_validate_uuid($conference_room_uuid, 'conference_room_uuid');

$data = get_request_data();

// Check if record exists
if (!api_record_exists('v_conference_rooms', 'conference_room_uuid', $conference_room_uuid)) {
    api_not_found('Conference Room');
}

// Check room name conflict if changing name
if (isset($data['conference_room_name'])) {
    $database = new database;

    // Get current conference_center_uuid
    $current_sql = "SELECT conference_center_uuid FROM v_conference_rooms
                    WHERE conference_room_uuid = :uuid AND domain_uuid = :domain_uuid";
    $current_params = [
        'uuid' => $conference_room_uuid,
        'domain_uuid' => $domain_uuid
    ];
    $current_center = $database->select($current_sql, $current_params, 'row');

    $check_sql = "SELECT COUNT(*) FROM v_conference_rooms
                  WHERE conference_room_name = :name
                  AND conference_center_uuid = :center_uuid
                  AND domain_uuid = :domain_uuid
                  AND conference_room_uuid != :uuid";
    $check_params = [
        'name' => $data['conference_room_name'],
        'center_uuid' => $current_center['conference_center_uuid'],
        'domain_uuid' => $domain_uuid,
        'uuid' => $conference_room_uuid
    ];

    if ($database->select($check_sql, $check_params, 'column') > 0) {
        api_conflict('conference_room_name', 'Room name already exists in this conference center');
    }
}

// Build update data
$allowed_fields = [
    'conference_room_name',
    'moderator_pin',
    'participant_pin',
    'profile',
    'record',
    'max_members',
    'wait_mod',
    'announce',
    'sounds',
    'mute',
    'enabled',
    'description'
];

$update_data = [];
foreach ($allowed_fields as $field) {
    if (isset($data[$field])) {
        $update_data[$field] = $data[$field];
    }
}

if (empty($update_data)) {
    api_error('VALIDATION_ERROR', 'No valid fields to update', null, 400);
}

// Update
$database = new database;
$database->app_name = 'api-conference-centers';
$database->app_uuid = 'a8a12918-69a4-4ece-a1ae-3932e2f8a8a9';
$database->update('v_conference_rooms', $update_data, [
    'conference_room_uuid' => $conference_room_uuid,
    'domain_uuid' => $domain_uuid
]);

// Clear cache
api_clear_dialplan_cache();

api_success(['conference_room_uuid' => $conference_room_uuid], 'Conference Room updated successfully');
