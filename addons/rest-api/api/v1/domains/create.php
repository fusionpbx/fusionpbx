<?php
require_once dirname(__DIR__) . '/base.php';

api_require_method('POST');

$request = get_request_data();
if (empty($request['domain_name'])) {
    api_error('VALIDATION_ERROR', 'Domain name is required', 'domain_name');
}

// Validate domain name format
$domain_name_input = strtolower(trim($request['domain_name']));
if (!preg_match('/^[a-z0-9][a-z0-9.\-]+[a-z0-9]$/', $domain_name_input)) {
    api_error('VALIDATION_ERROR', 'Invalid domain name format', 'domain_name');
}

// Check uniqueness
$database = new database;
$sql = "SELECT count(*) FROM v_domains WHERE lower(domain_name) = :domain_name";
if ($database->select($sql, ['domain_name' => $domain_name_input], 'column') > 0) {
    api_error('DUPLICATE_ERROR', 'Domain already exists', 'domain_name');
}

$new_domain_uuid = uuid();

// Build domain array
$array['domains'][0]['domain_uuid'] = $new_domain_uuid;
$array['domains'][0]['domain_name'] = $domain_name_input;
$array['domains'][0]['domain_enabled'] = $request['domain_enabled'] ?? 'true';
$array['domains'][0]['domain_description'] = $request['domain_description'] ?? '';

// Keep a copy before save empties the array
$domain_array = $array;

// Grant permissions
$p = permissions::new();
$p->add('domain_add', 'temp');

// Save domain
$database = new database;
$database->app_name = 'domains';
$database->app_uuid = '8b91605b-f6d2-42e6-a56d-5d1ded01bb44';
$database->save($array);

// Import dialplans for the new domain
if (file_exists(dirname(__DIR__, 5) . '/app/dialplans/app_config.php')) {
    require_once dirname(__DIR__, 5) . '/app/dialplans/resources/classes/dialplan.php';

    $dialplan = new dialplan;
    $dialplan->import($domain_array['domains']);
    unset($array);

    // Generate XML for dialplans with empty XML
    $dialplans = new dialplan;
    $dialplans->source = "details";
    $dialplans->destination = "database";
    $dialplans->context = $domain_name_input;
    $dialplans->is_empty = "dialplan_xml";
    $dialplans->xml();
}

// Create recordings directory
$settings = new settings(['database' => new database, 'domain_uuid' => $new_domain_uuid]);
$recordings_dir = $settings->get('switch', 'recordings', '');
if (!empty($recordings_dir) && !file_exists($recordings_dir . '/' . $domain_name_input)) {
    @mkdir($recordings_dir . '/' . $domain_name_input, 0770, true);
}

// Create voicemail directory
$voicemail_dir = $settings->get('switch', 'voicemail', '');
if (!empty($voicemail_dir) && !file_exists($voicemail_dir . '/default/' . $domain_name_input)) {
    @mkdir($voicemail_dir . '/default/' . $domain_name_input, 0770, true);
}

// Revoke temporary permissions
$p->delete('domain_add', 'temp');

// Clear cache
$cache = new cache;
$cache->delete("domains");

api_created([
    'domain_uuid' => $new_domain_uuid,
    'domain_name' => $domain_name_input
], 'Domain created successfully');
