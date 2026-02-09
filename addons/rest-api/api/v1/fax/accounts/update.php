<?php
require_once __DIR__ . '/../../base.php';
api_require_method(['PUT', 'PATCH']);

$fax_uuid = get_uuid_from_path();
api_validate_uuid($fax_uuid, 'fax_uuid');

// Check if record exists
if (!api_record_exists('v_fax', 'fax_uuid', $fax_uuid)) {
    api_not_found('Fax account');
}

$data = get_request_data();

// If updating extension, check for duplicates
if (isset($data['fax_extension'])) {
    $database = new database;
    $sql = "SELECT COUNT(*) FROM v_fax
            WHERE fax_extension = :fax_extension
            AND domain_uuid = :domain_uuid
            AND fax_uuid != :fax_uuid";
    $params = [
        'fax_extension' => $data['fax_extension'],
        'domain_uuid' => $domain_uuid,
        'fax_uuid' => $fax_uuid
    ];
    if ($database->select($sql, $params, 'column') > 0) {
        api_conflict('fax_extension', 'Fax extension already exists');
    }
}

// Prepare update data
$array['fax'][0]['fax_uuid'] = $fax_uuid;
$array['fax'][0]['domain_uuid'] = $domain_uuid;

$allowed_fields = [
    'fax_extension', 'fax_name', 'fax_email', 'fax_pin_number',
    'fax_caller_id_name', 'fax_caller_id_number', 'fax_forward_number',
    'fax_description', 'fax_send_channels'
];

foreach ($allowed_fields as $field) {
    if (isset($data[$field])) {
        $array['fax'][0][$field] = $data[$field];
    }
}

// Grant permissions
$p = permissions::new();
$p->add('fax_edit', 'temp');

// Update database
$database = new database;
$database->app_name = 'api-fax';
$database->app_uuid = 'c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4';
$database->save($array);

// Clear dialplan cache
api_clear_dialplan_cache();

// Revoke permissions
$p->delete('fax_edit', 'temp');

// Return updated resource
$fax = api_get_record('v_fax', 'fax_uuid', $fax_uuid);
api_success($fax, 'Fax account updated successfully');
