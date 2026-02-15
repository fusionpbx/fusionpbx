<?php
require_once dirname(__DIR__) . '/auth.php';
require_once dirname(__DIR__) . '/base.php';
validate_api_key();

$ivr_menu_uuid = get_uuid_from_path();
if (empty($ivr_menu_uuid)) {
    api_error('MISSING_UUID', 'IVR menu UUID is required');
}

$database = new database;
$sql = "SELECT ivr_menu_name, ivr_menu_extension, ivr_menu_language, ivr_menu_dialect, ivr_menu_voice,
        ivr_menu_ringback, ivr_menu_cid_prefix, ivr_menu_exit_app, ivr_menu_exit_data,
        ivr_menu_enabled, ivr_menu_description, dialplan_uuid
        FROM v_ivr_menus WHERE domain_uuid = :domain_uuid AND ivr_menu_uuid = :ivr_menu_uuid";
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
if (isset($request['ivr_menu_dialect'])) $array['ivr_menus'][0]['ivr_menu_dialect'] = $request['ivr_menu_dialect'];
if (isset($request['ivr_menu_voice'])) $array['ivr_menus'][0]['ivr_menu_voice'] = $request['ivr_menu_voice'];
if (isset($request['ivr_menu_greet_long'])) $array['ivr_menus'][0]['ivr_menu_greet_long'] = $request['ivr_menu_greet_long'];
if (isset($request['ivr_menu_greet_short'])) $array['ivr_menus'][0]['ivr_menu_greet_short'] = $request['ivr_menu_greet_short'];
if (isset($request['ivr_menu_invalid_sound'])) $array['ivr_menus'][0]['ivr_menu_invalid_sound'] = $request['ivr_menu_invalid_sound'];
if (isset($request['ivr_menu_exit_sound'])) $array['ivr_menus'][0]['ivr_menu_exit_sound'] = $request['ivr_menu_exit_sound'];
if (isset($request['ivr_menu_timeout'])) $array['ivr_menus'][0]['ivr_menu_timeout'] = $request['ivr_menu_timeout'];
if (isset($request['ivr_menu_max_failures'])) $array['ivr_menus'][0]['ivr_menu_max_failures'] = $request['ivr_menu_max_failures'];
if (isset($request['ivr_menu_max_timeouts'])) $array['ivr_menus'][0]['ivr_menu_max_timeouts'] = $request['ivr_menu_max_timeouts'];
if (isset($request['ivr_menu_direct_dial'])) $array['ivr_menus'][0]['ivr_menu_direct_dial'] = $request['ivr_menu_direct_dial'];
if (isset($request['ivr_menu_ringback'])) $array['ivr_menus'][0]['ivr_menu_ringback'] = $request['ivr_menu_ringback'];
if (isset($request['ivr_menu_cid_prefix'])) $array['ivr_menus'][0]['ivr_menu_cid_prefix'] = $request['ivr_menu_cid_prefix'];
if (isset($request['ivr_menu_exit_app'])) $array['ivr_menus'][0]['ivr_menu_exit_app'] = $request['ivr_menu_exit_app'];
if (isset($request['ivr_menu_exit_data'])) $array['ivr_menus'][0]['ivr_menu_exit_data'] = $request['ivr_menu_exit_data'];
if (isset($request['ivr_menu_enabled'])) $array['ivr_menus'][0]['ivr_menu_enabled'] = $request['ivr_menu_enabled'];
if (isset($request['ivr_menu_description'])) $array['ivr_menus'][0]['ivr_menu_description'] = $request['ivr_menu_description'];

// Resolve current values (request overrides existing)
$ivr_menu_name = $request['ivr_menu_name'] ?? $existing['ivr_menu_name'];
$ivr_menu_extension = $request['ivr_menu_extension'] ?? $existing['ivr_menu_extension'];
$ivr_menu_language = $request['ivr_menu_language'] ?? $existing['ivr_menu_language'] ?? 'en';
$ivr_menu_dialect = $request['ivr_menu_dialect'] ?? $existing['ivr_menu_dialect'] ?? '';
$ivr_menu_voice = $request['ivr_menu_voice'] ?? $existing['ivr_menu_voice'] ?? '';
$ivr_menu_ringback = $request['ivr_menu_ringback'] ?? $existing['ivr_menu_ringback'] ?? '';
$ivr_menu_cid_prefix = $request['ivr_menu_cid_prefix'] ?? $existing['ivr_menu_cid_prefix'] ?? '';
$ivr_menu_exit_app = $request['ivr_menu_exit_app'] ?? $existing['ivr_menu_exit_app'] ?? '';
$ivr_menu_exit_data = $request['ivr_menu_exit_data'] ?? $existing['ivr_menu_exit_data'] ?? '';
$ivr_menu_enabled = $request['ivr_menu_enabled'] ?? $existing['ivr_menu_enabled'] ?? 'true';
$ivr_menu_description = $request['ivr_menu_description'] ?? $existing['ivr_menu_description'] ?? '';

// Create dialplan_uuid if IVR doesn't have one yet
$dialplan_uuid = $existing['dialplan_uuid'];
$dialplan_is_new = false;
if (empty($dialplan_uuid) || !is_uuid($dialplan_uuid)) {
    $dialplan_uuid = uuid();
    $dialplan_is_new = true;
    $array['ivr_menus'][0]['dialplan_uuid'] = $dialplan_uuid;
}

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

// Add/update dialplan record
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
$p->add('ivr_menu_edit', 'temp');
if ($dialplan_is_new) {
    $p->add('dialplan_add', 'temp');
}
$p->add('dialplan_edit', 'temp');

$database = new database;
$database->app_name = 'ivr_menus';
$database->app_uuid = 'a5788e9b-58bc-bd1b-df59-fff5d51253ab';
$database->save($array);
unset($array);

$p->delete('ivr_menu_edit', 'temp');
$p->delete('dialplan_add', 'temp');
$p->delete('dialplan_edit', 'temp');

// Update options if provided (replace all)
if (isset($request['options']) && is_array($request['options'])) {
    $p2 = permissions::new();
    $p2->add('ivr_menu_option_delete', 'temp');

    $delete_array['ivr_menu_options'][0]['domain_uuid'] = $domain_uuid;
    $delete_array['ivr_menu_options'][0]['ivr_menu_uuid'] = $ivr_menu_uuid;
    $database->delete($delete_array);
    unset($delete_array);

    $p2->delete('ivr_menu_option_delete', 'temp');

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
        $p3 = permissions::new();
        $p3->add('ivr_menu_option_add', 'temp');

        $database = new database;
        $database->app_name = 'ivr_menus';
        $database->app_uuid = 'a5788e9b-58bc-bd1b-df59-fff5d51253ab';
        $database->save($array);
        unset($array);

        $p3->delete('ivr_menu_option_add', 'temp');
    }
}

// Clear caches
api_clear_dialplan_cache();
$cache = new cache;
$cache->delete("configuration:ivr.conf:" . $ivr_menu_uuid);

api_success(['ivr_menu_uuid' => $ivr_menu_uuid, 'dialplan_uuid' => $dialplan_uuid], 'IVR menu updated successfully');
