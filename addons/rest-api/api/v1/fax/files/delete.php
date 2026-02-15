<?php
require_once __DIR__ . '/../../base.php';
api_require_method('DELETE');

$fax_file_uuid = get_uuid_from_path();
api_validate_uuid($fax_file_uuid, 'fax_file_uuid');

// Check if record exists
if (!api_record_exists('v_fax_files', 'fax_file_uuid', $fax_file_uuid)) {
    api_not_found('Fax file');
}

// Get file path before deletion for optional physical file removal
$fax_file = api_get_record('v_fax_files', 'fax_file_uuid', $fax_file_uuid, 'fax_file_path');

// Delete fax file record
$array['fax_files'][0]['fax_file_uuid'] = $fax_file_uuid;
$array['fax_files'][0]['domain_uuid'] = $domain_uuid;

$database = new database;
$database->app_name = 'api-fax';
$database->app_uuid = 'c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4';
$database->delete($array);

// Optionally delete physical file (if requested and file exists)
if (isset($_GET['delete_file']) && $_GET['delete_file'] === 'true') {
    if (!empty($fax_file['fax_file_path']) && file_exists($fax_file['fax_file_path'])) {
        unlink($fax_file['fax_file_path']);
    }
}

api_no_content();
