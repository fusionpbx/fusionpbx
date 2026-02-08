<?php
require_once dirname(__DIR__) . '/auth.php';
validate_api_key();

$database = new database;

// Extensions stats
$sql = "SELECT count(*) FROM v_extensions WHERE domain_uuid = :domain_uuid";
$total_extensions = $database->select($sql, ['domain_uuid' => $domain_uuid], 'column');

// Users stats
$sql = "SELECT count(*) FROM v_users WHERE domain_uuid = :domain_uuid AND user_enabled = 'true'";
$database = new database;
$total_users = $database->select($sql, ['domain_uuid' => $domain_uuid], 'column');

// Gateways stats
$sql = "SELECT count(*) FROM v_gateways WHERE domain_uuid = :domain_uuid AND enabled = 'true'";
$database = new database;
$total_gateways = $database->select($sql, ['domain_uuid' => $domain_uuid], 'column');

// Voicemail stats
$sql = "SELECT count(*) FROM v_voicemail_messages vm
        JOIN v_voicemails v ON vm.voicemail_uuid = v.voicemail_uuid
        WHERE v.domain_uuid = :domain_uuid AND (vm.message_status = '' OR vm.message_status IS NULL)";
$database = new database;
$new_voicemails = $database->select($sql, ['domain_uuid' => $domain_uuid], 'column');

// Today's calls
$today = date('Y-m-d');
$sql = "SELECT count(*) FROM v_xml_cdr WHERE domain_uuid = :domain_uuid AND start_stamp >= :today";
$database = new database;
$today_calls = $database->select($sql, ['domain_uuid' => $domain_uuid, 'today' => $today . ' 00:00:00'], 'column');

api_success([
    'extensions' => ['total' => intval($total_extensions)],
    'users' => ['total' => intval($total_users)],
    'gateways' => ['total' => intval($total_gateways)],
    'voicemails' => ['new_messages' => intval($new_voicemails)],
    'calls' => ['today_total' => intval($today_calls)]
]);
