<?php
require_once dirname(__DIR__) . '/auth.php';
require_once dirname(__DIR__) . '/base.php';
validate_api_key();

$ivr_menu_uuid = get_uuid_from_path();
if (empty($ivr_menu_uuid)) {
    api_error('MISSING_UUID', 'IVR menu UUID is required');
}

$database = new database;
$sql = "SELECT ivr_menu_uuid FROM v_ivr_menus WHERE domain_uuid = :domain_uuid AND ivr_menu_uuid = :ivr_menu_uuid";
if (!$database->select($sql, ['domain_uuid' => $domain_uuid, 'ivr_menu_uuid' => $ivr_menu_uuid], 'row')) {
    api_not_found('IVR menu');
}

// Delete options first
$array['ivr_menu_options'][0]['domain_uuid'] = $domain_uuid;
$array['ivr_menu_options'][0]['ivr_menu_uuid'] = $ivr_menu_uuid;

// Delete IVR menu
$array['ivr_menus'][0]['domain_uuid'] = $domain_uuid;
$array['ivr_menus'][0]['ivr_menu_uuid'] = $ivr_menu_uuid;

$database = new database;
$database->app_name = 'ivr_menus';
$database->app_uuid = 'a5788e9b-58bc-bd1b-df59-fff5d51253ab';
$database->delete($array);

api_clear_dialplan_cache();

api_no_content();
