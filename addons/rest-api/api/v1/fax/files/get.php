<?php
require_once __DIR__ . '/../../base.php';
api_require_method('GET');

$fax_file_uuid = get_uuid_from_path();
api_validate_uuid($fax_file_uuid, 'fax_file_uuid');

$fax_file = api_get_record('v_fax_files', 'fax_file_uuid', $fax_file_uuid);

if (!$fax_file) {
    api_not_found('Fax file');
}

api_success($fax_file);
