<?php
require_once __DIR__ . '/../../base.php';
api_require_method('GET');

$fax_log_uuid = get_uuid_from_path();
api_validate_uuid($fax_log_uuid, 'fax_log_uuid');

$fax_log = api_get_record('v_fax_logs', 'fax_log_uuid', $fax_log_uuid);

if (!$fax_log) {
    api_not_found('Fax log');
}

api_success($fax_log);
