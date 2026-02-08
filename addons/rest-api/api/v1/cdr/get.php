<?php
require_once dirname(__DIR__) . '/auth.php';
validate_api_key();

$uuid = get_uuid_from_path();
if (empty($uuid) || !is_uuid($uuid)) {
    api_error('VALIDATION_ERROR', 'Invalid CDR UUID');
}

$database = new database;
$sql = "SELECT * FROM v_xml_cdr WHERE domain_uuid = :domain_uuid AND xml_cdr_uuid = :uuid";
$parameters = ['domain_uuid' => $domain_uuid, 'uuid' => $uuid];
$record = $database->select($sql, $parameters, 'row');

if (empty($record)) {
    api_error('NOT_FOUND', 'CDR record not found', null, 404);
}

// Add recording URL if exists
if (!empty($record['record_path']) && !empty($record['record_name'])) {
    $record['recording_file'] = $record['record_path'] . '/' . $record['record_name'];
}

api_success($record);
