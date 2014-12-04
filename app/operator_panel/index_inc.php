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


echo "<table cellpadding='0' cellspacing='0' border='0' align='right'>";
echo "	<tr>";
echo "		<td>";
if (sizeof($groups) > 0) {
	echo 		$text['label-call_group']." ";
	echo "		<select id='group' class='formfld' onchange='refresh_start();' onfocus='refresh_stop();' onblur='refresh_start();'>\n";
	echo "			<option value=''></option>";
	foreach ($groups as $group) {
		echo "		<option value='".$group."' ".(($_REQUEST['group'] == $group) ? "selected" : null).">".$group."</option>\n";
	}
	echo "		</select>\n";
}
echo "		</td>";
echo "	</tr>";
echo "</table>";

echo "<b>".$text['title-operator_panel']."</b>";
echo "<br><br><br>";

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
	$style = ($ext_state != '') ? "state_".$ext_state : null;

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
	if (!in_array($extension, $_SESSION['user']['extensions'])) {
		if ($ext_state == "ringing") {
			if ($_GET['vd_ext_from'] == '') {
				$draggable = true; // selectable - is ringing so can transfer away the call (can set as vd_ext_from)
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
	else if ($ext['uuid'] != '' && $ext['uuid'] == $ext['call_uuid'] && $ext['variable_bridge_uuid'] == '') {
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

	$block .= "<div id='".$extension."' class='ext ".$style."' ".(($_GET['vd_ext_from'] == $extension || $_GET['vd_ext_to'] == $extension) ? "style='border-style: dotted;'" : null)." ".(($ext_state != 'active' && $ext_state != 'ringing') ? "ondrop='drop(event, this.id);' ondragover='allowDrop(event, this.id);' ondragleave='discardDrop(event, this.id);'" : null).">"; // DRAG TO
	$block .= "<table class='ext ".$style."'>";
	$block .= "	<tr>";
	$block .= "		<td class='ext_icon'>";
	$block .= "			<span name='".$extension."'>"; // DRAG FROM
	$block .= 				"<img id='".$call_identifier."' class='ext_icon' src='resources/images/person.png' ".(($draggable) ? "draggable='true' ondragstart=\"drag(event, this.parentNode.getAttribute('name'));\" onclick=\"virtual_drag('".$call_identifier."', '".$extension."');\"" : "onfocus='this.blur();' draggable='false' style='cursor: not-allowed;'").">";
	$block .= 			"</span>";
	$block .= "		</td>";
	$block .= "		<td class='ext_info ".$style."'>";
	if ($dir_icon != '') {
		$block .= "			<img src='resources/images/".$dir_icon.".png' align='right' style='margin-top: 2px; width: 12px; height: 12px;' draggable='false'>";
	}
	$block .= "			<span class='user_info'>";
	if ($ext['effective_caller_id_name'] != '' && $ext['effective_caller_id_name'] != $extension) {
		$block .= "			<strong class='strong'>".$ext['effective_caller_id_name']."</strong> (".$extension.")";
	}
	else {
		$block .= "			<strong class='strong'>".$extension."</strong>";
	}
	$block .= "			</span><br>";
	if ($ext_state != '') {
		$block .= "		<span class='caller_info'>";
		$block .= "			<table align='right'><tr><td><span class='call_info'>".$ext['call_length']."</span></td></tr></table>";
		$block .= "			<strong>".$call_name."</strong><br>".$call_number;
		$block .= "		</span>";
	}
	else {
		if (in_array($extension, $_SESSION['user']['extensions'])) {
// 			$block .= "		<img src='resources/images/keypad.png' style='width: 12px; height: 12px; border: none; margin-top: 26px; cursor: pointer;' align='right' onclick=\"toggle_destination('".$extension."');\">";
// 			$block .= "		<form onsubmit=\"call_destination('".$extension."', document.getElementById('destination_".$extension."').value); return false;\">";
// 			$block .= "			<input type='text' class='formfld' name='destination' id='destination_".$extension."' style='width: 110px; min-width: 110px; max-width: 110px; margin-top: 10px; text-align: center; display: none;' onblur=\"if (this.value == '') { refresh_start(); }\">";
// 			$block .= "		</form>";
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