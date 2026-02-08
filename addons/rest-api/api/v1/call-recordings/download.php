<?php
/**
 * Get Call Recording File Information for Download
 *
 * GET /api/v1/call-recordings/download.php?call_recording_uuid={uuid}
 *
 * Query Parameters:
 * - call_recording_uuid: UUID of the call recording (required)
 *
 * Returns file path and metadata. Actual file serving is handled by the web server.
 * This endpoint provides the information needed to construct a download URL or
 * to retrieve the file through other means.
 */

require_once __DIR__ . '/../base.php';

api_require_method('GET');

// Validate UUID parameter
$call_recording_uuid = $_GET['call_recording_uuid'] ?? null;
api_validate_uuid($call_recording_uuid, 'call_recording_uuid');

// Get recording metadata
$recording = api_get_record(
    'view_call_recordings',
    'call_recording_uuid',
    $call_recording_uuid,
    'call_recording_uuid, call_recording_name, call_recording_path, call_recording_date, call_recording_length'
);

if (!$recording) {
    api_not_found('Call recording');
}

// Validate file information
if (empty($recording['call_recording_path']) || empty($recording['call_recording_name'])) {
    api_error('FILE_NOT_FOUND', 'Recording file information is missing', null, 404);
}

// Build full file path
$full_path = $recording['call_recording_path'] . '/' . $recording['call_recording_name'];

// Check if file exists
if (!file_exists($full_path)) {
    api_error('FILE_NOT_FOUND', 'Recording file does not exist on filesystem', null, 404);
}

// Get file information (no path disclosure)
$file_info = [
    'call_recording_uuid' => $recording['call_recording_uuid'],
    'file_name' => $recording['call_recording_name'],
    'file_size' => filesize($full_path),
    'mime_type' => mime_content_type($full_path),
    'recording_date' => $recording['call_recording_date'],
    'length' => $recording['call_recording_length']
];

api_success($file_info, 'File information retrieved successfully');
