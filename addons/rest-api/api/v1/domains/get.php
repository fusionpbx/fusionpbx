<?php
require_once dirname(__DIR__) . '/auth.php';
validate_api_key();

$uuid = get_uuid_from_path();
if (empty($uuid) || !is_uuid($uuid)) {
    api_error('VALIDATION_ERROR', 'Invalid domain UUID');
}

// Verify the requested domain matches the authenticated domain
if ($uuid !== $domain_uuid) {
    api_error('FORBIDDEN', 'Access denied to this domain', null, 403);
}

$database = new database;
$sql = "SELECT * FROM v_domains WHERE domain_uuid = :uuid";
$domain = $database->select($sql, ['uuid' => $uuid], 'row');

if (empty($domain)) {
    api_error('NOT_FOUND', 'Domain not found', null, 404);
}

// Get counts for this domain
$sql = "SELECT count(*) FROM v_extensions WHERE domain_uuid = :uuid";
$database = new database;
$domain['extension_count'] = (int)$database->select($sql, ['uuid' => $uuid], 'column');

$sql = "SELECT count(*) FROM v_users WHERE domain_uuid = :uuid";
$database = new database;
$domain['user_count'] = (int)$database->select($sql, ['uuid' => $uuid], 'column');

api_success($domain);
