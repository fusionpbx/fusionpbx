<?php
require_once dirname(__DIR__, 2) . "/resources/require.php";
require_once "resources/check_auth.php";

//check permission
if (!permission_exists('backup_manager_backup')) {
    echo "access denied";
    exit;
}

$file = basename($_GET['file'] ?? '');
$path = '/var/backups/fusionpbx/' . $file;

if ($file && file_exists($path)) {
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $file . '"');
    readfile($path);
    exit;
}

echo "File not found";
?>
