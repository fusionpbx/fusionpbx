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
*/

//includes files
	require_once dirname(__DIR__, 2) . "/resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('acd_add') || permission_exists('acd_edit')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//connect to database
	$database = database::new();

//create the settings object
	$settings = new settings(['database' => $database, 'domain_uuid' => $_SESSION['domain_uuid'] ?? '', 'user_uuid' => $_SESSION['user_uuid'] ?? '']);

//add multi-lingual support
	$language = new text;
	$text = $language->get(null, 'app/acd');

//include the class
	if (!class_exists('acd')) {
		require_once 'resources/classes/acd.php';
	}
	$obj = new acd;

//initialize the destinations object
	$destination = new destinations;

//get the domain context
	$domain_uuid = $_SESSION['domain_uuid'];
	$domain_name = $_SESSION['domain_name'];

//set defaults
	$queue_uuid               = '';
	$queue_name               = '';
	$queue_extension          = '';
	$queue_hold_music         = '';
	$queue_cid_name_prefix    = '';
	$queue_announce_position  = 'false';
	$queue_announce_interval  = '30';
	$queue_tier_advance_seconds = '20';
	$queue_ring_lower_tiers   = 'true';
	$queue_timeout            = '0';
	$queue_timeout_app        = '';
	$queue_timeout_data       = '';
	$queue_context            = $domain_name;
	$queue_enabled            = 'true';
	$queue_description        = '';
	$dialplan_uuid            = '';
	$queue_members            = [];

//action add or update
	if (!empty($_REQUEST['id']) && is_uuid($_REQUEST['id'])) {
		$action = 'update';
		$queue_uuid = $_REQUEST['id'];
	}
	elseif (!empty($_REQUEST['queue_uuid']) && is_uuid($_REQUEST['queue_uuid'])) {
		$action = 'update';
		$queue_uuid = $_REQUEST['queue_uuid'];
	}
	else {
		$action = 'add';
	}

//handle member delete (GET action)
	if (
		!empty($_GET['a']) && $_GET['a'] === 'delete_member'
		&& !empty($_GET['queue_member_uuid']) && is_uuid($_GET['queue_member_uuid'])
		&& is_uuid($queue_uuid)
		&& permission_exists('acd_edit')
	) {
		$queue_member_uuid = $_GET['queue_member_uuid'];

		//delete member with raw SQL (custom table PK naming mismatch)
		$database->execute(
			"DELETE FROM v_acd_queue_members WHERE queue_member_uuid = :queue_member_uuid AND domain_uuid = :domain_uuid",
			['queue_member_uuid' => $queue_member_uuid, 'domain_uuid' => $domain_uuid]
		);

		message::add($text['message-delete']);
		header("Location: acd_edit.php?id=".urlencode($queue_uuid));
		exit;
	}

//handle POST
	if ($_SERVER['REQUEST_METHOD'] === 'POST' && count($_POST) > 0) {

		//validate the token
		$token = new token;
		if (!$token->validate($_SERVER['PHP_SELF'])) {
			message::add($text['message-invalid_token'], 'negative');
			header("Location: acd.php");
			exit;
		}

		//check permissions
		if (!permission_exists('acd_add') && !permission_exists('acd_edit')) {
			echo "access denied"; exit;
		}

		//handle delete action from modal
		if (($_POST['action'] ?? '') === 'delete' && permission_exists('acd_delete')) {
			$del_uuid = trim($_POST['queue_uuid'] ?? '');
			if (is_uuid($del_uuid)) {
				$obj->delete([['uuid' => $del_uuid, 'checked' => 'true']]);
			}
			header("Location: acd.php");
			exit;
		}

		//get queue_uuid from POST (empty = new record)
		$post_queue_uuid = trim($_POST['queue_uuid'] ?? '');
		if (!empty($post_queue_uuid) && !is_uuid($post_queue_uuid)) {
			$post_queue_uuid = '';
		}

		//build queue array
		$queue_array = [];
		$queue_array['queue_uuid']              = $post_queue_uuid;
		$queue_array['domain_uuid']             = $domain_uuid;
		$queue_array['queue_name']              = htmlspecialchars(trim($_POST['queue_name'] ?? ''), ENT_QUOTES);
		$queue_array['queue_extension']         = htmlspecialchars(trim($_POST['queue_extension'] ?? ''), ENT_QUOTES);
		$queue_array['queue_hold_music']        = htmlspecialchars(trim($_POST['queue_hold_music'] ?? ''), ENT_QUOTES);
		$queue_array['queue_cid_name_prefix']   = htmlspecialchars(trim($_POST['queue_cid_name_prefix'] ?? ''), ENT_QUOTES);
		$queue_array['queue_announce_position'] = ($_POST['queue_announce_position'] ?? 'false') === 'true' ? 'true' : 'false';
		$queue_array['queue_announce_interval'] = is_numeric($_POST['queue_announce_interval'] ?? '') ? (string)(int)$_POST['queue_announce_interval'] : '30';
		$queue_array['queue_tier_advance_seconds'] = is_numeric($_POST['queue_tier_advance_seconds'] ?? '') ? (string)(int)$_POST['queue_tier_advance_seconds'] : '20';
		$queue_array['queue_ring_lower_tiers']  = ($_POST['queue_ring_lower_tiers'] ?? 'true') === 'true' ? 'true' : 'false';
		$queue_array['queue_timeout']           = is_numeric($_POST['queue_timeout'] ?? '') ? (string)(int)$_POST['queue_timeout'] : '0';
		$queue_array['queue_context']           = !empty($_POST['queue_context']) ? htmlspecialchars(trim($_POST['queue_context']), ENT_QUOTES) : $domain_name;
		$queue_array['queue_enabled']           = ($_POST['queue_enabled'] ?? 'true') === 'true' ? 'true' : 'false';
		$queue_array['queue_description']       = htmlspecialchars(trim($_POST['queue_description'] ?? ''), ENT_QUOTES);
		$queue_array['dialplan_uuid']           = (isset($_POST['dialplan_uuid']) && is_uuid($_POST['dialplan_uuid'])) ? $_POST['dialplan_uuid'] : '';

		//parse timeout destination
		$timeout_action = trim($_POST['queue_timeout_action'] ?? '');
		if (!empty($timeout_action)) {
			$timeout_parts = explode(':', $timeout_action, 2);
			$queue_array['queue_timeout_app']  = $timeout_parts[0] ?? '';
			$queue_array['queue_timeout_data'] = $timeout_parts[1] ?? '';
		}
		else {
			$queue_array['queue_timeout_app']  = '';
			$queue_array['queue_timeout_data'] = '';
		}

		//validate required fields
		$msg = '';
		if (empty($queue_array['queue_name']))      { $msg .= ($text['message-required'] ?? "Required").": ".($text['label-queue_name'] ?? "Queue Name")."<br>\n"; }
		if (empty($queue_array['queue_extension'])) { $msg .= ($text['message-required'] ?? "Required").": ".($text['label-extension'] ?? "Extension")."<br>\n"; }

		if (!empty($msg)) {
			message::add($msg, 'negative');
			//fall through to re-render the form with posted values preserved below
			//restore locals for re-display
			$queue_uuid               = $post_queue_uuid;
			$queue_name               = $queue_array['queue_name'];
			$queue_extension          = $queue_array['queue_extension'];
			$queue_hold_music         = $queue_array['queue_hold_music'];
			$queue_cid_name_prefix    = $queue_array['queue_cid_name_prefix'];
			$queue_announce_position  = $queue_array['queue_announce_position'];
			$queue_announce_interval  = $queue_array['queue_announce_interval'];
			$queue_tier_advance_seconds = $queue_array['queue_tier_advance_seconds'];
			$queue_ring_lower_tiers   = $queue_array['queue_ring_lower_tiers'];
			$queue_timeout            = $queue_array['queue_timeout'];
			$queue_timeout_app        = $queue_array['queue_timeout_app'];
			$queue_timeout_data       = $queue_array['queue_timeout_data'];
			$queue_context            = $queue_array['queue_context'];
			$queue_enabled            = $queue_array['queue_enabled'];
			$queue_description        = $queue_array['queue_description'];
			$dialplan_uuid            = $queue_array['dialplan_uuid'];
			$action                   = empty($post_queue_uuid) ? 'add' : 'update';
		}
		else {
			//build members array
			$members_array = [];
			$post_member_numbers      = $_POST['queue_member_number']            ?? [];
			$post_member_uuids        = $_POST['queue_member_uuid']              ?? [];
			$post_member_tiers        = $_POST['queue_member_tier']              ?? [];
			$post_member_follow_me    = $_POST['queue_member_honor_follow_me']   ?? [];
			$post_member_busy         = $_POST['queue_member_busy_handling']     ?? [];
			$post_member_enabled      = $_POST['queue_member_enabled']           ?? [];

			foreach ($post_member_numbers as $i => $member_number) {
				$member_number = trim($member_number);
				if (empty($member_number)) { continue; }
				$m_uuid = trim($post_member_uuids[$i] ?? '');
				if (!is_uuid($m_uuid)) { $m_uuid = ''; }

				$busy_handling = $post_member_busy[$i] ?? 'never_any';
				if (!in_array($busy_handling, ['never_any', 'never_queue', 'always'])) { $busy_handling = 'never_any'; }

				$members_array[] = [
					'queue_member_uuid'           => $m_uuid,
					'queue_member_number'         => htmlspecialchars($member_number, ENT_QUOTES),
					'queue_member_tier'           => is_numeric($post_member_tiers[$i] ?? '') ? (string)(int)$post_member_tiers[$i] : '1',
					'queue_member_honor_follow_me' => ($post_member_follow_me[$i] ?? 'true') === 'true' ? 'true' : 'false',
					'queue_member_busy_handling'  => $busy_handling,
					'queue_member_enabled'        => ($post_member_enabled[$i] ?? 'true') === 'true' ? 'true' : 'false',
				];
			}

			//save
			$result_uuid = $obj->save($queue_array, $members_array);
			message::add($text['message-update'] ?? 'Saved successfully.', 'positive');
			header("Location: acd_edit.php?id=".urlencode($result_uuid));
			exit;
		}
	}

//load existing record for edit
	if ($action === 'update' && is_uuid($queue_uuid) && empty($_POST)) {
		$sql = "select * from v_acd_queues "
			 . "where queue_uuid = :queue_uuid "
			 . "and domain_uuid = :domain_uuid";
		$params = ['queue_uuid' => $queue_uuid, 'domain_uuid' => $domain_uuid];
		$row = $database->select($sql, $params, 'row');
		if (is_array($row) && !empty($row)) {
			$queue_name               = $row['queue_name']               ?? '';
			$queue_extension          = $row['queue_extension']          ?? '';
			$queue_hold_music         = $row['queue_hold_music']         ?? '';
			$queue_cid_name_prefix    = $row['queue_cid_name_prefix']    ?? '';
			$queue_announce_position  = $row['queue_announce_position']  ?? 'false';
			$queue_announce_interval  = $row['queue_announce_interval']  ?? '30';
			$queue_tier_advance_seconds = $row['queue_tier_advance_seconds'] ?? '20';
			$queue_ring_lower_tiers   = $row['queue_ring_lower_tiers']   ?? 'true';
			$queue_timeout            = $row['queue_timeout']            ?? '0';
			$queue_timeout_app        = $row['queue_timeout_app']        ?? '';
			$queue_timeout_data       = $row['queue_timeout_data']       ?? '';
			$queue_context            = $row['queue_context']            ?? $domain_name;
			$queue_enabled            = $row['queue_enabled']            ?? 'true';
			$queue_description        = $row['queue_description']        ?? '';
			$dialplan_uuid            = $row['dialplan_uuid']            ?? '';
		}
		unset($sql, $params, $row);

		//load existing members
		$sql = "select * from v_acd_queue_members "
			 . "where queue_uuid = :queue_uuid "
			 . "and domain_uuid = :domain_uuid "
			 . "order by queue_member_tier::integer asc, (case when queue_member_number ~ '^[0-9]+$' then queue_member_number::integer else 0 end) asc, queue_member_number asc";
		$params = ['queue_uuid' => $queue_uuid, 'domain_uuid' => $domain_uuid];
		$queue_members = $database->select($sql, $params, 'all');
		if (!is_array($queue_members)) { $queue_members = []; }
		unset($sql, $params);
	}

//rebuild timeout action string for the destination select
	$queue_timeout_action = '';
	if (!empty($queue_timeout_app)) {
		$queue_timeout_action = $queue_timeout_app . ':' . $queue_timeout_data;
	}

//load music on hold list
	$sql = "select music_on_hold_uuid, music_on_hold_name from v_music_on_hold "
		 . "where (domain_uuid = :domain_uuid or domain_uuid is null) "
		 . "order by music_on_hold_name asc";
	$params = ['domain_uuid' => $domain_uuid];
	$moh_list = $database->select($sql, $params, 'all');
	if (!is_array($moh_list)) { $moh_list = []; }
	unset($sql, $params);

//determine add-row counts
	if ($action === 'add' || empty($queue_members)) {
		$member_extra_rows = (int)($settings->get('acd', 'member_rows_add', 5) ?? 5);
	}
	else {
		$member_extra_rows = (int)($settings->get('acd', 'member_rows_edit', 1) ?? 1);
	}

//create token
	$token_object = new token;
	$token_value  = $token_object->create($_SERVER['PHP_SELF']);

//page title
	$document['title'] = $text['title-singular'] ?? 'Call Center Queue';
	require_once "resources/header.php";

?>

<form method="post" name="frm" id="frm" action="acd_edit.php">
<input type="hidden" name="<?php echo $token_value['name']; ?>" value="<?php echo $token_value['hash']; ?>">
<input type="hidden" name="queue_uuid"    value="<?php echo escape($queue_uuid); ?>">
<input type="hidden" name="dialplan_uuid" value="<?php echo escape($dialplan_uuid); ?>">

<div class="action_bar" id="action_bar">
	<div class="heading"><b><?php echo $text['title-singular'] ?? 'Call Center Queue'; ?></b></div>
	<div class="actions">
		<?php echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$settings->get('theme','button_icon_back',''),'id'=>'btn_back','link'=>'acd.php']); ?>
		<?php
		if ($action === 'update') {
			if (permission_exists('acd_delete')) {
				echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$settings->get('theme','button_icon_delete',''),'name'=>'btn_delete','style'=>'margin-left: 15px;','onclick'=>"modal_open('modal-delete','btn_delete');"]);
			}
		}
		?>
		<?php echo button::create(['type'=>'submit','label'=>$text['button-save'],'icon'=>$settings->get('theme','button_icon_save',''),'id'=>'btn_save','style'=>'margin-left: 15px;']); ?>
	</div>
	<div style="clear: both;"></div>
</div>

<?php
if ($action === 'update' && permission_exists('acd_delete')) {
	echo modal::create(['id'=>'modal-delete','type'=>'delete','actions'=>button::create(['type'=>'submit','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_delete_confirm','style'=>'float: right; margin-left: 15px;','collapse'=>'never','name'=>'action','value'=>'delete','onclick'=>"modal_close();"])]);
}
?>

<br />

<div class="card">
<table width="100%" border="0" cellpadding="0" cellspacing="0">

	<tr>
		<td class="vncellreq" valign="top" align="left" nowrap="nowrap">
			<?php echo $text['label-queue_name'] ?? 'Queue Name'; ?>
		</td>
		<td class="vtable" align="left">
			<input class="formfld" type="text" name="queue_name" maxlength="255" required="required"
				value="<?php echo escape($queue_name); ?>"
				placeholder="<?php echo $text['placeholder-queue_name'] ?? 'Enter queue name'; ?>">
			<br />
			<?php echo $text['description-queue_name'] ?? ''; ?>
		</td>
	</tr>

	<tr>
		<td class="vncellreq" valign="top" align="left" nowrap="nowrap">
			<?php echo $text['label-extension'] ?? 'Extension'; ?>
		</td>
		<td class="vtable" align="left">
			<input class="formfld" type="text" name="queue_extension" maxlength="255" required="required"
				value="<?php echo escape($queue_extension); ?>"
				placeholder="<?php echo escape($settings->get('dialplan', 'extension_range', '') ?? ''); ?>">
			<br />
			<?php echo $text['description-extension'] ?? ''; ?>
		</td>
	</tr>

	<tr>
		<td class="vncell" valign="top" align="left" nowrap="nowrap">
			<?php echo $text['label-hold_music'] ?? 'Hold Music'; ?>
		</td>
		<td class="vtable" align="left">
			<select name="queue_hold_music" id="queue_hold_music" class="formfld">
				<option value=""></option>
				<?php
				foreach ($moh_list as $moh_row) {
					$moh_val  = 'local_stream://' . escape($moh_row['music_on_hold_name']);
					$selected = ($queue_hold_music === $moh_val || $queue_hold_music === $moh_row['music_on_hold_name']) ? " selected='selected'" : '';
					echo "<option value='" . $moh_val . "'" . $selected . ">" . escape($moh_row['music_on_hold_name']) . "</option>\n";
				}
				?>
			</select>
			<br />
			<?php echo $text['description-hold_music'] ?? ''; ?>
		</td>
	</tr>

	<tr>
		<td class="vncell" valign="top" align="left" nowrap="nowrap">
			<?php echo $text['label-cid_name_prefix'] ?? 'CID Name Prefix'; ?>
		</td>
		<td class="vtable" align="left">
			<input class="formfld" type="text" name="queue_cid_name_prefix" maxlength="255"
				value="<?php echo escape($queue_cid_name_prefix); ?>"
				placeholder="<?php echo $text['placeholder-cid_name_prefix'] ?? 'e.g. Sales - '; ?>">
			<br />
			<?php echo $text['description-cid_name_prefix'] ?? ''; ?>
		</td>
	</tr>

	<tr>
		<td class="vncell" valign="top" align="left" nowrap="nowrap">
			<?php echo $text['label-announce_position'] ?? 'Announce Position'; ?>
		</td>
		<td class="vtable" align="left">
			<select name="queue_announce_position" id="queue_announce_position" class="formfld"
				onchange="toggle_announce_interval(this.value);">
				<option value="false" <?php echo ($queue_announce_position !== 'true') ? "selected='selected'" : ''; ?>>
					<?php echo $text['label-no'] ?? 'No'; ?>
				</option>
				<option value="true" <?php echo ($queue_announce_position === 'true') ? "selected='selected'" : ''; ?>>
					<?php echo $text['label-yes'] ?? 'Yes'; ?>
				</option>
			</select>
			<br />
			<?php echo $text['description-announce_position'] ?? ''; ?>
		</td>
	</tr>

	<tr id="row_announce_interval" style="<?php echo ($queue_announce_position === 'true') ? '' : 'display:none;'; ?>">
		<td class="vncell" valign="top" align="left" nowrap="nowrap">
			<?php echo $text['label-announce_interval'] ?? 'Announce Interval'; ?>
		</td>
		<td class="vtable" align="left">
			<input class="formfld" type="text" name="queue_announce_interval"
				style="width: 60px;"
				value="<?php echo escape($queue_announce_interval ?: '30'); ?>">
			<?php echo $text['label-seconds'] ?? 'seconds'; ?>
			<br />
			<?php echo $text['description-announce_interval'] ?? ''; ?>
		</td>
	</tr>

	<tr>
		<td class="vncell" valign="top" align="left" nowrap="nowrap">
			<?php echo $text['label-tier_advance_seconds'] ?? 'Tier Advance Seconds'; ?>
		</td>
		<td class="vtable" align="left">
			<input class="formfld" type="text" name="queue_tier_advance_seconds"
				style="width: 60px;"
				value="<?php echo escape($queue_tier_advance_seconds ?: '20'); ?>">
			<?php echo $text['label-seconds'] ?? 'seconds'; ?>
			<br />
			<?php echo $text['description-tier_advance_seconds'] ?? ''; ?>
		</td>
	</tr>

	<tr>
		<td class="vncell" valign="top" align="left" nowrap="nowrap">
			<?php echo $text['label-ring_lower_tiers'] ?? 'Ring Lower Tiers'; ?>
		</td>
		<td class="vtable" align="left">
			<select name="queue_ring_lower_tiers" class="formfld">
				<option value="true"  <?php echo ($queue_ring_lower_tiers !== 'false') ? "selected='selected'" : ''; ?>>
					<?php echo $text['label-yes'] ?? 'Yes'; ?>
				</option>
				<option value="false" <?php echo ($queue_ring_lower_tiers === 'false') ? "selected='selected'" : ''; ?>>
					<?php echo $text['label-no'] ?? 'No'; ?>
				</option>
			</select>
			<br />
			<?php echo $text['description-ring_lower_tiers'] ?? ''; ?>
		</td>
	</tr>

	<tr>
		<td class="vncell" valign="top" align="left" nowrap="nowrap">
			<?php echo $text['label-queue_timeout'] ?? 'Queue Timeout'; ?>
		</td>
		<td class="vtable" align="left">
			<input class="formfld" type="text" name="queue_timeout"
				style="width: 80px;"
				value="<?php echo escape($queue_timeout ?: '0'); ?>">
			<?php echo $text['label-seconds'] ?? 'seconds'; ?>
			&nbsp;&nbsp;<span class="vexpl"><?php echo $text['description-queue_timeout'] ?? '(0 = never timeout)'; ?></span>
			<br />
		</td>
	</tr>

	<tr>
		<td class="vncell" valign="top" align="left" nowrap="nowrap">
			<?php echo $text['label-timeout_destination'] ?? 'Timeout Destination'; ?>
		</td>
		<td class="vtable" align="left">
			<?php echo $destination->select('dialplan', 'queue_timeout_action', $queue_timeout_action ?? ''); ?>
			<br />
			<?php echo $text['description-timeout_destination'] ?? ''; ?>
		</td>
	</tr>

	<tr>
		<td class="vncellreq" valign="top" align="left" nowrap="nowrap">
			<?php echo $text['label-enabled'] ?? 'Enabled'; ?>
		</td>
		<td class="vtable" align="left">
			<select name="queue_enabled" class="formfld">
				<option value="true"  <?php echo ($queue_enabled !== 'false') ? "selected='selected'" : ''; ?>>
					<?php echo $text['label-true'] ?? 'True'; ?>
				</option>
				<option value="false" <?php echo ($queue_enabled === 'false') ? "selected='selected'" : ''; ?>>
					<?php echo $text['label-false'] ?? 'False'; ?>
				</option>
			</select>
			<br />
			<?php echo $text['description-enabled'] ?? ''; ?>
		</td>
	</tr>

	<tr>
		<td class="vncell" valign="top" align="left" nowrap="nowrap">
			<?php echo $text['label-description'] ?? 'Description'; ?>
		</td>
		<td class="vtable" align="left">
			<input class="formfld" type="text" name="queue_description" maxlength="255"
				value="<?php echo escape($queue_description); ?>">
			<br />
			<?php echo $text['description-description'] ?? ''; ?>
		</td>
	</tr>

</table>
</div>

<br />

<div class="card">
<table width="100%" border="0" cellpadding="0" cellspacing="0" id="members_table" style="table-layout:fixed;">
	<colgroup>
		<col style="width:18%;"><!-- Extension -->
		<col style="width:60px;"><!-- Tier -->
		<col style="width:110px;"><!-- Follow Me -->
		<col><!-- Busy Handling -->
		<col style="width:90px;"><!-- Enabled -->
		<col style="width:80px;"><!-- Delete -->
	</colgroup>
	<thead>
	<tr>
		<td colspan="6" class="vncell" style="padding: 10px 12px 6px 12px;">
			<b><?php echo $text['label-members'] ?? 'Members'; ?></b>
		</td>
	</tr>
	<tr>
		<th class="vncell"><?php echo $text['label-member_number'] ?? 'Extension'; ?></th>
		<th class="vncell"><?php echo $text['label-member_tier'] ?? 'Tier'; ?></th>
		<th class="vncell"><?php echo $text['label-member_honor_follow_me'] ?? 'Follow Me'; ?></th>
		<th class="vncell"><?php echo $text['label-member_busy_handling'] ?? 'Busy Handling'; ?></th>
		<th class="vncell"><?php echo $text['label-enabled'] ?? 'Enabled'; ?></th>
		<th class="vncell"><?php echo $text['button-delete'] ?? 'Del'; ?></th>
	</tr>
	</thead>
<tbody id="members_tbody">

<?php
//render existing member rows
$row_num = 1;
foreach ($queue_members as $member) {
	$m_uuid    = escape($member['queue_member_uuid']            ?? '');
	$m_number  = escape($member['queue_member_number']          ?? '');
	$m_tier    = escape($member['queue_member_tier']            ?? '1');
	$m_follow  = $member['queue_member_honor_follow_me']        ?? 'true';
	$m_busy    = $member['queue_member_busy_handling']          ?? 'never_any';
	$m_enabled = $member['queue_member_enabled']                ?? 'true';

	echo "<tr>\n";
	echo "<td class='vtable'>\n";
	echo "  <input type='hidden' name='queue_member_uuid[]' value='" . $m_uuid . "'>\n";
	echo "  <input type='text' class='formfld' style='width:100%;' name='queue_member_number[]' value='" . $m_number . "' placeholder='Ext'>\n";
	echo "</td>\n";
	echo "<td class='vtable'>\n";
	echo "  <input type='text' class='formfld' style='width:100%;' name='queue_member_tier[]' value='" . $m_tier . "' placeholder='1'>\n";
	echo "</td>\n";
	echo "<td class='vtable'>\n";
	echo "  <select name='queue_member_honor_follow_me[]' class='formfld' style='width:100%;'>\n";
	echo "    <option value='true'"  . ($m_follow === 'true'  ? " selected='selected'" : '') . ">" . ($text['label-yes'] ?? 'Yes') . "</option>\n";
	echo "    <option value='false'" . ($m_follow === 'false' ? " selected='selected'" : '') . ">" . ($text['label-no']  ?? 'No')  . "</option>\n";
	echo "  </select>\n";
	echo "</td>\n";
	echo "<td class='vtable'>\n";
	echo "  <select name='queue_member_busy_handling[]' class='formfld' style='width:100%;'>\n";
	echo "    <option value='never_any'"   . ($m_busy === 'never_any'   ? " selected='selected'" : '') . ">" . ($text['label-busy_never_any']   ?? 'Never Ring (Any Call)')       . "</option>\n";
	echo "    <option value='never_queue'" . ($m_busy === 'never_queue' ? " selected='selected'" : '') . ">" . ($text['label-busy_never_queue'] ?? 'Never Ring (Queue Calls Only)') . "</option>\n";
	echo "    <option value='always'"      . ($m_busy === 'always'      ? " selected='selected'" : '') . ">" . ($text['label-busy_always']      ?? 'Always Ring')                  . "</option>\n";
	echo "  </select>\n";
	echo "</td>\n";
	echo "<td class='vtable'>\n";
	echo "  <select name='queue_member_enabled[]' class='formfld' style='width:100%;'>\n";
	echo "    <option value='true'"  . ($m_enabled === 'true'  ? " selected='selected'" : '') . ">" . ($text['label-true']  ?? 'True')  . "</option>\n";
	echo "    <option value='false'" . ($m_enabled === 'false' ? " selected='selected'" : '') . ">" . ($text['label-false'] ?? 'False') . "</option>\n";
	echo "  </select>\n";
	echo "</td>\n";
	echo "<td class='vtable' style='text-align:center;'>\n";
	if (!empty($m_uuid) && is_uuid($member['queue_member_uuid']) && is_uuid($queue_uuid)) {
		echo "  <a href='acd_edit.php?id=" . escape($queue_uuid) . "&a=delete_member&queue_member_uuid=" . $m_uuid . "'"
			. " class='btn btn-default btn-xs' style='padding:2px 8px;font-size:11px;'"
			. " onclick=\"return confirm('" . ($text['confirm-delete'] ?? 'Are you sure you want to delete this?') . "');\">"
			. ($text['button-delete'] ?? 'Delete') . "</a>\n";
	}
	echo "</td>\n";
	echo "</tr>\n";

	$row_num++;
}

//render blank new-member rows
for ($x = 0; $x < $member_extra_rows; $x++) {
	echo "<tr>\n";
	echo "<td class='vtable'>\n";
	echo "  <input type='hidden' name='queue_member_uuid[]' value=''>\n";
	echo "  <input type='text' class='formfld' style='width:100%;' name='queue_member_number[]' value='' placeholder='Ext'>\n";
	echo "</td>\n";
	echo "<td class='vtable'>\n";
	echo "  <input type='text' class='formfld' style='width:100%;' name='queue_member_tier[]' value='1' placeholder='1'>\n";
	echo "</td>\n";
	echo "<td class='vtable'>\n";
	echo "  <select name='queue_member_honor_follow_me[]' class='formfld' style='width:100%;'>\n";
	echo "    <option value='true' selected='selected'>"  . ($text['label-yes'] ?? 'Yes') . "</option>\n";
	echo "    <option value='false'>"                     . ($text['label-no']  ?? 'No')  . "</option>\n";
	echo "  </select>\n";
	echo "</td>\n";
	echo "<td class='vtable'>\n";
	echo "  <select name='queue_member_busy_handling[]' class='formfld' style='width:100%;'>\n";
	echo "    <option value='never_any' selected='selected'>" . ($text['label-busy_never_any']   ?? 'Never Ring (Any Call)')       . "</option>\n";
	echo "    <option value='never_queue'>"                   . ($text['label-busy_never_queue'] ?? 'Never Ring (Queue Calls Only)') . "</option>\n";
	echo "    <option value='always'>"                        . ($text['label-busy_always']      ?? 'Always Ring')                  . "</option>\n";
	echo "  </select>\n";
	echo "</td>\n";
	echo "<td class='vtable'>\n";
	echo "  <select name='queue_member_enabled[]' class='formfld' style='width:100%;'>\n";
	echo "    <option value='true' selected='selected'>" . ($text['label-true']  ?? 'True')  . "</option>\n";
	echo "    <option value='false'>"                    . ($text['label-false'] ?? 'False') . "</option>\n";
	echo "  </select>\n";
	echo "</td>\n";
	echo "<td class='vtable'></td>\n";
	echo "</tr>\n";

	$row_num++;
}
?>

	<tr>
		<td class="vtable" colspan="6" style="text-align: right; padding: 6px 12px;">
			<input type="button" class="btn" value="+ <?php echo $text['button-add'] ?? 'Add'; ?>"
				onclick="add_member_row(); return false;">
		</td>
	</tr>
</tbody>
</table>
</div>

<br />

<div class="card">
<table width="100%" border="0" cellpadding="0" cellspacing="0">
	<tr>
		<td class="vtable" align="right">
			<?php echo button::create(['type'=>'submit','label'=>$text['button-save'],'icon'=>$settings->get('theme','button_icon_save',''),'id'=>'btn_save2']); ?>
			<?php echo button::create(['type'=>'button','label'=>$text['button-cancel'] ?? 'Cancel','icon'=>$settings->get('theme','button_icon_back',''),'id'=>'btn_cancel','link'=>'acd.php','style'=>'margin-left: 15px;']); ?>
		</td>
	</tr>
</table>
</div>

</form>

<script type="text/javascript">
(function() {

	// Toggle announce interval row visibility
	window.toggle_announce_interval = function(value) {
		var row = document.getElementById('row_announce_interval');
		if (row) {
			row.style.display = (value === 'true') ? '' : 'none';
		}
	};

	// Add a blank member row by cloning the last row in the tbody
	window.add_member_row = function() {
		var tbody = document.getElementById('members_tbody');
		if (!tbody) { return; }
		var rows = tbody.getElementsByTagName('tr');
		if (rows.length === 0) { return; }

		// Clone last row
		var template = rows[rows.length - 1];
		var new_row  = template.cloneNode(true);

		// Clear text/hidden inputs
		var inputs = new_row.querySelectorAll('input[type="text"], input[type="hidden"]');
		for (var i = 0; i < inputs.length; i++) {
			if (inputs[i].name.indexOf('queue_member_tier') !== -1) {
				inputs[i].value = '1';
			} else {
				inputs[i].value = '';
			}
		}

		// Reset selects to defaults
		var selects = new_row.querySelectorAll('select');
		for (var j = 0; j < selects.length; j++) {
			selects[j].selectedIndex = 0;
		}

		// Remove any delete link from the new row (it's a new row with no UUID)
		var last_td = new_row.cells[new_row.cells.length - 1];
		if (last_td) { last_td.innerHTML = ''; }

		tbody.appendChild(new_row);
	};

})();
</script>

<?php require_once "resources/footer.php"; ?>
