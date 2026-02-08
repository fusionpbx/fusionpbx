<?php
/**
 * Delete Number Translation
 * DELETE /api/v1/number-translations/{number_translation_uuid}
 *
 * Deletes translation and all associated rules (cascade)
 */

require_once __DIR__ . '/../base.php';

api_require_method('DELETE');

// Get UUID from path
$uuid = get_uuid_from_path();
$uuid = api_validate_uuid($uuid, 'number_translation_uuid');

$database = new database;

// Verify translation exists - NOTE: No domain_uuid check (global scope)
$sql = "SELECT COUNT(*) FROM v_number_translations WHERE number_translation_uuid = :uuid";
$exists = $database->select($sql, ['uuid' => $uuid], 'column');

if (!$exists) {
    api_not_found('Number Translation');
}

$database->app_name = 'api';
$database->app_uuid = 'fd29e39c-c566-11e5-8ff6-00000000000a';

// Delete all associated details first
$database->delete('v_number_translation_details', 'number_translation_uuid', $uuid);

// Delete translation
$database->delete('v_number_translations', 'number_translation_uuid', $uuid);

// Clear dialplan cache
api_clear_dialplan_cache();

api_no_content();
