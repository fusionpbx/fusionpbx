<?php
require_once dirname(__DIR__) . '/auth.php';
validate_api_key();

$device_uuid = get_uuid_from_path();
if (empty($device_uuid)) {
    api_error('MISSING_UUID', 'Device UUID is required', 'device_uuid');
}

// Get device
$sql = "SELECT device_uuid, device_address, device_vendor, device_model, device_enabled, device_label,
        device_template, device_description, device_username, device_password, device_location,
        device_serial_number, device_firmware_version, device_profile_uuid
        FROM v_devices
        WHERE domain_uuid = :domain_uuid AND device_uuid = :device_uuid";

$parameters = [
    'domain_uuid' => $domain_uuid,
    'device_uuid' => $device_uuid
];

$database = new database;
$device = $database->select($sql, $parameters, 'row');

if (empty($device)) {
    api_error('NOT_FOUND', 'Device not found', null, 404);
}

// Get device lines
$sql = "SELECT device_line_uuid, line_number, server_address, display_name, user_id, auth_id,
        password, label, enabled, outbound_proxy_primary, outbound_proxy_secondary
        FROM v_device_lines
        WHERE domain_uuid = :domain_uuid AND device_uuid = :device_uuid
        ORDER BY line_number ASC";

$device['device_lines'] = $database->select($sql, $parameters, 'all') ?? [];

// Get device keys
$sql = "SELECT device_key_uuid, device_key_id, device_key_category, device_key_type, device_key_line,
        device_key_value, device_key_extension, device_key_label, device_key_icon
        FROM v_device_keys
        WHERE domain_uuid = :domain_uuid AND device_uuid = :device_uuid
        ORDER BY device_key_id ASC";

$device['device_keys'] = $database->select($sql, $parameters, 'all') ?? [];

api_success($device, 'Device retrieved successfully');
