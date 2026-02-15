<?php
require_once dirname(__DIR__) . '/base.php';
validate_api_key();

api_require_method('PUT');

// Get conference UUID from path
$conference_uuid = get_uuid_from_path();
api_validate_uuid($conference_uuid, 'conference_uuid');

// Get request data
$data = get_request_data();

// Check if conference exists
$conference = api_get_record('v_conferences', 'conference_uuid', $conference_uuid);
if (!$conference) {
    api_not_found('Conference');
}

// If extension is being changed, check for conflicts
if (isset($data['conference_extension']) && $data['conference_extension'] !== $conference['conference_extension']) {
    $database = new database;
    $check_sql = "SELECT COUNT(*) FROM v_conferences
                  WHERE domain_uuid = :domain_uuid
                  AND conference_extension = :extension
                  AND conference_uuid != :conference_uuid";
    $exists = $database->select($check_sql, [
        'domain_uuid' => $domain_uuid,
        'extension' => $data['conference_extension'],
        'conference_uuid' => $conference_uuid
    ], 'column');

    if ($exists > 0) {
        api_conflict('conference_extension', 'Conference extension already exists in this domain');
    }
}

// Build array for save - only update provided fields
$array['conferences'][0]['domain_uuid'] = $domain_uuid;
$array['conferences'][0]['conference_uuid'] = $conference_uuid;

$allowed_fields = [
    'conference_name', 'conference_extension', 'conference_pin', 'moderator_pin',
    'wait_mod', 'announce_name', 'announce_count', 'announce_recording',
    'mute', 'sounds', 'member_type', 'profile', 'max_members', 'record',
    'enabled', 'description'
];

foreach ($allowed_fields as $field) {
    if (isset($data[$field])) {
        $array['conferences'][0][$field] = $data[$field];
    }
}

// Grant permissions
$p = permissions::new();
$p->add('conference_edit', 'temp');

// Save using database->save()
$database = new database;
$database->app_name = 'conferences';
$database->app_uuid = 'b1b30ec1-60a0-df78-5e3a-a9a4b4d3baf4';
$database->save($array);
unset($array);

// Clean up permissions
$p->delete('conference_edit', 'temp');

// Clear dialplan cache
api_clear_dialplan_cache();

// Get updated conference
$updated_conference = api_get_record('v_conferences', 'conference_uuid', $conference_uuid);

api_success($updated_conference, 'Conference updated successfully');
