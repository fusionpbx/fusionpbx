<?php
/*
 * FusionPBX
 * Version: MPL 1.1
 *
 * The contents of this file are subject to the Mozilla Public License Version
 * 1.1 (the "License"); you may not use this file except in compliance with
 * the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
 * for the specific language governing rights and limitations under the
 * License.
 *
 * The Original Code is FusionPBX
 *
 * The Initial Developer of the Original Code is
 * Mark J Crane <markjcrane@fusionpbx.com>
 * Portions created by the Initial Developer are Copyright (C) 2008-2025
 * the Initial Developer. All Rights Reserved.
 *
 * Contributor(s):
 * Mark J Crane <markjcrane@fusionpbx.com>
 * Tim Fry <tim@fusionpbx.com>
 */

//includes files
require_once dirname(__DIR__, 2) . "/resources/require.php";
require_once "resources/check_auth.php";

//check permissions
if (!permission_exists('conference_interactive_view')) {
	echo "access denied";
	exit;
}

//show intended global variables
global $domain_uuid, $user_uuid, $settings, $database, $config;

//get the domain uuid
if (empty($domain_uuid)) {
	$domain_uuid = $_SESSION['domain_uuid'] ?? '';
}

//get the user uuid
if (empty($user_uuid)) {
	$user_uuid = $_SESSION['user_uuid'] ?? '';
}

//load the config
if (!($config instanceof config)) {
	$config = config::load();
}

//load the database
if (!($database instanceof database)) {
	$database = new database;
}

//load the settings
if (!($settings instanceof settings)) {
	$settings = new settings(['database' => $database, 'domain_uuid' => $domain_uuid, 'user_uuid' => $user_uuid]);
}

//add multi-lingual support
$language = new text;
$text = $language->get();

//get the http get or post and set it as php variables
if (!empty($_REQUEST["c"]) && is_numeric($_REQUEST["c"])) {
	$conference_id = $_REQUEST["c"];
}
elseif (!empty($_REQUEST["c"]) && is_uuid($_REQUEST["c"])) {
	$conference_id = $_REQUEST["c"];
}
else {
	//exit if the conference id is invalid
	exit;
}

//replace the space with underscore
$conference_name = $conference_id.'@'.$_SESSION['domain_name'];

//get and prepare the conference display name
$conference_display_name = str_replace("-", " ", $conference_id);
$conference_display_name = str_replace("_", " ", $conference_display_name);

//create token
$token = (new token())->create($_SERVER['PHP_SELF']);

// Pass the token to the subscriber class so that when this subscriber makes a websocket
// connection, the subscriber object can validate the information.
subscriber::save_token($token, ['active.conferences']);

//show the header
$document['title'] = $text['label-interactive'];
require_once dirname(__DIR__, 2) . "/resources/header.php";

//break the caching
$version = md5(file_get_contents(__DIR__ . '/resources/javascript/websocket_client.js'));

//build permissions object for client-side checks
$user_permissions = [
	'lock' => permission_exists('conference_interactive_lock'),
	'mute' => permission_exists('conference_interactive_mute'),
	'deaf' => permission_exists('conference_interactive_deaf'),
	'kick' => permission_exists('conference_interactive_kick'),
	'energy' => permission_exists('conference_interactive_energy'),
	'volume' => permission_exists('conference_interactive_volume'),
	'gain' => permission_exists('conference_interactive_gain'),
	'video' => permission_exists('conference_interactive_video'),
];

//get websocket settings from default settings
$ws_settings = [
	'reconnect_delay'          => (int)$settings->get('active_conferences', 'reconnect_delay', 2000),
	'ping_interval'            => (int)$settings->get('active_conferences', 'ping_interval', 30000),
	'auth_timeout'             => (int)$settings->get('active_conferences', 'auth_timeout', 10000),
	'pong_timeout'             => (int)$settings->get('active_conferences', 'pong_timeout', 10000),
	'refresh_interval'         => (int)$settings->get('active_conferences', 'refresh_interval', 0),
	'max_reconnect_delay'      => (int)$settings->get('active_conferences', 'max_reconnect_delay', 30000),
	'pong_timeout_max_retries' => (int)$settings->get('active_conferences', 'pong_timeout_max_retries', 3),
];

//get theme colors for status indicator
$status_colors = [
	'connected'    => $settings->get('theme', 'active_conference_status_connected', '#28a745'),
	'warning'      => $settings->get('theme', 'active_conference_status_warning', '#ffc107'),
	'disconnected' => $settings->get('theme', 'active_conference_status_disconnected', '#dc3545'),
	'connecting'   => $settings->get('theme', 'active_conference_status_connecting', '#6c757d'),
];

//get status indicator mode and icons
$status_indicator_mode = $settings->get('theme', 'active_conference_status_indicator_mode', 'color');
$status_icons = [
	'connected'    => $settings->get('theme', 'active_conference_status_icon_connected', 'fa-solid fa-plug-circle-check'),
	'warning'      => $settings->get('theme', 'active_conference_status_icon_warning', 'fa-solid fa-plug-circle-exclamation'),
	'disconnected' => $settings->get('theme', 'active_conference_status_icon_disconnected', 'fa-solid fa-plug-circle-xmark'),
	'connecting'   => $settings->get('theme', 'active_conference_status_icon_connecting', 'fa-solid fa-plug fa-fade'),
];

//get status tooltips from translations
$status_tooltips = [
	'connected' => $text['status-connected'],
	'warning' => $text['status-warning'],
	'disconnected' => $text['status-disconnected'],
	'connecting' => $text['status-connecting'],
];

?>

<script type="text/javascript">
//user permissions for client-side checks
const user_permissions = <?= json_encode($user_permissions) ?>;

//websocket configuration from server settings
const ws_config = <?= json_encode($ws_settings) ?>;

//status indicator colors from theme settings
const status_colors = <?= json_encode($status_colors) ?>;

//status indicator icons from settings
const status_icons = <?= json_encode($status_icons) ?>;

//status tooltips from translations
const status_tooltips = <?= json_encode($status_tooltips) ?>;

//status indicator mode: 'color' or 'icon'
const status_indicator_mode = <?= json_encode($status_indicator_mode) ?>;

//translations
const text = <?= json_encode($text) ?>;

//send action via WebSocket
function send_action(action, options = {}, skip_refresh = false) {
	if (!ws || !ws.ws || ws.ws.readyState !== WebSocket.OPEN) {
		console.error('WebSocket not connected');
		return Promise.reject('Not connected');
	}

	const payload = {
		action: action,
		conference_name: conference_name,
		domain_name: '<?= $_SESSION['domain_name'] ?>',
		...options
	};

	console.log('Sending action:', action, payload);

	return ws.request('active.conferences', 'action', payload)
		.then(response => {
			console.log('Action response:', response);
			const result = response.payload || response;
			if (!result.success) {
				console.error('Action failed:', result.message);
			}
			// Refresh data after action (unless skip_refresh is true)
			if (!skip_refresh) {
				load_conference_data();
			}
			return result;
		})
		.catch(err => {
			console.error('Action error:', err);
			throw err;
		});
}

//conference control functions
function conference_action(action, member_id, uuid, direction) {
	return send_action(action, {
		member_id: member_id || '',
		uuid: uuid || '',
		direction: direction || ''
	});
}

var record_count = 0;
</script>

<?php
$ws_client_file = __DIR__ . '/resources/javascript/websocket_client.js';
$ws_client_hash = file_exists($ws_client_file) ? md5_file($ws_client_file) : $version;

$ac_js_file = __DIR__ . '/resources/javascript/active_conferences.js';
$ac_js_hash = file_exists($ac_js_file) ? md5_file($ac_js_file) : $version;
?>
<script src="resources/javascript/websocket_client.js?v=<?= $ws_client_hash ?>"></script>
<script src="resources/javascript/active_conferences.js?v=<?= $ac_js_hash ?>"></script>

<?php

//page header
echo "<div class='action_bar' id='action_bar'>\n";
echo "<div class='heading'><b>".$text['label-interactive']."</b>&nbsp;";
if ($status_indicator_mode === 'icon') {
	echo "<span id='connection_status' class='".$status_icons['connecting']."' style='color: ".$status_colors['connecting'].";' title='".$status_tooltips['connecting']."'></span>";
} else {
	echo "<div id='connection_status' class='count' style='display: inline-block; min-width: 12px; height: 12px; vertical-align: middle; background: ".$status_colors['connecting'].";' title='".$status_tooltips['connecting']."'></div>";
}
echo "</div>\n";
echo "<div class='actions'>\n";
echo "</div>\n";
echo "<div style='clear: both;'></div>\n";
echo "</div>\n";

echo $text['description-interactive']."\n";
echo "<br /><br />\n";

//show the content
echo "<div id='conference_container'></div>\n";
echo "<br /><br />\n";

?>

<script>
const token = {
	name: '<?= $token['name'] ?>',
	hash: '<?= $token['hash'] ?>'
};

const conference_name = <?= json_encode($conference_name) ?>;
const conference_id = <?= json_encode($conference_id) ?>;
const domain_name = '<?= $_SESSION['domain_name'] ?>';

// Start websocket connection
connect_websocket();

render_conference_room();
</script>

<?php require_once "resources/footer.php"; ?>
