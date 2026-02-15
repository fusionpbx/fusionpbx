<?php
require_once __DIR__ . '/../../base.php';
api_require_method('DELETE');

$fax_uuid = get_uuid_from_path();
api_validate_uuid($fax_uuid, 'fax_uuid');

// Check if record exists
if (!api_record_exists('v_fax', 'fax_uuid', $fax_uuid)) {
    api_not_found('Fax account');
}

// Delete fax account
$array['fax'][0]['fax_uuid'] = $fax_uuid;
$array['fax'][0]['domain_uuid'] = $domain_uuid;

$database = new database;
$database->app_name = 'api-fax';
$database->app_uuid = 'c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4';
$database->delete($array);

// Clear dialplan cache
api_clear_dialplan_cache();

api_no_content();
