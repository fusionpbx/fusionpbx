<?php
require_once dirname(__DIR__) . '/auth.php';
validate_api_key();

$device_uuid = get_uuid_from_path();
if (empty($device_uuid)) {
    api_error('MISSING_UUID', 'Device UUID is required', 'device_uuid');
}

// Check if device exists
$sql = "SELECT device_uuid FROM v_devices WHERE domain_uuid = :domain_uuid AND device_uuid = :device_uuid";
$parameters = [
    'domain_uuid' => $domain_uuid,
    'device_uuid' => $device_uuid
];
$database = new database;
$exists = $database->select($sql, $parameters, 'column');
if (empty($exists)) {
    api_error('NOT_FOUND', 'Device not found', null, 404);
}
unset($sql, $parameters, $exists);

// Use the device class to delete
require_once dirname(__DIR__, 3) . '/app/devices/resources/classes/device.php';
$device = new device(['database' => $database, 'domain_uuid' => $domain_uuid]);
$array[0]['checked'] = 'true';
$array[0]['uuid'] = $device_uuid;
$device->delete($array);
unset($array);

// Regenerate provisioning files
$settings = new settings(['database' => new database, 'domain_uuid' => $domain_uuid]);
$provision_path = $settings->get('provision', 'path', '');
if (!empty($provision_path) && is_dir(dirname(__DIR__, 3).'/app/provision')) {
    require_once dirname(__DIR__, 3) . '/app/provision/resources/classes/provision.php';
    $prov = new provision;
    $prov->domain_uuid = $domain_uuid;
    $prov->write();
}

api_no_content();
