<?php
//------------------------------------------------------------------------------
// restore.php
// Restore Manager Web UI
//------------------------------------------------------------------------------
require_once dirname(__DIR__, 2) . "/resources/require.php";
require_once "resources/check_auth.php";

//check permissions
if (!permission_exists('backup_manager_restore')) {
    echo "access denied";
    exit;
}

$message = '';
$selected_file = $_GET['file'] ?? '';
if (!empty($_POST['action']) && $_POST['action'] === 'restore') {
    $backup_file = escapeshellarg('/var/backups/fusionpbx/' . $_POST['backup_file']);
    $options     = $_POST['restore_options'] ?? [];
    // Pre-restore safety dump
    exec('sudo /usr/local/bin/fusionpbx-pre-restore.sh');
    // Extract and restore based on options
    $script = '/usr/local/bin/fusionpbx-restore-manager.sh';
    $cmd = 'sudo ' . escapeshellarg($script) . ' ' . $backup_file . ' ' . implode(',', $options) . ' 2>&1';
    exec($cmd, $output, $status);
    $message = $status === 0 ? 'Restore completed successfully.' : 'Restore failed!';
}

require_once "resources/header.php";
echo '<h2>Restore Manager</h2>';
if ($message) {
    echo "<div class='message'>$message</div>";
}

echo '<form method="post">';
echo '<input type="hidden" name="action" value="restore" />';

echo '<label>Select Backup File:</label><br/>';
echo '<select name="backup_file">';
foreach (array_filter(scandir('/var/backups/fusionpbx', SCANDIR_SORT_DESCENDING), function($f){return preg_match('/\.tgz$/', $f);} ) as $file) {
    $sel = $selected_file === $file ? 'selected' : '';
    echo '<option value="' . htmlspecialchars($file) . '" ' . $sel . '>' . htmlspecialchars($file) . '</option>';
}
echo '</select><br/><br/>';

echo '<label>Restore Options:</label><br/>';
echo '<input type="checkbox" name="restore_options[]" value="db" /> Database (Config & CDRs)<br/>';
echo '<input type="checkbox" name="restore_options[]" value="media" /> Voicemails & Recordings<br/>';
echo '<input type="checkbox" name="restore_options[]" value="certs" /> SSL Certificates<br/><br/>';

echo '<button type="submit" class="btn">Run Restore</button>';
echo '</form>';

require_once "resources/footer.php";
?>
