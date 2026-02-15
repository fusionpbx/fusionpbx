<?php
/**
 * Request Call Recording Transcription
 *
 * POST /api/v1/call-recordings/transcribe.php
 *
 * Request Body (JSON):
 * {
 *   "call_recording_uuid": "uuid-here",  // Required: Recording UUID
 *   "language": "en-US",                 // Optional: Language code (default: en-US)
 *   "priority": "normal"                 // Optional: normal/high (default: normal)
 * }
 *
 * This is a placeholder endpoint that marks a recording for transcription.
 * Actual transcription integration (e.g., with speech-to-text services)
 * should be implemented separately based on the chosen provider.
 */

require_once __DIR__ . '/../base.php';

api_require_method('POST');

// Get request data
$data = get_request_data();

// Validate required fields
$errors = api_validate($data, ['call_recording_uuid']);
if (!empty($errors)) {
    api_validation_error($errors);
}

// Validate UUID
$call_recording_uuid = api_validate_uuid($data['call_recording_uuid'], 'call_recording_uuid');

// Validate optional parameters
$language = $data['language'] ?? 'en-US';
$priority = $data['priority'] ?? 'normal';

if (!in_array($priority, ['normal', 'high'])) {
    api_error('VALIDATION_ERROR', 'Invalid priority. Must be "normal" or "high"', 'priority', 400);
}

// Verify recording exists and get metadata
$recording = api_get_record(
    'view_call_recordings',
    'call_recording_uuid',
    $call_recording_uuid,
    'call_recording_uuid, call_recording_name, call_recording_path, call_recording_length'
);

if (!$recording) {
    api_not_found('Call recording');
}

// Verify file exists
$full_path = null;
if (!empty($recording['call_recording_path']) && !empty($recording['call_recording_name'])) {
    $full_path = $recording['call_recording_path'] . '/' . $recording['call_recording_name'];

    if (!file_exists($full_path)) {
        api_error('FILE_NOT_FOUND', 'Recording file does not exist on filesystem', null, 404);
    }
}

// TODO: Implement actual transcription logic
// This could involve:
// 1. Creating a queue entry in a transcription queue table
// 2. Calling an external API (e.g., Google Speech-to-Text, AWS Transcribe, Azure Speech)
// 3. Storing transcription status and results in a dedicated table
// 4. Implementing webhooks for async transcription completion

// For now, we'll create a placeholder response indicating the request was received
$transcription_request = [
    'call_recording_uuid' => $call_recording_uuid,
    'status' => 'queued',
    'language' => $language,
    'priority' => $priority,
    'requested_at' => date('Y-m-d H:i:s'),
    'message' => 'Transcription request queued successfully. Implementation pending.'
];

// Optional: Log the transcription request for future processing
api_log('transcription_requested', 'call_recording', $call_recording_uuid, [
    'language' => $language,
    'priority' => $priority,
    'file_path' => $full_path
]);

api_success($transcription_request, 'Transcription request received and queued');
