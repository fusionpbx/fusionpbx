<?php
/**
 * Test Number Translation
 * POST /api/v1/number-translations/test
 *
 * Request Body:
 * {
 *   "number_translation_uuid": "uuid-here",
 *   "test_number": "15551234567"
 * }
 *
 * Response:
 * {
 *   "success": true,
 *   "data": {
 *     "original_number": "15551234567",
 *     "translated_number": "5551234567",
 *     "rules_applied": [
 *       {
 *         "order": 1,
 *         "regex": "^1(\\d{10})$",
 *         "replacement": "$1",
 *         "matched": true
 *       }
 *     ]
 *   }
 * }
 */

require_once __DIR__ . '/../base.php';

api_require_method('POST');

// Get request data
$data = get_request_data();

// Validate required fields
$errors = api_validate($data, ['number_translation_uuid', 'test_number']);
if (!empty($errors)) {
    api_validation_error($errors);
}

$uuid = api_validate_uuid($data['number_translation_uuid'], 'number_translation_uuid');
$test_number = $data['test_number'];

$database = new database;

// Verify translation exists and belongs to the authenticated domain
$sql = "SELECT number_translation_uuid, number_translation_name, number_translation_enabled
        FROM v_number_translations
        WHERE number_translation_uuid = :uuid AND domain_uuid = :domain_uuid";

$translation = $database->select($sql, ['uuid' => $uuid, 'domain_uuid' => $domain_uuid], 'row');

if (!$translation) {
    api_not_found('Number Translation');
}

// Get translation rules sorted by order
$rules_sql = "SELECT number_translation_detail_uuid, number_translation_regex,
        number_translation_replacement, number_translation_order
        FROM v_number_translation_details
        WHERE number_translation_uuid = :uuid
        ORDER BY number_translation_order ASC";

$rules = $database->select($rules_sql, ['uuid' => $uuid], 'all');

if (empty($rules)) {
    api_error('NO_RULES', 'No translation rules defined for this translation', null, 400);
}

// Apply rules in order
$current_number = $test_number;
$rules_applied = [];

foreach ($rules as $rule) {
    $regex = $rule['number_translation_regex'];
    $replacement = $rule['number_translation_replacement'];

    // Apply regex replacement
    $new_number = @preg_replace('/' . $regex . '/', $replacement, $current_number);

    // Check if rule matched (number changed)
    $matched = ($new_number !== $current_number && $new_number !== null);

    $rules_applied[] = [
        'order' => (int)$rule['number_translation_order'],
        'regex' => $regex,
        'replacement' => $replacement,
        'matched' => $matched,
        'result' => $matched ? $new_number : $current_number
    ];

    // Update current number if match occurred
    if ($matched) {
        $current_number = $new_number;
    }
}

// Build response
$result = [
    'translation_name' => $translation['number_translation_name'],
    'translation_enabled' => $translation['number_translation_enabled'],
    'original_number' => $test_number,
    'translated_number' => $current_number,
    'number_changed' => ($current_number !== $test_number),
    'rules_applied' => $rules_applied
];

api_success($result);
