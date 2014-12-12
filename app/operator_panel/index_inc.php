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
	Portions created by the Initial Developer are Copyright (C) 2008-2012
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/
include "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
require_once "resources/functions/get_call_activity.php";

if (permission_exists('operator_panel_view')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
	include "app_languages.php";
	foreach($text as $key => $value) {
		$text[$key] = $value[$_SESSION['domain']['language']['code']];
	}

$activity = get_call_activity();

foreach ($activity as $extension => $fields) {
	if (substr_count($fields['call_group'], ',')) {
		$tmp = explode(',', $fields['call_group']);
		foreach ($tmp as $tmp_index => $tmp_value) {
			if (trim($tmp_value) == '') { unset($tmp[$tmp_index]); }
			else { $groups[] = $tmp_value; }
		}
	}
	else if ($fields['call_group'] != '') {
		$groups[] = $fields['call_group'];
	}
}
$groups = array_unique($groups);
sort($groups);

echo "<table width='100%'>";
echo "	<tr>";
echo "		<td valign='top' align='left' width='50%' nowrap>";
echo "			<b>".$text['title-operator_panel']."</b>";
echo "		</td>";
echo "		<td valign='top' align='center' nowrap>";

if (sizeof($_SESSION['user']['extensions']) > 0) {
	$status_options[1]['status'] = "Available";
	$status_options[1]['label'] = $text['label-status_available'];
	$status_options[1]['style'] = "op_btn_status_available";
	$status_options[2]['status'] = "Available (On Demand)";
	$status_options[2]['label'] = $text['label-status_on_demand'];
	$status_options[2]['style'] = "op_btn_status_available_on_demand";
	$status_options[3]['status'] = "On Break";
	$status_options[3]['label'] = $text['label-status_on_break'];
	$status_options[3]['style'] = "op_btn_status_on_break";
	$status_options[4]['status'] = "Do Not Disturb";
	$status_options[4]['label'] = $text['label-status_do_not_disturb'];
	$status_options[4]['style'] = "op_btn_status_do_not_disturb";
	$status_options[5]['status'] = "Logged Out";
	$status_options[5]['label'] = $text['label-status_logged_out'];
	$status_options[5]['style'] = "op_btn_status_logged_out";

	foreach ($status_options as $status_option) {
		echo "	<input type='button' id='".$status_option['style']."' class='btn' value=\"".$status_option['label']."\" onclick=\"send_cmd('index.php?status='+escape('".$status_option['status']."'));\">\n";
	}
}

echo "		</td>";
echo "		<td valign='top' align='right' width='50%' nowrap>";

if (sizeof($groups) > 0) {
	echo "		<input type='hidden' id='group' value=\"".$_REQUEST['group']."\">";
	if (sizeof($groups) > 5) {
		//show select box
		echo "	<select class='formfld' onchange=\"document.getElementById('group').value = this.options[this.selectedIndex].value; refresh_start();\" onfocus='refresh_stop();' onblur='refresh_start();'>\n";
		echo "		<option value='' ".(($_REQUEST['group'] == '') ? "selected" : null).">".$text['label-call_group']."</option>";
		echo "		<option value=''>".$text['button-all']."</option>";
		foreach ($groups as $group) {
			echo "	<option value='".$group."' ".(($_REQUEST['group'] == $group) ? "selected" : null).">".$group."</option>\n";
		}
		echo "	</select>\n";
	}
	else {
		//show buttons
		echo "	<input type='button' class='btn' title=\"".$text['label-call_group']."\" value=\"".$text['button-all']."\" onclick=\"document.getElementById('group').value = '';\">";
		foreach ($groups as $group) {
			echo "	<input type='button' class='btn' title=\"".$text['label-call_group']."\" value=\"".$group."\" ".(($_REQUEST['group'] == $group) ? "disabled='disabled'" : null)." onclick=\"document.getElementById('group').value = this.value;\">";
		}
	}
}

echo "		</td>";
echo "	</tr>";
echo "</table>";
echo "<br>";

foreach ($activity as $extension => $ext) {
	unset($block);

	//filter by group, if defined
	if ($_REQUEST['group'] != '' && substr_count($ext['call_group'], $_REQUEST['group']) == 0 && !in_array($extension, $_SESSION['user']['extensions'])) { continue; }

	//check if feature code being called
	$format_number = (substr($ext['dest'], 0, 1) == '*') ? false : true;

	//determine extension state, direction icon, and displayed name/number for caller/callee
	if ($ext['state'] == 'CS_EXECUTE') {
		if (($ext['callstate'] == 'RINGING' || $ext['callstate'] == 'EARLY' || $ext['callstate'] == 'RING_WAIT') && $ext['direction'] == 'inbound') {
			$ext_state = 'ringing';
		}
		else if ($ext['callstate'] == 'ACTIVE' && $ext['direction'] == 'outbound') {
			$ext_state = 'active';
		}
		else if ($ext['callstate'] == 'RING_WAIT' && $ext['direction'] == 'outbound') {
			$ext_state = 'ringing';
		}
		else if ($ext['callstate'] == 'ACTIVE' && $ext['direction'] == 'inbound') {
			$ext_state = 'active';
		}
		if (!$format_number) {
			$call_name = 'System';
			$call_number = $ext['dest'];
		}
		else {
			$call_name = $activity[(int) $ext['dest']]['effective_caller_id_name'];
			$call_number = format_phone((int) $ext['dest']);
		}
		$dir_icon = 'outbound';
	}
	else if ($ext['state'] == 'CS_CONSUME_MEDIA' || $ext['state'] == 'CS_EXCHANGE_MEDIA') {
		if ($ext['state'] == 'CS_CONSUME_MEDIA' && $ext['callstate'] == 'RINGING' && $ext['direction'] == 'outbound') {
			$ext_state = 'ringing';
		}
		else if ($ext['state'] == 'CS_EXCHANGE_MEDIA' && $ext['callstate'] == 'ACTIVE' && $ext['direction'] == 'outbound') {
			$ext_state = 'active';
		}
		$dir_icon = 'inbound';
		$call_name = $activity[(int) $ext['cid_num']]['effective_caller_id_name'];
		$call_number = format_phone((int) $ext['cid_num']);
	}
	else {
		unset($ext_state, $dir_icon, $call_name, $call_number);
	}

	//determine block style by state (if any)
	$style = ($ext_state != '') ? "op_state_".$ext_state : null;

	//determine the call identifier passed on drop
	if ($ext['uuid'] == $ext['call_uuid'] && $ext['variable_bridge_uuid'] == '') { // transfer an outbound internal call
		$call_identifier = $activity[$call_number]['uuid'];
	}
	else if (($ext['variable_call_direction'] == 'outbound' || $ext['variable_call_direction'] == 'local') && $ext['variable_bridge_uuid'] != '') { // transfer an outbound external call
		$call_identifier = $ext['variable_bridge_uuid'];
	}
	else {
		$call_identifier = $ext['call_uuid']; // transfer all other call types
	}

	//determine extension draggable state
	if (permission_exists('operator_panel_manage')) {
		if (!in_array($extension, $_SESSION['user']['extensions'])) {
			//other extension
			if ($ext_state == "ringing") {
				if ($_GET['vd_ext_from'] == '' && $dir_icon == 'inbound') {
					$draggable = true; // selectable - is ringing and not outbound so can transfer away the call (can set as vd_ext_from)
				}
				else {
					$draggable = false; // unselectable - is ringing so can't send a call to the ext (can't set as vd_ext_to)
				}
			}
			else if ($ext_state == 'active') {
				$draggable = false; // unselectable - on a call already so can't transfer or send a call to the ext (can't set as vd_ext_from or vd_ext_to)
			}
			else { // idle
				if ($_GET['vd_ext_from'] == '') {
					$draggable = false; // unselectable - is idle, but can't initiate a call from the ext as is not assigned to user (can't set as vd_ext_from)
				}
				else {
					$draggable = true; // selectable - is idle, so can transfer a call in to ext (can set as vd_ext_to).
				}
			}
		}
		else {
			//user extension
			if ($ext['uuid'] != '' && $ext['uuid'] == $ext['call_uuid'] && $ext['variable_bridge_uuid'] == '') {
				$draggable = false;
			}
			else if ($ext_state == 'ringing' && $ext['variable_call_direction'] == 'local') {
				$draggable = false;
			}
			else if ($ext_state != '' && !$format_number) {
				$draggable = false;
			}
			else {
				$draggable = true;
			}
		}
	}
	else {
		$draggable = false;
	}

	//determine extension (user) status
	switch ($ext['user_status']) {
		case "Available" :
			$status_icon = "available";
			$status_hover = $text['label-status_available'];
			break;
		case "Available (On Demand)" :
			$status_icon = "available_on_demand";
			$status_hover = $text['label-status_available_on_demand'];
			break;
		case "On Break" :
			$status_icon = "on_break";
			$status_hover = $text['label-status_on_break'];
			break;
		case "Do Not Disturb" :
			$status_icon = "do_not_disturb";
			$status_hover = $text['label-status_do_not_disturb'];
			break;
		default :
			$status_icon = "logged_out";
			$status_hover = $text['label-status_logged_out_or_unknown'];
	}

	$block .= "<div id='".$extension."' class='op_ext ".$style."' ".(($_GET['vd_ext_from'] == $extension || $_GET['vd_ext_to'] == $extension) ? "style='border-style: dotted;'" : null)." ".(($ext_state != 'active' && $ext_state != 'ringing') ? "ondrop='drop(event, this.id);' ondragover='allowDrop(event, this.id);' ondragleave='discardDrop(event, this.id);'" : null).">"; // DRAG TO
	$block .= "<table class='op_ext ".$style."'>";
	$block .= "	<tr>";
	$block .= "		<td class='op_ext_icon'>";
	$block .= "			<span name='".$extension."'>"; // DRAG FROM
	$block .= 				"<img id='".$call_identifier."' class='op_ext_icon' src='resources/images/status_".$status_icon.".png' title='".$status_hover."' ".(($draggable) ? "draggable='true' ondragstart=\"drag(event, this.parentNode.getAttribute('name'));\" onclick=\"virtual_drag('".$call_identifier."', '".$extension."');\"" : "onfocus='this.blur();' draggable='false' style='cursor: not-allowed;'").">";
	$block .= 			"</span>";
	$block .= "		</td>";
	$block .= "		<td class='op_ext_info ".$style."'>";
	if ($dir_icon != '') {
		$block .= "			<img src='resources/images/".$dir_icon.".png' align='right' style='margin-top: 3px; margin-right: 1px; width: 12px; height: 12px;' draggable='false'>";
	}
	$block .= "			<span class='op_user_info'>";
	if ($ext['effective_caller_id_name'] != '' && $ext['effective_caller_id_name'] != $extension) {
		$block .= "			<strong class='strong'>".$ext['effective_caller_id_name']."</strong> (".$extension.")";
	}
	else {
		$block .= "			<strong class='strong'>".$extension."</strong>";
	}
	$block .= "			</span><br>";
	if ($ext_state != '') {
		$block .= "		<span class='op_caller_info'>";
		$block .= "			<table align='right'><tr><td style='text-align: right;'>";
		$block .= "				<span class='op_call_info'>".$ext['call_length']."</span><br>";
		//record
		if (permission_exists('operator_panel_record') && $ext_state == 'active') {
			$call_identifier_record = $ext['call_uuid'];
			$rec_file = $_SESSION['switch']['recordings']['dir']."/archive/".date("Y")."/".date("M")."/".date("d")."/".$call_identifier_record.".wav";
			if (file_exists($rec_file)) {
				$block .= 		"<img src='resources/images/recording.png' style='width: 12px; height: 12px; border: none; margin: 4px 0px 0px 5px; cursor: help;' title=\"".$text['label-recording']."\">";
			}
			else {
				$block .= 		"<img src='resources/images/record.png' style='width: 12px; height: 12px; border: none; margin: 4px 0px 0px 5px; cursor: pointer;' title=\"".$text['label-record']."\" onclick=\"record_call('".$call_identifier_record."');\">";
			}
		}
		//eavesdrop
		if (permission_exists('operator_panel_eavesdrop') && $ext_state == 'active' && !in_array($extension, $_SESSION['user']['extensions'])) {
			$block .= 			"<img src='resources/images/eavesdrop.png' style='width: 12px; height: 12px; border: none; margin: 4px 0px 0px 5px; cursor: pointer;' title='".$text['label-eavesdrop']."' onclick=\"eavesdrop_call('".$extension."','".$call_identifier."');\">";
		}
		//kill
		if (in_array($extension, $_SESSION['user']['extensions']) || permission_exists('operator_panel_kill')) {
			if ($ext['variable_bridge_uuid'] == '' && $ext_state == 'ringing') {
				$call_identifier_kill = $ext['uuid'];
			}
			else if ($dir_icon == 'outbound') {
				$call_identifier_kill = $ext['uuid'];
			}
			else {
				$call_identifier_kill = $call_identifier;
			}
			$block .= 			"<img src='resources/images/kill.png' style='width: 12px; height: 12px; border: none; margin: 4px 0px 0px 5px; cursor: pointer;' title='".$text['label-kill']."' onclick=\"kill_call('".$call_identifier_kill."');\">";
		}
		$block .= "			</td></tr></table>";
		$block .= "			<strong>".$call_name."</strong><br>".$call_number;
		$block .= "		</span>";
	}
	else {
		if (in_array($extension, $_SESSION['user']['extensions'])) {
			$block .= "		<img src='resources/images/keypad.png' style='width: 12px; height: 12px; border: none; margin-top: 26px; cursor: pointer;' align='right' onclick=\"toggle_destination('".$extension."');\">";
			$block .= "		<form onsubmit=\"call_destination('".$extension."', document.getElementById('destination_".$extension."').value); return false;\">";
			$block .= "			<input type='text' class='formfld' name='destination' id='destination_".$extension."' style='width: 110px; min-width: 110px; max-width: 110px; margin-top: 10px; text-align: center; display: none;' onblur=\"if (this.value == '') { refresh_start(); }\">";
			$block .= "		</form>";
		}
	}
	$block .= "		</td>";
	$block .= "	</tr>";
	$block .= "</table>";

	if (isset($_GET['debug'])) {
		$block .= "<span style='font-size: 10px;'>";
		$block .= "From ID<br>&nbsp;&nbsp;&nbsp;&nbsp;<strong style='color: maroon'>".$extension."</strong><br>";
		$block .= "uuid<br>&nbsp;&nbsp;&nbsp;&nbsp;<strong style='color: ".($call_identifier == $ext['uuid'] ? 'blue' : 'black').";'>".$ext['uuid']."</strong><br>";
		$block .= "call_uuid<br>&nbsp;&nbsp;&nbsp;&nbsp;<strong style='color: ".($call_identifier == $ext['call_uuid'] ? 'blue' : 'black').";'>".$ext['call_uuid']."</strong><br>";
		$block .= "variable_bridge_uuid<br>&nbsp;&nbsp;&nbsp;&nbsp;<strong style='color: ".($call_identifier == $ext['variable_bridge_uuid'] ? 'blue' : 'black').";'>".$ext['variable_bridge_uuid']."</strong><br>";
		$block .= "direction<br>&nbsp;&nbsp;&nbsp;&nbsp;<strong style='color: black;'>".$ext['direction']."</strong><br>";
		$block .= "variable_call_direction<br>&nbsp;&nbsp;&nbsp;&nbsp;<strong style='color: black;'>".$ext['variable_call_direction']."</strong><br>";
		$block .= "state<br>&nbsp;&nbsp;&nbsp;&nbsp;<strong style='color: black;'>".$ext['state']."</strong><br>";
		$block .= "cid_num<br>&nbsp;&nbsp;&nbsp;&nbsp;<strong style='color: black;'>".$ext['cid_num']."</strong><br>";
		$block .= "dest<br>&nbsp;&nbsp;&nbsp;&nbsp;<strong style='color: black;'>".$ext['dest']."</strong><br>";
		$block .= "context<br>&nbsp;&nbsp;&nbsp;&nbsp;<strong style='color: black;'>".$ext['context']."</strong><br>";
		$block .= "presence_id<br>&nbsp;&nbsp;&nbsp;&nbsp;<strong style='color: black;'>".$ext['presence_id']."</strong><br>";
		$block .= "callstate<br>&nbsp;&nbsp;&nbsp;&nbsp;<strong style='color: black;'>".$ext['callstate']."</strong><br>";
		$block .= "</span>";
	}
	$block .= "</div>";

	if (in_array($extension, $_SESSION['user']['extensions'])) {
		$user_extensions[] = $block;
	}
	else {
		$other_extensions[] = $block;
	}
}


if (sizeof($user_extensions) > 0) {
	echo "<table width='100%'><tr><td>";
	foreach ($user_extensions as $ext_block) {
		echo $ext_block;
	}
	echo "</td></tr></table>";
}

if ($_REQUEST['group'] != '') {
	if (sizeof($user_extensions) > 0) { echo "<br>"; }
	echo "<strong style='color: black;'>".ucwords($_REQUEST['group'])."</strong>";
	echo "<br><br>";
}
else if (sizeof($user_extensions) > 0) {
	echo "<br>";
	echo "<strong style='color: black;'>".$text['label-other_extensions']."</strong>";
	echo "<br><br>";
}

if (sizeof($other_extensions) > 0) {
	echo "<table width='100%'><tr><td>";
	foreach ($other_extensions as $ext_block) {
		echo $ext_block;
	}
	echo "</td></tr></table>";
}
else {
	echo $text['label-no_extensions_found'];
}
echo "<br><br>";

if (isset($_GET['debug'])) {
	echo "<textarea style='width: 100%; height: 600px; overflow: scroll;' onfocus='refresh_stop();' onblur='refresh_start();'>";
	print_r($activity);
	echo "</textarea>";
	echo "<br><br>";

	echo '$_SESSION...';
	echo "<textarea style='width: 100%; height: 600px; overflow: scroll;' onfocus='refresh_stop();' onblur='refresh_start();'>";
	print_r($_SESSION);
	echo "</textarea>";
}
?>