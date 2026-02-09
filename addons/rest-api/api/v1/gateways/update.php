<?php
require_once dirname(__DIR__) . '/base.php';
require_once dirname(__DIR__, 5) . '/resources/switch.php';

$gateway_uuid = get_uuid_from_path();
api_validate_uuid($gateway_uuid, 'gateway_uuid');

$request = get_request_data();

// Check if gateway exists
$database = new database;
$sql = "SELECT gateway_uuid, profile FROM v_gateways WHERE gateway_uuid = :gateway_uuid AND domain_uuid = :domain_uuid";
$parameters = [
    'gateway_uuid' => $gateway_uuid,
    'domain_uuid' => $domain_uuid
];
$existing = $database->select($sql, $parameters, 'row');

if (empty($existing)) {
    api_error('NOT_FOUND', 'Gateway not found', null, 404);
}

// Get the old profile before update
$old_profile = $existing['profile'] ?? 'external';

// Build update array with only provided fields
$array['gateways'][0]['gateway_uuid'] = $gateway_uuid;
$array['gateways'][0]['domain_uuid'] = $domain_uuid;

// Update fields if provided
if (isset($request['gateway'])) $array['gateways'][0]['gateway'] = $request['gateway'];
if (isset($request['username'])) $array['gateways'][0]['username'] = $request['username'];
if (isset($request['password'])) $array['gateways'][0]['password'] = $request['password'];
if (isset($request['auth_username'])) $array['gateways'][0]['auth_username'] = $request['auth_username'];
if (isset($request['realm'])) $array['gateways'][0]['realm'] = $request['realm'];
if (isset($request['from_user'])) $array['gateways'][0]['from_user'] = $request['from_user'];
if (isset($request['from_domain'])) $array['gateways'][0]['from_domain'] = $request['from_domain'];
if (isset($request['proxy'])) $array['gateways'][0]['proxy'] = $request['proxy'];
if (isset($request['register_proxy'])) $array['gateways'][0]['register_proxy'] = $request['register_proxy'];
if (isset($request['outbound_proxy'])) $array['gateways'][0]['outbound_proxy'] = $request['outbound_proxy'];
if (isset($request['expire_seconds'])) $array['gateways'][0]['expire_seconds'] = $request['expire_seconds'];
if (isset($request['register'])) $array['gateways'][0]['register'] = $request['register'];
if (isset($request['register_transport'])) $array['gateways'][0]['register_transport'] = $request['register_transport'];
if (isset($request['retry_seconds'])) $array['gateways'][0]['retry_seconds'] = $request['retry_seconds'];
if (isset($request['extension'])) $array['gateways'][0]['extension'] = $request['extension'];
if (isset($request['ping'])) $array['gateways'][0]['ping'] = $request['ping'];
if (isset($request['context'])) $array['gateways'][0]['context'] = $request['context'];
if (isset($request['profile'])) $array['gateways'][0]['profile'] = $request['profile'];
if (isset($request['enabled'])) $array['gateways'][0]['enabled'] = $request['enabled'];
if (isset($request['description'])) $array['gateways'][0]['description'] = $request['description'];
if (isset($request['hostname'])) $array['gateways'][0]['hostname'] = $request['hostname'];
if (isset($request['distinct_to'])) $array['gateways'][0]['distinct_to'] = $request['distinct_to'];
if (isset($request['contact_params'])) $array['gateways'][0]['contact_params'] = $request['contact_params'];
if (isset($request['caller_id_in_from'])) $array['gateways'][0]['caller_id_in_from'] = $request['caller_id_in_from'];
if (isset($request['supress_cng'])) $array['gateways'][0]['supress_cng'] = $request['supress_cng'];
if (isset($request['sip_cid_type'])) $array['gateways'][0]['sip_cid_type'] = $request['sip_cid_type'];
if (isset($request['extension_in_contact'])) $array['gateways'][0]['extension_in_contact'] = $request['extension_in_contact'];
if (isset($request['ping_min'])) $array['gateways'][0]['ping_min'] = $request['ping_min'];
if (isset($request['ping_max'])) $array['gateways'][0]['ping_max'] = $request['ping_max'];
if (isset($request['contact_in_ping'])) $array['gateways'][0]['contact_in_ping'] = $request['contact_in_ping'];
if (isset($request['codec_prefs'])) $array['gateways'][0]['codec_prefs'] = $request['codec_prefs'];

// Grant permissions
$p = permissions::new();
$p->add('gateway_edit', 'temp');

// Save to database
$database->app_name = 'gateways';
$database->app_uuid = 'a2124650-6c38-c96a-0767-12ababf0a8d5';
$database->save($array);

// Regenerate gateway XML configuration
save_gateway_xml();

// Clear sofia config cache
$cache = new cache;
$cache->delete(gethostname() . ":configuration:sofia.conf");

// Reload gateway in FreeSWITCH
$fp = event_socket::create();
if ($fp && $fp->is_connected()) {
    $new_profile = $request['profile'] ?? $old_profile;

    // Validate profile exists in database before ESL command
    $db_check = new database;
    $check_sql = "SELECT COUNT(*) FROM v_sip_profiles WHERE sip_profile_name = :name";

    // Validate old_profile
    if ($db_check->select($check_sql, ['name' => $old_profile], 'column') == 0) {
        api_error('VALIDATION_ERROR', 'Invalid old SIP profile', 'profile', 400);
    }
    if (!preg_match('/^[a-zA-Z0-9_-]{1,64}$/', $old_profile)) {
        api_error('VALIDATION_ERROR', 'Invalid old profile name format', 'profile', 400);
    }

    // Validate new_profile if different
    if ($old_profile != $new_profile) {
        if ($db_check->select($check_sql, ['name' => $new_profile], 'column') == 0) {
            api_error('VALIDATION_ERROR', 'Invalid new SIP profile', 'profile', 400);
        }
        if (!preg_match('/^[a-zA-Z0-9_-]{1,64}$/', $new_profile)) {
            api_error('VALIDATION_ERROR', 'Invalid new profile name format', 'profile', 400);
        }
    }

    // Rescan both old and new profiles if profile changed
    if ($old_profile != $new_profile) {
        event_socket::api("sofia profile " . $old_profile . " rescan");
        event_socket::api("sofia profile " . $new_profile . " rescan");
    } else {
        event_socket::api("sofia profile " . $new_profile . " rescan");
    }
}

// Revoke permissions
$p->delete('gateway_edit', 'temp');

api_success(['gateway_uuid' => $gateway_uuid], 'Gateway updated successfully');
