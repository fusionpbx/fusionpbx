<?php
require_once dirname(__DIR__) . '/auth.php';
validate_api_key();

$voicemail_uuid = get_uuid_from_path();
api_validate_uuid($voicemail_uuid, 'voicemail_uuid');

// Verify voicemail exists
$database = new database;
$sql = "SELECT voicemail_id FROM v_voicemails WHERE domain_uuid = :domain_uuid AND voicemail_uuid = :voicemail_uuid";
$parameters = ['domain_uuid' => $domain_uuid, 'voicemail_uuid' => $voicemail_uuid];
$voicemail = $database->select($sql, $parameters, 'row');

if (empty($voicemail)) {
    api_error('NOT_FOUND', 'Voicemail not found', null, 404);
}

// Pagination parameters
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = isset($_GET['per_page']) ? min(100, max(1, intval($_GET['per_page']))) : 50;
$offset = ($page - 1) * $per_page;

// Optional status filter
$status_filter = '';
$status_param = null;
if (!empty($_GET['status']) && in_array($_GET['status'], ['read', 'unread'])) {
    if ($_GET['status'] === 'unread') {
        $status_filter = 'AND (message_status = \'\' OR message_status IS NULL)';
    } else {
        $status_filter = 'AND message_status != \'\' AND message_status IS NOT NULL';
    }
}

// Get messages
$sql = "SELECT voicemail_message_uuid, created_epoch, caller_id_name, caller_id_number,
        message_length, message_status, message_priority
        FROM v_voicemail_messages
        WHERE voicemail_uuid = :voicemail_uuid $status_filter
        ORDER BY created_epoch DESC
        LIMIT :limit OFFSET :offset";

$parameters = [
    'voicemail_uuid' => $voicemail_uuid,
    'limit' => $per_page,
    'offset' => $offset
];

$messages = $database->select($sql, $parameters, 'all');

// Get total count
$count_sql = "SELECT COUNT(*) FROM v_voicemail_messages WHERE voicemail_uuid = :voicemail_uuid $status_filter";
$count_params = ['voicemail_uuid' => $voicemail_uuid];
$total = $database->select($count_sql, $count_params, 'column');

$pagination = [
    'page' => $page,
    'per_page' => $per_page,
    'total' => (int)$total,
    'total_pages' => ceil($total / $per_page)
];

api_success($messages ?? [], null, $pagination);
