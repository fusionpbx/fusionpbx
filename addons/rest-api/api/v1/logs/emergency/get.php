<?php
/**
 * FusionPBX REST API - Get Single Emergency/E911 Log Entry
 *
 * GET /api/v1/logs/emergency/get.php?uuid={emergency_log_uuid}
 *
 * Query Parameters:
 * - uuid: Emergency log UUID (required)
 */

require_once __DIR__ . '/../../base.php';
api_require_method('GET');

// Get and validate UUID
$emergency_log_uuid = $_GET['uuid'] ?? '';
api_validate_uuid($emergency_log_uuid, 'emergency log UUID');

// Query the emergency log entry
$database = new database;
$sql = "SELECT
    emergency_log_uuid,
    domain_uuid,
    extension,
    event,
    insert_date,
    insert_user
FROM v_emergency_logs
WHERE emergency_log_uuid = :uuid
AND domain_uuid = :domain_uuid";

$parameters = [
    'uuid' => $emergency_log_uuid,
    'domain_uuid' => $domain_uuid
];

$log = $database->select($sql, $parameters, 'row');

if (!$log) {
    api_not_found('Emergency log');
}

api_success($log);
