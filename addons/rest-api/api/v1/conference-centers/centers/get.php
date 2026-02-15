<?php
require_once __DIR__ . '/../../base.php';
validate_api_key();

$conference_center_uuid = get_uuid_from_path();
api_validate_uuid($conference_center_uuid, 'conference_center_uuid');

// Get conference center
$database = new database;
$sql = "SELECT conference_center_uuid, conference_center_name, conference_center_extension,
        conference_center_pin_length, conference_center_greeting, conference_center_enabled,
        conference_center_description
        FROM v_conference_centers
        WHERE conference_center_uuid = :uuid AND domain_uuid = :domain_uuid";

$parameters = [
    'uuid' => $conference_center_uuid,
    'domain_uuid' => $domain_uuid
];

$center = $database->select($sql, $parameters, 'row');

if (!$center) {
    api_not_found('Conference Center');
}

// Get associated rooms
$rooms_sql = "SELECT conference_room_uuid, conference_room_name, moderator_pin, participant_pin,
        profile, record, max_members, wait_mod, announce, sounds, mute, enabled, description
        FROM v_conference_rooms
        WHERE conference_center_uuid = :conference_center_uuid AND domain_uuid = :domain_uuid
        ORDER BY conference_room_name ASC";

$rooms_params = [
    'conference_center_uuid' => $conference_center_uuid,
    'domain_uuid' => $domain_uuid
];

$rooms = $database->select($rooms_sql, $rooms_params, 'all');
$center['rooms'] = $rooms ?? [];

api_success($center);
