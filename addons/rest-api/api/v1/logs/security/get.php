<?php
/**
 * FusionPBX REST API - Get Single Event Guard Security Log
 *
 * GET /api/v1/logs/security/get.php?uuid={log_uuid}
 *
 * Query Parameters:
 * - uuid: Event guard log UUID (required)
 */

require_once __DIR__ . '/../../base.php';
api_require_method('GET');

// Get and validate UUID
$log_uuid = $_GET['uuid'] ?? '';
api_validate_uuid($log_uuid, 'log UUID');

// Query the log entry - event_guard_logs is global, no domain filtering
$database = new database;
$sql = "SELECT
    event_guard_log_uuid,
    hostname,
    log_date,
    filter,
    ip_address,
    extension,
    user_agent,
    log_status
FROM v_event_guard_logs
WHERE event_guard_log_uuid = :uuid";

$parameters = ['uuid' => $log_uuid];

$log = $database->select($sql, $parameters, 'row');

if (!$log) {
    api_not_found('Event guard log');
}

api_success($log);
