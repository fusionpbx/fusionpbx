<?php
require_once dirname(__DIR__, 2) . '/auth.php';
require_once dirname(__DIR__, 2) . '/base.php';
validate_api_key();

api_require_method('DELETE');

$access_control_node_uuid = get_uuid_from_path();
api_validate_uuid($access_control_node_uuid, 'access_control_node_uuid');

$database = new database;

// Verify node exists and user has access to parent access control
$check_sql = "SELECT acn.access_control_node_uuid
              FROM v_access_control_nodes acn
              JOIN v_access_controls ac ON acn.access_control_uuid = ac.access_control_uuid
              WHERE acn.access_control_node_uuid = :node_uuid
              AND ac.domain_uuid = :domain_uuid";
$exists = $database->select($check_sql, [
    'node_uuid' => $access_control_node_uuid,
    'domain_uuid' => $domain_uuid
], 'row');

if (!$exists) {
    api_not_found('Access Control Node');
}

// Delete node
$delete_sql = "DELETE FROM v_access_control_nodes WHERE access_control_node_uuid = :node_uuid";
$database->execute($delete_sql, ['node_uuid' => $access_control_node_uuid]);

// Clear ACL cache
api_clear_cache("configuration:acl.conf");

api_no_content();
