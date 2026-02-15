<?php
require_once dirname(__DIR__) . '/auth.php';
validate_api_key();

$voicemail_uuid = get_uuid_from_path();
api_validate_uuid($voicemail_uuid, 'voicemail_uuid');

// Get voicemail details
$sql = "SELECT voicemail_uuid, voicemail_id, voicemail_password, greeting_id,
        voicemail_alternate_greet_id, voicemail_mail_to, voicemail_sms_to,
        voicemail_transcription_enabled, voicemail_tutorial,
        voicemail_recording_instructions, voicemail_recording_options,
        voicemail_file, voicemail_local_after_email, voicemail_enabled,
        voicemail_description
        FROM v_voicemails
        WHERE domain_uuid = :domain_uuid AND voicemail_uuid = :voicemail_uuid";

$parameters = [
    'domain_uuid' => $domain_uuid,
    'voicemail_uuid' => $voicemail_uuid
];

$database = new database;
$voicemail = $database->select($sql, $parameters, 'row');

if (empty($voicemail)) {
    api_error('NOT_FOUND', 'Voicemail not found', null, 404);
}

// Get voicemail options
$options_sql = "SELECT voicemail_option_uuid, voicemail_option_digits,
                voicemail_option_action, voicemail_option_param
                FROM v_voicemail_options
                WHERE domain_uuid = :domain_uuid AND voicemail_uuid = :voicemail_uuid
                ORDER BY voicemail_option_digits ASC";
$voicemail['voicemail_options'] = $database->select($options_sql, $parameters, 'all') ?? [];

// Get message counts
$count_sql = "SELECT
              COUNT(*) as total_messages,
              SUM(CASE WHEN message_status = '' OR message_status IS NULL THEN 1 ELSE 0 END) as new_messages,
              SUM(CASE WHEN message_status != '' AND message_status IS NOT NULL THEN 1 ELSE 0 END) as saved_messages
              FROM v_voicemail_messages
              WHERE voicemail_uuid = :voicemail_uuid";
$counts = $database->select($count_sql, ['voicemail_uuid' => $voicemail_uuid], 'row');
$voicemail['message_counts'] = $counts;

api_success($voicemail);
