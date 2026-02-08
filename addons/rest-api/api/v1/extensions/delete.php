<?php
require_once dirname(__DIR__) . '/auth.php';
require_once dirname(__DIR__, 3) . '/app/extensions/resources/classes/extension.php';
validate_api_key();

$extension_uuid = get_uuid_from_path();
api_validate_uuid($extension_uuid, 'extension_uuid');

// Check if extension exists
$database = new database;
$sql = "SELECT extension, user_context FROM v_extensions WHERE domain_uuid = :domain_uuid AND extension_uuid = :extension_uuid";
$parameters = ['domain_uuid' => $domain_uuid, 'extension_uuid' => $extension_uuid];
$extension_data = $database->select($sql, $parameters, 'row');

if (empty($extension_data)) {
    api_error('NOT_FOUND', 'Extension not found', null, 404);
}

// Use extension class delete method which handles all cleanup
$ext = new extension(['domain_uuid' => $domain_uuid, 'domain_name' => $domain_name]);
$ext->delete_voicemail = true; // Delete voicemail with extension

// Build records array in the format expected by delete() method
$records = [
    [
        'uuid' => $extension_uuid,
        'checked' => 'true'
    ]
];

// Grant permission
$p = permissions::new();
$p->add('extension_delete', 'temp');

// Perform deletion (this handles: DB records, XML files, voicemail, cache, follow-me, ring groups)
$ext->delete($records);

$p->delete('extension_delete', 'temp');

// Regenerate FreeSWITCH XML
$ext->xml();

api_no_content();
