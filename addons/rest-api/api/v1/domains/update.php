<?php
require_once dirname(__DIR__) . '/base.php';

api_require_method('PUT');

$target_uuid = get_uuid_from_path();
api_validate_uuid($target_uuid, 'domain_uuid');

$request = get_request_data();
if (empty($request)) {
    api_error('VALIDATION_ERROR', 'No update data provided');
}

// Verify domain exists
$database = new database;
$sql = "SELECT domain_uuid, domain_name, domain_enabled, domain_description FROM v_domains WHERE domain_uuid = :uuid";
$existing = $database->select($sql, ['uuid' => $target_uuid], 'row');
if (empty($existing)) {
    api_not_found('Domain');
}

$original_domain_name = $existing['domain_name'];

// Build update array
$array['domains'][0]['domain_uuid'] = $target_uuid;
$array['domains'][0]['domain_name'] = $existing['domain_name'];
$array['domains'][0]['domain_enabled'] = $existing['domain_enabled'];
$array['domains'][0]['domain_description'] = $existing['domain_description'];

// Apply updates
if (isset($request['domain_enabled'])) {
    $array['domains'][0]['domain_enabled'] = $request['domain_enabled'];
}
if (isset($request['domain_description'])) {
    $array['domains'][0]['domain_description'] = $request['domain_description'];
}
if (isset($request['domain_name'])) {
    $new_name = strtolower(trim($request['domain_name']));
    if (!preg_match('/^[a-z0-9][a-z0-9.\-]+[a-z0-9]$/', $new_name)) {
        api_error('VALIDATION_ERROR', 'Invalid domain name format', 'domain_name');
    }
    // Check uniqueness if name changed
    if ($new_name !== $original_domain_name) {
        $database = new database;
        $sql = "SELECT count(*) FROM v_domains WHERE lower(domain_name) = :name AND domain_uuid != :uuid";
        if ($database->select($sql, ['name' => $new_name, 'uuid' => $target_uuid], 'column') > 0) {
            api_error('DUPLICATE_ERROR', 'Domain name already in use', 'domain_name');
        }
    }
    $array['domains'][0]['domain_name'] = $new_name;
}

// Grant permissions
$p = permissions::new();
$p->add('domain_edit', 'temp');

// Save
$database = new database;
$database->app_name = 'domains';
$database->app_uuid = '8b91605b-f6d2-42e6-a56d-5d1ded01bb44';
$database->save($array);

$p->delete('domain_edit', 'temp');

// Clear cache
$cache = new cache;
$cache->delete("domains");

api_success([
    'domain_uuid' => $target_uuid,
    'domain_name' => $array['domains'][0]['domain_name']
], 'Domain updated successfully');
