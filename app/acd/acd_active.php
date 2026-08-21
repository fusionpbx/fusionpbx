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
	Portions created by the Initial Developer are Copyright (C) 2010-2024
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	BlueCloud <support@blueuc.com>

	---------------------------------------------------------------------------
	Advanced Call Distribution — Active Calls / Queue Monitor

	Live view of:
	  • Waiting callers      — rows in v_acd_sessions
	  • Active agent legs    — rows in v_acd_busy
	                           (ringing AND in-conversation legs; the queue
	                            engine inserts on originate, deletes on the
	                            winner once the bridged call ends, and sweeps
	                            losers per-iteration)

	Caller-ID is enriched live from FreeSWITCH via Event Socket `uuid_getvar`,
	so this page has zero schema dependency on storing those values. Auto-
	refreshes every 3 seconds.
*/

//includes
	require_once dirname(__DIR__, 2) . "/resources/require.php";
	require_once "resources/check_auth.php";

//permission gate — re-use the view permission used by the list page
	if (!permission_exists('acd_view')) {
		echo "access denied"; exit;
	}

//database + i18n
	$database = database::new();
	$language = new text;
	$text = $language->get(null, 'app/acd');

//context
	$domain_uuid = $_SESSION['domain_uuid'] ?? '';
	$show_all    = ($_GET['show'] ?? '') === 'all' && permission_exists('acd_all');

//helper — pretty seconds → "1h 02m 13s"
	function fmt_duration($secs) {
		$secs = (int)$secs;
		if ($secs < 0) $secs = 0;
		$h = intdiv($secs, 3600);
		$m = intdiv($secs % 3600, 60);
		$s = $secs % 60;
		if ($h > 0) return sprintf('%dh %02dm %02ds', $h, $m, $s);
		if ($m > 0) return sprintf('%dm %02ds', $m, $s);
		return sprintf('%ds', $s);
	}

//helper — safe ESL value (treat _undef_ / error responses as empty)
	function esl_val($raw) {
		$v = trim((string)$raw);
		if ($v === '' || $v === '_undef_') return '';
		if (stripos($v, '-ERR') === 0 || stripos($v, 'error') === 0) return '';
		return $v;
	}

//connect to FreeSWITCH event socket once — used to enrich every row below.
//If it fails we just render UUIDs without CID enrichment.
	$esl = new event_socket;
	$esl_connected = $esl->connect();

//load active sessions (waiting callers)
	$params = [];
	$sql = "select s.session_uuid, s.queue_uuid, s.caller_channel_uuid, s.entered_at, "
		 . "       s.domain_uuid, s.winner_leg_uuid, s.winner_agent_extension, s.answered_at, "
		 . "       q.queue_name, q.queue_extension, "
		 . "       extract(epoch from (now() - s.entered_at))::int as seconds_waiting, "
		 . "       extract(epoch from (now() - s.answered_at))::int as seconds_talking "
		 . "from v_acd_sessions s "
		 . "join v_acd_queues q on q.queue_uuid = s.queue_uuid ";
	if (!$show_all) {
		$sql .= "where s.domain_uuid = :domain_uuid ";
		$params['domain_uuid'] = $domain_uuid;
	}
	$sql .= "order by s.entered_at asc";
	$sessions = $database->select($sql, $params, 'all');
	if (!is_array($sessions)) { $sessions = []; }
	unset($sql, $params);

//load busy rows (ringing + in-call agent legs)
	$params = [];
	$sql = "select b.busy_uuid, b.queue_uuid, b.domain_uuid, b.agent_extension, "
		 . "       b.destination, b.leg_uuid, b.started_at, "
		 . "       q.queue_name, q.queue_extension, "
		 . "       extract(epoch from (now() - b.started_at))::int as seconds_on_leg "
		 . "from v_acd_busy b "
		 . "join v_acd_queues q on q.queue_uuid = b.queue_uuid ";
	if (!$show_all) {
		$sql .= "where b.domain_uuid = :domain_uuid ";
		$params['domain_uuid'] = $domain_uuid;
	}
	$sql .= "order by b.started_at asc";
	$legs = $database->select($sql, $params, 'all');
	if (!is_array($legs)) { $legs = []; }
	unset($sql, $params);

//classify each leg as "ringing" / "in_call" / "gone" via ESL
	foreach ($legs as &$leg) {
		$leg['state'] = 'unknown';
		if ($esl_connected) {
			$exists = esl_val($esl->request("api uuid_exists " . $leg['leg_uuid']));
			if ($exists === 'true') {
				$at = esl_val($esl->request("api uuid_getvar " . $leg['leg_uuid'] . " answered_time"));
				$leg['state'] = ($at !== '' && $at !== '0') ? 'in_call' : 'ringing';
			} else {
				$leg['state'] = 'gone';
			}
		}
	}
	unset($leg);

//enrich each caller row with caller_id from ESL
	foreach ($sessions as &$s) {
		$s['cid_name']   = '';
		$s['cid_number'] = '';
		$s['exists']     = null;
		if ($esl_connected) {
			$s['cid_number'] = esl_val($esl->request("api uuid_getvar " . $s['caller_channel_uuid'] . " caller_id_number"));
			$s['cid_name']   = esl_val($esl->request("api uuid_getvar " . $s['caller_channel_uuid'] . " caller_id_name"));
			$s['exists']     = esl_val($esl->request("api uuid_exists " . $s['caller_channel_uuid'])) === 'true';
		}
	}
	unset($s);

//done with ESL
	if ($esl_connected) { $esl->close(); }

//classify sessions: a caller with a claimed winner is CONNECTED (talking to an
//agent); one without is still WAITING. The session row lives for the whole call
//and is deleted on hangup, so this is the reliable source of truth — unlike the
//_busy rows, which are removed as soon as the agent-answer intercept returns.
	$waiting   = [];
	$connected = [];
	foreach ($sessions as $s) {
		if (!empty($s['winner_leg_uuid'])) { $connected[] = $s; }
		else                               { $waiting[]   = $s; }
	}

//counts for the header
	$num_waiting   = count($waiting);
	$num_connected = count($connected);
	$num_ringing = 0;
	foreach ($legs as $l) {
		if (($l['state'] ?? '') === 'ringing') { $num_ringing++; }
	}

//page title + header
	$document['title'] = $text['title-active'] ?? 'Active Queue Calls';
	require_once "resources/header.php";

//auto-refresh
	echo "<script type='text/javascript'>setTimeout(function(){ location.reload(); }, 3000);</script>\n";

//action bar
	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>" . ($text['title-active'] ?? 'Active Queue Calls') . "</b>";
	echo "		<div class='count'>" . $num_waiting . " " . ($text['label-waiting'] ?? 'waiting') . " &middot; " . $num_connected . " " . ($text['label-connected'] ?? 'connected') . "</div>\n";
	echo "	</div>\n";
	echo "	<div class='actions'>\n";
	echo button::create([
		'type'  => 'button',
		'label' => $text['button-queues'] ?? 'Queues',
		'icon'  => $settings->get('theme', 'button_icon_back', 'arrow-left'),
		'link'  => 'acd.php',
	]);
	if (permission_exists('acd_all')) {
		if ($show_all) {
			echo button::create([
				'type'  => 'button',
				'label' => $text['button-show_domain'] ?? 'This Domain',
				'icon'  => $settings->get('theme', 'button_icon_filter', 'filter'),
				'link'  => 'acd_active.php',
			]);
		} else {
			echo button::create([
				'type'  => 'button',
				'label' => $text['button-show_all'] ?? 'Show All',
				'icon'  => $settings->get('theme', 'button_icon_all', 'th'),
				'link'  => 'acd_active.php?show=all',
			]);
		}
	}
	echo button::create([
		'type'  => 'button',
		'label' => $text['button-refresh'] ?? 'Refresh',
		'icon'  => 'sync-alt',
		'link'  => 'acd_active.php' . ($show_all ? '?show=all' : ''),
	]);
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

//ESL warning
	if (!$esl_connected) {
		echo "<br />\n";
		echo "<div class='card'>";
		echo "	<i class='fas fa-exclamation-triangle' style='color:#c80;'></i>&nbsp;";
		echo "	<b>" . ($text['warn-no_esl'] ?? 'FreeSWITCH Event Socket unreachable.') . "</b> ";
		echo escape($text['warn-no_esl_detail'] ?? 'Caller-ID and live channel state are not available. Showing raw database state only.');
		echo "</div>\n";
	}

	echo "<br />\n";

// ── Section 1 — Waiting callers ───────────────────────────────────────────────
	echo "<b>" . ($text['header-waiting'] ?? 'Callers Waiting in Queue') . "</b>\n";
	echo "<div class='card'>\n";
	echo "<table class='list'>\n";
	echo "<tr class='list-header'>\n";
	echo "	<th>" . ($text['label-queue']        ?? 'Queue')       . "</th>\n";
	echo "	<th>" . ($text['label-extension']    ?? 'Extension')   . "</th>\n";
	echo "	<th>" . ($text['label-caller_id']    ?? 'Caller ID')   . "</th>\n";
	echo "	<th>" . ($text['label-channel']      ?? 'Channel')     . "</th>\n";
	echo "	<th>" . ($text['label-entered']      ?? 'Entered')     . "</th>\n";
	echo "	<th>" . ($text['label-waiting']      ?? 'Waiting')     . "</th>\n";
	echo "</tr>\n";

	if (empty($waiting)) {
		echo "<tr class='list-row'><td colspan='6' style='text-align:center; padding:18px; color:#888;'>";
		echo escape($text['label-no_waiting'] ?? 'No callers currently waiting.');
		echo "</td></tr>\n";
	} else {
		foreach ($waiting as $s) {
			$cid_num  = $s['cid_number'] ?? '';
			$cid_name = $s['cid_name']   ?? '';
			if ($cid_num !== '' && $cid_name !== '' && $cid_name !== $cid_num) {
				$cid_disp = $cid_name . ' &lt;' . $cid_num . '&gt;';
			} elseif ($cid_num !== '') {
				$cid_disp = $cid_num;
			} elseif ($cid_name !== '') {
				$cid_disp = $cid_name;
			} else {
				$cid_disp = '&mdash;';
			}
			$ch_short = substr($s['caller_channel_uuid'], 0, 8);
			$dead     = ($s['exists'] === false);
			$row_style = $dead ? " style='opacity:.45;'" : '';

			echo "<tr class='list-row'" . $row_style . ">\n";
			echo "	<td>" . escape($s['queue_name'])            . "</td>\n";
			echo "	<td>" . escape($s['queue_extension'])       . "</td>\n";
			echo "	<td>" . $cid_disp;
			if ($dead) { echo " <span style='color:#a33; font-size:11px;'>(orphan)</span>"; }
			echo "</td>\n";
			echo "	<td title='" . escape($s['caller_channel_uuid']) . "'><code>" . escape($ch_short) . "&hellip;</code></td>\n";
			echo "	<td>" . escape(substr($s['entered_at'] ?? '', 0, 19)) . "</td>\n";
			echo "	<td><b>" . fmt_duration($s['seconds_waiting'] ?? 0) . "</b></td>\n";
			echo "</tr>\n";
		}
	}

	echo "</table>\n";
	echo "</div>\n";

	echo "<br />\n";

// ── Section 2 — Connected calls (caller bridged to an agent) ──────────────────
	echo "<b>" . ($text['header-connected'] ?? 'Connected Calls') . "</b>";
	echo " <span style='color:#888; font-size:12px;'>(" . $num_connected . " " . ($text['label-in_call'] ?? 'in call') . ")</span>\n";
	echo "<div class='card'>\n";
	echo "<table class='list'>\n";
	echo "<tr class='list-header'>\n";
	echo "	<th>" . ($text['label-queue']     ?? 'Queue')      . "</th>\n";
	echo "	<th>" . ($text['label-extension'] ?? 'Extension')  . "</th>\n";
	echo "	<th>" . ($text['label-caller_id'] ?? 'Caller ID')  . "</th>\n";
	echo "	<th>" . ($text['label-agent']     ?? 'Agent')      . "</th>\n";
	echo "	<th>" . ($text['label-answered']  ?? 'Answered')   . "</th>\n";
	echo "	<th>" . ($text['label-talk_time'] ?? 'Talk Time')  . "</th>\n";
	echo "</tr>\n";

	if (empty($connected)) {
		echo "<tr class='list-row'><td colspan='6' style='text-align:center; padding:18px; color:#888;'>";
		echo escape($text['label-no_connected'] ?? 'No connected calls.');
		echo "</td></tr>\n";
	} else {
		foreach ($connected as $s) {
			$cid_num  = $s['cid_number'] ?? '';
			$cid_name = $s['cid_name']   ?? '';
			if ($cid_num !== '' && $cid_name !== '' && $cid_name !== $cid_num) {
				$cid_disp = $cid_name . ' &lt;' . $cid_num . '&gt;';
			} elseif ($cid_num !== '') {
				$cid_disp = $cid_num;
			} elseif ($cid_name !== '') {
				$cid_disp = $cid_name;
			} else {
				$cid_disp = '&mdash;';
			}
			$dead      = ($s['exists'] === false);
			$row_style = $dead ? " style='opacity:.45;'" : '';
			echo "<tr class='list-row'" . $row_style . ">\n";
			echo "	<td>" . escape($s['queue_name'])      . "</td>\n";
			echo "	<td>" . escape($s['queue_extension']) . "</td>\n";
			echo "	<td>" . $cid_disp;
			if ($dead) { echo " <span style='color:#a33; font-size:11px;'>(orphan)</span>"; }
			echo "</td>\n";
			echo "	<td><b>" . escape($s['winner_agent_extension'] ?? '') . "</b></td>\n";
			echo "	<td>" . escape(substr($s['answered_at'] ?? '', 0, 19)) . "</td>\n";
			echo "	<td><b>" . fmt_duration($s['seconds_talking'] ?? 0) . "</b></td>\n";
			echo "</tr>\n";
		}
	}

	echo "</table>\n";
	echo "</div>\n";

	echo "<br />\n";

// ── Section 3 — Ringing agent legs ───────────────────────────────────────────
	echo "<b>" . ($text['header-active_legs'] ?? 'Ringing Agent Legs') . "</b>";
	echo " <span style='color:#888; font-size:12px;'>(" . $num_ringing . " " . ($text['label-ringing'] ?? 'ringing') . ")</span>\n";
	echo "<div class='card'>\n";
	echo "<table class='list'>\n";
	echo "<tr class='list-header'>\n";
	echo "	<th>" . ($text['label-queue']       ?? 'Queue')        . "</th>\n";
	echo "	<th>" . ($text['label-agent']       ?? 'Agent Ext')    . "</th>\n";
	echo "	<th>" . ($text['label-destination'] ?? 'Destination')  . "</th>\n";
	echo "	<th>" . ($text['label-leg']         ?? 'Leg')          . "</th>\n";
	echo "	<th class='center'>" . ($text['label-state'] ?? 'State') . "</th>\n";
	echo "	<th>" . ($text['label-started']     ?? 'Started')      . "</th>\n";
	echo "	<th>" . ($text['label-elapsed']     ?? 'Elapsed')      . "</th>\n";
	echo "</tr>\n";

	if (empty($legs)) {
		echo "<tr class='list-row'><td colspan='7' style='text-align:center; padding:18px; color:#888;'>";
		echo escape($text['label-no_legs'] ?? 'No active agent legs.');
		echo "</td></tr>\n";
	} else {
		foreach ($legs as $l) {
			$st = $l['state'] ?? 'unknown';
			if ($st === 'ringing') {
				$badge_icon  = 'fa-bell';
				$badge_color = '#0a6';
				$badge_text  = $text['label-ringing'] ?? 'Ringing';
				$row_style   = '';
			} elseif ($st === 'in_call') {
				$badge_icon  = 'fa-phone-volume';
				$badge_color = '#06a';
				$badge_text  = $text['label-in_call'] ?? 'In Call';
				$row_style   = '';
			} elseif ($st === 'gone') {
				$badge_icon  = 'fa-times-circle';
				$badge_color = '#a33';
				$badge_text  = $text['label-gone'] ?? 'Gone (orphan)';
				$row_style   = " style='opacity:.45;'";
			} else {
				$badge_icon  = 'fa-question-circle';
				$badge_color = '#888';
				$badge_text  = $text['label-unknown'] ?? 'Unknown';
				$row_style   = '';
			}
			$leg_short = substr($l['leg_uuid'], 0, 8);

			echo "<tr class='list-row'" . $row_style . ">\n";
			echo "	<td>" . escape($l['queue_name']) . "</td>\n";
			echo "	<td><b>" . escape($l['agent_extension']) . "</b></td>\n";
			echo "	<td>" . escape($l['destination']) . "</td>\n";
			echo "	<td title='" . escape($l['leg_uuid']) . "'><code>" . escape($leg_short) . "&hellip;</code></td>\n";
			echo "	<td class='center' style='color:" . $badge_color . "; white-space:nowrap;'>";
			echo "<i class='fas " . $badge_icon . "'></i>&nbsp;<b>" . $badge_text . "</b></td>\n";
			echo "	<td>" . escape(substr($l['started_at'] ?? '', 0, 19)) . "</td>\n";
			echo "	<td><b>" . fmt_duration($l['seconds_on_leg'] ?? 0) . "</b></td>\n";
			echo "</tr>\n";
		}
	}

	echo "</table>\n";
	echo "</div>\n";

	echo "<br />\n";
	echo "<div style='text-align:center; color:#999; font-size:11px;'>";
	echo escape($text['hint-auto_refresh'] ?? 'This page auto-refreshes every 3 seconds.');
	echo "</div>\n";
	echo "<br />\n";

	require_once "resources/footer.php";
