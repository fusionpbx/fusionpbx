<?php
require_once __DIR__ . '/../../base.php';
api_require_method('GET');

$fax_uuid = get_uuid_from_path();
api_validate_uuid($fax_uuid, 'fax_uuid');

$fax = api_get_record('v_fax', 'fax_uuid', $fax_uuid);

if (!$fax) {
    api_not_found('Fax account');
}

api_success($fax);
