<?php
require_once dirname(__DIR__) . '/auth.php';
require_once dirname(__DIR__) . '/base.php';
validate_api_key();

$ivr_menu_uuid = get_uuid_from_path();
if (empty($ivr_menu_uuid)) {
    api_error('MISSING_UUID', 'IVR menu UUID is required');
}

$database = new database;

$sql = "SELECT * FROM v_ivr_menus WHERE domain_uuid = :domain_uuid AND ivr_menu_uuid = :ivr_menu_uuid";
$parameters = ['domain_uuid' => $domain_uuid, 'ivr_menu_uuid' => $ivr_menu_uuid];
$ivr_menu = $database->select($sql, $parameters, 'row');

if (empty($ivr_menu)) {
    api_not_found('IVR menu');
}

// Get menu options
$options_sql = "SELECT ivr_menu_option_uuid, ivr_menu_option_digits, ivr_menu_option_action,
                ivr_menu_option_param, ivr_menu_option_order, ivr_menu_option_enabled,
                ivr_menu_option_description
                FROM v_ivr_menu_options
                WHERE domain_uuid = :domain_uuid AND ivr_menu_uuid = :ivr_menu_uuid
                ORDER BY ivr_menu_option_order ASC";
$ivr_menu['options'] = $database->select($options_sql, $parameters, 'all') ?? [];

api_success($ivr_menu);
