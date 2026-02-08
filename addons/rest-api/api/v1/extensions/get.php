<?php
require_once dirname(__DIR__) . '/auth.php';
validate_api_key();

$extension_uuid = get_uuid_from_path();
api_validate_uuid($extension_uuid, 'extension_uuid');

// Get extension details (explicit columns to exclude password)
$sql = "SELECT extension_uuid, extension, number_alias, effective_caller_id_name, effective_caller_id_number,
        outbound_caller_id_name, outbound_caller_id_number, emergency_caller_id_name, emergency_caller_id_number,
        directory_first_name, directory_last_name, directory_visible, directory_exten_visible,
        max_registrations, limit_max, limit_destination, user_context, enabled, description,
        forward_all_destination, forward_all_enabled, forward_busy_destination, forward_busy_enabled,
        forward_no_answer_destination, forward_no_answer_enabled,
        forward_user_not_registered_destination, forward_user_not_registered_enabled,
        follow_me_uuid, do_not_disturb, accountcode
        FROM v_extensions WHERE domain_uuid = :domain_uuid AND extension_uuid = :extension_uuid";
$parameters = [
    'domain_uuid' => $domain_uuid,
    'extension_uuid' => $extension_uuid
];

$database = new database;
$extension = $database->select($sql, $parameters, 'row');

if (empty($extension)) {
    api_error('NOT_FOUND', 'Extension not found', null, 404);
}

// Get related voicemail info
$voicemail_sql = "SELECT voicemail_uuid, voicemail_id, voicemail_password, voicemail_mail_to,
                  voicemail_enabled, voicemail_description
                  FROM v_voicemails
                  WHERE domain_uuid = :domain_uuid
                  AND (voicemail_id = :extension OR voicemail_id = :number_alias)";
$voicemail_params = [
    'domain_uuid' => $domain_uuid,
    'extension' => $extension['extension'],
    'number_alias' => $extension['number_alias'] ?? $extension['extension']
];
$voicemail = $database->select($voicemail_sql, $voicemail_params, 'row');
if (!empty($voicemail)) {
    $extension['voicemail'] = $voicemail;
}

// Get linked user info
$user_sql = "SELECT u.user_uuid, u.username, CONCAT(u.contact_name_given, ' ', u.contact_name_family) as full_name
             FROM v_extension_users eu
             JOIN v_users u ON eu.user_uuid = u.user_uuid
             WHERE eu.extension_uuid = :extension_uuid AND eu.domain_uuid = :domain_uuid";
$user_params = [
    'extension_uuid' => $extension_uuid,
    'domain_uuid' => $domain_uuid
];
$user = $database->select($user_sql, $user_params, 'row');
if (!empty($user)) {
    $extension['user'] = $user;
}

api_success($extension);
