<?php
require_once dirname(__DIR__) . '/auth.php';
validate_api_key();

// Pagination parameters
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = isset($_GET['per_page']) ? min(100, max(1, intval($_GET['per_page']))) : 50;
$offset = ($page - 1) * $per_page;

// Build query with message count subquery
$sql = "SELECT v.voicemail_uuid, v.voicemail_id, v.voicemail_mail_to,
        v.voicemail_enabled, v.voicemail_description,
        (SELECT COUNT(*) FROM v_voicemail_messages m
         WHERE m.voicemail_uuid = v.voicemail_uuid
         AND (m.message_status = '' OR m.message_status IS NULL)) as new_message_count
        FROM v_voicemails v
        WHERE v.domain_uuid = :domain_uuid
        ORDER BY v.voicemail_id ASC
        LIMIT :limit OFFSET :offset";

$parameters = [
    'domain_uuid' => $domain_uuid,
    'limit' => $per_page,
    'offset' => $offset
];

$database = new database;
$voicemails = $database->select($sql, $parameters, 'all');

// Get total count for pagination
$count_sql = "SELECT COUNT(*) FROM v_voicemails WHERE domain_uuid = :domain_uuid";
$count_params = ['domain_uuid' => $domain_uuid];
$total = $database->select($count_sql, $count_params, 'column');

$pagination = [
    'page' => $page,
    'per_page' => $per_page,
    'total' => (int)$total,
    'total_pages' => ceil($total / $per_page)
];

api_success($voicemails ?? [], null, $pagination);
