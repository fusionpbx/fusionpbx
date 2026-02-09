<?php
require_once dirname(__DIR__) . '/base.php';
require_once dirname(__DIR__, 5) . '/resources/switch.php';

$gateway_uuid = get_uuid_from_path();
if (empty($gateway_uuid)) {
    api_error('MISSING_UUID', 'Gateway UUID is required', null, 400);
}

$database = new database;
// Get gateway details (explicit columns to exclude password and auth_username)
$sql = "SELECT gateway_uuid, gateway, domain_uuid, sip_profile_uuid,
        enabled, description, hostname, port, transport, proxy, register,
        context, caller_id_in_from, supress_cng, sip_cid_type, codec_prefs,
        channels, extension, ping, register_proxy, register_transport
        FROM v_gateways WHERE gateway_uuid = :gateway_uuid AND domain_uuid = :domain_uuid";
$parameters = [
    'gateway_uuid' => $gateway_uuid,
    'domain_uuid' => $domain_uuid
];

$gateway = $database->select($sql, $parameters, 'row');

if (empty($gateway)) {
    api_error('NOT_FOUND', 'Gateway not found', null, 404);
}

// Try to get registration status from FreeSWITCH
$gateway['registration_status'] = null;
$gateway['registration_state'] = null;

$esl = event_socket::create();
if ($esl && $esl->is_connected()) {
    $cmd = 'sofia xmlstatus gateway ' . $gateway_uuid;
    $response = trim(event_socket::api($cmd));

    if ($response == "Invalid Gateway!") {
        $cmd = 'sofia xmlstatus gateway ' . strtoupper($gateway_uuid);
        $response = trim(event_socket::api($cmd));
    }

    if ($response != "Invalid Gateway!") {
        try {
            $xml = new SimpleXMLElement($response);
            $gateway['registration_status'] = 'running';
            $gateway['registration_state'] = (string)$xml->state;
        } catch (Exception $e) {
            $gateway['registration_status'] = 'error';
        }
    } else {
        $gateway['registration_status'] = 'stopped';
    }
}

api_success($gateway);
