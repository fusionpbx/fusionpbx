<?php
require_once dirname(__DIR__) . '/base.php';

// Domain-scoped: returns only the authenticated domain's info
$database = new database;
$sql = "SELECT domain_uuid, domain_name, domain_enabled, domain_description
        FROM v_domains WHERE domain_uuid = :domain_uuid";
$domain = $database->select($sql, ['domain_uuid' => $domain_uuid], 'row');

if (empty($domain)) {
    api_error('NOT_FOUND', 'Domain not found', null, 404);
}

api_success([$domain]);
