<?php
require_once dirname(__DIR__) . '/base.php';
validate_api_key();

api_require_method('POST');

// Get request data
$data = get_request_data();

// Validate required fields
$errors = api_validate($data, ['conference_uuid', 'user_uuid']);
if (!empty($errors)) {
    api_validation_error($errors);
}

$conference_uuid = $data['conference_uuid'];
$user_uuid = $data['user_uuid'];

// Validate UUIDs
api_validate_uuid($conference_uuid, 'conference_uuid');
api_validate_uuid($user_uuid, 'user_uuid');

// Check if conference exists
if (!api_record_exists('v_conferences', 'conference_uuid', $conference_uuid)) {
    api_not_found('Conference');
}

// Check if user exists in this domain
if (!api_record_exists('v_users', 'user_uuid', $user_uuid)) {
    api_error('NOT_FOUND', 'User not found in this domain', 'user_uuid', 404);
}

// Check if user is already in conference
$database = new database;
$check_sql = "SELECT COUNT(*) FROM v_conference_users
              WHERE conference_uuid = :conference_uuid
              AND user_uuid = :user_uuid
              AND domain_uuid = :domain_uuid";
$exists = $database->select($check_sql, [
    'conference_uuid' => $conference_uuid,
    'user_uuid' => $user_uuid,
    'domain_uuid' => $domain_uuid
], 'column');

if ($exists > 0) {
    api_conflict('user_uuid', 'User is already a member of this conference');
}

// Generate UUID for conference_user
$conference_user_uuid = uuid();

// Build array for save
$array['conference_users'][0]['domain_uuid'] = $domain_uuid;
$array['conference_users'][0]['conference_user_uuid'] = $conference_user_uuid;
$array['conference_users'][0]['conference_uuid'] = $conference_uuid;
$array['conference_users'][0]['user_uuid'] = $user_uuid;

// Grant permissions
$p = permissions::new();
$p->add('conference_user_add', 'temp');

// Save using database->save()
$database = new database;
$database->app_name = 'conferences';
$database->app_uuid = 'b1b30ec1-60a0-df78-5e3a-a9a4b4d3baf4';
$database->save($array);
unset($array);

// Clean up permissions
$p->delete('conference_user_add', 'temp');

// Clear dialplan cache
api_clear_dialplan_cache();

// Return created record
api_created([
    'conference_user_uuid' => $conference_user_uuid,
    'conference_uuid' => $conference_uuid,
    'user_uuid' => $user_uuid
], 'User added to conference successfully');
