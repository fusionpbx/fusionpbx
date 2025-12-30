<?php
/*
	FusionPBX
	Version: MPL 1.1

	The contents of this file are subject to the Mozilla Public License Version
	1.1 (the "License"); you may not use this file except in compliance with
	the License. You may obtain a copy of the License at
	http://www.mozilla.org/MPL/

	Software distributed under the License is distributed on an "AS IS" basis,
	WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
	for the specific language governing rights and limitations under the
	License.

	The Original Code is FusionPBX

	The Initial Developer of the Original Code is
	Mark J Crane <markjcrane@fusionpbx.com>
	Portions created by the Initial Developer are Copyright (C) 2008-2024
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	James Rose <james.o.rose@gmail.com>
*/

//includes files
	require_once dirname(__DIR__, 2) . "/resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (!permission_exists('conference_active_view')) {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//create token
	$token = (new token())->create($_SERVER['PHP_SELF']);

//pass the token to the subscriber class so that when this subscriber makes a websocket
//connection, the subscriber object can validate the information.
	subscriber::save_token($token, ['active.conferences']);

//include the header
	$document['title'] = $text['title-active_conferences'];
	require_once "resources/header.php";

//break the caching
	$version = md5(file_get_contents(__DIR__ . '/resources/javascript/websocket_client.js'));

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
		'connected'    => $text['status-connected'],
		'warning'      => $text['status-warning'],
		'disconnected' => $text['status-disconnected'],
		'connecting'   => $text['status-connecting'],
	];

?>

<script type="text/javascript">

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

//permissions
const permissions = {
	conference_interactive_view: <?= permission_exists('conference_interactive_view') ? 'true' : 'false' ?>,
	list_row_edit_button: <?= $settings->get('theme', 'list_row_edit_button', false) ? 'true' : 'false' ?>
};

//button icon
const button_icon_view = '<?= $settings->get('theme', 'button_icon_view') ?>';

</script>

<?php
$ws_client_file = __DIR__ . '/resources/javascript/websocket_client.js';
$ws_client_hash = file_exists($ws_client_file) ? md5_file($ws_client_file) : $version;

$ac_js_file = __DIR__ . '/resources/javascript/active_conferences.js';
$ac_js_hash = file_exists($ac_js_file) ? md5_file($ac_js_file) : $version;
?>
<script src="resources/javascript/websocket_client.js?v=<?= $ws_client_hash ?>"></script>
<script src="resources/javascript/active_conferences.js?v=<?= $ac_js_hash ?>"></script>

<script type="text/javascript">
	const token = {
		name: '<?= $token['name'] ?>',
		hash: '<?= $token['hash'] ?>'
	};

	// Domain name for filtering
	const domain_name = '<?= $_SESSION['domain_name'] ?>';

	// Start websocket connection
	connect_websocket();
</script>

<?php

//page header
	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-active_conferences']."</b>";
	if ($status_indicator_mode === 'icon') {
		echo "<span id='connection_status' class='".$status_icons['connecting']."' style='color: ".$status_colors['connecting'].";' title='".$status_tooltips['connecting']."'></span>";
	} else {
		echo "<div id='connection_status' class='count'><span id='conference_count'>0</span></div>";
	}
	echo "</div>\n";
	echo "	<div class='actions'>\n";
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	echo $text['description-active']."\n";
	echo "<br /><br />\n";

//show the content
	echo "<div id='conferences_container'></div>"; // Replaced ajax_response
	echo "<br><br>";

//include the footer
	require_once "resources/footer.php";

?>
