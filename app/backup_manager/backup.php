<?php
//------------------------------------------------------------------------------
// backup.php
// Backup Manager Web UI
//------------------------------------------------------------------------------
//includes files
require_once dirname(__DIR__, 2) . "/resources/require.php";
require_once "resources/check_auth.php";

//check permissions
if (!permission_exists('backup_manager_backup')) {
    echo "access denied";
    exit;
}

$message = '';
if (!empty($_POST['action']) && $_POST['action'] === 'backup') {
    // Path to your backup script
    $script = '/usr/local/bin/fusionpbx-backup-manager.sh';
    // Execute backup (ensure www-data has sudo rights for this script)
    exec('sudo ' . escapeshellarg($script) . ' 2>&1',
	$output,
	$status
	);
    $message = $status === 0 ? 'Backup completed successfully.' : 'Backup failed!';
}

require_once "resources/header.php";
echo '<h2>Backup Manager</h2>';
if ($message) {
    echo "<div class='message'>$message</div>";
}

echo "<form method='post'>";
echo "  <input type='hidden' name='action' value='backup' />";
echo "  <button type='submit' class='btn'>Run Backup</button>";
echo "</form>";

// List existing backups
$dir = '/var/backups/fusionpbx';
$files = array_filter(scandir($dir, SCANDIR_SORT_DESCENDING), function($f) {
    return preg_match('/\.tgz$/', $f);
});
if (!empty($files)) {
    echo '<h3>Available Backups</h3>';
    echo '<table>';    
    echo '<tr><th>Filename</th><th>Size</th><th>Date</th><th>Download</th></tr>';
    foreach (array_slice($files, 0, 10) as $file) {
        $path = $dir . '/' . $file;
        $size = round(filesize($path) / 1024 / 1024, 2) . ' MB';
        $date = date('Y-m-d H:i:s', filemtime($path));
        $url  = '/app/backup_manager/download.php?file=' . urlencode($file);
        echo "<tr><td>$file</td><td>$size</td><td>$date</td><td><a href='$url'>Download</a></td></tr>";
    }
    echo '</table>';
}

require_once "resources/footer.php";
?>