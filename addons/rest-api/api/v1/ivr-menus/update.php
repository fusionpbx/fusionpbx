<?php
require_once dirname(__DIR__) . '/auth.php';
require_once dirname(__DIR__) . '/base.php';
validate_api_key();

$ivr_menu_uuid = get_uuid_from_path();
if (empty($ivr_menu_uuid)) {
    api_error('MISSING_UUID', 'IVR menu UUID is required');
}

$database = new database;
$sql = "SELECT ivr_menu_extension FROM v_ivr_menus WHERE domain_uuid = :domain_uuid AND ivr_menu_uuid = :ivr_menu_uuid";
$existing = $database->select($sql, ['domain_uuid' => $domain_uuid, 'ivr_menu_uuid' => $ivr_menu_uuid], 'row');

if (empty($existing)) {
    api_not_found('IVR menu');
}

$request = get_request_data();

$array['ivr_menus'][0]['ivr_menu_uuid'] = $ivr_menu_uuid;
$array['ivr_menus'][0]['domain_uuid'] = $domain_uuid;

if (isset($request['ivr_menu_name'])) $array['ivr_menus'][0]['ivr_menu_name'] = $request['ivr_menu_name'];
if (isset($request['ivr_menu_extension'])) {
    if ($request['ivr_menu_extension'] !== $existing['ivr_menu_extension']) {
        $check_sql = "SELECT COUNT(*) FROM v_ivr_menus WHERE domain_uuid = :domain_uuid AND ivr_menu_extension = :extension AND ivr_menu_uuid != :ivr_menu_uuid";
        if ($database->select($check_sql, ['domain_uuid' => $domain_uuid, 'extension' => $request['ivr_menu_extension'], 'ivr_menu_uuid' => $ivr_menu_uuid], 'column') > 0) {
            api_conflict('ivr_menu_extension', 'Extension already exists');
        }
    }
    $array['ivr_menus'][0]['ivr_menu_extension'] = $request['ivr_menu_extension'];
}
if (isset($request['ivr_menu_language'])) $array['ivr_menus'][0]['ivr_menu_language'] = $request['ivr_menu_language'];
if (isset($request['ivr_menu_greet_long'])) $array['ivr_menus'][0]['ivr_menu_greet_long'] = $request['ivr_menu_greet_long'];
if (isset($request['ivr_menu_greet_short'])) $array['ivr_menus'][0]['ivr_menu_greet_short'] = $request['ivr_menu_greet_short'];
if (isset($request['ivr_menu_invalid_sound'])) $array['ivr_menus'][0]['ivr_menu_invalid_sound'] = $request['ivr_menu_invalid_sound'];
if (isset($request['ivr_menu_exit_sound'])) $array['ivr_menus'][0]['ivr_menu_exit_sound'] = $request['ivr_menu_exit_sound'];
if (isset($request['ivr_menu_timeout'])) $array['ivr_menus'][0]['ivr_menu_timeout'] = $request['ivr_menu_timeout'];
if (isset($request['ivr_menu_max_failures'])) $array['ivr_menus'][0]['ivr_menu_max_failures'] = $request['ivr_menu_max_failures'];
if (isset($request['ivr_menu_max_timeouts'])) $array['ivr_menus'][0]['ivr_menu_max_timeouts'] = $request['ivr_menu_max_timeouts'];
if (isset($request['ivr_menu_direct_dial'])) $array['ivr_menus'][0]['ivr_menu_direct_dial'] = $request['ivr_menu_direct_dial'];
if (isset($request['ivr_menu_exit_app'])) $array['ivr_menus'][0]['ivr_menu_exit_app'] = $request['ivr_menu_exit_app'];
if (isset($request['ivr_menu_exit_data'])) $array['ivr_menus'][0]['ivr_menu_exit_data'] = $request['ivr_menu_exit_data'];
if (isset($request['ivr_menu_enabled'])) $array['ivr_menus'][0]['ivr_menu_enabled'] = $request['ivr_menu_enabled'];
if (isset($request['ivr_menu_description'])) $array['ivr_menus'][0]['ivr_menu_description'] = $request['ivr_menu_description'];

// Grant permissions
$p = permissions::new();
$p->add('ivr_menu_edit', 'temp');

$database = new database;
$database->app_name = 'ivr_menus';
$database->app_uuid = 'a5788e9b-58bc-bd1b-df59-fff5d51253ab';
$database->save($array);
unset($array);

$p->delete('ivr_menu_edit', 'temp');

// Update options if provided (replace all)
if (isset($request['options']) && is_array($request['options'])) {
    $delete_array['ivr_menu_options'][0]['domain_uuid'] = $domain_uuid;
    $delete_array['ivr_menu_options'][0]['ivr_menu_uuid'] = $ivr_menu_uuid;
    $database->delete($delete_array);
    unset($delete_array);

    foreach ($request['options'] as $index => $opt) {
        if (!isset($opt['ivr_menu_option_digits'])) continue;

        $array['ivr_menu_options'][$index]['ivr_menu_option_uuid'] = uuid();
        $array['ivr_menu_options'][$index]['domain_uuid'] = $domain_uuid;
        $array['ivr_menu_options'][$index]['ivr_menu_uuid'] = $ivr_menu_uuid;
        $array['ivr_menu_options'][$index]['ivr_menu_option_digits'] = $opt['ivr_menu_option_digits'];
        $array['ivr_menu_options'][$index]['ivr_menu_option_action'] = $opt['ivr_menu_option_action'] ?? 'menu-exec-app';
        $array['ivr_menu_options'][$index]['ivr_menu_option_param'] = $opt['ivr_menu_option_param'] ?? '';
        $array['ivr_menu_options'][$index]['ivr_menu_option_order'] = $opt['ivr_menu_option_order'] ?? (($index + 1) * 10);
        $array['ivr_menu_options'][$index]['ivr_menu_option_enabled'] = $opt['ivr_menu_option_enabled'] ?? 'true';
        $array['ivr_menu_options'][$index]['ivr_menu_option_description'] = $opt['ivr_menu_option_description'] ?? '';
    }

    if (!empty($array)) {
        $database = new database;
        $database->app_name = 'ivr_menus';
        $database->app_uuid = 'a5788e9b-58bc-bd1b-df59-fff5d51253ab';
        $database->save($array);
    }
}

api_clear_dialplan_cache();

api_success(['ivr_menu_uuid' => $ivr_menu_uuid], 'IVR menu updated successfully');
