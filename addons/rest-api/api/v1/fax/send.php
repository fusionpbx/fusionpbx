<?php
require_once __DIR__ . '/../base.php';
api_require_method('POST');

$data = get_request_data();

// Validate required fields
$errors = api_validate($data, ['fax_uuid', 'destination_number', 'file_path']);
if (!empty($errors)) {
    api_validation_error($errors);
}

$fax_uuid = $data['fax_uuid'];
api_validate_uuid($fax_uuid, 'fax_uuid');

// Validate fax account exists
if (!api_record_exists('v_fax', 'fax_uuid', $fax_uuid)) {
    api_error('NOT_FOUND', 'Fax account not found', 'fax_uuid', 404);
}

// Get fax account details
$fax = api_get_record('v_fax', 'fax_uuid', $fax_uuid);

// Validate file path is within allowed fax directory
$file_path = $data['file_path'] ?? '';
$allowed_base = '/var/lib/freeswitch/storage/fax/' . $domain_name;
$real_path = realpath($file_path);
if ($real_path === false || strpos($real_path, $allowed_base) !== 0) {
    api_error('VALIDATION_ERROR', 'File must be within the fax storage directory', 'file_path', 400);
}

// File existence check (using validated real_path)
if (!file_exists($real_path)) {
    api_error('VALIDATION_ERROR', 'File not found at specified path', 'file_path', 400);
}
$file_path = $real_path;

// Validate file type (PDF or TIFF)
$file_extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
if (!in_array($file_extension, ['pdf', 'tif', 'tiff'])) {
    api_error('VALIDATION_ERROR', 'File must be PDF or TIFF format', 'file_path', 400);
}

// Generate UUID for fax file
$fax_file_uuid = uuid();

// Get current timestamp
$fax_epoch = time();
$fax_date = date('Y-m-d', $fax_epoch);
$fax_time = date('H:i:s', $fax_epoch);

// Determine caller ID
$caller_id_number = $data['caller_id_number'] ?? $fax['fax_caller_id_number'] ?? $fax['fax_extension'];
$caller_id_name = $data['caller_id_name'] ?? $fax['fax_caller_id_name'] ?? $fax['fax_name'];

// Determine file type
$fax_file_type = ($file_extension === 'pdf') ? 'pdf' : 'tif';

// Create fax file record with 'queued' status
$array['fax_files'][0]['fax_file_uuid'] = $fax_file_uuid;
$array['fax_files'][0]['domain_uuid'] = $domain_uuid;
$array['fax_files'][0]['fax_uuid'] = $fax_uuid;
$array['fax_files'][0]['fax_mode'] = 'sent';
$array['fax_files'][0]['fax_file_type'] = $fax_file_type;
$array['fax_files'][0]['fax_file_path'] = $file_path;
$array['fax_files'][0]['fax_caller_id_name'] = $caller_id_name;
$array['fax_files'][0]['fax_caller_id_number'] = $caller_id_number;
$array['fax_files'][0]['fax_destination'] = $data['destination_number'];
$array['fax_files'][0]['fax_date'] = $fax_date;
$array['fax_files'][0]['fax_time'] = $fax_time;
$array['fax_files'][0]['fax_epoch'] = $fax_epoch;
$array['fax_files'][0]['fax_status'] = 'queued';
$array['fax_files'][0]['fax_retry_count'] = 0;

// Insert into database
$database = new database;
$database->app_name = 'api-fax';
$database->app_uuid = 'c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4';
$database->save($array);

// Return created resource
$fax_file = api_get_record('v_fax_files', 'fax_file_uuid', $fax_file_uuid);
api_created($fax_file, 'Fax queued for sending');
