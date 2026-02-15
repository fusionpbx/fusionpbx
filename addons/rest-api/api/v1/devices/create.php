<?php
require_once dirname(__DIR__) . '/auth.php';
validate_api_key();

$request = get_request_data();
if (empty($request['device_address'])) {
    api_error('VALIDATION_ERROR', 'MAC address is required', 'device_address');
}

// Normalize MAC address (remove separators, lowercase)
$mac = strtolower(preg_replace('/[^a-fA-F0-9]/', '', $request['device_address']));
if (strlen($mac) !== 12) {
    api_error('VALIDATION_ERROR', 'Invalid MAC address format', 'device_address');
}

// Check for duplicates
$sql = "SELECT COUNT(*) FROM v_devices WHERE device_address = :device_address";
$parameters = ['device_address' => $mac];
$database = new database;
$exists = $database->select($sql, $parameters, 'column');
if ($exists > 0) {
    api_error('DUPLICATE_ERROR', 'Device with this MAC address already exists', 'device_address', 409);
}
unset($sql, $parameters, $exists);

$device_uuid = uuid();

$array['devices'][0]['domain_uuid'] = $domain_uuid;
$array['devices'][0]['device_uuid'] = $device_uuid;
$array['devices'][0]['device_address'] = $mac;
$array['devices'][0]['device_vendor'] = $request['device_vendor'] ?? '';
$array['devices'][0]['device_model'] = $request['device_model'] ?? '';
$array['devices'][0]['device_label'] = $request['device_label'] ?? '';
$array['devices'][0]['device_template'] = $request['device_template'] ?? '';
$array['devices'][0]['device_enabled'] = $request['device_enabled'] ?? 'true';
$array['devices'][0]['device_description'] = $request['device_description'] ?? '';
$array['devices'][0]['device_username'] = $request['device_username'] ?? '';
$array['devices'][0]['device_password'] = $request['device_password'] ?? '';
$array['devices'][0]['device_location'] = $request['device_location'] ?? '';
$array['devices'][0]['device_serial_number'] = $request['device_serial_number'] ?? '';
$array['devices'][0]['device_firmware_version'] = $request['device_firmware_version'] ?? '';

if (!empty($request['device_profile_uuid']) && is_uuid($request['device_profile_uuid'])) {
    $array['devices'][0]['device_profile_uuid'] = $request['device_profile_uuid'];
}

// Grant permissions
$p = permissions::new();
$p->add('device_add', 'temp');

$database = new database;
$database->app_name = 'devices';
$database->app_uuid = '4efa1a1a-32e7-bf83-534b-6c8299958a8e';
$database->save($array);
unset($array);

// Add device lines if provided
if (!empty($request['lines']) && is_array($request['lines'])) {
    foreach ($request['lines'] as $index => $line) {
        $array['device_lines'][$index]['device_line_uuid'] = uuid();
        $array['device_lines'][$index]['domain_uuid'] = $domain_uuid;
        $array['device_lines'][$index]['device_uuid'] = $device_uuid;
        $array['device_lines'][$index]['line_number'] = $line['line_number'] ?? ($index + 1);
        $array['device_lines'][$index]['server_address'] = $line['server_address'] ?? $domain_name;
        $array['device_lines'][$index]['display_name'] = $line['display_name'] ?? '';
        $array['device_lines'][$index]['user_id'] = $line['user_id'] ?? '';
        $array['device_lines'][$index]['auth_id'] = $line['auth_id'] ?? $line['user_id'] ?? '';
        $array['device_lines'][$index]['password'] = $line['password'] ?? '';
        $array['device_lines'][$index]['label'] = $line['label'] ?? '';
        $array['device_lines'][$index]['enabled'] = $line['enabled'] ?? 'true';
        $array['device_lines'][$index]['outbound_proxy_primary'] = $line['outbound_proxy_primary'] ?? '';
        $array['device_lines'][$index]['outbound_proxy_secondary'] = $line['outbound_proxy_secondary'] ?? '';
    }
    $database = new database;
    $database->save($array);
    unset($array);
}

// Write provisioning files
$settings = new settings(['database' => new database, 'domain_uuid' => $domain_uuid]);
$provision_path = $settings->get('provision', 'path', '');
if (!empty($provision_path) && is_dir(dirname(__DIR__, 5).'/app/provision')) {
    require_once dirname(__DIR__, 5) . '/app/provision/resources/classes/provision.php';
    $prov = new provision;
    $prov->domain_uuid = $domain_uuid;
    $prov->write();
}

// Revoke permissions
$p->delete('device_add', 'temp');

api_success(['device_uuid' => $device_uuid], 'Device created successfully');
