<?php
require_once dirname(__DIR__) . '/base.php';
validate_api_key();

api_require_method('DELETE');

// Get conference UUID from path
$conference_uuid = get_uuid_from_path();
api_validate_uuid($conference_uuid, 'conference_uuid');

// Check if conference exists
$conference = api_get_record('v_conferences', 'conference_uuid', $conference_uuid);
if (!$conference) {
    api_not_found('Conference');
}

// Delete all conference users first (foreign key relationship)
$array['conference_users'][0]['conference_uuid'] = $conference_uuid;

$database = new database;
$database->app_name = 'conferences';
$database->app_uuid = 'b1b30ec1-60a0-df78-5e3a-a9a4b4d3baf4';
$database->delete($array);
unset($array);

// Grant permissions
$p = permissions::new();
$p->add('conference_delete', 'temp');

// Delete conference
$array['conferences'][0]['conference_uuid'] = $conference_uuid;

$database = new database;
$database->app_name = 'conferences';
$database->app_uuid = 'b1b30ec1-60a0-df78-5e3a-a9a4b4d3baf4';
$database->delete($array);
unset($array);

// Clean up permissions
$p->delete('conference_delete', 'temp');

// Clear dialplan cache
api_clear_dialplan_cache();

api_no_content();
