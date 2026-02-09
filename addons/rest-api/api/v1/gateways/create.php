<?php
require_once dirname(__DIR__) . '/base.php';
require_once dirname(__DIR__, 5) . '/resources/switch.php';

$request = get_request_data();

// Validate required fields
if (empty($request['gateway'])) {
    api_error('VALIDATION_ERROR', 'Gateway name is required', 'gateway');
}
if (empty($request['proxy'])) {
    api_error('VALIDATION_ERROR', 'Proxy is required', 'proxy');
}

// Generate UUID
$gateway_uuid = uuid();

// Build gateway array
$array['gateways'][0]['domain_uuid'] = $domain_uuid;
$array['gateways'][0]['gateway_uuid'] = $gateway_uuid;
$array['gateways'][0]['gateway'] = $request['gateway'];
$array['gateways'][0]['username'] = $request['username'] ?? '';
$array['gateways'][0]['password'] = $request['password'] ?? '';
$array['gateways'][0]['auth_username'] = $request['auth_username'] ?? '';
$array['gateways'][0]['realm'] = $request['realm'] ?? '';
$array['gateways'][0]['from_user'] = $request['from_user'] ?? '';
$array['gateways'][0]['from_domain'] = $request['from_domain'] ?? '';
$array['gateways'][0]['proxy'] = $request['proxy'];
$array['gateways'][0]['register_proxy'] = $request['register_proxy'] ?? '';
$array['gateways'][0]['outbound_proxy'] = $request['outbound_proxy'] ?? '';
$array['gateways'][0]['expire_seconds'] = $request['expire_seconds'] ?? '800';
$array['gateways'][0]['register'] = $request['register'] ?? 'false';
$array['gateways'][0]['register_transport'] = $request['register_transport'] ?? '';
$array['gateways'][0]['retry_seconds'] = $request['retry_seconds'] ?? '30';
$array['gateways'][0]['extension'] = $request['extension'] ?? '';
$array['gateways'][0]['ping'] = $request['ping'] ?? '';
$array['gateways'][0]['context'] = $request['context'] ?? '';
$array['gateways'][0]['profile'] = $request['profile'] ?? 'external';
$array['gateways'][0]['enabled'] = $request['enabled'] ?? 'true';
$array['gateways'][0]['description'] = $request['description'] ?? '';
$array['gateways'][0]['hostname'] = $request['hostname'] ?? '';
$array['gateways'][0]['distinct_to'] = $request['distinct_to'] ?? '';
$array['gateways'][0]['contact_params'] = $request['contact_params'] ?? '';
$array['gateways'][0]['caller_id_in_from'] = $request['caller_id_in_from'] ?? '';
$array['gateways'][0]['supress_cng'] = $request['supress_cng'] ?? '';
$array['gateways'][0]['sip_cid_type'] = $request['sip_cid_type'] ?? '';
$array['gateways'][0]['extension_in_contact'] = $request['extension_in_contact'] ?? '';
$array['gateways'][0]['ping_min'] = $request['ping_min'] ?? '';
$array['gateways'][0]['ping_max'] = $request['ping_max'] ?? '';
$array['gateways'][0]['contact_in_ping'] = $request['contact_in_ping'] ?? '';
$array['gateways'][0]['codec_prefs'] = $request['codec_prefs'] ?? '';

// Save to database
$database = new database;
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
    $profile = $request['profile'] ?? 'external';

    // Validate profile exists in database before ESL command
    $db_check = new database;
    $check_sql = "SELECT COUNT(*) FROM v_sip_profiles WHERE sip_profile_name = :name";
    if ($db_check->select($check_sql, ['name' => $profile], 'column') == 0) {
        api_error('VALIDATION_ERROR', 'Invalid SIP profile', 'profile', 400);
    }
    // Also sanitize - profile names should be alphanumeric with hyphens/underscores only
    if (!preg_match('/^[a-zA-Z0-9_-]{1,64}$/', $profile)) {
        api_error('VALIDATION_ERROR', 'Invalid profile name format', 'profile', 400);
    }

    event_socket::api("sofia profile " . $profile . " rescan");
}

api_success(['gateway_uuid' => $gateway_uuid], 'Gateway created successfully');
