<?php
require_once dirname(__DIR__) . '/auth.php';
require_once dirname(__DIR__) . '/base.php';
validate_api_key();

$request = get_request_data();

if (empty($request['ivr_menu_name'])) {
    api_error('VALIDATION_ERROR', 'IVR menu name is required', 'ivr_menu_name');
}
if (empty($request['ivr_menu_extension'])) {
    api_error('VALIDATION_ERROR', 'IVR menu extension is required', 'ivr_menu_extension');
}

// Check extension uniqueness
$database = new database;
$sql = "SELECT COUNT(*) FROM v_ivr_menus WHERE domain_uuid = :domain_uuid AND ivr_menu_extension = :extension";
if ($database->select($sql, ['domain_uuid' => $domain_uuid, 'extension' => $request['ivr_menu_extension']], 'column') > 0) {
    api_conflict('ivr_menu_extension', 'Extension already exists');
}

$ivr_menu_uuid = uuid();

$array['ivr_menus'][0]['domain_uuid'] = $domain_uuid;
$array['ivr_menus'][0]['ivr_menu_uuid'] = $ivr_menu_uuid;
$array['ivr_menus'][0]['ivr_menu_name'] = $request['ivr_menu_name'];
$array['ivr_menus'][0]['ivr_menu_extension'] = $request['ivr_menu_extension'];
$array['ivr_menus'][0]['ivr_menu_language'] = $request['ivr_menu_language'] ?? 'en';
$array['ivr_menus'][0]['ivr_menu_greet_long'] = $request['ivr_menu_greet_long'] ?? '';
$array['ivr_menus'][0]['ivr_menu_greet_short'] = $request['ivr_menu_greet_short'] ?? '';
$array['ivr_menus'][0]['ivr_menu_invalid_sound'] = $request['ivr_menu_invalid_sound'] ?? '';
$array['ivr_menus'][0]['ivr_menu_exit_sound'] = $request['ivr_menu_exit_sound'] ?? '';
$array['ivr_menus'][0]['ivr_menu_confirm_macro'] = $request['ivr_menu_confirm_macro'] ?? '';
$array['ivr_menus'][0]['ivr_menu_confirm_key'] = $request['ivr_menu_confirm_key'] ?? '';
$array['ivr_menus'][0]['ivr_menu_confirm_attempts'] = $request['ivr_menu_confirm_attempts'] ?? '3';
$array['ivr_menus'][0]['ivr_menu_timeout'] = $request['ivr_menu_timeout'] ?? '3000';
$array['ivr_menus'][0]['ivr_menu_inter_digit_timeout'] = $request['ivr_menu_inter_digit_timeout'] ?? '2000';
$array['ivr_menus'][0]['ivr_menu_max_failures'] = $request['ivr_menu_max_failures'] ?? '3';
$array['ivr_menus'][0]['ivr_menu_max_timeouts'] = $request['ivr_menu_max_timeouts'] ?? '3';
$array['ivr_menus'][0]['ivr_menu_digit_len'] = $request['ivr_menu_digit_len'] ?? '5';
$array['ivr_menus'][0]['ivr_menu_direct_dial'] = $request['ivr_menu_direct_dial'] ?? 'false';
$array['ivr_menus'][0]['ivr_menu_ringback'] = $request['ivr_menu_ringback'] ?? '';
$array['ivr_menus'][0]['ivr_menu_cid_prefix'] = $request['ivr_menu_cid_prefix'] ?? '';
$array['ivr_menus'][0]['ivr_menu_exit_app'] = $request['ivr_menu_exit_app'] ?? 'hangup';
$array['ivr_menus'][0]['ivr_menu_exit_data'] = $request['ivr_menu_exit_data'] ?? '';
$array['ivr_menus'][0]['ivr_menu_context'] = $domain_name;
$array['ivr_menus'][0]['ivr_menu_enabled'] = $request['ivr_menu_enabled'] ?? 'true';
$array['ivr_menus'][0]['ivr_menu_description'] = $request['ivr_menu_description'] ?? '';

// Grant permissions
$p = permissions::new();
$p->add('ivr_menu_add', 'temp');

$database = new database;
$database->app_name = 'ivr_menus';
$database->app_uuid = 'a5788e9b-58bc-bd1b-df59-fff5d51253ab';
$database->save($array);
unset($array);

$p->delete('ivr_menu_add', 'temp');

// Add options if provided
if (!empty($request['options']) && is_array($request['options'])) {
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

    $p = permissions::new();
    $p->add('ivr_menu_option_add', 'temp');

    $database = new database;
    $database->app_name = 'ivr_menus';
    $database->app_uuid = 'a5788e9b-58bc-bd1b-df59-fff5d51253ab';
    $database->save($array);

    $p->delete('ivr_menu_option_add', 'temp');
}

api_clear_dialplan_cache();

// Reload XML
if (class_exists('event_socket')) {
    event_socket::api('reloadxml');
}

api_created(['ivr_menu_uuid' => $ivr_menu_uuid], 'IVR menu created successfully');
