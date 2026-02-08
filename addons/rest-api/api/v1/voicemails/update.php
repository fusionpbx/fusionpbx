<?php
require_once dirname(__DIR__) . '/auth.php';
validate_api_key();

$voicemail_uuid = get_uuid_from_path();
api_validate_uuid($voicemail_uuid, 'voicemail_uuid');

// Verify voicemail exists
$database = new database;
$sql = "SELECT voicemail_id FROM v_voicemails WHERE domain_uuid = :domain_uuid AND voicemail_uuid = :voicemail_uuid";
$parameters = ['domain_uuid' => $domain_uuid, 'voicemail_uuid' => $voicemail_uuid];
$existing = $database->select($sql, $parameters, 'row');

if (empty($existing)) {
    api_error('NOT_FOUND', 'Voicemail not found', null, 404);
}

$request = get_request_data();
$old_voicemail_id = $existing['voicemail_id'];

// Build update array
$array['voicemails'][0]['domain_uuid'] = $domain_uuid;
$array['voicemails'][0]['voicemail_uuid'] = $voicemail_uuid;

if (isset($request['voicemail_id'])) {
    // Validate voicemail_id is numeric
    if (!is_numeric($request['voicemail_id'])) {
        api_error('VALIDATION_ERROR', 'Voicemail ID must be numeric', 'voicemail_id');
    }
    // Check uniqueness if changing voicemail_id
    if ($request['voicemail_id'] != $old_voicemail_id) {
        $sql = "SELECT count(*) FROM v_voicemails WHERE domain_uuid = :domain_uuid AND voicemail_id = :voicemail_id";
        $parameters = ['domain_uuid' => $domain_uuid, 'voicemail_id' => $request['voicemail_id']];
        if ($database->select($sql, $parameters, 'column') > 0) {
            api_error('DUPLICATE_ERROR', 'Voicemail ID already exists', 'voicemail_id');
        }
    }
    $array['voicemails'][0]['voicemail_id'] = $request['voicemail_id'];
}

if (isset($request['voicemail_password'])) {
    $array['voicemails'][0]['voicemail_password'] = $request['voicemail_password'];
}
if (isset($request['greeting_id'])) {
    $array['voicemails'][0]['greeting_id'] = $request['greeting_id'] != '' ? $request['greeting_id'] : null;
}
if (isset($request['voicemail_alternate_greet_id'])) {
    $array['voicemails'][0]['voicemail_alternate_greet_id'] = $request['voicemail_alternate_greet_id'] != '' ? $request['voicemail_alternate_greet_id'] : null;
}
if (isset($request['voicemail_mail_to'])) {
    $array['voicemails'][0]['voicemail_mail_to'] = str_replace(" ", "", $request['voicemail_mail_to']);
}
if (isset($request['voicemail_sms_to'])) {
    $array['voicemails'][0]['voicemail_sms_to'] = $request['voicemail_sms_to'];
}
if (isset($request['voicemail_transcription_enabled'])) {
    $array['voicemails'][0]['voicemail_transcription_enabled'] = $request['voicemail_transcription_enabled'];
}
if (isset($request['voicemail_tutorial'])) {
    $array['voicemails'][0]['voicemail_tutorial'] = $request['voicemail_tutorial'];
}
if (isset($request['voicemail_recording_instructions'])) {
    $array['voicemails'][0]['voicemail_recording_instructions'] = $request['voicemail_recording_instructions'];
}
if (isset($request['voicemail_recording_options'])) {
    $array['voicemails'][0]['voicemail_recording_options'] = $request['voicemail_recording_options'];
}
if (isset($request['voicemail_file'])) {
    $array['voicemails'][0]['voicemail_file'] = $request['voicemail_file'];
}
if (isset($request['voicemail_local_after_email'])) {
    $array['voicemails'][0]['voicemail_local_after_email'] = $request['voicemail_local_after_email'];
}
if (isset($request['voicemail_enabled'])) {
    $array['voicemails'][0]['voicemail_enabled'] = $request['voicemail_enabled'];
}
if (isset($request['voicemail_description'])) {
    $array['voicemails'][0]['voicemail_description'] = $request['voicemail_description'];
}

$database = new database;
$database->app_name = 'voicemails';
$database->app_uuid = 'b523c2d2-64cd-46f1-9520-ca4b4098e044';
$database->save($array);
unset($array);

// Handle voicemail directory rename if voicemail_id changed
if (isset($request['voicemail_id']) && $request['voicemail_id'] != $old_voicemail_id) {
    $settings = new settings(['database' => new database, 'domain_uuid' => $domain_uuid]);
    $switch_voicemail = $settings->get('switch', 'voicemail', '/var/lib/freeswitch/storage/voicemail');
    $old_dir = $switch_voicemail.'/default/'.$domain_name.'/'.$old_voicemail_id;
    $new_dir = $switch_voicemail.'/default/'.$domain_name.'/'.$request['voicemail_id'];

    if (file_exists($old_dir) && !file_exists($new_dir)) {
        @rename($old_dir, $new_dir);
    } else if (!file_exists($new_dir)) {
        @mkdir($new_dir, 0770, true);
    }
}

// Update voicemail options if provided
if (isset($request['voicemail_options'])) {
    // Delete existing options
    $sql = "DELETE FROM v_voicemail_options WHERE domain_uuid = :domain_uuid AND voicemail_uuid = :voicemail_uuid";
    $parameters = ['domain_uuid' => $domain_uuid, 'voicemail_uuid' => $voicemail_uuid];
    $database->execute($sql, $parameters);

    // Add new options
    if (is_array($request['voicemail_options']) && !empty($request['voicemail_options'])) {
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
}

api_success(['voicemail_uuid' => $voicemail_uuid], 'Voicemail updated successfully');
