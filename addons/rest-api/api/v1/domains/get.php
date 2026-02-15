<?php
require_once dirname(__DIR__) . '/base.php';

api_require_method('GET');

$uuid = get_uuid_from_path();
api_validate_uuid($uuid, 'domain_uuid');

$database = new database;
$sql = "SELECT domain_uuid, domain_name, domain_enabled, domain_description
        FROM v_domains WHERE domain_uuid = :uuid";
$domain = $database->select($sql, ['uuid' => $uuid], 'row');

if (empty($domain)) {
    api_not_found('Domain');
}

// Get counts for this domain
$database = new database;
$sql = "SELECT count(*) FROM v_extensions WHERE domain_uuid = :uuid";
$domain['extension_count'] = (int) $database->select($sql, ['uuid' => $uuid], 'column');

$database = new database;
$sql = "SELECT count(*) FROM v_users WHERE domain_uuid = :uuid";
$domain['user_count'] = (int) $database->select($sql, ['uuid' => $uuid], 'column');

api_success($domain);
