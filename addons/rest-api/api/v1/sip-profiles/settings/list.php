<?php
require_once __DIR__ . '/../../base.php';

api_require_method('GET');

// Get sip_profile_uuid from query parameter
$sip_profile_uuid = $_GET['sip_profile_uuid'] ?? null;
api_validate_uuid($sip_profile_uuid, 'sip_profile_uuid');

$database = new database;

// Check if profile exists
$check_sql = "SELECT COUNT(*) FROM v_sip_profiles WHERE sip_profile_uuid = :sip_profile_uuid";
$exists = $database->select($check_sql, ['sip_profile_uuid' => $sip_profile_uuid], 'column');

if (!$exists) {
    api_not_found('SIP Profile');
}

// Pagination
$params = get_pagination_params();
$page = $params['page'];
$per_page = $params['per_page'];

// Filters
$filters = get_filter_params(['sip_profile_setting_enabled']);
$filter_data = api_build_filters($filters, ['sip_profile_setting_enabled']);

// Build query
$sql = "SELECT sip_profile_setting_uuid, sip_profile_uuid, sip_profile_setting_name,
        sip_profile_setting_value, sip_profile_setting_enabled, sip_profile_setting_description
        FROM v_sip_profile_settings
        WHERE sip_profile_uuid = :sip_profile_uuid" . $filter_data['where'] . "
        ORDER BY sip_profile_setting_name ASC";

$count_sql = "SELECT COUNT(*) FROM v_sip_profile_settings
              WHERE sip_profile_uuid = :sip_profile_uuid" . $filter_data['where'];

// Add profile UUID to parameters
$filter_data['parameters']['sip_profile_uuid'] = $sip_profile_uuid;

// Execute query with pagination
$result = api_paginate($sql, $count_sql, $filter_data['parameters'], $page, $per_page);

api_success($result['items'], null, $result['pagination']);
