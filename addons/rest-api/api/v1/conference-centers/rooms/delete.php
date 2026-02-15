<?php
require_once __DIR__ . '/../../base.php';
validate_api_key();
api_require_method('DELETE');

$conference_room_uuid = get_uuid_from_path();
api_validate_uuid($conference_room_uuid, 'conference_room_uuid');

// Check if record exists
if (!api_record_exists('v_conference_rooms', 'conference_room_uuid', $conference_room_uuid)) {
    api_not_found('Conference Room');
}

// Delete
$database = new database;
$database->app_name = 'api-conference-centers';
$database->app_uuid = 'a8a12918-69a4-4ece-a1ae-3932e2f8a8a9';
$database->delete('v_conference_rooms', [
    'conference_room_uuid' => $conference_room_uuid,
    'domain_uuid' => $domain_uuid
]);

// Clear cache
api_clear_dialplan_cache();

api_no_content();
