<?php
require_once dirname(__DIR__) . '/auth.php';
validate_api_key();

$request = get_request_data();
if (empty($request['voicemail_id'])) {
    api_error('VALIDATION_ERROR', 'Voicemail ID is required', 'voicemail_id');
}

// Validate voicemail_id is numeric
if (!is_numeric($request['voicemail_id'])) {
    api_error('VALIDATION_ERROR', 'Voicemail ID must be numeric', 'voicemail_id');
}

// Check uniqueness
$database = new database;
$sql = "SELECT count(*) FROM v_voicemails WHERE domain_uuid = :domain_uuid AND voicemail_id = :voicemail_id";
$parameters = ['domain_uuid' => $domain_uuid, 'voicemail_id' => $request['voicemail_id']];
if ($database->select($sql, $parameters, 'column') > 0) {
    api_error('DUPLICATE_ERROR', 'Voicemail already exists', 'voicemail_id');
}

$voicemail_uuid = uuid();

$array['voicemails'][0]['domain_uuid'] = $domain_uuid;
$array['voicemails'][0]['voicemail_uuid'] = $voicemail_uuid;
$array['voicemails'][0]['voicemail_id'] = $request['voicemail_id'];
$array['voicemails'][0]['voicemail_password'] = $request['voicemail_password'] ?? generate_numeric_password();
$array['voicemails'][0]['greeting_id'] = $request['greeting_id'] ?? null;
$array['voicemails'][0]['voicemail_alternate_greet_id'] = $request['voicemail_alternate_greet_id'] ?? null;
$array['voicemails'][0]['voicemail_mail_to'] = $request['voicemail_mail_to'] ?? '';
$array['voicemails'][0]['voicemail_sms_to'] = $request['voicemail_sms_to'] ?? '';
$array['voicemails'][0]['voicemail_transcription_enabled'] = $request['voicemail_transcription_enabled'] ?? 'false';
$array['voicemails'][0]['voicemail_tutorial'] = $request['voicemail_tutorial'] ?? 'false';
$array['voicemails'][0]['voicemail_recording_instructions'] = $request['voicemail_recording_instructions'] ?? 'true';
$array['voicemails'][0]['voicemail_recording_options'] = $request['voicemail_recording_options'] ?? 'true';
$array['voicemails'][0]['voicemail_file'] = $request['voicemail_file'] ?? 'attach';
$array['voicemails'][0]['voicemail_local_after_email'] = $request['voicemail_local_after_email'] ?? 'true';
$array['voicemails'][0]['voicemail_enabled'] = $request['voicemail_enabled'] ?? 'true';
$array['voicemails'][0]['voicemail_description'] = $request['voicemail_description'] ?? '';

// Grant permissions
$p = permissions::new();
$p->add('voicemail_add', 'temp');

$database = new database;
$database->app_name = 'voicemails';
$database->app_uuid = 'b523c2d2-64cd-46f1-9520-ca4b4098e044';
$database->save($array);
unset($array);

// Create voicemail directory
$settings = new settings(['database' => new database, 'domain_uuid' => $domain_uuid]);
$switch_voicemail = $settings->get('switch', 'voicemail', '/var/lib/freeswitch/storage/voicemail');
$voicemail_dir = $switch_voicemail.'/default/'.$domain_name.'/'.$request['voicemail_id'];
if (!file_exists($voicemail_dir)) {
    @mkdir($voicemail_dir, 0770, true);
}

// Add voicemail options if provided
if (!empty($request['voicemail_options']) && is_array($request['voicemail_options'])) {
    foreach ($request['voicemail_options'] as $index => $option) {
        if (empty($option['digits']) || empty($option['action'])) {
            continue;
        }
        $array['voicemail_options'][$index]['voicemail_option_uuid'] = uuid();
        $array['voicemail_options'][$index]['domain_uuid'] = $domain_uuid;
        $array['voicemail_options'][$index]['voicemail_uuid'] = $voicemail_uuid;
        $array['voicemail_options'][$index]['voicemail_option_digits'] = $option['digits'];
        $array['voicemail_options'][$index]['voicemail_option_action'] = $option['action'];
        $array['voicemail_options'][$index]['voicemail_option_param'] = $option['param'] ?? '';
        $array['voicemail_options'][$index]['voicemail_option_description'] = $option['description'] ?? '';
    }
    if (!empty($array)) {
        $database = new database;
        $database->app_name = 'voicemail_options';
        $database->app_uuid = 'b523c2d2-64cd-46f1-9520-ca4b4098e044';
        $database->save($array);
        unset($array);
    }
}

// Revoke permissions
$p->delete('voicemail_add', 'temp');

api_success(['voicemail_uuid' => $voicemail_uuid], 'Voicemail created successfully');
