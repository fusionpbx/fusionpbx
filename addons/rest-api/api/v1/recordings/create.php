<?php
require_once dirname(__DIR__) . '/auth.php';
require_once dirname(__DIR__) . '/base.php';
validate_api_key();

// Accept both multipart/form-data (file upload) and JSON (base64)
$recording_name = '';
$recording_description = '';
$recording_filename = '';
$file_contents = null;

if (!empty($_FILES['file'])) {
    // Multipart file upload
    $upload = $_FILES['file'];
    if ($upload['error'] !== UPLOAD_ERR_OK) {
        $upload_errors = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds server max upload size',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds form max size',
            UPLOAD_ERR_PARTIAL => 'File only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temp directory',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file',
        ];
        api_error('UPLOAD_ERROR', $upload_errors[$upload['error']] ?? 'Upload failed', 'file');
    }

    // Validate file size (max 10MB)
    if ($upload['size'] > 10485760) {
        api_error('VALIDATION_ERROR', 'File too large. Maximum 10MB', 'file');
    }

    $recording_filename = $_POST['recording_filename'] ?? basename($upload['name']);
    $recording_name = $_POST['recording_name'] ?? pathinfo($recording_filename, PATHINFO_FILENAME);
    $recording_description = $_POST['recording_description'] ?? '';
    $file_contents = file_get_contents($upload['tmp_name']);
} else {
    // JSON with base64
    $request = get_request_data();
    if (empty($request['file_base64']) && empty($request['recording_filename'])) {
        api_error('VALIDATION_ERROR', 'File upload or base64 content is required. Use multipart/form-data with "file" field, or JSON with "file_base64"', 'file');
    }

    $recording_filename = $request['recording_filename'] ?? '';
    $recording_name = $request['recording_name'] ?? pathinfo($recording_filename, PATHINFO_FILENAME);
    $recording_description = $request['recording_description'] ?? '';

    if (!empty($request['file_base64'])) {
        $file_contents = base64_decode($request['file_base64'], true);
        if ($file_contents === false) {
            api_error('VALIDATION_ERROR', 'Invalid base64 content', 'file_base64');
        }
    }
}

// Validate filename
if (empty($recording_filename)) {
    api_error('VALIDATION_ERROR', 'Recording filename is required', 'recording_filename');
}

// Sanitize filename - remove path traversal and invalid chars
$recording_filename = basename($recording_filename);
$recording_filename = preg_replace('/[^a-zA-Z0-9._\-]/', '-', $recording_filename);

// Validate extension
$ext = strtolower(pathinfo($recording_filename, PATHINFO_EXTENSION));
if (!in_array($ext, ['mp3', 'wav'])) {
    api_error('VALIDATION_ERROR', 'Only MP3 and WAV files are allowed', 'file');
}

// Get recordings path from settings
$database = new database;
$sql = "SELECT default_setting_value FROM v_default_settings
        WHERE default_setting_category = 'switch'
        AND default_setting_subcategory = 'recordings'
        AND default_setting_enabled = 'true' LIMIT 1";
$recordings_dir = $database->select($sql, [], 'column');
if (empty($recordings_dir)) {
    $recordings_dir = '/var/lib/freeswitch/recordings';
}

$recording_path = rtrim($recordings_dir, '/') . '/' . $domain_name;

// Create directory if it doesn't exist
if (!is_dir($recording_path)) {
    if (!mkdir($recording_path, 0770, true)) {
        api_error('SERVER_ERROR', 'Failed to create recordings directory', null, 500);
    }
    // Set ownership to match FusionPBX
    @chown($recording_path, 'www-data');
    @chgrp($recording_path, 'www-data');
}

// Save the file
$full_path = $recording_path . '/' . $recording_filename;
if ($file_contents !== null) {
    if (file_put_contents($full_path, $file_contents) === false) {
        api_error('SERVER_ERROR', 'Failed to save recording file', null, 500);
    }
    @chmod($full_path, 0664);
    @chown($full_path, 'www-data');
    @chgrp($full_path, 'www-data');
}

// Check if recording already exists in DB
$sql = "SELECT recording_uuid FROM v_recordings WHERE domain_uuid = :domain_uuid AND recording_filename = :filename";
$existing_uuid = $database->select($sql, ['domain_uuid' => $domain_uuid, 'filename' => $recording_filename], 'column');

$recording_uuid = !empty($existing_uuid) ? $existing_uuid : uuid();

// Save to database
$array['recordings'][0]['domain_uuid'] = $domain_uuid;
$array['recordings'][0]['recording_uuid'] = $recording_uuid;
$array['recordings'][0]['recording_filename'] = $recording_filename;
$array['recordings'][0]['recording_name'] = $recording_name;
$array['recordings'][0]['recording_description'] = $recording_description;

$p = permissions::new();
if (!empty($existing_uuid)) {
    $p->add('recording_edit', 'temp');
} else {
    $p->add('recording_add', 'temp');
}

$database = new database;
$database->app_name = 'recordings';
$database->app_uuid = '83124285-9428-c498-e498-41f996b3223e';
$database->save($array);

$p->delete('recording_add', 'temp');
$p->delete('recording_edit', 'temp');

api_created([
    'recording_uuid' => $recording_uuid,
    'recording_filename' => $recording_filename,
    'recording_path' => $full_path,
], 'Recording uploaded successfully');