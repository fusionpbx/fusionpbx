<?php
require_once dirname(__DIR__, 2) . '/auth.php';
require_once dirname(__DIR__, 2) . '/base.php';
validate_api_key();

// Get access_control_uuid from query parameter
$access_control_uuid = $_GET['access_control_uuid'] ?? null;
api_validate_uuid($access_control_uuid, 'access_control_uuid');

$database = new database;

// Verify access control exists and user has access
$check_sql = "SELECT access_control_uuid FROM v_access_controls
              WHERE access_control_uuid = :access_control_uuid
              AND (domain_uuid = :domain_uuid OR domain_uuid IS NULL)";
$exists = $database->select($check_sql, [
    'access_control_uuid' => $access_control_uuid,
    'domain_uuid' => $domain_uuid
], 'row');

if (!$exists) {
    api_not_found('Access Control');
}

// Get pagination
$params = get_pagination_params();
$page = $params['page'];
$per_page = $params['per_page'];

// Build query
$sql = "SELECT access_control_node_uuid, access_control_uuid, node_type, node_cidr, node_domain, node_description
        FROM v_access_control_nodes
        WHERE access_control_uuid = :access_control_uuid
        ORDER BY node_type, node_cidr";

$count_sql = "SELECT COUNT(*) FROM v_access_control_nodes WHERE access_control_uuid = :access_control_uuid";

$parameters = ['access_control_uuid' => $access_control_uuid];

$result = api_paginate($sql, $count_sql, $parameters, $page, $per_page);

api_success($result['items'], null, $result['pagination']);
