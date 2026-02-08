<?php
require_once dirname(__DIR__) . '/base.php';
validate_api_key();

api_require_method('DELETE');

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

// Check if user is in conference
$database = new database;
$check_sql = "SELECT conference_user_uuid FROM v_conference_users
              WHERE conference_uuid = :conference_uuid
              AND user_uuid = :user_uuid
              AND domain_uuid = :domain_uuid";
$conference_user = $database->select($check_sql, [
    'conference_uuid' => $conference_uuid,
    'user_uuid' => $user_uuid,
    'domain_uuid' => $domain_uuid
], 'row');

if (!$conference_user) {
    api_error('NOT_FOUND', 'User is not a member of this conference', 'user_uuid', 404);
}

// Grant permissions
$p = permissions::new();
$p->add('conference_user_delete', 'temp');

// Delete conference user
$array['conference_users'][0]['conference_user_uuid'] = $conference_user['conference_user_uuid'];

$database = new database;
$database->app_name = 'conferences';
$database->app_uuid = 'b1b30ec1-60a0-df78-5e3a-a9a4b4d3baf4';
$database->delete($array);
unset($array);

// Clean up permissions
$p->delete('conference_user_delete', 'temp');

// Clear dialplan cache
api_clear_dialplan_cache();

api_no_content();
