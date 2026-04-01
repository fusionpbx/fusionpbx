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

// Get the status for the current user
	$sql = 'select user_status from v_users where user_uuid = :user_uuid';
	$parameters = ['user_uuid' => $_SESSION['user_uuid'] ?? ''];
	$user_status = $database->select($sql, $parameters, 'column') ?? '';

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

// Cache-busting hash for CSS
	$operator_panel_css_file = $settings->get('theme', 'operator_panel_css_file', 'operator_panel.css');
	$operator_panel_css_file = preg_replace('/[^a-z0-9_\-\.]/i', '', $operator_panel_css_file);
	$operator_panel_css_file = realpath(__DIR__ . "/resources/css/$operator_panel_css_file");
	$operator_panel_css_hash = md5_file($operator_panel_css_file);
	echo "<link rel='stylesheet' href='resources/css/" . basename($operator_panel_css_file) . "?v=$operator_panel_css_hash'>\n";

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
	const user_status = <?= json_encode($user_status) ?>;

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

	// Session ID used in contact photo URLs for cache control
	const contact_image_sid = <?= json_encode(session_id()) ?>;

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
			<?= htmlspecialchars($text['label-parked_calls'] ?? 'Parked') ?>
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
			<!-- <div class="op-transfer-mode" id="transfer_mode_control"> -->
				<!-- <span class="op-transfer-mode-label"><?= htmlspecialchars($text['label-transfer_mode'] ?? 'Transfer') ?>:</span> -->
				<?php if ($perm['operator_panel_transfer_attended']): ?>
				<!-- <button type="button" class="op-transfer-mode-btn active" id="btn_transfer_mode_toggle" onclick="toggle_transfer_mode()" -->
					<!-- title="<?= htmlspecialchars($text['label-blind_transfer_title'] ?? 'Blind transfer: immediately connect the call to the destination') ?>"> -->
					<!-- <?= htmlspecialchars($text['label-blind_transfer'] ?? 'Blind') ?> -->
				<!-- </button> -->
				<?php else: ?>
				<!-- <span class="op-transfer-mode-btn active" style="cursor:default;" title="<?= htmlspecialchars($text['label-blind_transfer_title'] ?? 'Blind transfer: immediately connect the call to the destination') ?>"> -->
					<!-- <?= htmlspecialchars($text['label-blind_transfer'] ?? 'Blind') ?> -->
				<!-- </span> -->
				<?php endif; ?>
			<!-- </div> -->
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

	<!-- PARKED TAB -->
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

<!-- Attended transfer consultation bar -->
<div id="attended_transfer_bar" class="op-att-bar" style="display:none;">
	<i class="fa-solid fa-phone-volume op-att-icon"></i>
	<span class="op-att-label"></span>
	<button type="button" class="op-att-btn op-att-complete" onclick="complete_attended_transfer()">
		<i class="fa-solid fa-check"></i> <?= htmlspecialchars($text['button-complete_transfer'] ?? 'Complete Transfer') ?>
	</button>
	<button type="button" class="op-att-btn op-att-cancel" onclick="cancel_attended_transfer()">
		<i class="fa-solid fa-xmark"></i> <?= htmlspecialchars($text['button-cancel_transfer'] ?? 'Cancel') ?>
	</button>
</div>

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
		<input type="hidden" id="transfer_source_extension">
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
