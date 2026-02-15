<?php
require_once dirname(__DIR__) . '/auth.php';
require_once dirname(__DIR__) . '/base.php';
validate_api_key();

$access_control_uuid = get_uuid_from_path();
api_validate_uuid($access_control_uuid, 'access_control_uuid');

$database = new database;

// Get access control - can be domain-specific or global
$sql = "SELECT access_control_uuid, access_control_name, access_control_default, access_control_description, domain_uuid
        FROM v_access_controls
        WHERE access_control_uuid = :access_control_uuid
        AND (domain_uuid = :domain_uuid OR domain_uuid IS NULL)";

$parameters = [
    'access_control_uuid' => $access_control_uuid,
    'domain_uuid' => $domain_uuid
];

$access_control = $database->select($sql, $parameters, 'row');

if (!$access_control) {
    api_not_found('Access Control');
}

// Get associated nodes
$nodes_sql = "SELECT access_control_node_uuid, node_type, node_cidr, node_domain, node_description
              FROM v_access_control_nodes
              WHERE access_control_uuid = :access_control_uuid
              ORDER BY node_type, node_cidr";

$nodes = $database->select($nodes_sql, ['access_control_uuid' => $access_control_uuid], 'all');

$access_control['nodes'] = $nodes ?? [];

api_success($access_control);
