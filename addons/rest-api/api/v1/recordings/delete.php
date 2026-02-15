<?php
require_once dirname(__DIR__) . '/auth.php';
require_once dirname(__DIR__) . '/base.php';
validate_api_key();

$recording_uuid = get_uuid_from_path();
if (empty($recording_uuid)) {
    api_error('MISSING_UUID', 'Recording UUID is required');
}

$database = new database;
$sql = "SELECT recording_uuid, recording_filename FROM v_recordings WHERE domain_uuid = :domain_uuid AND recording_uuid = :recording_uuid";
$existing = $database->select($sql, ['domain_uuid' => $domain_uuid, 'recording_uuid' => $recording_uuid], 'row');

if (empty($existing)) {
    api_not_found('Recording');
}

// Delete file from disk
$sql = "SELECT default_setting_value FROM v_default_settings
        WHERE default_setting_category = 'switch'
        AND default_setting_subcategory = 'recordings'
        AND default_setting_enabled = 'true' LIMIT 1";
$recordings_dir = $database->select($sql, [], 'column');
if (empty($recordings_dir)) {
    $recordings_dir = '/var/lib/freeswitch/recordings';
}

$file_path = rtrim($recordings_dir, '/') . '/' . $domain_name . '/' . $existing['recording_filename'];
if (file_exists($file_path)) {
    @unlink($file_path);
}

// Delete from database
$array['recordings'][0]['domain_uuid'] = $domain_uuid;
$array['recordings'][0]['recording_uuid'] = $recording_uuid;

$p = permissions::new();
$p->add('recording_delete', 'temp');

$database = new database;
$database->app_name = 'recordings';
$database->app_uuid = '83124285-9428-c498-e498-41f996b3223e';
$database->delete($array);

$p->delete('recording_delete', 'temp');

api_no_content();