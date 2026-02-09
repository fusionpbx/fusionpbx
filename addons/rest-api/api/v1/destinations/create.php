<?php
require_once dirname(__DIR__) . '/auth.php';
require_once dirname(__DIR__, 5) . '/app/destinations/resources/classes/destinations.php';
validate_api_key();

$request = get_request_data();

// Validate required fields
if (empty($request['destination_number'])) {
    api_error('VALIDATION_ERROR', 'Destination number is required', 'destination_number');
}

if (empty($request['destination_context'])) {
    api_error('VALIDATION_ERROR', 'Destination context is required', 'destination_context');
}

// Set defaults
$destination_type = $request['destination_type'] ?? 'inbound';
$destination_prefix = $request['destination_prefix'] ?? '';
$destination_enabled = $request['destination_enabled'] ?? 'true';

// Check for duplicates if enabled in settings
$settings = new settings(['database' => new database, 'domain_uuid' => $domain_uuid]);
if ($destination_type == 'inbound' && $settings->get('destinations', 'unique', false)) {
    $database = new database;
    $sql = "SELECT count(*) FROM v_destinations
            WHERE domain_uuid = :domain_uuid
            AND (destination_number = :destination_number
            OR destination_prefix || destination_number = :destination_number)
            AND destination_type = 'inbound'";
    $parameters = [
        'domain_uuid' => $domain_uuid,
        'destination_number' => $destination_prefix . $request['destination_number']
    ];
    $count = $database->select($sql, $parameters, 'column');
    if ($count > 0) {
        api_error('DUPLICATE_ERROR', 'Destination already exists', 'destination_number');
    }
}

// Generate UUIDs
$destination_uuid = uuid();
$dialplan_uuid = uuid();

// Initialize destination object for regex conversion
$destination = new destinations(['database' => new database, 'domain_uuid' => $domain_uuid]);

// Convert destination number to regex
$destination_numbers = [];
if (!empty($destination_prefix)) {
    $destination_numbers['destination_prefix'] = $destination_prefix;
}
$destination_numbers['destination_number'] = $request['destination_number'];
$destination_number_regex = $destination->to_regex($destination_numbers);

// Build destination array
$array['destinations'][0]['domain_uuid'] = $domain_uuid;
$array['destinations'][0]['destination_uuid'] = $destination_uuid;
$array['destinations'][0]['dialplan_uuid'] = $dialplan_uuid;
$array['destinations'][0]['destination_type'] = $destination_type;
$array['destinations'][0]['destination_number'] = $request['destination_number'];
$array['destinations'][0]['destination_prefix'] = $destination_prefix;
$array['destinations'][0]['destination_context'] = $request['destination_context'];
$array['destinations'][0]['destination_enabled'] = $destination_enabled;
$array['destinations'][0]['destination_description'] = $request['destination_description'] ?? '';
$array['destinations'][0]['destination_order'] = $request['destination_order'] ?? '100';

// Build dialplan array
$dialplan_name = !empty($request['dialplan_name']) ? $request['dialplan_name'] : format_phone($request['destination_number']);
$app_uuid = ($destination_type == 'inbound') ? 'c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4' : 'b5242951-686f-448f-8b4e-5031ba0601a4';

$array['dialplans'][0]['domain_uuid'] = $domain_uuid;
$array['dialplans'][0]['dialplan_uuid'] = $dialplan_uuid;
$array['dialplans'][0]['app_uuid'] = $app_uuid;
$array['dialplans'][0]['dialplan_name'] = $dialplan_name;
$array['dialplans'][0]['dialplan_number'] = $request['destination_number'];
$array['dialplans'][0]['dialplan_context'] = $request['destination_context'];
$array['dialplans'][0]['dialplan_continue'] = 'false';
$array['dialplans'][0]['dialplan_order'] = $request['destination_order'] ?? '100';
$array['dialplans'][0]['dialplan_enabled'] = $destination_enabled;
$array['dialplans'][0]['dialplan_description'] = $request['destination_description'] ?? '';

// Build dialplan XML
$dialplan_xml = "<extension name=\"" . xml::sanitize($dialplan_name) . "\" continue=\"false\" uuid=\"" . xml::sanitize($dialplan_uuid) . "\">\n";
$dialplan_xml .= "	<condition field=\"destination_number\" expression=\"" . xml::sanitize($destination_number_regex) . "\">\n";
$dialplan_xml .= "		<action application=\"export\" data=\"call_direction=inbound\" inline=\"true\"/>\n";
$dialplan_xml .= "		<action application=\"set\" data=\"domain_uuid=" . $domain_uuid . "\" inline=\"true\"/>\n";
$dialplan_xml .= "		<action application=\"set\" data=\"domain_name=" . $domain_name . "\" inline=\"true\"/>\n";

// Add destination actions
if (!empty($request['destination_actions']) && is_array($request['destination_actions'])) {
    $dialplan_detail_order = 100;
    $y = 0;

    foreach ($request['destination_actions'] as $action) {
        if (empty($action['destination_app'])) {
            continue;
        }

        $destination_app = $action['destination_app'];
        $destination_data = $action['destination_data'] ?? '';

        // Validate action
        if ($destination->valid($destination_app . ':' . $destination_data)) {
            // Add to XML
            $dialplan_xml .= "		<action application=\"" . xml::sanitize($destination_app) . "\" data=\"" . xml::sanitize($destination_data) . "\"/>\n";

            // Add to dialplan_details array if dialplan_details setting is enabled
            if ($settings->get('destinations', 'dialplan_details', '')) {
                $array['dialplan_details'][$y]['domain_uuid'] = $domain_uuid;
                $array['dialplan_details'][$y]['dialplan_uuid'] = $dialplan_uuid;
                $array['dialplan_details'][$y]['dialplan_detail_uuid'] = uuid();
                $array['dialplan_details'][$y]['dialplan_detail_tag'] = 'action';
                $array['dialplan_details'][$y]['dialplan_detail_type'] = $destination_app;
                $array['dialplan_details'][$y]['dialplan_detail_data'] = $destination_data;
                $array['dialplan_details'][$y]['dialplan_detail_order'] = $dialplan_detail_order;
                $array['dialplan_details'][$y]['dialplan_detail_enabled'] = 'true';
                $dialplan_detail_order += 10;
                $y++;
            }
        }
    }
}

$dialplan_xml .= "	</condition>\n";
$dialplan_xml .= "</extension>\n";

$array['dialplans'][0]['dialplan_xml'] = $dialplan_xml;

// Grant temporary permissions
$p = permissions::new();
$p->add('destination_add', 'temp');
$p->add('dialplan_add', 'temp');
$p->add('dialplan_detail_add', 'temp');

// Save to database
$database = new database;
$database->app_name = 'destinations';
$database->app_uuid = '5ec89622-b19c-3559-64f0-afde802ab139';
$database->save($array);

// Revoke temporary permissions
$p->delete('destination_add', 'temp');
$p->delete('dialplan_add', 'temp');
$p->delete('dialplan_detail_add', 'temp');

// Clear cache
$cache = new cache;
$cache->delete("dialplan:" . $request['destination_context']);

api_success(['destination_uuid' => $destination_uuid], 'Destination created successfully');
