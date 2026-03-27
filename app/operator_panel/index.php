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
	Portions created by the Initial Developer are Copyright (C) 2008-2025
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	Tim Fry <tim@fusionpbx.com>
*/

// Includes
	require_once dirname(__DIR__, 2) . "/resources/require.php";
	require_once "resources/check_auth.php";

// Check permissions
	if (!permission_exists('operator_panel_view')) {
		echo "access denied";
		exit;
	}

// Multi-lingual support
	$language = new text;
	$text = $language->get();

// Create token and register with the active operator panel service
	$token = (new token())->create($_SERVER['PHP_SELF']);
	subscriber::save_token($token, ['active.operator.panel']);

// Gather user permissions for the JS side
	$perm = [
		'operator_panel_view'        => permission_exists('operator_panel_view'),
		'operator_panel_manage'      => permission_exists('operator_panel_manage'),
		'operator_panel_hangup'      => permission_exists('operator_panel_hangup'),
		'operator_panel_eavesdrop'   => permission_exists('operator_panel_eavesdrop'),
		'operator_panel_record'      => permission_exists('operator_panel_record'),
		'operator_panel_originate'   => permission_exists('operator_panel_originate'),
		'operator_panel_coach'       => permission_exists('operator_panel_coach'),
		'operator_panel_call_details' => permission_exists('operator_panel_call_details'),
		'operator_panel_on_demand'   => permission_exists('operator_panel_on_demand'),
		'operator_panel_transfer_attended' => permission_exists('operator_panel_transfer_attended'),
		'operator_panel_extensions'  => permission_exists('operator_panel_extensions'),
		'operator_panel_calls'       => permission_exists('operator_panel_calls'),
		'operator_panel_conferences' => permission_exists('operator_panel_conferences'),
		'operator_panel_agents'      => permission_exists('operator_panel_agents'),
	];

// WebSocket settings from default_settings
	$ws_settings = [
		'reconnect_delay'          => (int)$settings->get('operator_panel', 'reconnect_delay',          500),
		'ping_interval'            => (int)$settings->get('operator_panel', 'ping_interval',            5000),
		'auth_timeout'             => (int)$settings->get('operator_panel', 'auth_timeout',             5000),
		'pong_timeout'             => (int)$settings->get('operator_panel', 'pong_timeout',             1500),
		'max_reconnect_delay'      => (int)$settings->get('operator_panel', 'max_reconnect_delay',      5000),
		'pong_timeout_max_retries' => (int)$settings->get('operator_panel', 'pong_timeout_max_retries', 2),
		'refresh_interval'         => (int)$settings->get('operator_panel', 'refresh_interval',         0),
	];

// Theme colors for connection status indicator
	$status_colors = [
		'connected'    => $settings->get('theme', 'operator_panel_status_connected',    '#28a745'),
		'warning'      => $settings->get('theme', 'operator_panel_status_warning',      '#ffc107'),
		'disconnected' => $settings->get('theme', 'operator_panel_status_disconnected', '#dc3545'),
		'connecting'   => $settings->get('theme', 'operator_panel_status_connecting',   '#6c757d'),
	];
	$status_icons = [
		'connected'    => $settings->get('theme', 'operator_panel_status_icon_connected',    'fa-solid fa-plug-circle-check'),
		'warning'      => $settings->get('theme', 'operator_panel_status_icon_warning',      'fa-solid fa-plug-circle-exclamation'),
		'disconnected' => $settings->get('theme', 'operator_panel_status_icon_disconnected', 'fa-solid fa-plug-circle-xmark'),
		'connecting'   => $settings->get('theme', 'operator_panel_status_icon_connecting',   'fa-solid fa-plug fa-fade'),
	];
	$conference_action_icons = [
		'mute'        => $settings->get('theme', 'operator_panel_conference_icon_mute', 'fas fa-microphone'),
		'unmute'      => $settings->get('theme', 'operator_panel_conference_icon_unmute', 'fas fa-microphone-slash'),
		'deaf'        => $settings->get('theme', 'operator_panel_conference_icon_deaf', 'fas fa-headphones'),
		'undeaf'      => $settings->get('theme', 'operator_panel_conference_icon_undeaf', 'fas fa-deaf'),
		'energy_up'   => $settings->get('theme', 'operator_panel_conference_icon_energy_up', 'fas fa-plus'),
		'energy_down' => $settings->get('theme', 'operator_panel_conference_icon_energy_down', 'fas fa-minus'),
		'volume_down' => $settings->get('theme', 'operator_panel_conference_icon_volume_down', 'fas fa-volume-down'),
		'volume_up'   => $settings->get('theme', 'operator_panel_conference_icon_volume_up', 'fas fa-volume-up'),
		'gain_down'   => $settings->get('theme', 'operator_panel_conference_icon_gain_down', 'fas fa-sort-amount-down'),
		'gain_up'     => $settings->get('theme', 'operator_panel_conference_icon_gain_up', 'fas fa-sort-amount-up'),
		'kick'        => $settings->get('theme', 'operator_panel_conference_icon_kick', 'fas fa-ban'),
	];
	$status_show_icon = $settings->get('theme', 'operator_panel_status_show_icon', 'true') === 'true';

// Optional user status list for the presence dropdown
	$user_statuses = ['Available', 'Available (On Demand)', 'On Break', 'Do Not Disturb', 'Logged Out'];

// Card label position for extension group cards: top, left, right, bottom, hidden
	$card_label_position = strtolower((string)$settings->get('operator_panel', 'card_label_position', 'left'));
	if (!in_array($card_label_position, ['top', 'left', 'right', 'bottom', 'hidden'], true)) {
		$card_label_position = 'left';
	}

// Optional polling reconciliation of registration state (can be disabled).
	$registrations_reconcile_enabled = $settings->get('operator_panel', 'registrations_reconcile_enabled', 'false') === 'true';

// Default auto-park destination for drag/drop parking.
	$park_destination = (string)$settings->get('operator_panel', 'park_destination', '*5900');
	if (!preg_match('/^[0-9*#+]+$/', $park_destination)) {
		$park_destination = '*5900';
	}

// Get the logged-in user's own extension numbers (shown at top of Extensions panel)
// and primary eavesdrop destination extension
	$user_own_extensions = [];
	if (!empty($_SESSION['user']['extensions'])) {
		// $_SESSION['user']['extensions'] is an array of extension number strings
		$user_own_extensions = array_values(array_filter($_SESSION['user']['extensions']));
	} elseif (!empty($_SESSION['user']['extension'])) {
		foreach ($_SESSION['user']['extension'] as $ext_record) {
			if (!empty($ext_record['destination'])) {
				$user_own_extensions[] = $ext_record['destination'];
			}
		}
	}

// Include the page header
	$document['title'] = $text['title-operator_panel'] ?? 'Operator Panel';
	require_once "resources/header.php";

// Cache-busting hashes for JS assets
	$ws_client_hash = md5_file(__DIR__ . '/resources/javascript/websocket_client.js');
	$lop_js_hash    = md5_file(__DIR__ . '/resources/javascript/operator_panel.js');

?>

<script type="text/javascript">

	// WebSocket configuration (server settings)
	const ws_config = <?= json_encode($ws_settings, JSON_UNESCAPED_SLASHES) ?>;

	// Theme colors and icons for connection status indicator
	const status_colors = <?= json_encode($status_colors, JSON_UNESCAPED_SLASHES) ?>;
	const status_icons  = <?= json_encode($status_icons,  JSON_UNESCAPED_SLASHES) ?>;
	const conference_action_icons = <?= json_encode($conference_action_icons, JSON_UNESCAPED_SLASHES) ?>;
	const status_tooltips = {
		connected:    <?= json_encode($text['status-connected']    ?? 'Connected') ?>,
		warning:      <?= json_encode($text['status-warning']      ?? 'Warning') ?>,
		disconnected: <?= json_encode($text['status-disconnected'] ?? 'Disconnected') ?>,
		connecting:   <?= json_encode($text['status-connecting']   ?? 'Connecting') ?>
	};
	const status_show_icon = <?= json_encode($status_show_icon) ?>;

	// Permissions passed from PHP
	const permissions = <?= json_encode($perm, JSON_UNESCAPED_SLASHES) ?>;

	// Translation strings
	const text = <?= json_encode($text, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;

	// Domain context for this session
	const domain_name = <?= json_encode($_SESSION['domain_name'] ?? '') ?>;

	// User identity (for user_status action)
	const user_uuid = <?= json_encode($_SESSION['user_uuid'] ?? '') ?>;

	// User status options
	const user_statuses = <?= json_encode($user_statuses) ?>;

	// The logged-in user's own extension numbers — shown first / highlighted in the Extensions panel
	const user_own_extensions = <?= json_encode($user_own_extensions, JSON_UNESCAPED_SLASHES) ?>;

	// Theme extras
	const button_icon_view = '<?= $settings->get('theme', 'button_icon_view') ?>';

	// Group card label position (top, left, right, bottom, hidden)
	const card_label_position = <?= json_encode($card_label_position) ?>;

	// Optional registrations-state reconciliation polling
	const registrations_reconcile_enabled = <?= json_encode($registrations_reconcile_enabled) ?>;

	// Default auto-park destination for drag/drop parking
	const park_destination = <?= json_encode($park_destination) ?>;

</script>

<script src="resources/javascript/websocket_client.js?v=<?= $ws_client_hash ?>"></script>
<script src="resources/javascript/operator_panel.js?v=<?= $lop_js_hash ?>"></script>
<script src="../../resources/sortablejs/sortable.min.js"></script>

<script type="text/javascript">
	// Authentication token for WebSocket handshake
	const token = {
		name: <?= json_encode($token['name']) ?>,
		hash: <?= json_encode($token['hash']) ?>
	};

	// Boot the panel after DOM is ready
	document.addEventListener('DOMContentLoaded', function () {
		connect_websocket();
	});
</script>

<?php

// Page header bar
	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>" . $text['title-operator_panel'] . "</b>\n";

	// Connection status indicator (icon + text)
	echo "\t\t<span id='connection_status' class='badge ms-2' style='background-color:" . htmlspecialchars($status_colors['connecting']) . "; color:#fff;'"
			. " title='" . htmlspecialchars($text['status-connecting'] ?? 'Connecting') . "'>";
	if ($status_show_icon) {
		echo "<i id='connection_status_icon' class='" . htmlspecialchars($status_icons['connecting']) . "' style='margin-right:5px;'></i>";
	}
	echo "<span id='connection_status_text'>" . htmlspecialchars($text['status-connecting'] ?? 'Connecting') . "</span>";
	echo "</span>\n";

	echo "	</div>\n";

	// My status buttons (matching the original design)
	if ($perm['operator_panel_view']) {
		$status_btn_colors = [
			'Available'            => '#28a745',
			'Available (On Demand)'=> '#28a745',
			'On Break'             => '#b8860b',
			'Do Not Disturb'       => '#dc3545',
			'Logged Out'           => '#6c757d',
		];
		echo "	<div class='actions' style='display:flex; align-items:center; gap:0;'>\n";
		echo "		<div id='user_status_buttons' style='display:inline-flex; gap:4px; margin-right:12px;'>\n";
		foreach ($user_statuses as $s) {
			$color = $status_btn_colors[$s] ?? '#6c757d';
			$label = strtoupper(htmlspecialchars($s));
			echo "			<button type='button' class='op-status-btn' data-status='" . htmlspecialchars($s) . "'"
				. " style='background-color:" . htmlspecialchars($color) . ";'"
				. " onclick='select_user_status(this)'>" . $label . "</button>\n";
		}
		echo "		</div>\n";
		echo "	</div>\n";
	}

	echo "	<div style='clear:both;'></div>\n";
	echo "</div>\n";

?>

<style>
/* Active Operator Panel — extension blocks */
.op-ext-grid {
	display: flex;
	flex-wrap: wrap;
	gap: 0;
	padding: 4px 0 12px;
}
/* Status buttons */
.op-status-btn {
	border: 2px solid transparent;
	border-radius: 4px;
	padding: 3px 10px;
	font-size: 11px;
	font-weight: 700;
	color: #fff;
	cursor: pointer;
	text-transform: uppercase;
	letter-spacing: .5px;
	line-height: 1.4;
	transition: opacity .15s, border-color .15s;
	opacity: 0.55;
}
.op-status-btn:hover { opacity: 0.8; }
.op-status-btn.active { opacity: 1; border-color: rgba(0,0,0,.35); }
/* Filter bar */
.op-filter-bar {
	display: flex;
	align-items: center;
	gap: 10px;
	padding: 6px 0 10px;
	flex-wrap: wrap;
}
.op-group-filters {
	display: inline-flex;
	gap: 4px;
	flex-wrap: wrap;
}
.op-group-filter-btn {
	border: none;
	border-radius: 4px;
	padding: 3px 10px;
	font-size: 11px;
	font-weight: 700;
	color: #fff;
	cursor: pointer;
	text-transform: uppercase;
	letter-spacing: .3px;
	line-height: 1.4;
	background-color: #4a8cdb;
	transition: opacity .15s;
	opacity: 0.55;
}
.op-group-filter-btn:hover { opacity: 0.8; }
.op-group-filter-btn.active { opacity: 1; background-color: #2a7fff; }
.op-text-filter {
	border: 1px solid #ccc;
	border-radius: 4px;
	padding: 3px 8px;
	font-size: 12px;
	line-height: 1.4;
	width: 130px;
	outline: none;
}
.op-text-filter:focus { border-color: #80bdff; box-shadow: 0 0 0 2px rgba(0,123,255,.15); }
/* Edit mode button */
.op-edit-btn {
	border: 1px solid #ccc;
	border-radius: 4px;
	background: #fff;
	padding: 3px 8px;
	font-size: 14px;
	cursor: pointer;
	color: #6c757d;
	line-height: 1;
	transition: background .15s, color .15s;
}
.op-edit-btn:hover { background: #e9ecef; }
.op-edit-btn.active { background: #0d6efd; color: #fff; border-color: #0d6efd; }
/* Transfer mode toggle */
.op-transfer-mode {
	display: inline-flex;
	align-items: center;
	gap: 5px;
	margin-left: auto;
	font-size: 12px;
	color: #555;
	white-space: nowrap;
}
.op-transfer-mode-label {
	font-weight: 600;
	color: #444;
}
.op-transfer-mode-btn {
	padding: 2px 10px;
	border: 1px solid #ccc;
	border-radius: 4px;
	background: #fff;
	font-size: 11px;
	font-weight: 600;
	cursor: pointer;
	color: #555;
	transition: background .15s, color .15s, border-color .15s;
}
.op-transfer-mode-btn:hover { background: #e9ecef; }
.op-transfer-mode-btn.active { background: #0d6efd; color: #fff; border-color: #0d6efd; }
/* Context menu */
.op-ctx-menu {
	position: fixed;
	z-index: 9990;
	background: #fff;
	border: 1px solid #d0d7de;
	border-radius: 6px;
	box-shadow: 0 8px 24px rgba(0,0,0,.18);
	min-width: 165px;
	padding: 4px 0;
	display: none;
	margin: 0;
}
.op-ctx-header {
	font-size: 11px;
	color: #888;
	padding: 3px 14px 2px;
	text-transform: uppercase;
	letter-spacing: .5px;
	font-weight: 600;
	user-select: none;
}
.op-ctx-separator {
	height: 1px;
	background: #e0e0e0;
	margin: 4px 0;
}
.op-ctx-item {
	display: flex;
	align-items: center;
	gap: 8px;
	padding: 7px 14px;
	font-size: 13px;
	cursor: pointer;
	color: #24292f;
	white-space: nowrap;
	border: none;
	background: none;
	width: 100%;
	text-align: left;
	line-height: 1.3;
}
.op-ctx-item:hover { background: #f0f6ff; }
.op-ctx-item .op-ctx-icon { font-size: 12px; opacity: .7; flex-shrink: 0; }
.op-ctx-danger { color: #c9242d; }
.op-ctx-danger:hover { background: #fff1f0; }
.op-ext-block {
	display: flex;
	width: 235px;
	margin: 0 8px 8px 0;
	border-style: solid;
	border-width: 1px 3px;
	border-radius: 5px;
	border-color: #b9c5d8;
	background-color: #e5eaf5;
	box-shadow: 0 0 3px #c8cdd9;
	position: relative;
	overflow: hidden;
	user-select: none;
	cursor: default;
}
.op-ext-icon {
	display: flex;
	align-items: center;
	justify-content: center;
	min-width: 47px;
	width: 47px;
	background-color: #e5eaf5;
	border-radius: 4px 0 0 4px;
	color: #7a8499;
	font-size: 26px;
	padding: 4px 0;
}
.op-ext-status-icon {
	font-size: 28px;
	line-height: 1;
	color: inherit;
}
.op-ext-info {
	flex: 1;
	padding: 5px 8px 5px 8px;
	background: #fff;
	border-radius: 0 3px 3px 0;
	font-family: arial, sans-serif;
	font-size: 10px;
	min-width: 0;
	position: relative;
	min-height: 50px;
}
.op-ext-number     { font-size: 12px; font-weight: bold; color: #3164AD; line-height: 1.4; }
.op-ext-name       { font-size: 10px; color: #444; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.op-ext-state-info { font-size: 10px; color: #555; margin-top: 3px; }
.op-ext-info.op-has-live-call { padding-right: 78px; padding-bottom: 15px; box-sizing: border-box; }
.op-ext-info.op-has-live-call .op-ext-state-info { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.op-ext-mine-label { position: absolute; top: 2px; right: 4px; font-size: 9px; color: #0d6efd; font-weight: 600; }
.op-ext-dial-wrap  { position: absolute; top: 22px; right: 3px; }
.op-ext-dial-toggle {
	border: none;
	background: transparent;
	padding: 0;
	margin: 0;
	line-height: 0;
	cursor: pointer;
}
.op-ext-dial-toggle img { display: block; }
.op-ext-dial-input {
	position: absolute;
	top: -1px;
	right: 18px;
	width: 100px;
	min-width: 100px;
	max-width: 100px;
	height: 20px;
	padding: 1px 6px;
	font-size: 12px;
	border: 1px solid #b9c5d8;
	border-radius: 2px;
	background-color: #fff;
	text-align: center;
}
.op-ext-call-meta {
	position: absolute;
	top: 2px;
	right: 20px;
	display: flex;
	align-items: center;
	gap: 6px;
}
.op-ext-call-direction {
	width: 12px;
	height: 12px;
	border: none;
}
.op-ext-call-duration {
	font-size: 12px;
	color: #4a4a4a;
	line-height: 1;
}
.op-ext-call-actions {
	position: absolute;
	bottom: 2px;
	right: 18px;
	display: flex;
	align-items: center;
	gap: 5px;
}
.op-ext-action-icon {
	width: 12px;
	height: 12px;
	border: none;
	cursor: pointer;
}
body.op-dragging, body.op-dragging * {
	cursor: none !important;
}
/* user status: available — green */
.op-ext-available               { border-color: #28a745; background-color: #d4edda; }
.op-ext-available .op-ext-icon  { background-color: #c3e6cb; }
.op-ext-available .op-ext-icon .op-ext-status-icon  { color: #1e7e34; }
.op-ext-available .op-ext-info  { background-color: #eaf6ec; }
/* user status: on break — gold */
.op-ext-on-break               { border-color: #b8860b; background-color: #fdf3d7; }
.op-ext-on-break .op-ext-icon  { background-color: #f5e6b8; }
.op-ext-on-break .op-ext-icon .op-ext-status-icon  { color: #8a6508; }
.op-ext-on-break .op-ext-info  { background-color: #fef9eb; }
/* user status: do not disturb — red */
.op-ext-dnd               { border-color: #dc3545; background-color: #f8d7da; }
.op-ext-dnd .op-ext-icon  { background-color: #f1b0b7; }
.op-ext-dnd .op-ext-icon .op-ext-status-icon  { color: #a71d2a; }
.op-ext-dnd .op-ext-info  { background-color: #fce4e7; }
/* registered (no explicit status / no user attached) — blue */
.op-ext-registered               { border-color: #4a8cdb; background-color: #d6e9f8; }
.op-ext-registered .op-ext-icon  { background-color: #c3ddf2; }
.op-ext-registered .op-ext-icon .op-ext-status-icon  { color: #2b6cb0; }
.op-ext-registered .op-ext-info  { background-color: #eaf3fc; }
/* user status: logged out — grey */
.op-ext-logged-out               { border-color: #9da5ae; background-color: #e2e3e5; }
.op-ext-logged-out .op-ext-icon  { background-color: #d6d8db; }
.op-ext-logged-out .op-ext-icon .op-ext-status-icon  { color: #1e7e34; }
.op-ext-logged-out .op-ext-info  { background-color: #f0f1f2; }
.op-ext-logged-out .op-ext-number { color: #888; }
.op-ext-logged-out .op-ext-name  { color: #999; }
/* unregistered — grey with muted content */
.op-ext-unregistered               { border-color: #9da5ae; background-color: #e2e3e5; cursor: not-allowed; }
.op-ext-unregistered .op-ext-icon  { background-color: #d6d8db; }
.op-ext-unregistered .op-ext-icon .op-ext-status-icon  { color: #6c757d; opacity: .4; filter: grayscale(100%); }
.op-ext-unregistered .op-ext-info  { background-color: #f0f1f2; color: #999; }
.op-ext-unregistered .op-ext-number { color: #999; }
.op-ext-unregistered .op-ext-name  { color: #aaa; }
/* call state: ringing — blue */
.op-ext-ringing               { border-color: #41b9eb; background-color: #a8dbf0; }
.op-ext-ringing .op-ext-icon  { background-color: #a8dbf0; }
.op-ext-ringing .op-ext-icon .op-ext-status-icon  { color: #0e6882; }
.op-ext-ringing .op-ext-info  { background-color: #d1f1ff; }
/* call state: active (on call) — bright green */
.op-ext-active                { border-color: #77d779; background-color: #baf4bb; }
.op-ext-active .op-ext-icon   { background-color: #baf4bb; }
.op-ext-active .op-ext-icon .op-ext-status-icon   { color: #2a7a2b; }
.op-ext-active .op-ext-info   { background-color: #e1ffe2; }
/* call state: held — teal */
.op-ext-held                  { border-color: #5bbfd1; background-color: #b3e5ee; }
.op-ext-held .op-ext-icon     { background-color: #b3e5ee; }
.op-ext-held .op-ext-icon .op-ext-status-icon     { color: #1a6c7a; }
.op-ext-held .op-ext-info     { background-color: #ddf4f8; }
/* mine highlight */
.op-ext-mine       { border-width: 2px 3px !important; border-color: #0d6efd !important; }
/* drop target */
.op-ext-drop-over  { box-shadow: 0 0 0 3px #0d6efd; }
.op-ext-drop-over .op-ext-info { background-color: #cfe2ff !important; }
/* section labels */
.op-ext-section-label { font-weight: 600; font-size: .85em; color: #6c757d; margin: 8px 0 4px; width: 100%; }
/* My Extensions container — own line above other groups */
#my_extensions_container:not(:empty) {
	margin-bottom: 14px;
	padding-bottom: 10px;
}
/* call group cards */
.op-group-card {
	border: 1px solid #d0d8e5;
	border-radius: 5px;
	background-color: #fff;
	box-shadow: 0 1px 3px #d0d8e5;
	margin-bottom: 14px;
	overflow: hidden;
	display: inline-flex;
	vertical-align: top;
	margin-right: 14px;
}
.op-group-card.op-hidden { display: none; }

/* Card frame orientation by label position */
.op-group-card[data-position="left"]   { flex-direction: row; }
.op-group-card[data-position="right"]  { flex-direction: row-reverse; }
.op-group-card[data-position="top"]    { flex-direction: column; }
.op-group-card[data-position="bottom"] { flex-direction: column-reverse; }
.op-group-card[data-position="hidden"] { flex-direction: row; }
/* Edit mode: cards grid container */
#extensions_container {
	transition: background .2s;
}
#extensions_container.op-edit-mode .op-group-card {
	cursor: grab;
	border: 2px dashed #80bdff;
}
#extensions_container.op-edit-mode .op-group-card.sortable-ghost {
	opacity: .4;
}
/* In edit mode, force hidden headers visible so Sortable has a drag handle */
#extensions_container.op-edit-mode .op-group-card[data-position="hidden"] .op-group-card-header {
	display: flex;
	min-width: 18px;
	padding: 4px 2px;
	cursor: grab;
	background: #d0d8e5;
	writing-mode: vertical-rl;
	text-orientation: mixed;
	transform: rotate(180deg);
	align-items: center;
	justify-content: center;
	font-size: 11px;
	color: #888;
}
/* Card header - default/left side orientation with vertical text */
.op-group-card-header {
	background-color: #e5e9f0;
	padding: 8px 4px;
	font-size: 12px;
	font-weight: 600;
	color: #444;
	border-right: 1px solid #d0d8e5;
	font-family: Calibri, Candara, Segoe, 'Segoe UI', Optima, Arial, sans-serif;
	writing-mode: vertical-rl;
	text-orientation: mixed;
	transform: rotate(180deg);
	letter-spacing: .6px;
	text-transform: uppercase;
	white-space: nowrap;
	display: flex;
	align-items: center;
	justify-content: center;
	min-width: 34px;
}

/* Top position - horizontal text, border at bottom */
.op-group-card[data-position="top"] .op-group-card-header {
	writing-mode: horizontal-tb;
	transform: none;
	border-right: none;
	border-bottom: 1px solid #d0d8e5;
	min-width: auto;
	padding: 6px 12px;
}

/* Right position - vertical text, border at left */
.op-group-card[data-position="right"] .op-group-card-header {
	writing-mode: vertical-rl;
	text-orientation: mixed;
	transform: rotate(0deg);
	border-right: none;
	border-left: 1px solid #d0d8e5;
	min-width: 34px;
}

/* Bottom position - horizontal text, border at top */
.op-group-card[data-position="bottom"] .op-group-card-header {
	writing-mode: horizontal-tb;
	transform: none;
	border-right: none;
	border-top: 1px solid #d0d8e5;
	min-width: auto;
	padding: 6px 12px;
}

/* Hidden position - no header visible */
.op-group-card[data-position="hidden"] .op-group-card-header {
	display: none;
}
/* Tooltip on hover - show group name in title attribute */
.op-group-card:hover {
	cursor: help;
}
/* Hide text for "My Extensions" but keep grey shading */
.op-group-card-header.op-hidden-text {
	color: transparent;
	text-shadow: none;
}
.op-group-card-body {
	padding: 10px 8px 4px;
	flex: 1;
}

/* Top row in Extensions tab: My Extensions + Parked Calls */
.op-top-row {
	display: flex;
	align-items: stretch;
	gap: 14px;
	flex-wrap: wrap;
	margin-bottom: 14px;
}
#my_extensions_container,
#parked_side_container {
	flex: 1 1 420px;
	min-width: 320px;
}
#my_extensions_container:not(:empty),
#parked_side_container:not(:empty) {
	margin-bottom: 0;
	padding-bottom: 0;
}

/* Parked calls side panel */
.op-parked-card {
	border: 1px solid #d0d8e5;
	border-radius: 5px;
	background-color: #fff;
	box-shadow: 0 1px 3px #d0d8e5;
	overflow: hidden;
}
.op-parked-header {
	display: flex;
	align-items: center;
	justify-content: space-between;
	background-color: #e5e9f0;
	border-bottom: 1px solid #d0d8e5;
	padding: 6px 10px;
	font-size: 12px;
	font-weight: 700;
	color: #444;
	text-transform: uppercase;
	letter-spacing: .4px;
}
.op-parked-badge {
	font-size: 11px;
	font-weight: 600;
	background: #6c757d;
	color: #fff;
	padding: 2px 6px;
	border-radius: 10px;
}
.op-parked-list {
	padding: 8px;
	max-height: 280px;
	overflow: auto;
}
.op-parked-item {
	border: 1px solid #d0d8e5;
	border-radius: 4px;
	background: #f8fbff;
	padding: 6px 8px;
	margin-bottom: 7px;
	cursor: grab;
	user-select: none;
	line-height: 1.25;
}
.op-parked-item:last-child { margin-bottom: 0; }
.op-parked-item:hover { background: #eef5ff; border-color: #80bdff; }
.op-parked-main { font-size: 12px; font-weight: 600; color: #2c3e50; }
.op-parked-sub { font-size: 11px; color: #555; margin-top: 2px; }
.op-parked-drop-over {
	box-shadow: 0 0 0 3px #0d6efd;
	border-color: #0d6efd;
}
.op-parked-empty {
	padding: 10px;
	font-size: 12px;
	color: #6c757d;
	font-style: italic;
}

@media (max-width: 980px) {
	#my_extensions_container,
	#parked_side_container {
		flex-basis: 100%;
		min-width: 0;
	}
}

/* HTML5 dialog styles */
.op-dialog {
	border: 1px solid #ccc;
	border-radius: 8px;
	padding: 0;
	max-width: 400px;
	width: 90%;
	box-shadow: 0 4px 20px rgba(0,0,0,0.3);
}
.op-dialog::backdrop {
	background: rgba(0,0,0,0.4);
}
.op-dialog-sm {
	max-width: 320px;
}
.op-dialog-header {
	display: flex;
	align-items: center;
	justify-content: space-between;
	padding: 12px 16px;
	border-bottom: 1px solid #ddd;
}
.op-dialog-header h5 {
	margin: 0;
	font-size: 1.1rem;
}
.op-dialog-close {
	background: none;
	border: none;
	font-size: 1.4rem;
	cursor: pointer;
	line-height: 1;
	padding: 0 4px;
	opacity: 0.6;
}
.op-dialog-close:hover { opacity: 1; }
.op-dialog-body {
	padding: 16px;
}
.op-dialog-footer {
	display: flex;
	justify-content: flex-end;
	gap: 8px;
	padding: 12px 16px;
	border-top: 1px solid #ddd;
}
.op-dialog-input {
	width: 100%;
	padding: 6px 10px;
	border: 1px solid #ccc;
	border-radius: 4px;
	font-size: 0.95rem;
	box-sizing: border-box;
}
.op-dialog-input:focus {
	outline: 2px solid #0d6efd;
	outline-offset: -1px;
}
.op-dialog-actions {
	display: flex;
	flex-direction: column;
	gap: 8px;
}
.op-dialog-btn {
	display: block;
	width: 100%;
	padding: 8px 16px;
	border: none;
	border-radius: 4px;
	font-size: 0.95rem;
	cursor: pointer;
	color: #fff;
}
.op-btn-primary   { background: #0d6efd; }
.op-btn-primary:hover { background: #0b5ed7; }
.op-btn-success   { background: #198754; }
.op-btn-success:hover { background: #157347; }
.op-btn-info      { background: #0dcaf0; color: #000; }
.op-btn-info:hover { background: #31d2f2; }
.op-btn-secondary { background: #6c757d; }
.op-btn-secondary:hover { background: #5c636a; }
</style>

<!-- Bootstrap tabs: Extensions | Calls | Conferences | Agents -->
<ul class="nav nav-tabs" id="lop_tabs" role="tablist" style="margin-bottom:16px;">
<?php if ($perm['operator_panel_extensions']): ?>
	<li class="nav-item" role="presentation">
		<button class="nav-link active" id="tab-extensions" data-bs-toggle="tab" data-bs-target="#panel-extensions"
			type="button" role="tab" aria-controls="panel-extensions" aria-selected="true">
			<?= htmlspecialchars($text['tab-extensions'] ?? 'Extensions') ?>
			<span id="extensions_count" class="badge ms-1" style="background:#6c757d;color:#fff;">0</span>
		</button>
	</li>
<?php endif; ?>
<?php if ($perm['operator_panel_calls']): ?>
	<li class="nav-item" role="presentation">
		<button class="nav-link<?= !$perm['operator_panel_extensions'] ? ' active' : '' ?>" id="tab-calls" data-bs-toggle="tab" data-bs-target="#panel-calls"
			type="button" role="tab" aria-controls="panel-calls" aria-selected="<?= !$perm['operator_panel_extensions'] ? 'true' : 'false' ?>">
			<?= htmlspecialchars($text['tab-calls'] ?? 'Calls') ?>
			<span id="calls_count" class="badge ms-1" style="background:#6c757d;color:#fff;">0</span>
		</button>
	</li>
<?php endif; ?>
<?php if ($perm['operator_panel_extensions']): ?>
	<li class="nav-item" role="presentation">
		<button class="nav-link" id="tab-parked" data-bs-toggle="tab" data-bs-target="#panel-parked"
			type="button" role="tab" aria-controls="panel-parked" aria-selected="false">
			<?= htmlspecialchars($text['label-parked_calls'] ?? 'Parked Calls') ?>
			<span id="parked_count" class="badge ms-1" style="background:#6c757d;color:#fff;">0</span>
		</button>
	</li>
<?php endif; ?>
<?php if ($perm['operator_panel_conferences']): ?>
	<li class="nav-item" role="presentation">
		<button class="nav-link" id="tab-conferences" data-bs-toggle="tab" data-bs-target="#panel-conferences"
			type="button" role="tab" aria-controls="panel-conferences" aria-selected="false">
			<?= htmlspecialchars($text['tab-conferences'] ?? 'Conferences') ?>
			<span id="conferences_count" class="badge ms-1" style="background:#6c757d;color:#fff;">0</span>
		</button>
	</li>
<?php endif; ?>
<?php if ($perm['operator_panel_agents']): ?>
	<li class="nav-item" role="presentation">
		<button class="nav-link" id="tab-agents" data-bs-toggle="tab" data-bs-target="#panel-agents"
			type="button" role="tab" aria-controls="panel-agents" aria-selected="false">
			<?= htmlspecialchars($text['tab-agents'] ?? 'Agents') ?>
			<span id="agents_count" class="badge ms-1" style="background:#6c757d;color:#fff;">0</span>
		</button>
	</li>
<?php endif; ?>
</ul>

<div class="tab-content" id="lop_tab_content">

	<!-- EXTENSIONS TAB -->
<?php if ($perm['operator_panel_extensions']): ?>
	<div class="tab-pane fade<?= $perm['operator_panel_extensions'] ? ' show active' : '' ?>" id="panel-extensions" role="tabpanel" aria-labelledby="tab-extensions">
		<!-- Group filter bar -->
		<div id="extensions_filter_bar" class="op-filter-bar" style="display:none;">
			<button type="button" class="op-edit-btn" id="edit_mode_btn" onclick="toggle_edit_mode()" title="<?= htmlspecialchars($text['label-edit_mode'] ?? 'Edit Mode') ?>">
				<i class="fa-solid fa-pen-to-square"></i>
			</button>
			<div id="group_filter_buttons" class="op-group-filters"></div>
			<input type="text" id="extensions_text_filter" class="op-text-filter" placeholder="<?= htmlspecialchars($text['label-filter'] ?? 'Filter...') ?>" oninput="apply_extension_filters()">
		<?php if ($perm['operator_panel_manage']): ?>
		<div class="op-transfer-mode" id="transfer_mode_control">
			<span class="op-transfer-mode-label"><?= htmlspecialchars($text['label-transfer_mode'] ?? 'Transfer') ?>:</span>
			<?php if ($perm['operator_panel_transfer_attended']): ?>
			<button type="button" class="op-transfer-mode-btn active" id="btn_transfer_mode_toggle" onclick="toggle_transfer_mode()"
				title="<?= htmlspecialchars($text['label-blind_transfer_title'] ?? 'Blind transfer: immediately connect the call to the destination') ?>">
				<?= htmlspecialchars($text['label-blind_transfer'] ?? 'Blind') ?>
			</button>
			<?php else: ?>
			<span class="op-transfer-mode-btn active" style="cursor:default;" title="<?= htmlspecialchars($text['label-blind_transfer_title'] ?? 'Blind transfer: immediately connect the call to the destination') ?>">
				<?= htmlspecialchars($text['label-blind_transfer'] ?? 'Blind') ?>
			</span>
			<?php endif; ?>
		</div>
		<?php endif; ?>
		</div>
		<div class="op-top-row" id="extensions_top_row">
			<div id="my_extensions_container"></div>
			<div id="parked_side_container">
				<p class="text-muted"><?= htmlspecialchars($text['label-connecting'] ?? 'Connecting...') ?></p>
			</div>
		</div>
		<div id="extensions_container">
			<p class="text-muted"><?= htmlspecialchars($text['label-connecting'] ?? 'Connecting...') ?></p>
		</div>
	</div>
<?php endif; ?>

	<!-- CALLS TAB -->
<?php if ($perm['operator_panel_calls']): ?>
	<div class="tab-pane fade<?= !$perm['operator_panel_extensions'] && $perm['operator_panel_calls'] ? ' show active' : '' ?>" id="panel-calls" role="tabpanel" aria-labelledby="tab-calls">
		<div id="calls_filter_bar" class="op-filter-bar" style="display:none;">
			<div id="group_filter_buttons_calls" class="op-group-filters"></div>
			<input type="text" id="calls_text_filter" class="op-text-filter" placeholder="<?= htmlspecialchars($text['label-filter'] ?? 'Filter...') ?>" oninput="apply_calls_filters()">
		</div>
		<div id="calls_container">
			<p class="text-muted"><?= htmlspecialchars($text['label-connecting'] ?? 'Connecting...') ?></p>
		</div>
	</div>
<?php endif; ?>

	<!-- PARKED CALLS TAB -->
<?php if ($perm['operator_panel_extensions']): ?>
	<div class="tab-pane fade" id="panel-parked" role="tabpanel" aria-labelledby="tab-parked">
		<div id="parked_filter_bar" class="op-filter-bar" style="display:none;">
			<div id="group_filter_buttons_parked" class="op-group-filters"></div>
			<input type="text" id="parked_text_filter" class="op-text-filter" placeholder="<?= htmlspecialchars($text['label-filter'] ?? 'Filter...') ?>" oninput="apply_parked_filters()">
		</div>
		<div id="parked_container">
			<p class="text-muted"><?= htmlspecialchars($text['label-connecting'] ?? 'Connecting...') ?></p>
		</div>
	</div>
<?php endif; ?>

	<!-- CONFERENCES TAB -->
<?php if ($perm['operator_panel_conferences']): ?>
	<div class="tab-pane fade" id="panel-conferences" role="tabpanel" aria-labelledby="tab-conferences">
		<div id="conferences_filter_bar" class="op-filter-bar" style="display:none;">
			<div id="group_filter_buttons_conferences" class="op-group-filters"></div>
			<input type="text" id="conferences_text_filter" class="op-text-filter" placeholder="<?= htmlspecialchars($text['label-filter'] ?? 'Filter...') ?>" oninput="apply_conferences_filters()">
		</div>
		<div id="conferences_container">
			<p class="text-muted"><?= htmlspecialchars($text['label-connecting'] ?? 'Connecting...') ?></p>
		</div>
	</div>
<?php endif; ?>

	<!-- AGENTS TAB -->
<?php if ($perm['operator_panel_agents']): ?>
	<div class="tab-pane fade" id="panel-agents" role="tabpanel" aria-labelledby="tab-agents">
		<div id="agents_filter_bar" class="op-filter-bar" style="display:none;">
			<div id="group_filter_buttons_agents" class="op-group-filters"></div>
			<input type="text" id="agents_text_filter" class="op-text-filter" placeholder="<?= htmlspecialchars($text['label-filter'] ?? 'Filter...') ?>" oninput="apply_agents_filters()">
		</div>
		<div id="agents_container">
			<p class="text-muted"><?= htmlspecialchars($text['label-connecting'] ?? 'Connecting...') ?></p>
		</div>
	</div>
<?php endif; ?>

</div>

<!-- Right-click context menu -->
<div id="op_context_menu" class="op-ctx-menu" role="menu" aria-label="<?= htmlspecialchars($text['label-actions'] ?? 'Actions') ?>"></div>

<!-- Transfer dialog -->
<dialog id="transfer_dialog" class="op-dialog">
	<div class="op-dialog-header">
		<h5><?= htmlspecialchars($text['label-transfer'] ?? 'Transfer Call') ?></h5>
		<button type="button" class="op-dialog-close" onclick="document.getElementById('transfer_dialog').close()" aria-label="Close">&times;</button>
	</div>
	<div class="op-dialog-body">
		<label for="transfer_destination" style="font-weight:600; display:block; margin-bottom:4px;">
			<?= htmlspecialchars($text['label-destination'] ?? 'Destination') ?>
		</label>
		<input type="text" id="transfer_destination" class="op-dialog-input" placeholder="1001" autocomplete="off">
		<input type="hidden" id="transfer_uuid">
	</div>
	<div class="op-dialog-footer">
		<button type="button" class="op-dialog-btn op-btn-secondary" onclick="document.getElementById('transfer_dialog').close()">
			<?= htmlspecialchars($text['button-cancel'] ?? 'Cancel') ?>
		</button>
		<button type="button" class="op-dialog-btn op-btn-primary" onclick="confirm_transfer()">
			<?= htmlspecialchars($text['button-transfer'] ?? 'Transfer') ?>
		</button>
	</div>
</dialog>

<!-- Ringing Action dialog -->
<dialog id="ringing_action_dialog" class="op-dialog op-dialog-sm">
	<div class="op-dialog-header">
		<h5><?= htmlspecialchars($text['label-choose_action'] ?? 'Choose Action') ?></h5>
		<button type="button" class="op-dialog-close" onclick="document.getElementById('ringing_action_dialog').close()" aria-label="Close">&times;</button>
	</div>
	<div class="op-dialog-body" style="text-align:center;">
		<p id="ringing_action_description" style="margin-bottom:12px;"></p>
		<div class="op-dialog-actions">
			<button type="button" class="op-dialog-btn op-btn-success" id="ringing_action_intercept">
				<?= htmlspecialchars($text['button-intercept'] ?? 'Intercept') ?>
			</button>
			<button type="button" class="op-dialog-btn op-btn-primary" id="ringing_action_call">
				<?= htmlspecialchars($text['button-call'] ?? 'Call') ?>
			</button>
			<button type="button" class="op-dialog-btn op-btn-info" id="ringing_action_eavesdrop">
				<?= htmlspecialchars($text['label-eavesdrop'] ?? 'Eavesdrop') ?>
			</button>
			<button type="button" class="op-dialog-btn op-btn-secondary" onclick="document.getElementById('ringing_action_dialog').close()">
				<?= htmlspecialchars($text['button-cancel'] ?? 'Cancel') ?>
			</button>
		</div>
	</div>
</dialog>

<br><br>

<?php
	require_once "resources/footer.php";
