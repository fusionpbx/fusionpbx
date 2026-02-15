<?php
require_once dirname(__DIR__) . '/auth.php';
require_once dirname(__DIR__) . '/base.php';
validate_api_key();

$ivr_menu_uuid = get_uuid_from_path();
if (empty($ivr_menu_uuid)) {
    api_error('MISSING_UUID', 'IVR menu UUID is required');
}

$database = new database;
$sql = "SELECT ivr_menu_uuid, dialplan_uuid, ivr_menu_context FROM v_ivr_menus WHERE domain_uuid = :domain_uuid AND ivr_menu_uuid = :ivr_menu_uuid";
$existing = $database->select($sql, ['domain_uuid' => $domain_uuid, 'ivr_menu_uuid' => $ivr_menu_uuid], 'row');
if (empty($existing)) {
    api_not_found('IVR menu');
}

// Delete options, IVR menu, and associated dialplan
$array['ivr_menu_options'][0]['domain_uuid'] = $domain_uuid;
$array['ivr_menu_options'][0]['ivr_menu_uuid'] = $ivr_menu_uuid;

$array['ivr_menus'][0]['domain_uuid'] = $domain_uuid;
$array['ivr_menus'][0]['ivr_menu_uuid'] = $ivr_menu_uuid;

if (!empty($existing['dialplan_uuid']) && is_uuid($existing['dialplan_uuid'])) {
    $array['dialplans'][0]['dialplan_uuid'] = $existing['dialplan_uuid'];
}

$p = permissions::new();
$p->add('ivr_menu_option_delete', 'temp');
$p->add('dialplan_delete', 'temp');

$database = new database;
$database->app_name = 'ivr_menus';
$database->app_uuid = 'a5788e9b-58bc-bd1b-df59-fff5d51253ab';
$database->delete($array);

$p->delete('ivr_menu_option_delete', 'temp');
$p->delete('dialplan_delete', 'temp');

// Clear caches
api_clear_dialplan_cache();
$cache = new cache;
$cache->delete("configuration:ivr.conf:" . $ivr_menu_uuid);

api_no_content();
