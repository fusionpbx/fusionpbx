<?php
/**
 * Update Number Translation
 * PUT /api/v1/number-translations/{number_translation_uuid}
 *
 * Request Body:
 * {
 *   "number_translation_name": "Updated name",
 *   "number_translation_enabled": "false",
 *   "number_translation_description": "Updated description",
 *   "number_translation_order": 200,
 *   "details": [
 *     {
 *       "number_translation_detail_uuid": "existing-uuid-to-update",
 *       "number_translation_regex": "^\\+1(\\d{10})$",
 *       "number_translation_replacement": "$1",
 *       "number_translation_order": 1
 *     },
 *     {
 *       "number_translation_regex": "^(\\d{7})$",
 *       "number_translation_replacement": "555$1",
 *       "number_translation_order": 2
 *     }
 *   ]
 * }
 *
 * Details handling:
 * - If detail has number_translation_detail_uuid: Update existing
 * - If detail has no UUID: Create new
 * - Existing details not in array: Delete
 */

require_once __DIR__ . '/../base.php';

api_require_method('PUT');

// Get UUID from path
$uuid = get_uuid_from_path();
$uuid = api_validate_uuid($uuid, 'number_translation_uuid');

// Get request data
$data = get_request_data();

$database = new database;

// Verify translation exists - NOTE: No domain_uuid check (global scope)
$sql = "SELECT COUNT(*) FROM v_number_translations WHERE number_translation_uuid = :uuid";
$exists = $database->select($sql, ['uuid' => $uuid], 'column');

if (!$exists) {
    api_not_found('Number Translation');
}

// Validate regex patterns if details provided
if (isset($data['details']) && is_array($data['details'])) {
    foreach ($data['details'] as $index => $detail) {
        if (empty($detail['number_translation_regex'])) {
            api_error('VALIDATION_ERROR', "Regex is required for detail at index {$index}", 'details', 400);
        }

        // Validate regex pattern
        $regex = $detail['number_translation_regex'];
        if (@preg_match('/' . $regex . '/', '') === false) {
            api_error('VALIDATION_ERROR', "Invalid regex pattern at index {$index}: {$regex}", 'details', 400);
        }
    }
}

// Update translation record
$translation_data = [];
if (isset($data['number_translation_name'])) {
    $translation_data['number_translation_name'] = $data['number_translation_name'];
}
if (isset($data['number_translation_enabled'])) {
    $translation_data['number_translation_enabled'] = $data['number_translation_enabled'];
}
if (isset($data['number_translation_description'])) {
    $translation_data['number_translation_description'] = $data['number_translation_description'];
}
if (isset($data['number_translation_order'])) {
    $translation_data['number_translation_order'] = $data['number_translation_order'];
}

if (!empty($translation_data)) {
    $database->app_name = 'api';
    $database->app_uuid = 'fd29e39c-c566-11e5-8ff6-00000000000a';
    $database->update('v_number_translations', $translation_data, 'number_translation_uuid', $uuid);
}

// Handle details if provided
if (isset($data['details']) && is_array($data['details'])) {
    // Get existing detail UUIDs
    $existing_sql = "SELECT number_translation_detail_uuid FROM v_number_translation_details
                     WHERE number_translation_uuid = :uuid";
    $existing_details = $database->select($existing_sql, ['uuid' => $uuid], 'all');
    $existing_uuids = array_column($existing_details, 'number_translation_detail_uuid');

    $processed_uuids = [];

    // Process each detail in request
    foreach ($data['details'] as $detail) {
        if (isset($detail['number_translation_detail_uuid'])) {
            // Update existing detail
            $detail_uuid = $detail['number_translation_detail_uuid'];
            $processed_uuids[] = $detail_uuid;

            $detail_data = [
                'number_translation_regex' => $detail['number_translation_regex'],
                'number_translation_replacement' => $detail['number_translation_replacement'] ?? '',
                'number_translation_order' => $detail['number_translation_order'] ?? 100
            ];

            $database->update('v_number_translation_details', $detail_data, 'number_translation_detail_uuid', $detail_uuid);
        } else {
            // Create new detail
            $detail_uuid = uuid();
            $processed_uuids[] = $detail_uuid;

            $detail_data = [
                'number_translation_detail_uuid' => $detail_uuid,
                'number_translation_uuid' => $uuid,
                'number_translation_regex' => $detail['number_translation_regex'],
                'number_translation_replacement' => $detail['number_translation_replacement'] ?? '',
                'number_translation_order' => $detail['number_translation_order'] ?? 100
            ];

            $database->insert('v_number_translation_details', $detail_data);
        }
    }

    // Delete details not in the request
    $to_delete = array_diff($existing_uuids, $processed_uuids);
    foreach ($to_delete as $delete_uuid) {
        $database->delete('v_number_translation_details', 'number_translation_detail_uuid', $delete_uuid);
    }
}

// Clear dialplan cache
api_clear_dialplan_cache();

api_success(['number_translation_uuid' => $uuid], 'Number Translation updated successfully');
