<?php
require_once dirname(__DIR__) . '/auth.php';
require_once dirname(__DIR__, 3) . '/app/destinations/resources/classes/destinations.php';
validate_api_key();

$destination_uuid = get_uuid_from_path();
if (empty($destination_uuid)) {
    api_error('MISSING_UUID', 'Destination UUID is required');
}

$request = get_request_data();

// Get existing destination
$database = new database;
$sql = "SELECT * FROM v_destinations
        WHERE domain_uuid = :domain_uuid
        AND destination_uuid = :destination_uuid";
$parameters = [
    'domain_uuid' => $domain_uuid,
    'destination_uuid' => $destination_uuid
];
$existing = $database->select($sql, $parameters, 'row');

if (empty($existing)) {
    api_error('NOT_FOUND', 'Destination not found', null, 404);
}

// Initialize destination object
$destination = new destinations(['database' => $database, 'domain_uuid' => $domain_uuid]);
$settings = new settings(['database' => $database, 'domain_uuid' => $domain_uuid]);

// Build update array
$array['destinations'][0]['domain_uuid'] = $domain_uuid;
$array['destinations'][0]['destination_uuid'] = $destination_uuid;
$array['destinations'][0]['dialplan_uuid'] = $existing['dialplan_uuid'];

// Update fields if provided
if (isset($request['destination_number'])) {
    if (empty($request['destination_number'])) {
        api_error('VALIDATION_ERROR', 'Destination number cannot be empty', 'destination_number');
    }
    $array['destinations'][0]['destination_number'] = $request['destination_number'];
    $destination_number = $request['destination_number'];
} else {
    $destination_number = $existing['destination_number'];
}

if (isset($request['destination_prefix'])) {
    $array['destinations'][0]['destination_prefix'] = $request['destination_prefix'];
    $destination_prefix = $request['destination_prefix'];
} else {
    $destination_prefix = $existing['destination_prefix'] ?? '';
}

if (isset($request['destination_context'])) {
    if (empty($request['destination_context'])) {
        api_error('VALIDATION_ERROR', 'Destination context cannot be empty', 'destination_context');
    }
    $array['destinations'][0]['destination_context'] = $request['destination_context'];
    $destination_context = $request['destination_context'];
} else {
    $destination_context = $existing['destination_context'];
}

if (isset($request['destination_enabled'])) {
    $array['destinations'][0]['destination_enabled'] = $request['destination_enabled'];
    $destination_enabled = $request['destination_enabled'];
} else {
    $destination_enabled = $existing['destination_enabled'];
}

if (isset($request['destination_description'])) {
    $array['destinations'][0]['destination_description'] = $request['destination_description'];
}

if (isset($request['destination_order'])) {
    $array['destinations'][0]['destination_order'] = $request['destination_order'];
    $destination_order = $request['destination_order'];
} else {
    $destination_order = $existing['destination_order'] ?? '100';
}

$destination_type = $existing['destination_type'];

// Convert destination number to regex
$destination_numbers = [];
if (!empty($destination_prefix)) {
    $destination_numbers['destination_prefix'] = $destination_prefix;
}
$destination_numbers['destination_number'] = $destination_number;
$destination_number_regex = $destination->to_regex($destination_numbers);

// Update dialplan if dialplan_uuid exists
if (!empty($existing['dialplan_uuid'])) {
    $dialplan_uuid = $existing['dialplan_uuid'];
    $dialplan_name = !empty($request['dialplan_name']) ? $request['dialplan_name'] : format_phone($destination_number);

    $array['dialplans'][0]['domain_uuid'] = $domain_uuid;
    $array['dialplans'][0]['dialplan_uuid'] = $dialplan_uuid;
    $array['dialplans'][0]['dialplan_name'] = $dialplan_name;
    $array['dialplans'][0]['dialplan_number'] = $destination_number;
    $array['dialplans'][0]['dialplan_context'] = $destination_context;
    $array['dialplans'][0]['dialplan_order'] = $destination_order;
    $array['dialplans'][0]['dialplan_enabled'] = $destination_enabled;

    if (isset($request['destination_description'])) {
        $array['dialplans'][0]['dialplan_description'] = $request['destination_description'];
    }

    // Build dialplan XML
    $dialplan_xml = "<extension name=\"" . xml::sanitize($dialplan_name) . "\" continue=\"false\" uuid=\"" . xml::sanitize($dialplan_uuid) . "\">\n";
    $dialplan_xml .= "	<condition field=\"destination_number\" expression=\"" . xml::sanitize($destination_number_regex) . "\">\n";
    $dialplan_xml .= "		<action application=\"export\" data=\"call_direction=inbound\" inline=\"true\"/>\n";
    $dialplan_xml .= "		<action application=\"set\" data=\"domain_uuid=" . $domain_uuid . "\" inline=\"true\"/>\n";
    $dialplan_xml .= "		<action application=\"set\" data=\"domain_name=" . $domain_name . "\" inline=\"true\"/>\n";

    // Handle destination actions if provided
    if (isset($request['destination_actions']) && is_array($request['destination_actions'])) {
        // Delete existing dialplan details (actions)
        $delete_sql = "DELETE FROM v_dialplan_details
                       WHERE dialplan_uuid = :dialplan_uuid
                       AND dialplan_detail_tag = 'action'";
        $database->execute($delete_sql, ['dialplan_uuid' => $dialplan_uuid]);

        // Add new actions
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
    } else {
        // Keep existing actions
        $actions_sql = "SELECT dialplan_detail_type, dialplan_detail_data
                        FROM v_dialplan_details
                        WHERE dialplan_uuid = :dialplan_uuid
                        AND dialplan_detail_tag = 'action'
                        ORDER BY dialplan_detail_order ASC";
        $actions = $database->select($actions_sql, ['dialplan_uuid' => $dialplan_uuid], 'all');

        if (!empty($actions)) {
            foreach ($actions as $action) {
                $dialplan_xml .= "		<action application=\"" . xml::sanitize($action['dialplan_detail_type']) . "\" data=\"" . xml::sanitize($action['dialplan_detail_data']) . "\"/>\n";
            }
        }
    }

    $dialplan_xml .= "	</condition>\n";
    $dialplan_xml .= "</extension>\n";

    $array['dialplans'][0]['dialplan_xml'] = $dialplan_xml;
}

// Grant temporary permissions
$p = permissions::new();
$p->add('destination_edit', 'temp');
$p->add('dialplan_edit', 'temp');
$p->add('dialplan_detail_add', 'temp');
$p->add('dialplan_detail_delete', 'temp');

// Save to database
$database->app_name = 'destinations';
$database->app_uuid = '5ec89622-b19c-3559-64f0-afde802ab139';
$database->save($array);

// Revoke temporary permissions
$p->delete('destination_edit', 'temp');
$p->delete('dialplan_edit', 'temp');
$p->delete('dialplan_detail_add', 'temp');
$p->delete('dialplan_detail_delete', 'temp');

// Clear cache
$cache = new cache;
$cache->delete("dialplan:" . $destination_context);
if ($destination_context != $existing['destination_context']) {
    $cache->delete("dialplan:" . $existing['destination_context']);
}

api_success(['destination_uuid' => $destination_uuid], 'Destination updated successfully');
