<?php
/**
 * Delete Call Recording
 *
 * DELETE /api/v1/call-recordings/delete.php?call_recording_uuid={uuid}
 *
 * Query Parameters:
 * - call_recording_uuid: UUID of the call recording (required)
 *
 * Deletes the recording from the database and removes the file from the filesystem
 */

require_once __DIR__ . '/../base.php';

api_require_method('DELETE');

// Validate UUID parameter
$call_recording_uuid = $_GET['call_recording_uuid'] ?? null;
api_validate_uuid($call_recording_uuid, 'call_recording_uuid');

// Get recording metadata before deletion
$recording = api_get_record(
    'view_call_recordings',
    'call_recording_uuid',
    $call_recording_uuid,
    'call_recording_uuid, call_recording_name, call_recording_path'
);

if (!$recording) {
    api_not_found('Call recording');
}

// Build full file path
$full_path = null;
if (!empty($recording['call_recording_path']) && !empty($recording['call_recording_name'])) {
    $full_path = $recording['call_recording_path'] . '/' . $recording['call_recording_name'];
}

// Begin transaction
$database = new database;
$database->execute("BEGIN");

try {
    // Delete from database
    $sql = "DELETE FROM view_call_recordings
            WHERE call_recording_uuid = :call_recording_uuid
            AND domain_uuid = :domain_uuid";

    $parameters = [
        'call_recording_uuid' => $call_recording_uuid,
        'domain_uuid' => $domain_uuid
    ];

    $database->execute($sql, $parameters);

    // Delete file from filesystem if it exists
    $file_deleted = false;
    $file_error = null;

    if ($full_path && file_exists($full_path)) {
        if (!@unlink($full_path)) {
            // File deletion failed, but we'll log it and continue
            // The database record is still deleted
            $file_error = "Failed to delete file: {$full_path}";
            error_log("Call Recording API: " . $file_error);
        } else {
            $file_deleted = true;
        }
    }

    // Commit transaction
    $database->execute("COMMIT");

    // Return appropriate response
    if ($file_error) {
        api_success(
            ['file_deleted' => false, 'warning' => $file_error],
            'Recording deleted from database, but file could not be removed from filesystem'
        );
    } else {
        api_success(
            ['file_deleted' => $file_deleted],
            'Recording deleted successfully'
        );
    }

} catch (Exception $e) {
    // Rollback transaction on error
    $database->execute("ROLLBACK");
    api_error('DELETE_ERROR', 'Failed to delete recording: ' . $e->getMessage(), null, 500);
}
