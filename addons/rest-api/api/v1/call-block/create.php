<?php
/**
 * Create Call Block Rule
 * POST /api/v1/call-block/create.php
 *
 * Request Body (JSON):
 * {
 *   "call_block_number": "1234567890",
 *   "call_block_name": "Spammer",
 *   "call_block_action": "reject",
 *   "call_block_enabled": true,
 *   "call_block_description": "Known spam caller"
 * }
 *
 * Required fields: call_block_number
 */

require_once __DIR__ . '/../base.php';

api_require_method('POST');

// Get request data
$data = get_request_data();

// Validate required fields
$errors = api_validate($data, ['call_block_number']);
if (!empty($errors)) {
    api_validation_error($errors);
}

// Validate phone number format (allow digits, +, -, *, #, spaces)
if (!preg_match('/^[\d\+\-\*\#\s\(\)]+$/', $data['call_block_number'])) {
    api_error('VALIDATION_ERROR', 'Invalid phone number format', 'call_block_number', 400);
}

// Check for duplicate number
$database = new database;
$check_sql = "SELECT COUNT(*) FROM v_call_block
              WHERE domain_uuid = :domain_uuid
              AND call_block_number = :number";
$check_params = [
    'domain_uuid' => $domain_uuid,
    'number' => $data['call_block_number']
];
$exists = $database->select($check_sql, $check_params, 'column') > 0;

if ($exists) {
    api_conflict('call_block_number', 'This number is already blocked');
}

// Generate UUID
$call_block_uuid = uuid();

// Prepare save array
$array['call_block'][0]['domain_uuid'] = $domain_uuid;
$array['call_block'][0]['call_block_uuid'] = $call_block_uuid;
$array['call_block'][0]['call_block_number'] = $data['call_block_number'];
$array['call_block'][0]['call_block_name'] = $data['call_block_name'] ?? null;
$array['call_block'][0]['call_block_action'] = $data['call_block_action'] ?? 'reject';
$array['call_block'][0]['call_block_enabled'] = isset($data['call_block_enabled']) && $data['call_block_enabled'] ? 'true' : 'false';
$array['call_block'][0]['call_block_description'] = $data['call_block_description'] ?? null;
$array['call_block'][0]['call_block_count'] = 0;

// Add temporary permission
$p = permissions::new();
$p->add('call_block_add', 'temp');

// Save record
$database = new database;
$database->app_name = 'call_block';
$database->app_uuid = '2c2453c0-1bea-4475-9f44-caa32501f825';
$database->save($array);
unset($array);

// Remove temporary permission
$p->delete('call_block_add', 'temp');

// Clear dialplan cache
api_clear_dialplan_cache();

// Prepare response
$response = [
    'call_block_uuid' => $call_block_uuid,
    'call_block_number' => $data['call_block_number'],
    'call_block_name' => $data['call_block_name'] ?? null,
    'call_block_action' => $data['call_block_action'] ?? 'reject',
    'call_block_enabled' => isset($data['call_block_enabled']) && $data['call_block_enabled'],
    'call_block_description' => $data['call_block_description'] ?? null,
    'call_block_count' => 0
];

api_created($response, 'Call block rule created successfully');
