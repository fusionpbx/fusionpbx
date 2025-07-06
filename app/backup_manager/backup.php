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

$settings_file = '/var/backups/backup_settings.json';
$settings = ['auto_enabled'=>false,'frequency'=>'daily','keep'=>7];
if (file_exists($settings_file)) {
    $json = file_get_contents($settings_file);
    $data = json_decode($json, true);
    if (is_array($data)) $settings = array_merge($settings, $data);
}

$message = '';
if (!empty($_POST['action']) && $_POST['action'] === 'backup') {
    // Path to your backup script
    $script = '/var/www/fusionpbx/app/backup_manager/scripts/fusionpbx-backup-manager.sh';
    // Execute backup (ensure www-data has sudo rights for this script)
    exec('sudo ' . escapeshellarg($script) . ' 2>&1',
	$output,
	$status
	);
    $message = $status === 0 ? 'Backup completed successfully.' : 'Backup failed!';
}

if (!empty($_GET['delete'])) {
    $del = basename($_GET['delete']);
    $path = '/var/backups/fusionpbx/' . $del;
    if (file_exists($path)) {
        if (unlink($path)) {
            $message = 'Backup deleted.';
        } else {
            $message = 'Failed to delete backup.';
        }
    }
}

if (isset($_POST['save_settings'])) {
    $settings['auto_enabled'] = isset($_POST['auto_enabled']);
    $settings['frequency'] = $_POST['frequency'] ?? 'daily';
    $settings['keep'] = (int)($_POST['keep'] ?? 7);
    file_put_contents($settings_file, json_encode($settings));
    $message = 'Settings saved';
}

require_once "resources/header.php";
echo '<h2>Backup Manager</h2>';
if ($message) {
    echo "<div class='message'>$message</div>";
}

// settings form
echo "<form method='post' style='margin-bottom:20px;'>";
echo "<h3>Auto Backup Settings</h3>";
echo "<label><input type='checkbox' name='auto_enabled'".($settings['auto_enabled']?' checked':'')."> Enable Auto Backup</label><br>";
echo "<label>Frequency:</label>";
echo "<select name='frequency'>";
foreach (['daily','weekly','monthly'] as $freq) {
    $sel = $settings['frequency']==$freq ? 'selected' : '';
    echo "<option value='$freq' $sel>$freq</option>";
}
echo "</select><br>";
echo "<label>Keep Backups:</label> <input type='number' name='keep' value='".intval($settings['keep'])."' min='1' style='width:60px;'>";
echo "<br><button type='submit' name='save_settings' class='btn'>Save Settings</button>";
echo "</form>";

// manual backup button
echo "<form method='post' style='margin-bottom:20px;'>";
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
    echo '<tr><th>Filename</th><th>Size</th><th>Date</th><th>Actions</th></tr>';
    foreach (array_slice($files, 0, 10) as $file) {
        $path = $dir . '/' . $file;
        $size = round(filesize($path) / 1024 / 1024, 2) . ' MB';
        $date = date('Y-m-d H:i:s', filemtime($path));
        $url_download  = '/app/backup_manager/download.php?file=' . urlencode($file);
        $url_restore   = '/app/backup_manager/restore.php?file=' . urlencode($file);
        $url_delete    = '/app/backup_manager/backup.php?delete=' . urlencode($file);
        echo "<tr><td>$file</td><td>$size</td><td>$date</td><td><a href='$url_restore'>Restore</a> | <a href='$url_download'>Download</a> | <a href='$url_delete' onclick=\"return confirm('Delete?');\">Delete</a></td></tr>";
    }
    echo '</table>';
}

require_once "resources/footer.php";
?>
