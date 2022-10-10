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
	Copyright (C) 2010 - 2020
	All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	Luis Daniel Lucio Quiroz <dlucio@okay.com.mx>
*/

//set the include path
	$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
	set_include_path(parse_ini_file($conf[0])['document.root']);

//includes files
	require_once "resources/require.php";
	require_once "resources/check_auth.php";
	require_once "resources/paging.php";

//check permissions
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

//process the HTTP POST
	if (count($_POST) > 0 && strlen($_POST["persistformvar"]) == 0) {

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
			$dialplan_context = $_SESSION['domain_name'];
			$domain_uuid = $_SESSION['domain_uuid'];
			$dialplan_detail_order = 0;

			//start building the dialplan array
			$y=0;
			$array["dialplans"][$y]["domain_uuid"] = $domain_uuid;
			$array["dialplans"][$y]["dialplan_uuid"] = $dialplan_uuid;
			$array["dialplans"][$y]["app_uuid"] = $app_uuid;
			$array["dialplans"][$y]["dialplan_name"] = $extension_name;
			$array["dialplans"][$y]["dialplan_order"] = "$dialplan_order";
			$array["dialplans"][$y]["dialplan_context"] = $dialplan_context;
			$array["dialplans"][$y]["dialplan_enabled"] = $dialplan_enabled;
			$array["dialplans"][$y]["dialplan_order"] = $dialplan_order;
			$array["dialplans"][$y]["dialplan_description"] = $dialplan_description;
			$y++;

			if (is_uuid($dialplan_uuid)) {
				//set the destination number
				$array["dialplan_details"][$y]["domain_uuid"] = $domain_uuid;
				$array["dialplan_details"][$y]["dialplan_uuid"] = $dialplan_uuid;
				$array["dialplan_details"][$y]["dialplan_detail_tag"] = "condition";
				$array["dialplan_details"][$y]["dialplan_detail_type"] = "destination_number";
				$array["dialplan_details"][$y]["dialplan_detail_data"] = '^'.$queue_extension_number.'$';
				$array["dialplan_details"][$y]["dialplan_detail_inline"] = "";
				$array["dialplan_details"][$y]["dialplan_detail_order"] = $dialplan_detail_order;
				if ((strlen($agent_queue_extension_number) > 0) || (strlen($agent_login_logout_extension_number) > 0)) {
					$array["dialplan_details"][$y]["dialplan_detail_break"] = 'on-true';
				}
				$array["dialplan_details"][$y]["dialplan_detail_group"] = '1';
				$y++;

				//increment the dialplan detial order
				$dialplan_detail_order = $dialplan_detail_order + 10;

				//set the hold music
				$array["dialplan_details"][$y]["domain_uuid"] = $domain_uuid;
				$array["dialplan_details"][$y]["dialplan_uuid"] = $dialplan_uuid;
				$array["dialplan_details"][$y]["dialplan_detail_tag"] = "action";
				$array["dialplan_details"][$y]["dialplan_detail_type"] = "set";
				$array["dialplan_details"][$y]["dialplan_detail_data"] = "fifo_music=\$\${hold_music}";
				$array["dialplan_details"][$y]["dialplan_detail_inline"] = "true";
				$array["dialplan_details"][$y]["dialplan_detail_order"] = $dialplan_detail_order;
				$array["dialplan_details"][$y]["dialplan_detail_group"] = '1';
				$y++;

				//increment the dialplan detial order
				$dialplan_detail_order = $dialplan_detail_order + 10;

				//action answer
				$array["dialplan_details"][$y]["domain_uuid"] = $domain_uuid;
				$array["dialplan_details"][$y]["dialplan_uuid"] = $dialplan_uuid;
				$array["dialplan_details"][$y]["dialplan_detail_tag"] = "action";
				$array["dialplan_details"][$y]["dialplan_detail_type"] = "answer";
				$array["dialplan_details"][$y]["dialplan_detail_data"] = "";
				$array["dialplan_details"][$y]["dialplan_detail_inline"] = "";
				$array["dialplan_details"][$y]["dialplan_detail_order"] = $dialplan_detail_order;
				$array["dialplan_details"][$y]["dialplan_detail_group"] = '1';
				$y++;

				//increment the dialplan detial order
				$dialplan_detail_order = $dialplan_detail_order + 10;

				//action fifo
				$queue_action_data = $queue_name." in";
				$array["dialplan_details"][$y]["domain_uuid"] = $domain_uuid;
				$array["dialplan_details"][$y]["dialplan_uuid"] = $dialplan_uuid;
				$array["dialplan_details"][$y]["dialplan_detail_tag"] = "action";
				$array["dialplan_details"][$y]["dialplan_detail_type"] = "fifo";
				$array["dialplan_details"][$y]["dialplan_detail_data"] = $queue_action_data;
				$array["dialplan_details"][$y]["dialplan_detail_inline"] = "";
				$array["dialplan_details"][$y]["dialplan_detail_order"] = $dialplan_detail_order;
				$array["dialplan_details"][$y]["dialplan_detail_group"] = '1';
				$y++;
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
			$queue_name = $extension_name."@\${domain_name}";
			if (is_uuid($dialplan_uuid)) {

				//set the dialplan detial order to zero
				$dialplan_detail_order = 0;

				//set the destination number
				$array["dialplan_details"][$y]["domain_uuid"] = $domain_uuid;
				$array["dialplan_details"][$y]["dialplan_uuid"] = $dialplan_uuid;
				$array["dialplan_details"][$y]["dialplan_detail_tag"] = "condition";
				$array["dialplan_details"][$y]["dialplan_detail_type"] = "destination_number";
				$array["dialplan_details"][$y]["dialplan_detail_data"] = '^'.$agent_queue_extension_number.'$';
				$array["dialplan_details"][$y]["dialplan_detail_inline"] = "";
				if (strlen($agent_login_logout_extension_number) > 0) {
					$array["dialplan_details"][$y]["dialplan_detail_break"] = 'on-true';
				}
				$array["dialplan_details"][$y]["dialplan_detail_order"] = $dialplan_detail_order;
				$array["dialplan_details"][$y]["dialplan_detail_group"] = '2';
				$y++;

				//increment the dialplan detial order
				$dialplan_detail_order = $dialplan_detail_order + 10;

				//set the hold music
				$array["dialplan_details"][$y]["domain_uuid"] = $domain_uuid;
				$array["dialplan_details"][$y]["dialplan_uuid"] = $dialplan_uuid;
				$array["dialplan_details"][$y]["dialplan_detail_tag"] = "action";
				$array["dialplan_details"][$y]["dialplan_detail_type"] = "set";
				$array["dialplan_details"][$y]["dialplan_detail_data"] = "fifo_music=\$\${hold_music}";
				$array["dialplan_details"][$y]["dialplan_detail_inline"] = "true";
				$array["dialplan_details"][$y]["dialplan_detail_order"] = $dialplan_detail_order;
				$array["dialplan_details"][$y]["dialplan_detail_group"] = '2';
				$y++;

				//increment the dialplan detial order
				$dialplan_detail_order = $dialplan_detail_order + 10;

				//action answer
				$array["dialplan_details"][$y]["domain_uuid"] = $domain_uuid;
				$array["dialplan_details"][$y]["dialplan_uuid"] = $dialplan_uuid;
				$array["dialplan_details"][$y]["dialplan_detail_tag"] = "action";
				$array["dialplan_details"][$y]["dialplan_detail_type"] = "answer";
				$array["dialplan_details"][$y]["dialplan_detail_data"] = "";
				$array["dialplan_details"][$y]["dialplan_detail_inline"] = "";
				$array["dialplan_details"][$y]["dialplan_detail_order"] = $dialplan_detail_order;
				$array["dialplan_details"][$y]["dialplan_detail_group"] = '2';
				$y++;

				//increment the dialplan detial order
				$dialplan_detail_order = $dialplan_detail_order + 10;

				//action fifo
				$queue_action_data = $queue_name." out wait";
				$array["dialplan_details"][$y]["domain_uuid"] = $domain_uuid;
				$array["dialplan_details"][$y]["dialplan_uuid"] = $dialplan_uuid;
				$array["dialplan_details"][$y]["dialplan_detail_tag"] = "action";
				$array["dialplan_details"][$y]["dialplan_detail_type"] = "fifo";
				$array["dialplan_details"][$y]["dialplan_detail_data"] = $queue_action_data;
				$array["dialplan_details"][$y]["dialplan_detail_inline"] = "";
				$array["dialplan_details"][$y]["dialplan_detail_order"] = $dialplan_detail_order;
				$array["dialplan_details"][$y]["dialplan_detail_group"] = '2';
				$y++;
			}
		}

		// agent or member login / logout
		if (strlen($agent_login_logout_extension_number) > 0) {
			//--------------------------------------------------------
			// Agent Queue [FIFO login logout]
			//<extension name="Agent_login_logout">
			//	<condition field="destination_number" expression="^7012\$">
			//		<action application="set" data="queue_name=myq" inline="true"/>
			//		<action application="set" data="user_name=${caller_id_number}@${domain_name}" inline="true"/>
			//		<action application="set" data="fifo_simo=1" inline="true"/>
			//		<action application="set" data="fifo_timeout=10" inline="true"/>
			//		<action application="set" data="fifo_lag=10" inline="true"/>
			//		<action application="set" data="pin_number=" inline="true"/>
			//		<action application="lua" data="fifo_member.lua"/>
			//	</condition>
			//</extension>
			//--------------------------------------------------------
			$queue_name = $extension_name."@\${domain_name}";
			if (is_uuid($dialplan_uuid)) {

				//set the dialplan detial order to zero
				$dialplan_detail_order = 0;

				//set the destination number
				$array["dialplan_details"][$y]["domain_uuid"] = $domain_uuid;
				$array["dialplan_details"][$y]["dialplan_uuid"] = $dialplan_uuid;
				$array["dialplan_details"][$y]["dialplan_detail_tag"] = "condition";
				$array["dialplan_details"][$y]["dialplan_detail_type"] = "destination_number";
				$array["dialplan_details"][$y]["dialplan_detail_data"] = '^'.$agent_login_logout_extension_number.'$';
				$array["dialplan_details"][$y]["dialplan_detail_inline"] = "";
				$array["dialplan_details"][$y]["dialplan_detail_break"] = 'on-true';
				$array["dialplan_details"][$y]["dialplan_detail_order"] = $dialplan_detail_order;
				$array["dialplan_details"][$y]["dialplan_detail_group"] = '3';
				$y++;

				//increment the dialplan detial order
				$dialplan_detail_order = $dialplan_detail_order + 10;

				//set the queue_name
				$array["dialplan_details"][$y]["domain_uuid"] = $domain_uuid;
				$array["dialplan_details"][$y]["dialplan_uuid"] = $dialplan_uuid;
				$array["dialplan_details"][$y]["dialplan_detail_tag"] = "action";
				$array["dialplan_details"][$y]["dialplan_detail_type"] = "set";
				$array["dialplan_details"][$y]["dialplan_detail_data"] = 'queue_name='.$queue_name;
				$array["dialplan_details"][$y]["dialplan_detail_inline"] = "true";
				$array["dialplan_details"][$y]["dialplan_detail_order"] = $dialplan_detail_order;
				$array["dialplan_details"][$y]["dialplan_detail_group"] = '3';
				$y++;

				//increment the dialplan detial order
				$dialplan_detail_order = $dialplan_detail_order + 10;

				//set the user_name
				$array["dialplan_details"][$y]["domain_uuid"] = $domain_uuid;
				$array["dialplan_details"][$y]["dialplan_uuid"] = $dialplan_uuid;
				$array["dialplan_details"][$y]["dialplan_detail_tag"] = "action";
				$array["dialplan_details"][$y]["dialplan_detail_type"] = "set";
				$array["dialplan_details"][$y]["dialplan_detail_data"] = 'user_name=${caller_id_number}@${domain_name}';
				$array["dialplan_details"][$y]["dialplan_detail_inline"] = "true";
				$array["dialplan_details"][$y]["dialplan_detail_order"] = $dialplan_detail_order;
				$array["dialplan_details"][$y]["dialplan_detail_group"] = '3';
				$y++;

				//increment the dialplan detial order
				$dialplan_detail_order = $dialplan_detail_order + 10;

				//set the fifo_simo
				$array["dialplan_details"][$y]["domain_uuid"] = $domain_uuid;
				$array["dialplan_details"][$y]["dialplan_uuid"] = $dialplan_uuid;
				$array["dialplan_details"][$y]["dialplan_detail_tag"] = "action";
				$array["dialplan_details"][$y]["dialplan_detail_type"] = "set";
				$array["dialplan_details"][$y]["dialplan_detail_data"] = 'fifo_simo=1';
				$array["dialplan_details"][$y]["dialplan_detail_inline"] = "true";
				$array["dialplan_details"][$y]["dialplan_detail_order"] = $dialplan_detail_order;
				$array["dialplan_details"][$y]["dialplan_detail_group"] = '3';
				$y++;

				//increment the dialplan detial order
				$dialplan_detail_order = $dialplan_detail_order + 10;

				//set the fifo_timeout
				$array["dialplan_details"][$y]["domain_uuid"] = $domain_uuid;
				$array["dialplan_details"][$y]["dialplan_uuid"] = $dialplan_uuid;
				$array["dialplan_details"][$y]["dialplan_detail_tag"] = "action";
				$array["dialplan_details"][$y]["dialplan_detail_type"] = "set";
				$array["dialplan_details"][$y]["dialplan_detail_data"] = 'fifo_timeout=10';
				$array["dialplan_details"][$y]["dialplan_detail_inline"] = "true";
				$array["dialplan_details"][$y]["dialplan_detail_order"] = $dialplan_detail_order;
				$array["dialplan_details"][$y]["dialplan_detail_group"] = '3';
				$y++;

				//increment the dialplan detial order
				$dialplan_detail_order = $dialplan_detail_order + 10;

				//set the fifo_lag
				$array["dialplan_details"][$y]["domain_uuid"] = $domain_uuid;
				$array["dialplan_details"][$y]["dialplan_uuid"] = $dialplan_uuid;
				$array["dialplan_details"][$y]["dialplan_detail_tag"] = "action";
				$array["dialplan_details"][$y]["dialplan_detail_type"] = "set";
				$array["dialplan_details"][$y]["dialplan_detail_data"] = 'fifo_lag=10';
				$array["dialplan_details"][$y]["dialplan_detail_inline"] = "true";
				$array["dialplan_details"][$y]["dialplan_detail_order"] = $dialplan_detail_order;
				$array["dialplan_details"][$y]["dialplan_detail_group"] = '3';
				$y++;

				//increment the dialplan detial order
				$dialplan_detail_order = $dialplan_detail_order + 10;

				//set the pin_number
				$array["dialplan_details"][$y]["domain_uuid"] = $domain_uuid;
				$array["dialplan_details"][$y]["dialplan_uuid"] = $dialplan_uuid;
				$array["dialplan_details"][$y]["dialplan_detail_tag"] = "action";
				$array["dialplan_details"][$y]["dialplan_detail_type"] = "set";
				$array["dialplan_details"][$y]["dialplan_detail_data"] = 'pin_number=';
				$array["dialplan_details"][$y]["dialplan_detail_inline"] = "true";
				$array["dialplan_details"][$y]["dialplan_detail_order"] = $dialplan_detail_order;
				$array["dialplan_details"][$y]["dialplan_detail_group"] = '3';
				$y++;

				//increment the dialplan detial order
				$dialplan_detail_order = $dialplan_detail_order + 10;

				//action lua
				$array["dialplan_details"][$y]["domain_uuid"] = $domain_uuid;
				$array["dialplan_details"][$y]["dialplan_uuid"] = $dialplan_uuid;
				$array["dialplan_details"][$y]["dialplan_detail_tag"] = "action";
				$array["dialplan_details"][$y]["dialplan_detail_type"] = "lua";
				$array["dialplan_details"][$y]["dialplan_detail_data"] = "fifo_member.lua";
				$array["dialplan_details"][$y]["dialplan_detail_inline"] = "";
				$array["dialplan_details"][$y]["dialplan_detail_order"] = $dialplan_detail_order;
				$array["dialplan_details"][$y]["dialplan_detail_group"] = '3';
				$y++;
			}
		}

		//add the dialplan permission
		$p = new permissions;
		$p->add("dialplan_add", "temp");
		$p->add("dialplan_edit", "temp");

		//save to the data
		$database = new database;
		$database->app_name = 'fifo';
		$database->app_uuid = '16589224-c876-aeb3-f59f-523a1c0801f7';
		$database->save($array);
		//$message = $database->message;

		//remove the temporary permission
		$p->delete("dialplan_add", "temp");
		$p->delete("dialplan_edit", "temp");

		//clear the cache
		$cache = new cache;
		$cache->delete("dialplan:".$_SESSION["domain_name"]);

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
