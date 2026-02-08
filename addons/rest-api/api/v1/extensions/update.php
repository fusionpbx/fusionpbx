<?php
require_once dirname(__DIR__) . '/auth.php';
require_once dirname(__DIR__, 3) . '/app/extensions/resources/classes/extension.php';
validate_api_key();

$extension_uuid = get_uuid_from_path();
api_validate_uuid($extension_uuid, 'extension_uuid');

// Check if extension exists
$database = new database;
$sql = "SELECT extension FROM v_extensions WHERE domain_uuid = :domain_uuid AND extension_uuid = :extension_uuid";
$parameters = ['domain_uuid' => $domain_uuid, 'extension_uuid' => $extension_uuid];
$existing = $database->select($sql, $parameters, 'row');

if (empty($existing)) {
    api_error('NOT_FOUND', 'Extension not found', null, 404);
}

$request = get_request_data();

// Build update array
$array['extensions'][0]['extension_uuid'] = $extension_uuid;
$array['extensions'][0]['domain_uuid'] = $domain_uuid;

// Only update provided fields
if (isset($request['extension'])) {
    // Check if new extension number is unique (if changed)
    if ($request['extension'] !== $existing['extension']) {
        $check_sql = "SELECT count(*) FROM v_extensions WHERE domain_uuid = :domain_uuid AND extension = :extension AND extension_uuid != :extension_uuid";
        $check_params = ['domain_uuid' => $domain_uuid, 'extension' => $request['extension'], 'extension_uuid' => $extension_uuid];
        if ($database->select($check_sql, $check_params, 'column') > 0) {
            api_error('DUPLICATE_ERROR', 'Extension already exists', 'extension');
        }
    }
    $array['extensions'][0]['extension'] = $request['extension'];
}

if (isset($request['password'])) $array['extensions'][0]['password'] = $request['password'];
if (isset($request['accountcode'])) $array['extensions'][0]['accountcode'] = $request['accountcode'];
if (isset($request['effective_caller_id_name'])) $array['extensions'][0]['effective_caller_id_name'] = $request['effective_caller_id_name'];
if (isset($request['effective_caller_id_number'])) $array['extensions'][0]['effective_caller_id_number'] = $request['effective_caller_id_number'];
if (isset($request['outbound_caller_id_name'])) $array['extensions'][0]['outbound_caller_id_name'] = $request['outbound_caller_id_name'];
if (isset($request['outbound_caller_id_number'])) $array['extensions'][0]['outbound_caller_id_number'] = $request['outbound_caller_id_number'];
if (isset($request['emergency_caller_id_name'])) $array['extensions'][0]['emergency_caller_id_name'] = $request['emergency_caller_id_name'];
if (isset($request['emergency_caller_id_number'])) $array['extensions'][0]['emergency_caller_id_number'] = $request['emergency_caller_id_number'];
if (isset($request['directory_first_name'])) $array['extensions'][0]['directory_first_name'] = $request['directory_first_name'];
if (isset($request['directory_last_name'])) $array['extensions'][0]['directory_last_name'] = $request['directory_last_name'];
if (isset($request['directory_visible'])) $array['extensions'][0]['directory_visible'] = $request['directory_visible'];
if (isset($request['directory_exten_visible'])) $array['extensions'][0]['directory_exten_visible'] = $request['directory_exten_visible'];
if (isset($request['max_registrations'])) $array['extensions'][0]['max_registrations'] = $request['max_registrations'];
if (isset($request['limit_max'])) $array['extensions'][0]['limit_max'] = $request['limit_max'];
if (isset($request['limit_destination'])) $array['extensions'][0]['limit_destination'] = $request['limit_destination'];
if (isset($request['enabled'])) $array['extensions'][0]['enabled'] = $request['enabled'];
if (isset($request['description'])) $array['extensions'][0]['description'] = $request['description'];

// Grant permissions
$p = permissions::new();
$p->add('extension_edit', 'temp');

// Save extension
$database = new database;
$database->app_name = 'extensions';
$database->app_uuid = 'e68d9571-2566-b51f-2b32-3ec0a3929ce8';
$database->save($array);
unset($array);

// Update voicemail if requested
if (isset($request['voicemail_password']) || isset($request['voicemail_mail_to']) || isset($request['voicemail_enabled'])) {
    $extension_number = $request['extension'] ?? $existing['extension'];
    $voicemail_sql = "SELECT voicemail_uuid FROM v_voicemails WHERE domain_uuid = :domain_uuid AND voicemail_id = :voicemail_id";
    $voicemail_params = ['domain_uuid' => $domain_uuid, 'voicemail_id' => $extension_number];
    $voicemail_uuid = $database->select($voicemail_sql, $voicemail_params, 'column');

    if (!empty($voicemail_uuid)) {
        $p->add('voicemail_edit', 'temp');
        $vm_array['voicemails'][0]['voicemail_uuid'] = $voicemail_uuid;
        $vm_array['voicemails'][0]['domain_uuid'] = $domain_uuid;
        if (isset($request['voicemail_password'])) $vm_array['voicemails'][0]['voicemail_password'] = $request['voicemail_password'];
        if (isset($request['voicemail_mail_to'])) $vm_array['voicemails'][0]['voicemail_mail_to'] = $request['voicemail_mail_to'];
        if (isset($request['voicemail_enabled'])) $vm_array['voicemails'][0]['voicemail_enabled'] = $request['voicemail_enabled'];

        $database->app_name = 'voicemails';
        $database->app_uuid = 'b523c2d2-64cd-46f1-9520-ca4b4098e044';
        $database->save($vm_array);
        $p->delete('voicemail_edit', 'temp');
    }
}

$p->delete('extension_edit', 'temp');

// Regenerate FreeSWITCH XML
$ext = new extension;
$ext->domain_uuid = $domain_uuid;
$ext->domain_name = $domain_name;
$ext->xml();

// Clear cache
$extension_number = $request['extension'] ?? $existing['extension'];
$cache = new cache;
$cache->delete(gethostname().":directory:".$extension_number."@".$domain_name);
if (isset($request['extension']) && $request['extension'] !== $existing['extension']) {
    // Clear old extension cache too
    $cache->delete(gethostname().":directory:".$existing['extension']."@".$domain_name);
}

api_success(['extension_uuid' => $extension_uuid], 'Extension updated successfully');
