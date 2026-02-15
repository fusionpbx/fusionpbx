<?php
require_once __DIR__ . '/../../base.php';
validate_api_key();
api_require_method('DELETE');

$conference_center_uuid = get_uuid_from_path();
api_validate_uuid($conference_center_uuid, 'conference_center_uuid');

// Check if record exists
if (!api_record_exists('v_conference_centers', 'conference_center_uuid', $conference_center_uuid)) {
    api_not_found('Conference Center');
}

// Check for associated rooms
$database = new database;
$check_sql = "SELECT COUNT(*) FROM v_conference_rooms
              WHERE conference_center_uuid = :uuid AND domain_uuid = :domain_uuid";
$check_params = [
    'uuid' => $conference_center_uuid,
    'domain_uuid' => $domain_uuid
];

$room_count = $database->select($check_sql, $check_params, 'column');
if ($room_count > 0) {
    api_error('CONSTRAINT_ERROR', 'Cannot delete conference center with associated rooms', null, 409);
}

// Delete
$database->app_name = 'api-conference-centers';
$database->app_uuid = 'a8a12918-69a4-4ece-a1ae-3932e2f8a8a9';
$database->delete('v_conference_centers', [
    'conference_center_uuid' => $conference_center_uuid,
    'domain_uuid' => $domain_uuid
]);

// Clear cache
api_clear_dialplan_cache();

api_no_content();
