<?php
require_once __DIR__ . '/../../base.php';
validate_api_key();
api_require_method('POST');

$data = get_request_data();

// Validate required fields
$errors = api_validate($data, ['conference_center_uuid', 'conference_room_name']);
if (!empty($errors)) {
    api_validation_error($errors);
}

// Validate conference_center_uuid
api_validate_uuid($data['conference_center_uuid'], 'conference_center_uuid');

// Check if conference center exists
if (!api_record_exists('v_conference_centers', 'conference_center_uuid', $data['conference_center_uuid'])) {
    api_error('VALIDATION_ERROR', 'Conference Center not found', 'conference_center_uuid', 404);
}

// Check if room name already exists in this center
$database = new database;
$check_sql = "SELECT COUNT(*) FROM v_conference_rooms
              WHERE conference_room_name = :name
              AND conference_center_uuid = :center_uuid
              AND domain_uuid = :domain_uuid";
$check_params = [
    'name' => $data['conference_room_name'],
    'center_uuid' => $data['conference_center_uuid'],
    'domain_uuid' => $domain_uuid
];

if ($database->select($check_sql, $check_params, 'column') > 0) {
    api_conflict('conference_room_name', 'Room name already exists in this conference center');
}

// Generate UUID
$conference_room_uuid = uuid();

// Prepare save array
$array['conference_rooms'][0]['domain_uuid'] = $domain_uuid;
$array['conference_rooms'][0]['conference_room_uuid'] = $conference_room_uuid;
$array['conference_rooms'][0]['conference_center_uuid'] = $data['conference_center_uuid'];
$array['conference_rooms'][0]['conference_room_name'] = $data['conference_room_name'];
$array['conference_rooms'][0]['moderator_pin'] = $data['moderator_pin'] ?? null;
$array['conference_rooms'][0]['participant_pin'] = $data['participant_pin'] ?? null;
$array['conference_rooms'][0]['profile'] = $data['profile'] ?? 'default';
$array['conference_rooms'][0]['record'] = $data['record'] ?? 'false';
$array['conference_rooms'][0]['max_members'] = $data['max_members'] ?? null;
$array['conference_rooms'][0]['wait_mod'] = $data['wait_mod'] ?? 'false';
$array['conference_rooms'][0]['announce'] = $data['announce'] ?? 'false';
$array['conference_rooms'][0]['sounds'] = $data['sounds'] ?? 'default';
$array['conference_rooms'][0]['mute'] = $data['mute'] ?? 'false';
$array['conference_rooms'][0]['enabled'] = $data['enabled'] ?? 'true';
$array['conference_rooms'][0]['description'] = $data['description'] ?? null;

// Add temporary permission
$p = permissions::new();
$p->add('conference_room_add', 'temp');

// Save record
$database->app_name = 'conference_centers';
$database->app_uuid = '1e46a1a6-0c43-4f35-8a89-67b26d7e1c27';
$database->save($array);
unset($array);

// Remove temporary permission
$p->delete('conference_room_add', 'temp');

// Clear cache
api_clear_dialplan_cache();

api_created(['conference_room_uuid' => $conference_room_uuid], 'Conference Room created successfully');
