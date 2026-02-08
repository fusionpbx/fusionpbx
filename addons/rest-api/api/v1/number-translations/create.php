<?php
/**
 * Create Number Translation
 * POST /api/v1/number-translations/
 *
 * Request Body:
 * {
 *   "number_translation_name": "Remove country code",
 *   "number_translation_enabled": "true",
 *   "number_translation_description": "Strip +1 from US numbers",
 *   "number_translation_order": 100,
 *   "details": [
 *     {
 *       "number_translation_regex": "^\\+1(\\d{10})$",
 *       "number_translation_replacement": "$1",
 *       "number_translation_order": 1
 *     }
 *   ]
 * }
 */

require_once __DIR__ . '/../base.php';

api_require_method('POST');

// Get request data
$data = get_request_data();

// Validate required fields
$errors = api_validate($data, ['number_translation_name']);
if (!empty($errors)) {
    api_validation_error($errors);
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

// Generate UUID for translation
$number_translation_uuid = uuid();

// Prepare translation record - NOTE: No domain_uuid (global scope)
$array['number_translations'][0]['number_translation_uuid'] = $number_translation_uuid;
$array['number_translations'][0]['number_translation_name'] = $data['number_translation_name'];
$array['number_translations'][0]['number_translation_enabled'] = $data['number_translation_enabled'] ?? 'true';
$array['number_translations'][0]['number_translation_description'] = $data['number_translation_description'] ?? null;
$array['number_translations'][0]['number_translation_order'] = $data['number_translation_order'] ?? 100;

// Add details if provided
if (isset($data['details']) && is_array($data['details'])) {
    foreach ($data['details'] as $index => $detail) {
        $array['number_translation_details'][$index]['number_translation_detail_uuid'] = uuid();
        $array['number_translation_details'][$index]['number_translation_uuid'] = $number_translation_uuid;
        $array['number_translation_details'][$index]['number_translation_regex'] = $detail['number_translation_regex'];
        $array['number_translation_details'][$index]['number_translation_replacement'] = $detail['number_translation_replacement'] ?? '';
        $array['number_translation_details'][$index]['number_translation_order'] = $detail['number_translation_order'] ?? 100;
    }
}

// Add temporary permission
$p = permissions::new();
$p->add('number_translation_add', 'temp');

// Save translation and details
$database = new database;
$database->app_name = 'number_translations';
$database->app_uuid = '5c6f597c-9b51-485a-b8f2-3d67e5a5cf3d';
$database->save($array);
unset($array);

// Remove temporary permission
$p->delete('number_translation_add', 'temp');

// Clear dialplan cache
api_clear_dialplan_cache();

// Return created translation
$result = [
    'number_translation_uuid' => $number_translation_uuid,
    'number_translation_name' => $data['number_translation_name']
];

api_created($result, 'Number Translation created successfully');
