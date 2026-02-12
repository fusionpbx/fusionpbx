<?php
require_once dirname(__DIR__, 2) . '/auth.php';
require_once dirname(__DIR__, 2) . '/base.php';
validate_api_key();

// Get parameters
$filter = $_GET['filter'] ?? '';
$lines = min(2000, max(10, intval($_GET['lines'] ?? 200)));
$log_file_name = $_GET['log_file'] ?? 'freeswitch.log';

// Sanitize log file name - only allow freeswitch.log variants
if (!preg_match('/^freeswitch\.log(\.\d{1,4}(-\d{2}-\d{2})?)?$/', $log_file_name)) {
    api_error('VALIDATION_ERROR', 'Invalid log file name. Use freeswitch.log or freeswitch.log.N', 'log_file');
}

// Get the log directory from settings
$database = new database;
$sql = "SELECT default_setting_value FROM v_default_settings
        WHERE default_setting_category = 'switch'
        AND default_setting_subcategory = 'log'
        AND default_setting_enabled = 'true' LIMIT 1";
$log_dir = $database->select($sql, [], 'column');

if (empty($log_dir)) {
    $log_dir = '/var/log/freeswitch';
}

$log_path = rtrim($log_dir, '/') . '/' . $log_file_name;

if (!file_exists($log_path)) {
    api_error('NOT_FOUND', 'Log file not found: ' . $log_file_name, null, 404);
}

// Read the last N KB of the file (max 1MB to avoid memory issues)
$max_bytes = min(1048576, max(32768, intval($_GET['size'] ?? 512) * 1024));
$file_size = filesize($log_path);
$offset = max(0, $file_size - $max_bytes);

$fp = fopen($log_path, 'r');
if (!$fp) {
    api_error('SERVER_ERROR', 'Cannot open log file', null, 500);
}

fseek($fp, $offset);
if ($offset > 0) {
    // Skip partial first line
    fgets($fp);
}

$all_lines = [];
while (($line = fgets($fp)) !== false) {
    $line = rtrim($line, "\r\n");
    if (empty($line)) continue;

    // Filter by domain name - tenants only see their own logs
    if (stripos($line, $domain_name) === false) continue;

    // Apply additional keyword filter
    if (!empty($filter) && stripos($line, $filter) === false) continue;

    $all_lines[] = $line;
}
fclose($fp);

// Return only the last N lines
$result_lines = array_slice($all_lines, -$lines);

// List available log files
$available_files = [];
$log_files = glob(rtrim($log_dir, '/') . '/freeswitch.log*');
if (is_array($log_files)) {
    foreach ($log_files as $f) {
        $available_files[] = basename($f);
    }
    sort($available_files);
}

api_success([
    'lines' => $result_lines,
    'count' => count($result_lines),
    'total_matched' => count($all_lines),
    'log_file' => $log_file_name,
    'file_size' => $file_size,
    'available_files' => $available_files,
    'filter' => $filter,
    'domain_filter' => $domain_name
]);