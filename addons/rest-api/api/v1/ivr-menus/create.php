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
$dialplan_uuid = uuid();

// Collect IVR settings
$ivr_menu_name = $request['ivr_menu_name'];
$ivr_menu_extension = $request['ivr_menu_extension'];
$ivr_menu_language = $request['ivr_menu_language'] ?? 'en';
$ivr_menu_dialect = $request['ivr_menu_dialect'] ?? '';
$ivr_menu_voice = $request['ivr_menu_voice'] ?? '';
$ivr_menu_ringback = $request['ivr_menu_ringback'] ?? '';
$ivr_menu_cid_prefix = $request['ivr_menu_cid_prefix'] ?? '';
$ivr_menu_exit_app = $request['ivr_menu_exit_app'] ?? 'hangup';
$ivr_menu_exit_data = $request['ivr_menu_exit_data'] ?? '';
$ivr_menu_enabled = $request['ivr_menu_enabled'] ?? 'true';
$ivr_menu_description = $request['ivr_menu_description'] ?? '';

$array['ivr_menus'][0]['domain_uuid'] = $domain_uuid;
$array['ivr_menus'][0]['ivr_menu_uuid'] = $ivr_menu_uuid;
$array['ivr_menus'][0]['dialplan_uuid'] = $dialplan_uuid;
$array['ivr_menus'][0]['ivr_menu_name'] = $ivr_menu_name;
$array['ivr_menus'][0]['ivr_menu_extension'] = $ivr_menu_extension;
$array['ivr_menus'][0]['ivr_menu_language'] = $ivr_menu_language;
$array['ivr_menus'][0]['ivr_menu_dialect'] = $ivr_menu_dialect;
$array['ivr_menus'][0]['ivr_menu_voice'] = $ivr_menu_voice;
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
$array['ivr_menus'][0]['ivr_menu_ringback'] = $ivr_menu_ringback;
$array['ivr_menus'][0]['ivr_menu_cid_prefix'] = $ivr_menu_cid_prefix;
$array['ivr_menus'][0]['ivr_menu_exit_app'] = $ivr_menu_exit_app;
$array['ivr_menus'][0]['ivr_menu_exit_data'] = $ivr_menu_exit_data;
$array['ivr_menus'][0]['ivr_menu_context'] = $domain_name;
$array['ivr_menus'][0]['ivr_menu_enabled'] = $ivr_menu_enabled;
$array['ivr_menus'][0]['ivr_menu_description'] = $ivr_menu_description;

// Build dialplan XML (matches FusionPBX ivr_menu_edit.php format)
$esc = function($v) { return htmlspecialchars($v ?? '', ENT_XML1 | ENT_QUOTES, 'UTF-8'); };
$dialplan_xml = '<extension name="' . $esc($ivr_menu_name) . '" continue="false" uuid="' . $dialplan_uuid . '">' . "\n";
$dialplan_xml .= "\t" . '<condition field="destination_number" expression="^' . $esc($ivr_menu_extension) . '$">' . "\n";
$dialplan_xml .= "\t\t" . '<action application="ring_ready" data=""/>' . "\n";
$dialplan_xml .= "\t\t" . '<action application="sleep" data="1000"/>' . "\n";
$dialplan_xml .= "\t\t" . '<action application="set" data="hangup_after_bridge=true"/>' . "\n";
if (!empty($ivr_menu_ringback)) {
    $dialplan_xml .= "\t\t" . '<action application="set" data="ringback=' . $ivr_menu_ringback . '"/>' . "\n";
}
if (!empty($ivr_menu_language)) {
    $dialect = !empty($ivr_menu_dialect) ? $ivr_menu_dialect : 'us';
    $voice = !empty($ivr_menu_voice) ? $ivr_menu_voice : 'callie';
    $dialplan_xml .= "\t\t" . '<action application="set" data="sound_prefix=$${sounds_dir}/' . $esc($ivr_menu_language) . '/' . $esc($dialect) . '/' . $esc($voice) . '" inline="true"/>' . "\n";
    $dialplan_xml .= "\t\t" . '<action application="set" data="default_language=' . $esc($ivr_menu_language) . '" inline="true"/>' . "\n";
    $dialplan_xml .= "\t\t" . '<action application="set" data="default_dialect=' . $esc($dialect) . '" inline="true"/>' . "\n";
    $dialplan_xml .= "\t\t" . '<action application="set" data="default_voice=' . $esc($voice) . '" inline="true"/>' . "\n";
}
if (!empty($ivr_menu_ringback)) {
    $dialplan_xml .= "\t\t" . '<action application="set" data="transfer_ringback=' . $ivr_menu_ringback . '"/>' . "\n";
}
$dialplan_xml .= "\t\t" . '<action application="set" data="ivr_menu_uuid=' . $ivr_menu_uuid . '"/>' . "\n";
if (!empty($ivr_menu_cid_prefix)) {
    $dialplan_xml .= "\t\t" . '<action application="set" data="caller_id_name=' . $esc($ivr_menu_cid_prefix) . '#${caller_id_name}"/>' . "\n";
    $dialplan_xml .= "\t\t" . '<action application="set" data="effective_caller_id_name=${caller_id_name}"/>' . "\n";
}
$dialplan_xml .= "\t\t" . '<action application="ivr" data="' . $ivr_menu_uuid . '"/>' . "\n";
if (!empty($ivr_menu_exit_app)) {
    $dialplan_xml .= "\t\t" . '<action application="' . $esc($ivr_menu_exit_app) . '" data="' . $esc($ivr_menu_exit_data) . '"/>' . "\n";
}
$dialplan_xml .= "\t" . '</condition>' . "\n";
$dialplan_xml .= '</extension>';

// Add dialplan record
$array['dialplans'][0]['domain_uuid'] = $domain_uuid;
$array['dialplans'][0]['dialplan_uuid'] = $dialplan_uuid;
$array['dialplans'][0]['dialplan_name'] = $ivr_menu_name;
$array['dialplans'][0]['dialplan_number'] = $ivr_menu_extension;
$array['dialplans'][0]['dialplan_context'] = $domain_name;
$array['dialplans'][0]['dialplan_continue'] = 'false';
$array['dialplans'][0]['dialplan_xml'] = $dialplan_xml;
$array['dialplans'][0]['dialplan_order'] = '101';
$array['dialplans'][0]['dialplan_enabled'] = $ivr_menu_enabled;
$array['dialplans'][0]['dialplan_description'] = $ivr_menu_description;
$array['dialplans'][0]['app_uuid'] = 'a5788e9b-58bc-bd1b-df59-fff5d51253ab';

// Grant permissions
$p = permissions::new();
$p->add('ivr_menu_add', 'temp');
$p->add('dialplan_add', 'temp');

$database = new database;
$database->app_name = 'ivr_menus';
$database->app_uuid = 'a5788e9b-58bc-bd1b-df59-fff5d51253ab';
$database->save($array);
unset($array);

$p->delete('ivr_menu_add', 'temp');
$p->delete('dialplan_add', 'temp');

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
    unset($array);

    $p->delete('ivr_menu_option_add', 'temp');
}

// Clear caches
api_clear_dialplan_cache();
$cache = new cache;
$cache->delete("configuration:ivr.conf:" . $ivr_menu_uuid);

api_created(['ivr_menu_uuid' => $ivr_menu_uuid, 'dialplan_uuid' => $dialplan_uuid], 'IVR menu created successfully');
