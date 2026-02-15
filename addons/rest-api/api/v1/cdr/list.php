<?php
require_once dirname(__DIR__) . '/auth.php';
validate_api_key();

$page = intval($_GET['page'] ?? 1);
$per_page = min(intval($_GET['per_page'] ?? $_GET['page_size'] ?? 50), 100);
$offset = ($page - 1) * $per_page;

// Build WHERE clause
$where = "WHERE domain_uuid = :domain_uuid";
$parameters = ['domain_uuid' => $domain_uuid];

// Date filters
if (!empty($_GET['start_date'])) {
    $where .= " AND start_stamp >= :start_date";
    $parameters['start_date'] = $_GET['start_date'] . ' 00:00:00';
}
if (!empty($_GET['end_date'])) {
    $where .= " AND start_stamp <= :end_date";
    $parameters['end_date'] = $_GET['end_date'] . ' 23:59:59';
}

// Direction filter
if (!empty($_GET['direction']) && in_array($_GET['direction'], ['inbound', 'outbound', 'local'])) {
    $where .= " AND direction = :direction";
    $parameters['direction'] = $_GET['direction'];
}

// Extension filter
if (!empty($_GET['extension'])) {
    $where .= " AND (extension_uuid IN (SELECT extension_uuid FROM v_extensions WHERE extension = :ext)
                OR caller_id_number = :ext OR destination_number = :ext)";
    $parameters['ext'] = $_GET['extension'];
}

// Caller ID filter
if (!empty($_GET['caller_id'])) {
    $where .= " AND caller_id_number LIKE :caller_id";
    $parameters['caller_id'] = '%' . $_GET['caller_id'] . '%';
}

// Count total
$database = new database;
$sql = "SELECT count(*) FROM v_xml_cdr " . $where;
$total = $database->select($sql, $parameters, 'column');

// Get records
$sql = "SELECT xml_cdr_uuid, direction, caller_id_name, caller_id_number,
               caller_destination, destination_number, start_stamp, answer_stamp,
               end_stamp, duration, billsec, hangup_cause, record_path, record_name
        FROM v_xml_cdr " . $where . "
        ORDER BY start_stamp DESC LIMIT :limit OFFSET :offset";
$parameters['limit'] = $per_page;
$parameters['offset'] = $offset;
$database = new database;
$records = $database->select($sql, $parameters, 'all');

// Format records (no path disclosure)
foreach ($records as &$record) {
    // Only return filename, not full path
    $record['recording_file'] = $record['record_name'] ?? null;
    unset($record['record_path'], $record['record_name']);
}

api_success($records, null, [
    'page' => $page, 'per_page' => $per_page,
    'total' => intval($total), 'total_pages' => ceil($total / $per_page)
]);
