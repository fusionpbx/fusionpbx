<?php
require_once __DIR__ . '/../../base.php';
validate_api_key();

// Pagination
$params = get_pagination_params();
$page = $params['page'];
$per_page = $params['per_page'];

// Build query with optional filter by conference_center_uuid
$sql = "SELECT conference_room_uuid, conference_center_uuid, conference_room_name,
        moderator_pin, participant_pin, profile, record, max_members, wait_mod,
        announce, sounds, mute, created, enabled, description
        FROM v_conference_rooms
        WHERE domain_uuid = :domain_uuid";

$count_sql = "SELECT COUNT(*) FROM v_conference_rooms WHERE domain_uuid = :domain_uuid";

$parameters = ['domain_uuid' => $domain_uuid];

// Filter by conference_center_uuid if provided
if (isset($_GET['conference_center_uuid']) && !empty($_GET['conference_center_uuid'])) {
    $conference_center_uuid = $_GET['conference_center_uuid'];
    api_validate_uuid($conference_center_uuid, 'conference_center_uuid');

    $sql .= " AND conference_center_uuid = :conference_center_uuid";
    $count_sql .= " AND conference_center_uuid = :conference_center_uuid";
    $parameters['conference_center_uuid'] = $conference_center_uuid;
}

$sql .= " ORDER BY conference_room_name ASC";

$result = api_paginate($sql, $count_sql, $parameters, $page, $per_page);

api_success($result['items'], null, $result['pagination']);
