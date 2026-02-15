<?php
/**
 * List Number Translations
 * GET /api/v1/number-translations/
 *
 * Query Parameters:
 * - page: Page number (default: 1)
 * - per_page: Items per page (default: 50, max: 100)
 * - enabled: Filter by enabled status (true/false)
 * - sort: Sort field (default: number_translation_name)
 * - order: Sort order ASC/DESC (default: ASC)
 */

require_once __DIR__ . '/../base.php';

api_require_method('GET');

// Get pagination parameters
$params = get_pagination_params();
$page = $params['page'];
$per_page = $params['per_page'];

// Get sort parameters
$sort_params = get_sort_params(
    'number_translation_name',
    'ASC',
    ['number_translation_name', 'number_translation_enabled', 'number_translation_order']
);

// Build base query
// NOTE: v_number_translations table does not contain domain_uuid.
// Number translations are intentionally global/system-wide resources.
// In multi-tenant environments, consider restricting this endpoint to superadmin users.
$where_conditions = [];
$parameters = [];

// Filter by enabled status
if (isset($_GET['enabled'])) {
    $enabled = filter_var($_GET['enabled'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    if ($enabled !== null) {
        $where_conditions[] = "number_translation_enabled = :enabled";
        $parameters['enabled'] = $enabled ? 'true' : 'false';
    }
}

$where_clause = count($where_conditions) > 0 ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Main query
$sql = "SELECT number_translation_uuid, number_translation_name, number_translation_enabled,
        number_translation_description, number_translation_order
        FROM v_number_translations
        {$where_clause}
        ORDER BY {$sort_params['field']} {$sort_params['order']}";

// Count query
$count_sql = "SELECT COUNT(*) FROM v_number_translations {$where_clause}";

// Execute paginated query
$result = api_paginate($sql, $count_sql, $parameters, $page, $per_page);

api_success($result['items'], null, $result['pagination']);
