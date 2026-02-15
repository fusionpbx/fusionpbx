<?php
/**
 * Download Call Recording File
 *
 * GET /api/v1/call-recordings/download.php?call_recording_uuid={uuid}
 *
 * Query Parameters:
 * - call_recording_uuid: UUID of the call recording (required)
 *
 * Streams the actual recording file as a binary download.
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
    'call_recording_uuid, call_recording_name, call_recording_path'
);

if (!$recording) {
    api_not_found('Call recording');
}

// Validate file information
if (empty($recording['call_recording_path']) || empty($recording['call_recording_name'])) {
    api_error('FILE_NOT_FOUND', 'Recording file information is missing', null, 404);
}

// Sanitize file name to prevent path traversal
$file_name = basename($recording['call_recording_name']);
$full_path = realpath($recording['call_recording_path'] . '/' . $file_name);

// Verify realpath resolved and file exists
if ($full_path === false || !file_exists($full_path)) {
    api_error('FILE_NOT_FOUND', 'Recording file does not exist on filesystem', null, 404);
}

// Verify the resolved path is still within the recording directory
if (strpos($full_path, realpath($recording['call_recording_path'])) !== 0) {
    api_error('FORBIDDEN', 'Invalid file path', null, 403);
}

// Stream the file
$file_size = filesize($full_path);
$mime_type = mime_content_type($full_path) ?: 'application/octet-stream';

header('Content-Type: ' . $mime_type);
header('Content-Length: ' . $file_size);
header('Content-Disposition: attachment; filename="' . $file_name . '"');
header('Cache-Control: no-cache, no-store, must-revalidate');

// Flush output buffers to avoid memory issues with large files
while (ob_get_level()) {
    ob_end_clean();
}

readfile($full_path);
exit;
