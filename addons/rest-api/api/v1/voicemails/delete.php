<?php
require_once dirname(__DIR__) . '/auth.php';
require_once dirname(__DIR__, 5) . '/app/voicemails/resources/classes/voicemail.php';
validate_api_key();

$voicemail_uuid = get_uuid_from_path();
api_validate_uuid($voicemail_uuid, 'voicemail_uuid');

// Verify voicemail exists and get voicemail_id
$database = new database;
$sql = "SELECT voicemail_id FROM v_voicemails WHERE domain_uuid = :domain_uuid AND voicemail_uuid = :voicemail_uuid";
$parameters = ['domain_uuid' => $domain_uuid, 'voicemail_uuid' => $voicemail_uuid];
$voicemail = $database->select($sql, $parameters, 'row');

if (empty($voicemail)) {
    api_error('NOT_FOUND', 'Voicemail not found', null, 404);
}

$voicemail_id = $voicemail['voicemail_id'];

// Delete voicemail options
$sql = "DELETE FROM v_voicemail_options WHERE domain_uuid = :domain_uuid AND voicemail_uuid = :voicemail_uuid";
$database->execute($sql, $parameters);

// Delete voicemail destinations
$sql = "DELETE FROM v_voicemail_destinations WHERE domain_uuid = :domain_uuid AND voicemail_uuid = :voicemail_uuid";
$database->execute($sql, $parameters);

// Delete voicemail messages
$sql = "DELETE FROM v_voicemail_messages WHERE domain_uuid = :domain_uuid AND voicemail_uuid = :voicemail_uuid";
$database->execute($sql, $parameters);

// Delete voicemail greetings
$sql = "DELETE FROM v_voicemail_greetings WHERE domain_uuid = :domain_uuid AND voicemail_id = :voicemail_id";
$parameters['voicemail_id'] = $voicemail_id;
$database->execute($sql, $parameters);

// Delete voicemail record
$sql = "DELETE FROM v_voicemails WHERE domain_uuid = :domain_uuid AND voicemail_uuid = :voicemail_uuid";
$parameters = ['domain_uuid' => $domain_uuid, 'voicemail_uuid' => $voicemail_uuid];
$database->execute($sql, $parameters);

// Delete voicemail directory and files
$settings = new settings(['database' => new database, 'domain_uuid' => $domain_uuid]);
$switch_voicemail = $settings->get('switch', 'voicemail', '/var/lib/freeswitch/storage/voicemail');
$voicemail_dir = $switch_voicemail.'/default/'.$domain_name.'/'.$voicemail_id;

if (file_exists($voicemail_dir)) {
    // Recursively delete directory
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($voicemail_dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($files as $fileinfo) {
        $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
        @$todo($fileinfo->getRealPath());
    }
    @rmdir($voicemail_dir);
}

// Delete cache entries
$cache = new cache;
$cache->delete("voicemail:".$voicemail_id."@".$domain_name);

api_no_content();
