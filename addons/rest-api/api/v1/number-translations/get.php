<?php
/**
 * Get Number Translation Details
 * GET /api/v1/number-translations/{number_translation_uuid}
 *
 * Returns translation with nested details array sorted by order
 */

require_once __DIR__ . '/../base.php';

api_require_method('GET');

// Get UUID from path
$uuid = get_uuid_from_path();
$uuid = api_validate_uuid($uuid, 'number_translation_uuid');

$database = new database;

// Get translation record - NOTE: No domain_uuid check (global scope)
$sql = "SELECT number_translation_uuid, number_translation_name, number_translation_enabled,
        number_translation_description, number_translation_order
        FROM v_number_translations
        WHERE number_translation_uuid = :uuid";

$parameters = ['uuid' => $uuid];
$translation = $database->select($sql, $parameters, 'row');

if (!$translation) {
    api_not_found('Number Translation');
}

// Get translation details (rules) sorted by order
$details_sql = "SELECT number_translation_detail_uuid, number_translation_uuid,
        number_translation_regex, number_translation_replacement, number_translation_order
        FROM v_number_translation_details
        WHERE number_translation_uuid = :uuid
        ORDER BY number_translation_order ASC";

$details = $database->select($details_sql, $parameters, 'all');

// Add details array to translation
$translation['details'] = $details ?? [];

api_success($translation);
