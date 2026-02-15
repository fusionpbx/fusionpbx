<?php
/**
 * Get Call Recording Metadata
 *
 * GET /api/v1/call-recordings/get.php?call_recording_uuid={uuid}
 *
 * Query Parameters:
 * - call_recording_uuid: UUID of the call recording (required)
 *
 * Returns recording metadata including file information
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
    $call_recording_uuid
);

if (!$recording) {
    api_not_found('Call recording');
}

// Check if file exists and add metadata (without exposing path)
if (!empty($recording['call_recording_path']) && !empty($recording['call_recording_name'])) {
    $full_path = $recording['call_recording_path'] . '/' . $recording['call_recording_name'];
    $recording['file_exists'] = file_exists($full_path);

    if ($recording['file_exists']) {
        $recording['file_size'] = filesize($full_path);
    }

    // Remove path from response for security
    unset($recording['call_recording_path']);
}

api_success($recording);
