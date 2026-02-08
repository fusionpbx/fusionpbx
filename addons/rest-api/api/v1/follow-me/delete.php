<?php
require_once dirname(__DIR__) . '/base.php';
validate_api_key();
api_require_method('DELETE');

// Get UUID from path
$follow_me_uuid = get_uuid_from_path();
api_validate_uuid($follow_me_uuid, 'follow_me_uuid');

// Check if record exists
if (!api_record_exists('v_follow_me', 'follow_me_uuid', $follow_me_uuid)) {
    api_not_found('Follow Me configuration');
}

// Grant permissions
$p = new permissions;
$p->add('follow_me_delete', 'temp');

// Delete destinations first (child records)
$array['follow_me_destinations'][0]['follow_me_uuid'] = $follow_me_uuid;
$array['follow_me_destinations'][0]['domain_uuid'] = $domain_uuid;

$database = new database;
$database->app_name = 'follow_me';
$database->app_uuid = '93b50fce-256f-4e2f-85a2-393087349764';
$database->delete($array);
unset($array);

// Delete follow_me record
$array['follow_me'][0]['follow_me_uuid'] = $follow_me_uuid;
$array['follow_me'][0]['domain_uuid'] = $domain_uuid;

$database = new database;
$database->app_name = 'follow_me';
$database->app_uuid = '93b50fce-256f-4e2f-85a2-393087349764';
$database->delete($array);
unset($array);

// Delete permissions
$p->delete('follow_me_delete', 'temp');

// Clear dialplan cache
api_clear_dialplan_cache();

// Return no content (204)
api_no_content();
