<?php
require_once dirname(__DIR__) . '/auth.php';
require_once dirname(__DIR__, 3) . '/app/extensions/resources/classes/extension.php';
validate_api_key();

$request = get_request_data();
if (empty($request['extension'])) {
    api_error('VALIDATION_ERROR', 'Extension number is required', 'extension');
}

// Check uniqueness
$database = new database;
$sql = "SELECT count(*) FROM v_extensions WHERE domain_uuid = :domain_uuid AND extension = :extension";
$parameters = ['domain_uuid' => $domain_uuid, 'extension' => $request['extension']];
if ($database->select($sql, $parameters, 'column') > 0) {
    api_error('DUPLICATE_ERROR', 'Extension already exists', 'extension');
}

$extension_uuid = uuid();

// Build extension array
$array['extensions'][0]['domain_uuid'] = $domain_uuid;
$array['extensions'][0]['extension_uuid'] = $extension_uuid;
$array['extensions'][0]['extension'] = $request['extension'];
$array['extensions'][0]['password'] = $request['password'] ?? api_generate_password();
$array['extensions'][0]['accountcode'] = $request['accountcode'] ?? '';
$array['extensions'][0]['effective_caller_id_name'] = $request['effective_caller_id_name'] ?? $request['extension'];
$array['extensions'][0]['effective_caller_id_number'] = $request['effective_caller_id_number'] ?? $request['extension'];
$array['extensions'][0]['outbound_caller_id_name'] = $request['outbound_caller_id_name'] ?? '';
$array['extensions'][0]['outbound_caller_id_number'] = $request['outbound_caller_id_number'] ?? '';
$array['extensions'][0]['emergency_caller_id_name'] = $request['emergency_caller_id_name'] ?? '';
$array['extensions'][0]['emergency_caller_id_number'] = $request['emergency_caller_id_number'] ?? '';
$array['extensions'][0]['directory_first_name'] = $request['directory_first_name'] ?? '';
$array['extensions'][0]['directory_last_name'] = $request['directory_last_name'] ?? '';
$array['extensions'][0]['directory_visible'] = $request['directory_visible'] ?? 'true';
$array['extensions'][0]['directory_exten_visible'] = $request['directory_exten_visible'] ?? 'true';
$array['extensions'][0]['max_registrations'] = $request['max_registrations'] ?? '1';
$array['extensions'][0]['limit_max'] = $request['limit_max'] ?? '5';
$array['extensions'][0]['limit_destination'] = $request['limit_destination'] ?? 'error/user_busy';
$array['extensions'][0]['user_context'] = $domain_name;
$array['extensions'][0]['enabled'] = $request['enabled'] ?? 'true';
$array['extensions'][0]['description'] = $request['description'] ?? '';

// Grant permissions
$p = permissions::new();
$p->add('extension_add', 'temp');
$p->add('voicemail_add', 'temp');

// Save extension
$database = new database;
$database->app_name = 'extensions';
$database->app_uuid = 'e68d9571-2566-b51f-2b32-3ec0a3929ce8';
$database->save($array);
unset($array);

// Create voicemail if enabled
if (($request['voicemail_enabled'] ?? 'false') === 'true') {
    $settings = new settings(['database' => new database, 'domain_uuid' => $domain_uuid]);
    $voicemail_uuid = uuid();
    $array['voicemails'][0]['domain_uuid'] = $domain_uuid;
    $array['voicemails'][0]['voicemail_uuid'] = $voicemail_uuid;
    $array['voicemails'][0]['voicemail_id'] = $request['extension'];
    $array['voicemails'][0]['voicemail_password'] = $request['voicemail_password'] ?? generate_numeric_password();
    $array['voicemails'][0]['voicemail_mail_to'] = $request['voicemail_mail_to'] ?? '';
    $array['voicemails'][0]['voicemail_enabled'] = 'true';

    $database = new database;
    $database->app_name = 'voicemails';
    $database->app_uuid = 'b523c2d2-64cd-46f1-9520-ca4b4098e044';
    $database->save($array);
    unset($array);

    // Create voicemail directory (sanitize extension to prevent path traversal)
    $switch_voicemail = $settings->get('switch', 'voicemail', '/var/lib/freeswitch/storage/voicemail');
    $safe_extension = preg_replace('/[^a-zA-Z0-9_-]/', '', $request['extension']);
    $voicemail_dir = $switch_voicemail.'/default/'.$domain_name.'/'.$safe_extension;
    if (!file_exists($voicemail_dir)) {
        @mkdir($voicemail_dir, 0770, true);
    }
}

// Link to user if provided
if (!empty($request['user_uuid'])) {
    $array['extension_users'][0]['extension_user_uuid'] = uuid();
    $array['extension_users'][0]['domain_uuid'] = $domain_uuid;
    $array['extension_users'][0]['extension_uuid'] = $extension_uuid;
    $array['extension_users'][0]['user_uuid'] = $request['user_uuid'];
    $database = new database;
    $database->save($array);
    unset($array);
}

$p->delete('extension_add', 'temp');
$p->delete('voicemail_add', 'temp');

// CRITICAL: Generate FreeSWITCH XML
$ext = new extension;
$ext->domain_uuid = $domain_uuid;
$ext->domain_name = $domain_name;
$ext->xml();

// Clear cache
$cache = new cache;
$cache->delete(gethostname().":directory:".$request['extension']."@".$domain_name);

api_success(['extension_uuid' => $extension_uuid], 'Extension created successfully');
