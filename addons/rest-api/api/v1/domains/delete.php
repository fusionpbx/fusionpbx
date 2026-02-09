<?php
require_once dirname(__DIR__) . '/base.php';

api_require_method('DELETE');

$target_uuid = get_uuid_from_path();
api_validate_uuid($target_uuid, 'domain_uuid');

// Prevent deleting your own authenticated domain
if ($target_uuid === $domain_uuid) {
    api_error('FORBIDDEN', 'Cannot delete the domain you are authenticated against', null, 403);
}

// Verify domain exists
$database = new database;
$sql = "SELECT domain_uuid, domain_name FROM v_domains WHERE domain_uuid = :uuid";
$existing = $database->select($sql, ['uuid' => $target_uuid], 'row');
if (empty($existing)) {
    api_not_found('Domain');
}

$target_domain_name = $existing['domain_name'];

// Grant permissions
$p = permissions::new();
$p->add('domain_delete', 'temp');

// Get all app configs to find tables with domain_uuid
$app_config_files = glob(dirname(__DIR__, 4) . '/app/*/app_config.php') ?: [];
$core_config_files = glob(dirname(__DIR__, 4) . '/core/*/app_config.php') ?: [];
$all_configs = array_merge($app_config_files, $core_config_files);

// Delete all domain-scoped records from every table
foreach ($all_configs as $config_file) {
    $apps = [];
    include $config_file;
    if (!empty($apps)) {
        foreach ($apps as $app) {
            if (!empty($app['db'])) {
                foreach ($app['db'] as $table_def) {
                    $table_name = $table_def['table']['name'] ?? '';
                    if ($table_name === 'v_domains') continue;
                    // Check if table has domain_uuid field
                    $fields = $table_def['fields'] ?? [];
                    foreach ($fields as $field) {
                        if (($field['name'] ?? '') === 'domain_uuid') {
                            $db = new database;
                            $sql = "DELETE FROM " . $table_name . " WHERE domain_uuid = :domain_uuid";
                            $db->execute($sql, ['domain_uuid' => $target_uuid]);
                            break;
                        }
                    }
                }
            }
        }
    }
}

// Delete filesystem directories
$settings = new settings(['database' => new database]);

$recordings_dir = $settings->get('switch', 'recordings', '');
if (!empty($recordings_dir) && is_dir($recordings_dir . '/' . $target_domain_name)) {
    exec('rm -rf ' . escapeshellarg($recordings_dir . '/' . $target_domain_name));
}

$voicemail_dir = $settings->get('switch', 'voicemail', '');
if (!empty($voicemail_dir) && is_dir($voicemail_dir . '/default/' . $target_domain_name)) {
    exec('rm -rf ' . escapeshellarg($voicemail_dir . '/default/' . $target_domain_name));
}

$dialplan_dir = $settings->get('switch', 'dialplan', '');
if (!empty($dialplan_dir)) {
    // Remove dialplan XML and directory
    if (file_exists($dialplan_dir . '/' . $target_domain_name . '.xml')) {
        unlink($dialplan_dir . '/' . $target_domain_name . '.xml');
    }
    if (is_dir($dialplan_dir . '/' . $target_domain_name)) {
        exec('rm -rf ' . escapeshellarg($dialplan_dir . '/' . $target_domain_name));
    }
    // Remove public dialplan
    if (file_exists($dialplan_dir . '/public/' . $target_domain_name . '.xml')) {
        unlink($dialplan_dir . '/public/' . $target_domain_name . '.xml');
    }
    if (is_dir($dialplan_dir . '/public/' . $target_domain_name)) {
        exec('rm -rf ' . escapeshellarg($dialplan_dir . '/public/' . $target_domain_name));
    }
}

$extensions_dir = $settings->get('switch', 'extensions', '');
if (!empty($extensions_dir)) {
    if (file_exists($extensions_dir . '/' . $target_domain_name . '.xml')) {
        unlink($extensions_dir . '/' . $target_domain_name . '.xml');
    }
    if (is_dir($extensions_dir . '/' . $target_domain_name)) {
        exec('rm -rf ' . escapeshellarg($extensions_dir . '/' . $target_domain_name));
    }
}

$storage_dir = $settings->get('switch', 'storage', '');
if (!empty($storage_dir) && is_dir($storage_dir . '/fax/' . $target_domain_name)) {
    exec('rm -rf ' . escapeshellarg($storage_dir . '/fax/' . $target_domain_name));
}

// Delete the domain record itself
$database = new database;
$sql = "DELETE FROM v_domains WHERE domain_uuid = :uuid";
$database->execute($sql, ['uuid' => $target_uuid]);

$p->delete('domain_delete', 'temp');

// Clear cache
$cache = new cache;
$cache->delete("domains");

// Reload XML
api_clear_cache("configuration:sofia.conf");

api_no_content();
