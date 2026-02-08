<?php
require_once dirname(__DIR__) . '/auth.php';
require_once dirname(__DIR__, 3) . '/resources/switch.php';
validate_api_key();

$gateway_uuid = get_uuid_from_path();
api_validate_uuid($gateway_uuid, 'gateway_uuid');

// Check if gateway exists and get its profile
$database = new database;
$sql = "SELECT gateway_uuid, profile FROM v_gateways WHERE gateway_uuid = :gateway_uuid AND domain_uuid = :domain_uuid";
$parameters = [
    'gateway_uuid' => $gateway_uuid,
    'domain_uuid' => $domain_uuid
];
$gateway = $database->select($sql, $parameters, 'row');

if (empty($gateway)) {
    api_error('NOT_FOUND', 'Gateway not found', null, 404);
}

$profile = $gateway['profile'] ?? 'external';

// Kill gateway in FreeSWITCH before deleting
$fp = event_socket::create();
if ($fp && $fp->is_connected()) {
    $cmd = 'sofia profile ' . $profile . ' killgw ' . $gateway_uuid;
    event_socket::api($cmd);
}

// Delete from database
$array['gateways'][0]['gateway_uuid'] = $gateway_uuid;
$database->app_name = 'gateways';
$database->app_uuid = 'a2124650-6c38-c96a-0767-12ababf0a8d5';
$database->delete($array);

// Regenerate gateway XML configuration (removes deleted gateway)
save_gateway_xml();

// Clear sofia config cache
$cache = new cache;
$cache->delete(gethostname() . ":configuration:sofia.conf");

// Rescan profile to remove gateway
if ($fp && $fp->is_connected()) {
    event_socket::api("sofia profile " . $profile . " rescan");
}

api_no_content();
