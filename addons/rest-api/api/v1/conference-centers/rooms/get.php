<?php
require_once __DIR__ . '/../../base.php';
validate_api_key();

$conference_room_uuid = get_uuid_from_path();
api_validate_uuid($conference_room_uuid, 'conference_room_uuid');

// Get conference room
$database = new database;
$sql = "SELECT conference_room_uuid, conference_center_uuid, conference_room_name,
        moderator_pin, participant_pin, profile, record, max_members, wait_mod,
        announce, sounds, mute, created, enabled, description
        FROM v_conference_rooms
        WHERE conference_room_uuid = :uuid AND domain_uuid = :domain_uuid";

$parameters = [
    'uuid' => $conference_room_uuid,
    'domain_uuid' => $domain_uuid
];

$room = $database->select($sql, $parameters, 'row');

if (!$room) {
    api_not_found('Conference Room');
}

api_success($room);
