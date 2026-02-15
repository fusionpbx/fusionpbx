<?php
/**
 * FusionPBX REST API - Get Single Database Audit Transaction
 *
 * GET /api/v1/logs/audit/get.php?uuid={transaction_uuid}
 *
 * Query Parameters:
 * - uuid: Database transaction UUID (required)
 */

require_once __DIR__ . '/../../base.php';
api_require_method('GET');

// Get and validate UUID
$transaction_uuid = $_GET['uuid'] ?? '';
api_validate_uuid($transaction_uuid, 'transaction UUID');

// Query the transaction
$database = new database;
$sql = "SELECT
    database_transaction_uuid,
    domain_uuid,
    user_uuid,
    app_name,
    app_uuid,
    transaction_code,
    transaction_address,
    transaction_type,
    transaction_date,
    transaction_old,
    transaction_new,
    transaction_result
FROM v_database_transactions
WHERE database_transaction_uuid = :uuid
AND (domain_uuid = :domain_uuid OR domain_uuid IS NULL)";

$parameters = [
    'uuid' => $transaction_uuid,
    'domain_uuid' => $domain_uuid
];

$transaction = $database->select($sql, $parameters, 'row');

if (!$transaction) {
    api_not_found('Database transaction');
}

api_success($transaction);
