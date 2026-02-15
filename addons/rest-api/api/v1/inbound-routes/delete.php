<?php
require_once dirname(__DIR__) . '/auth.php';
validate_api_key();

$app_uuid = 'c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4'; // Inbound routes

$dialplan_uuid = $_GET['id'] ?? '';
if (!is_uuid($dialplan_uuid)) {
    api_error('VALIDATION_ERROR', 'Valid dialplan_uuid is required', 'id');
}

// Verify route exists and belongs to this domain
$database = new database;
$sql = "SELECT dialplan_uuid, dialplan_context FROM v_dialplans
        WHERE domain_uuid = :domain_uuid AND app_uuid = :app_uuid AND dialplan_uuid = :dialplan_uuid";
$parameters = [
    'domain_uuid' => $domain_uuid,
    'app_uuid' => $app_uuid,
    'dialplan_uuid' => $dialplan_uuid
];
$route = $database->select($sql, $parameters, 'row');

if (!$route) {
    api_error('NOT_FOUND', 'Inbound route not found', null, 404);
}

// Grant permissions
$p = permissions::new();
$p->add('dialplan_delete', 'temp');
$p->add('dialplan_detail_delete', 'temp');

// Delete dialplan
$array['dialplans'][0]['dialplan_uuid'] = $dialplan_uuid;
$array['dialplans'][0]['domain_uuid'] = $domain_uuid;

$database = new database;
$database->app_name = 'dialplans';
$database->app_uuid = 'c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4';
$database->delete($array);

$p->delete('dialplan_delete', 'temp');
$p->delete('dialplan_detail_delete', 'temp');

// Clear dialplan cache
$cache = new cache;
$dialplan_context = $route['dialplan_context'] ?? '${domain_name}';
if ($dialplan_context == '${domain_name}' || $dialplan_context == 'global') {
    $dialplan_context = '*';
}
$cache->delete('dialplan:' . $dialplan_context);

api_no_content();
