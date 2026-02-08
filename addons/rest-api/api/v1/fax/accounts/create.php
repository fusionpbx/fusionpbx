<?php
require_once __DIR__ . '/../../base.php';
api_require_method('POST');

$data = get_request_data();

// Validate required fields
$errors = api_validate($data, ['fax_extension', 'fax_name']);
if (!empty($errors)) {
    api_validation_error($errors);
}

// Check for duplicate extension
if (api_record_exists('v_fax', 'fax_extension', $data['fax_extension'])) {
    api_conflict('fax_extension', 'Fax extension already exists');
}

// Generate UUID
$fax_uuid = uuid();

// Prepare insert data
$array['fax'][0]['fax_uuid'] = $fax_uuid;
$array['fax'][0]['domain_uuid'] = $domain_uuid;
$array['fax'][0]['fax_extension'] = $data['fax_extension'];
$array['fax'][0]['fax_name'] = $data['fax_name'];
$array['fax'][0]['fax_email'] = $data['fax_email'] ?? null;
$array['fax'][0]['fax_pin_number'] = $data['fax_pin_number'] ?? null;
$array['fax'][0]['fax_caller_id_name'] = $data['fax_caller_id_name'] ?? null;
$array['fax'][0]['fax_caller_id_number'] = $data['fax_caller_id_number'] ?? null;
$array['fax'][0]['fax_forward_number'] = $data['fax_forward_number'] ?? null;
$array['fax'][0]['fax_description'] = $data['fax_description'] ?? null;
$array['fax'][0]['fax_send_channels'] = $data['fax_send_channels'] ?? null;

// Insert into database
$database = new database;
$database->app_name = 'api-fax';
$database->app_uuid = 'c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4';
$database->save($array);

// Clear dialplan cache
api_clear_dialplan_cache();

// Return created resource
$fax = api_get_record('v_fax', 'fax_uuid', $fax_uuid);
api_created($fax, 'Fax account created successfully');
