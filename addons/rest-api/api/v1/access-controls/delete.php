<?php
require_once dirname(__DIR__) . '/auth.php';
require_once dirname(__DIR__) . '/base.php';
validate_api_key();

api_require_method('DELETE');

$access_control_uuid = get_uuid_from_path();
api_validate_uuid($access_control_uuid, 'access_control_uuid');

$database = new database;

// Check if access control exists and has access
$check_sql = "SELECT access_control_uuid, domain_uuid FROM v_access_controls
              WHERE access_control_uuid = :access_control_uuid
              AND domain_uuid = :domain_uuid";
$existing = $database->select($check_sql, [
    'access_control_uuid' => $access_control_uuid,
    'domain_uuid' => $domain_uuid
], 'row');

if (!$existing) {
    api_not_found('Access Control');
}

// Grant permissions
$p = permissions::new();
$p->add('access_control_delete', 'temp');

// Delete associated nodes first
$delete_nodes_sql = "DELETE FROM v_access_control_nodes WHERE access_control_uuid = :access_control_uuid";
$database->execute($delete_nodes_sql, ['access_control_uuid' => $access_control_uuid]);

// Delete access control
$delete_sql = "DELETE FROM v_access_controls WHERE access_control_uuid = :access_control_uuid";
$database->execute($delete_sql, ['access_control_uuid' => $access_control_uuid]);

// Clear ACL cache
api_clear_cache("configuration:acl.conf");

// Revoke permissions
$p->delete('access_control_delete', 'temp');

api_no_content();
