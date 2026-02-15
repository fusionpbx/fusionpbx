<?php
require_once dirname(__DIR__) . '/base.php';
validate_api_key();

api_require_method('POST');

// Get request data
$data = get_request_data();

// Validate required fields
$errors = api_validate($data, ['conference_name', 'conference_extension']);
if (!empty($errors)) {
    api_validation_error($errors);
}

// Check if extension already exists in this domain
$database = new database;
$check_sql = "SELECT COUNT(*) FROM v_conferences
              WHERE domain_uuid = :domain_uuid
              AND conference_extension = :extension";
$exists = $database->select($check_sql, [
    'domain_uuid' => $domain_uuid,
    'extension' => $data['conference_extension']
], 'column');

if ($exists > 0) {
    api_conflict('conference_extension', 'Conference extension already exists in this domain');
}

// Generate UUID
$conference_uuid = uuid();

// Build array for save
$array['conferences'][0]['domain_uuid'] = $domain_uuid;
$array['conferences'][0]['conference_uuid'] = $conference_uuid;
$array['conferences'][0]['conference_name'] = $data['conference_name'];
$array['conferences'][0]['conference_extension'] = $data['conference_extension'];
$array['conferences'][0]['conference_pin'] = $data['conference_pin'] ?? null;
$array['conferences'][0]['moderator_pin'] = $data['moderator_pin'] ?? null;
$array['conferences'][0]['wait_mod'] = $data['wait_mod'] ?? 'true';
$array['conferences'][0]['announce_name'] = $data['announce_name'] ?? 'true';
$array['conferences'][0]['announce_count'] = $data['announce_count'] ?? 'true';
$array['conferences'][0]['announce_recording'] = $data['announce_recording'] ?? 'false';
$array['conferences'][0]['mute'] = $data['mute'] ?? 'false';
$array['conferences'][0]['sounds'] = $data['sounds'] ?? 'true';
$array['conferences'][0]['member_type'] = $data['member_type'] ?? 'member';
$array['conferences'][0]['profile'] = $data['profile'] ?? 'default';
$array['conferences'][0]['max_members'] = $data['max_members'] ?? 0;
$array['conferences'][0]['record'] = $data['record'] ?? 'false';
$array['conferences'][0]['enabled'] = $data['enabled'] ?? 'true';
$array['conferences'][0]['description'] = $data['description'] ?? null;

// Grant permissions
$p = permissions::new();
$p->add('conference_add', 'temp');

// Save using database->save()
$database = new database;
$database->app_name = 'conferences';
$database->app_uuid = 'b1b30ec1-60a0-df78-5e3a-a9a4b4d3baf4';
$database->save($array);
unset($array);

// Clean up permissions
$p->delete('conference_add', 'temp');

// Clear dialplan cache
api_clear_dialplan_cache();

// Return created conference
api_created([
    'conference_uuid' => $conference_uuid,
    'conference_name' => $data['conference_name'],
    'conference_extension' => $data['conference_extension']
], 'Conference created successfully');
