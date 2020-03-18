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
	Copyright (C) 2010
	All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	Luis Daniel Lucio Quiroz <dlucio@okay.com.mx>
*/
require_once "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
require_once "resources/paging.php";

if (permission_exists('fifo_add')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get http values and set them as variables
	if (count($_POST)>0) {
		$order_by = $_GET["order_by"];
		$order = $_GET["order"];
		$extension_name = $_POST["extension_name"];
		$queue_extension_number = $_POST["queue_extension_number"];
		$agent_queue_extension_number = $_POST["agent_queue_extension_number"];
		$agent_login_logout_extension_number = $_POST["agent_login_logout_extension_number"];
		$dialplan_order = $_POST["dialplan_order"];
		$pin_number = $_POST["pin_number"];
		$profile = $_POST["profile"];
		$flags = $_POST["flags"];
		$dialplan_enabled = $_POST["dialplan_enabled"];
		$dialplan_description = $_POST["dialplan_description"];
		if (strlen($dialplan_enabled) == 0) { $dialplan_enabled = "true"; } //set default to enabled
	}

if (count($_POST)>0 && strlen($_POST["persistformvar"]) == 0) {

	//validate the token
		$token = new token;
		if (!$token->validate($_SERVER['PHP_SELF'])) {
			message::add($text['message-invalid_token'],'negative');
			header('Location: dialplans.php');
			exit;
		}

	//check for all required data
		if (strlen($domain_uuid) == 0) { $msg .= $text['message-required']."domain_uuid<br>\n"; }
		if (strlen($extension_name) == 0) { $msg .= $text['message-required'].$text['label-name']."<br>\n"; }
		if (strlen($queue_extension_number) == 0) { $msg .= $text['message-required'].$text['label-extension']."<br>\n"; }
		if (strlen($msg) > 0 && strlen($_POST["persistformvar"]) == 0) {
			require_once "resources/header.php";
			require_once "resources/persist_form_var.php";
			echo "<div align='center'>\n";
			echo "<table><tr><td>\n";
			echo $msg."<br />";
			echo "</td></tr></table>\n";
			persistformvar($_POST);
			echo "</div>\n";
			require_once "resources/footer.php";
			return;
		}

	if (strlen($queue_extension_number) > 0) {
		//--------------------------------------------------------
		//Caller Queue [FIFO in]
		//<extension name="Queue_Call_In">
		//	<condition field="destination_number" expression="^7011\$">
		//		<action application="set" data="fifo_music=$${hold_music}"/>
		//		<action application="answer"/>
		//		<action application="fifo" data="myq in"/>
		//	</condition>
		//</extension>
		//--------------------------------------------------------
			$queue_name = $extension_name."@\${domain_name}";
			$app_uuid = '16589224-c876-aeb3-f59f-523a1c0801f7';
			$dialplan_uuid = uuid();
			$dialplan_context = $_SESSION['context'];
			dialplan_add($domain_uuid, $dialplan_uuid, $extension_name, $dialplan_order, $dialplan_context, $dialplan_enabled, $dialplan_description, $app_uuid);
			if (is_uuid($dialplan_uuid)) {
				//set the destination number
					$dialplan_detail_tag = 'condition'; //condition, action, antiaction
					$dialplan_detail_type = 'destination_number';
					$dialplan_detail_data = '^'.$queue_extension_number.'$';
					$dialplan_detail_order = '000';
					$dialplan_detail_group = '1';
					if ((strlen($agent_queue_extension_number) > 0) || (strlen($agent_login_logout_extension_number) > 0)) {
						$dialplan_detail_break = 'on-true';
					}
					else {
						$dialplan_detail_break = '';
					}
					dialplan_detail_add($_SESSION['domain_uuid'], $dialplan_uuid, $dialplan_detail_tag, $dialplan_detail_order, $dialplan_detail_group, $dialplan_detail_type, $dialplan_detail_data, $dialplan_detail_break);
				//set the hold music
					$dialplan_detail_tag = 'action'; //condition, action, antiaction
					$dialplan_detail_type = 'set';
					$dialplan_detail_data = 'fifo_music=$${hold_music}';
					$dialplan_detail_order = '001';
					$dialplan_detail_group = '1';
					dialplan_detail_add($_SESSION['domain_uuid'], $dialplan_uuid, $dialplan_detail_tag, $dialplan_detail_order, $dialplan_detail_group, $dialplan_detail_type, $dialplan_detail_data);
				//action answer
					$dialplan_detail_tag = 'action'; //condition, action, antiaction
					$dialplan_detail_type = 'answer';
					$dialplan_detail_data = '';
					$dialplan_detail_order = '002';
					$dialplan_detail_group = '1';
					dialplan_detail_add($_SESSION['domain_uuid'], $dialplan_uuid, $dialplan_detail_tag, $dialplan_detail_order, $dialplan_detail_group, $dialplan_detail_type, $dialplan_detail_data);
				//action fifo
					//if (strlen($pin_number) > 0) { $pin_number = "+".$pin_number; }
					//if (strlen($flags) > 0) { $flags = "+{".$flags."}"; }
					//$queue_action_data = $extension_name."@\${domain_name}".$profile.$flags.$pin_number;
					$queue_action_data = $queue_name." in";
					$dialplan_detail_tag = 'action'; //condition, action, antiaction
					$dialplan_detail_type = 'fifo';
					$dialplan_detail_data = $queue_action_data;
					$dialplan_detail_order = '003';
					$dialplan_detail_group = '1';
					dialplan_detail_add($_SESSION['domain_uuid'], $dialplan_uuid, $dialplan_detail_tag, $dialplan_detail_order, $dialplan_detail_group, $dialplan_detail_type, $dialplan_detail_data);
			}
	}


	// Caller Queue / Agent Queue
	if (strlen($agent_queue_extension_number) > 0) {
		//--------------------------------------------------------
		// Agent Queue [FIFO out]
		//<extension name="Agent_Wait">
		//	<condition field="destination_number" expression="^7010\$">
		//		<action application="set" data="fifo_music=$${hold_music}"/>
		//		<action application="answer"/>
		//		<action application="fifo" data="myq out wait"/>
		//	</condition>
		//</extension>
		//--------------------------------------------------------
			$queue_name = $extension_name."_agent@\${domain_name}";
			if (is_uuid($dialplan_uuid)) {
				//set the destination number
					$dialplan_detail_tag = 'condition'; //condition, action, antiaction
					$dialplan_detail_type = 'destination_number';
					$dialplan_detail_data = '^'.$agent_queue_extension_number.'$';
					$dialplan_detail_order = '000';
					$dialplan_detail_group = '2';
					if (strlen($agent_login_logout_extension_number) > 0) {
						$dialplan_detail_break = 'on-true';
					}
					else {
						$dialplan_detail_break = '';
					}
					dialplan_detail_add($_SESSION['domain_uuid'], $dialplan_uuid, $dialplan_detail_tag, $dialplan_detail_order, $dialplan_detail_group, $dialplan_detail_type, $dialplan_detail_data, $dialplan_detail_break);
				//set the hold music
					$dialplan_detail_tag = 'action'; //condition, action, antiaction
					$dialplan_detail_type = 'set';
					$dialplan_detail_data = 'fifo_music=$${hold_music}';
					$dialplan_detail_order = '001';
					$dialplan_detail_group = '2';
					dialplan_detail_add($_SESSION['domain_uuid'], $dialplan_uuid, $dialplan_detail_tag, $dialplan_detail_order, $dialplan_detail_group, $dialplan_detail_type, $dialplan_detail_data);
				//action answer
					$dialplan_detail_tag = 'action'; //condition, action, antiaction
					$dialplan_detail_type = 'answer';
					$dialplan_detail_data = '';
					$dialplan_detail_order = '002';
					$dialplan_detail_group = '2';
					dialplan_detail_add($_SESSION['domain_uuid'], $dialplan_uuid, $dialplan_detail_tag, $dialplan_detail_order, $dialplan_detail_group, $dialplan_detail_type, $dialplan_detail_data);
				//action fifo
					//if (strlen($pin_number) > 0) { $pin_number = "+".$pin_number; }
					//if (strlen($flags) > 0) { $flags = "+{".$flags."}"; }
					//$queue_action_data = $extension_name."@\${domain_name}".$profile.$flags.$pin_number;
					$queue_action_data = $queue_name." out wait";
					$dialplan_detail_tag = 'action'; //condition, action, antiaction
					$dialplan_detail_type = 'fifo';
					$dialplan_detail_data = $queue_action_data;
					$dialplan_detail_order = '003';
					$dialplan_detail_group = '2';
					dialplan_detail_add($_SESSION['domain_uuid'], $dialplan_uuid, $dialplan_detail_tag, $dialplan_detail_order, $dialplan_detail_group, $dialplan_detail_type, $dialplan_detail_data);
			}
	}

	// agent or member login / logout
	if (strlen($agent_login_logout_extension_number) > 0) {
		//--------------------------------------------------------
		// Agent Queue [FIFO out]
		//<extension name="Agent_Wait">
		//	<condition field="destination_number" expression="^7010\$">
		//		<action application="set" data="fifo_music=$${hold_music}"/>
		//		<action application="answer"/>
		//		<action application="fifo" data="myq out wait"/>
		//	</condition>
		//</extension>
		//--------------------------------------------------------
			$queue_name = $extension_name."@\${domain_name}";
			if (is_uuid($dialplan_uuid)) {
				//set the destination number
					$dialplan_detail_tag = 'condition'; //condition, action, antiaction
					$dialplan_detail_type = 'destination_number';
					$dialplan_detail_data = '^'.$agent_login_logout_extension_number.'$';
					$dialplan_detail_order = '000';
					$dialplan_detail_group = '3';
					dialplan_detail_add($_SESSION['domain_uuid'], $dialplan_uuid, $dialplan_detail_tag, $dialplan_detail_order, $dialplan_detail_group, $dialplan_detail_type, $dialplan_detail_data);
				//set the queue_name
					$dialplan_detail_tag = 'action'; //condition, action, antiaction
					$dialplan_detail_type = 'set';
					$dialplan_detail_data = 'queue_name='.$queue_name;
					$dialplan_detail_order = '001';
					$dialplan_detail_group = '3';
					dialplan_detail_add($_SESSION['domain_uuid'], $dialplan_uuid, $dialplan_detail_tag, $dialplan_detail_order, $dialplan_detail_group, $dialplan_detail_type, $dialplan_detail_data);
				//set the user_name
					$dialplan_detail_tag = 'action'; //condition, action, antiaction
					$dialplan_detail_type = 'set';
					$dialplan_detail_data = 'user_name=${caller_id_number}@${domain_name}';
					$dialplan_detail_order = '002';
					$dialplan_detail_group = '3';
					dialplan_detail_add($_SESSION['domain_uuid'], $dialplan_uuid, $dialplan_detail_tag, $dialplan_detail_order, $dialplan_detail_group, $dialplan_detail_type, $dialplan_detail_data);
				//set the fifo_simo
					$dialplan_detail_tag = 'action'; //condition, action, antiaction
					$dialplan_detail_type = 'set';
					$dialplan_detail_data = 'fifo_simo=1';
					$dialplan_detail_order = '003';
					$dialplan_detail_group = '3';
					dialplan_detail_add($_SESSION['domain_uuid'], $dialplan_uuid, $dialplan_detail_tag, $dialplan_detail_order, $dialplan_detail_group, $dialplan_detail_type, $dialplan_detail_data);
				//set the fifo_timeout
					$dialplan_detail_tag = 'action'; //condition, action, antiaction
					$dialplan_detail_type = 'set';
					$dialplan_detail_data = 'fifo_timeout=10';
					$dialplan_detail_order = '004';
					$dialplan_detail_group = '3';
					dialplan_detail_add($_SESSION['domain_uuid'], $dialplan_uuid, $dialplan_detail_tag, $dialplan_detail_order, $dialplan_detail_group, $dialplan_detail_type, $dialplan_detail_data);
				//set the fifo_lag
					$dialplan_detail_tag = 'action'; //condition, action, antiaction
					$dialplan_detail_type = 'set';
					$dialplan_detail_data = 'fifo_lag=10';
					$dialplan_detail_order = '005';
					$dialplan_detail_group = '3';
					dialplan_detail_add($_SESSION['domain_uuid'], $dialplan_uuid, $dialplan_detail_tag, $dialplan_detail_order, $dialplan_detail_group, $dialplan_detail_type, $dialplan_detail_data);
				//set the pin_number
					$dialplan_detail_tag = 'action'; //condition, action, antiaction
					$dialplan_detail_type = 'set';
					$dialplan_detail_data = 'pin_number=';
					$dialplan_detail_order = '006';
					$dialplan_detail_group = '3';
					dialplan_detail_add($_SESSION['domain_uuid'], $dialplan_uuid, $dialplan_detail_tag, $dialplan_detail_order, $dialplan_detail_group, $dialplan_detail_type, $dialplan_detail_data);
				//action lua
					$dialplan_detail_tag = 'action'; //condition, action, antiaction
					$dialplan_detail_type = 'lua';
					$dialplan_detail_data = 'fifo_member.lua';
					$dialplan_detail_order = '007';
					$dialplan_detail_group = '3';
					dialplan_detail_add($_SESSION['domain_uuid'], $dialplan_uuid, $dialplan_detail_tag, $dialplan_detail_order, $dialplan_detail_group, $dialplan_detail_type, $dialplan_detail_data);
			}
	}

	//synchronize the xml config
		save_dialplan_xml();

	//clear the cache
		$cache = new cache;
		$cache->delete("dialplan:".$_SESSION["context"]);

	//redirect the user
		message::add($text['message-add']);
		header("Location: ".PROJECT_PATH."/app/dialplans/dialplans.php?app_uuid=16589224-c876-aeb3-f59f-523a1c0801f7");
		return;

}

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//includes and title
	require_once "resources/header.php";
	$document['title'] = $text['title-queue_add'];

//show the content
	echo "<form method='post' name='frm' id='frm'>\n";

	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['header-queue_add']."</b></div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$_SESSION['theme']['button_icon_back'],'id'=>'btn_back','style'=>'margin-right: 15px;','link'=>PROJECT_PATH.'/app/dialplans/dialplans.php?app_uuid=16589224-c876-aeb3-f59f-523a1c0801f7']);
	echo button::create(['type'=>'submit','label'=>$text['button-save'],'icon'=>$_SESSION['theme']['button_icon_save'],'id'=>'btn_save']);
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	echo $text['description-queue_add']."\n";
	echo "<br /><br />\n";

	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";

	echo "	<tr>\n";
	echo "	<td width='30%' class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "		".$text['label-name']."\n";
	echo "	</td>\n";
	echo "	<td width='70%' class='vtable' align='left'>\n";
	echo "		<input class='formfld' type='text' name='extension_name' maxlength='255' value=\"$extension_name\" required='required'>\n";
	echo "		<br />\n";
	echo "		".$text['description-name']."\n";
	echo "	</td>\n";
	echo "	</tr>\n";

	echo "	<tr>\n";
	echo "	<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-extension']."\n";
	echo "	</td>\n";
	echo "	<td class='vtable' align='left'>\n";
	echo "		<input class='formfld' type='text' name='queue_extension_number' maxlength='255' min='0' step='1' value=\"$queue_extension_number\" required='required'>\n";
	echo "		<br />\n";
	echo "		".$text['description-extension']."\n";
	echo "	</td>\n";
	echo "	</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-order']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select name='dialplan_order' class='formfld'>\n";
	$i = 300;
	while ($i <= 999) {
		$selected = ($dialplan_order == $i) ? "selected" : null;
		if (strlen($i) == 1) { echo "<option value='00$i' ".$selected.">00$i</option>\n"; }
		if (strlen($i) == 2) { echo "<option value='0$i' ".$selected.">0$i</option>\n"; }
		if (strlen($i) == 3) { echo "<option value='$i' ".$selected.">$i</option>\n"; }
		$i++;
	}
	echo "	</select>\n";
	echo "	<br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-enabled']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <select class='formfld' name='dialplan_enabled'>\n";
	if ($dialplan_enabled == "true") {
		echo "    <option value='true' selected='selected' >".$text['option-true']."</option>\n";
	}
	else {
		echo "    <option value='true'>".$text['option-true']."</option>\n";
	}
	if ($dialplan_enabled == "false") {
		echo "    <option value='false' selected='selected' >".$text['option-false']."</option>\n";
	}
	else {
		echo "    <option value='false'>".$text['option-false']."</option>\n";
	}
	echo "    </select>\n";
	echo "<br />\n";
	echo "\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-description']."\n";
	echo "</td>\n";
	echo "<td colspan='4' class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='dialplan_description' maxlength='255' value=\"$dialplan_description\">\n";
	echo "<br />\n";
	echo "\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "</table>\n";
	echo "<br><br>\n";

	echo "<b>".$text['header-agent_details']."</b>\n";
	echo "<br /><br />\n";

	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";

	echo "<tr>\n";
	echo "<td width='30%' class='vncell' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-agent_queue_extension']."\n";
	echo "</td>\n";
	echo "<td width='70%' class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='agent_queue_extension_number' maxlength='255' min='0' step='1' value=\"$agent_queue_extension_number\">\n";
	echo "<br />\n";
	echo $text['description-agent_queue_extension']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-agent_loginout_extension']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='agent_login_logout_extension_number' maxlength='255' min='0' step='1' value=\"$agent_login_logout_extension_number\">\n";
	echo "<br />\n";
	echo $text['description-agent_loginout_extension']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "</table>\n";
	echo "<br><br>\n";

	if ($action == "update") {
		echo "<input type='hidden' name='dialplan_uuid' value='$dialplan_uuid'>\n";
	}
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";

	echo "</form>";

//show the footer
	require_once "resources/footer.php";

?>