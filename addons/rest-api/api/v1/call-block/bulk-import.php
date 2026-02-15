<?php
/**
 * Bulk Import Call Block Rules
 * POST /api/v1/call-block/bulk-import.php
 *
 * Request Body (JSON):
 * {
 *   "rules": [
 *     {
 *       "number": "1234567890",
 *       "name": "Spammer 1",
 *       "action": "reject",
 *       "enabled": true,
 *       "description": "Known spam caller"
 *     },
 *     {
 *       "number": "9876543210",
 *       "name": "Spammer 2",
 *       "action": "reject"
 *     }
 *   ],
 *   "skip_duplicates": true
 * }
 *
 * Required fields per rule: number
 */

require_once __DIR__ . '/../base.php';

api_require_method('POST');

// Get request data
$data = get_request_data();

// Validate request structure
if (empty($data['rules']) || !is_array($data['rules'])) {
    api_error('VALIDATION_ERROR', 'rules array is required', 'rules', 400);
}

$skip_duplicates = $data['skip_duplicates'] ?? true;
$database = new database;

$results = [
    'imported' => 0,
    'skipped' => 0,
    'errors' => []
];

foreach ($data['rules'] as $index => $rule) {
    // Validate required fields
    if (empty($rule['number'])) {
        $results['errors'][] = [
            'index' => $index,
            'number' => $rule['number'] ?? 'missing',
            'error' => 'number is required'
        ];
        $results['skipped']++;
        continue;
    }

    // Validate phone number format
    if (!preg_match('/^[\d\+\-\*\#\s\(\)]+$/', $rule['number'])) {
        $results['errors'][] = [
            'index' => $index,
            'number' => $rule['number'],
            'error' => 'Invalid phone number format'
        ];
        $results['skipped']++;
        continue;
    }

    // Check for duplicate
    $check_sql = "SELECT COUNT(*) FROM v_call_block
                  WHERE domain_uuid = :domain_uuid
                  AND call_block_number = :number";
    $check_params = [
        'domain_uuid' => $domain_uuid,
        'number' => $rule['number']
    ];
    $exists = $database->select($check_sql, $check_params, 'column') > 0;

    if ($exists) {
        if ($skip_duplicates) {
            $results['skipped']++;
            continue;
        } else {
            $results['errors'][] = [
                'index' => $index,
                'number' => $rule['number'],
                'error' => 'Number already blocked'
            ];
            $results['skipped']++;
            continue;
        }
    }

    // Prepare save array
    $call_block_uuid = uuid();
    $array['call_block'][0]['domain_uuid'] = $domain_uuid;
    $array['call_block'][0]['call_block_uuid'] = $call_block_uuid;
    $array['call_block'][0]['call_block_number'] = $rule['number'];
    $array['call_block'][0]['call_block_name'] = $rule['name'] ?? null;
    $array['call_block'][0]['call_block_action'] = $rule['action'] ?? 'reject';
    $array['call_block'][0]['call_block_enabled'] = isset($rule['enabled']) && $rule['enabled'] ? 'true' : 'false';
    $array['call_block'][0]['call_block_description'] = $rule['description'] ?? null;
    $array['call_block'][0]['call_block_count'] = 0;

    // Insert record
    try {
        // Add temporary permission
        $p = permissions::new();
        $p->add('call_block_add', 'temp');

        // Save record
        $database->app_name = 'call_block';
        $database->app_uuid = '2c2453c0-1bea-4475-9f44-caa32501f825';
        $database->save($array);
        unset($array);

        // Remove temporary permission
        $p->delete('call_block_add', 'temp');

        $results['imported']++;
    } catch (Exception $e) {
        $results['errors'][] = [
            'index' => $index,
            'number' => $rule['number'],
            'error' => 'Database error occurred'
        ];
        $results['skipped']++;
    }
}

// Clear dialplan cache if any records were imported
if ($results['imported'] > 0) {
    api_clear_dialplan_cache();
}

api_success($results, "Bulk import completed: {$results['imported']} imported, {$results['skipped']} skipped");
