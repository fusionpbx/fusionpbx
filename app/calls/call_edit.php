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
	Portions created by the Initial Developer are Copyright (C) 2008-2014
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/
require_once "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
require_once "app_languages.php";
if (permission_exists('follow_me') || permission_exists('call_forward') || permission_exists('do_not_disturb')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
	foreach($text as $key => $value) {
		$text[$key] = $value[$_SESSION['domain']['language']['code']];
	}

//define the destination_select function
	function destination_select($select_name, $select_value, $select_default) {
		if (strlen($select_value) == 0) { $select_value = $select_default; }
		echo "	<select class='formfld' style='width: 55px;' name='$select_name'>\n";
		$i = 0;
		while($i <= 100) {
			if ($select_value == $i) {
				echo "	<option value='$i' selected='selected'>$i</option>\n";
			}
			else {
				echo "	<option value='$i'>$i</option>\n";
			}
			$i = $i + 5;
		}
		echo "</select>\n";
	}

//get the extension_uuid
	$extension_uuid = check_str($_REQUEST["id"]);

//get the extension number
	$sql = "select * from v_extensions ";
	$sql .= "where domain_uuid = '$domain_uuid' ";
	$sql .= "and extension_uuid = '$extension_uuid' ";
	if (!(if_group("admin") || if_group("superadmin"))) {
		if (count($_SESSION['user']['extension']) > 0) {
			$sql .= "and (";
			$x = 0;
			foreach($_SESSION['user']['extension'] as $row) {
				if ($x > 0) { $sql .= "or "; }
				$sql .= "extension = '".$row['user']."' ";
				$x++;
			}
			$sql .= ")";
		}
		else {
			//hide any results when a user has not been assigned an extension
			$sql .= "and extension = 'disabled' ";
		}
	}
	$sql .= "and enabled = 'true' ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	if (count($result)== 0) {
		echo "access denied";
		exit;
	}
	else {
		foreach ($result as &$row) {
			$extension = $row["extension"];
			$accountcode = $row["accountcode"];
			$effective_caller_id_name = $row["effective_caller_id_name"];
			$effective_caller_id_number = $row["effective_caller_id_number"];
			$outbound_caller_id_name = $row["outbound_caller_id_name"];
			$outbound_caller_id_number = $row["outbound_caller_id_number"];
			$do_not_disturb = $row["do_not_disturb"];
			$forward_all_destination = $row["forward_all_destination"];
			$forward_all_enabled = $row["forward_all_enabled"];
			$forward_busy_destination = $row["forward_busy_destination"];
			$forward_busy_enabled = $row["forward_busy_enabled"];
			$follow_me_uuid = $row["follow_me_uuid"];
			break; //limit to 1 row
		}
		if (strlen($do_not_disturb) == 0) {
			$do_not_disturb = "false";
		}
	}
	unset ($prep_statement);

if (count($_POST)>0 && strlen($_POST["persistformvar"]) == 0) {

	//get http post variables and set them to php variables
		if (count($_POST)>0) {
			$forward_all_enabled = check_str($_POST["forward_all_enabled"]);
			$forward_all_destination = check_str($_POST["forward_all_destination"]);
			$cid_name_prefix = check_str($_POST["cid_name_prefix"]);
			$cid_number_prefix = check_str($_POST["cid_number_prefix"]);
			$follow_me_enabled = check_str($_POST["follow_me_enabled"]);

			$destination_data_1 = check_str($_POST["destination_data_1"]);
			$destination_delay_1 = check_str($_POST["destination_delay_1"]);
			$destination_prompt_1 = check_str($_POST["destination_prompt_1"]);
			$destination_timeout_1 = check_str($_POST["destination_timeout_1"]);

			$destination_data_2 = check_str($_POST["destination_data_2"]);
			$destination_delay_2 = check_str($_POST["destination_delay_2"]);
			$destination_prompt_2 = check_str($_POST["destination_prompt_2"]);
			$destination_timeout_2 = check_str($_POST["destination_timeout_2"]);

			$destination_data_3 = check_str($_POST["destination_data_3"]);
			$destination_delay_3 = check_str($_POST["destination_delay_3"]);
			$destination_prompt_3 = check_str($_POST["destination_prompt_3"]);
			$destination_timeout_3 = check_str($_POST["destination_timeout_3"]);

			$destination_data_4 = check_str($_POST["destination_data_4"]);
			$destination_delay_4 = check_str($_POST["destination_delay_4"]);
			$destination_prompt_4 = check_str($_POST["destination_prompt_4"]);
			$destination_timeout_4 = check_str($_POST["destination_timeout_4"]);

			$destination_data_5 = check_str($_POST["destination_data_5"]);
			$destination_delay_5 = check_str($_POST["destination_delay_5"]);
			$destination_prompt_5 = check_str($_POST["destination_prompt_5"]);
			$destination_timeout_5 = check_str($_POST["destination_timeout_5"]);

			$dnd_enabled = check_str($_POST["dnd_enabled"]);

			if (strlen($forward_all_destination) > 0) {
			//	$forward_all_destination = preg_replace("~[^0-9]~", "",$forward_all_destination);
			}
			if (strlen($destination_data_1) > 0) {
			//	$destination_data_1 = preg_replace("~[^0-9]~", "",$destination_data_1);
			}
			if (strlen($destination_data_2) > 0) {
			//	$destination_data_2 = preg_replace("~[^0-9]~", "",$destination_data_2);
			}
			if (strlen($destination_data_3) > 0) {
			//	$destination_data_3 = preg_replace("~[^0-9]~", "",$destination_data_3);
			}
			if (strlen($destination_data_4) > 0) {
			//	$destination_data_4 = preg_replace("~[^0-9]~", "",$destination_data_4);
			}
			if (strlen($destination_data_5) > 0) {
			//	$destination_data_5 = preg_replace("~[^0-9]~", "",$destination_data_5);
			}
		}

		//check for all required data
			//if (strlen($forward_all_enabled) == 0) { $msg .= "Please provide: Call Forward<br>\n"; }
			//if (strlen($forward_all_destination) == 0) { $msg .= "Please provide: Number<br>\n"; }
			//if (strlen($follow_me_enabled) == 0) { $msg .= "Please provide: Follow Me<br>\n"; }
			//if (strlen($destination_data_1) == 0) { $msg .= "Please provide: 1st Number<br>\n"; }
			//if (strlen($destination_timeout_1) == 0) { $msg .= "Please provide: sec<br>\n"; }
			//if (strlen($destination_data_2) == 0) { $msg .= "Please provide: 2nd Number<br>\n"; }
			//if (strlen($destination_timeout_2) == 0) { $msg .= "Please provide: sec<br>\n"; }
			//if (strlen($destination_data_3) == 0) { $msg .= "Please provide: 3rd Number<br>\n"; }
			//if (strlen($destination_timeout_3) == 0) { $msg .= "Please provide: sec<br>\n"; }
			//if (strlen($destination_data_4) == 0) { $msg .= "Please provide: 4th Number<br>\n"; }
			//if (strlen($destination_timeout_4) == 0) { $msg .= "Please provide: sec<br>\n"; }
			//if (strlen($destination_data_5) == 0) { $msg .= "Please provide: 5th Number<br>\n"; }
			//if (strlen($destination_timeout_5) == 0) { $msg .= "Please provide: sec<br>\n"; }
			//if (strlen($destination_data_6) == 0) { $msg .= "Please provide: 6th Number<br>\n"; }
			//if (strlen($destination_timeout_6) == 0) { $msg .= "Please provide: sec<br>\n"; }
			//if (strlen($destination_data_7) == 0) { $msg .= "Please provide: 7th Number<br>\n"; }
			//if (strlen($destination_timeout_7) == 0) { $msg .= "Please provide: sec<br>\n"; }
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

	//set the default action to add
		$call_forward_action = "add";

	//determine if this is an add or an update
		if (strlen($follow_me_uuid) == 0) {
			$follow_me_action = "add";
		}
		else {
			$follow_me_action = "update";
		}

	//include the classes
		include "resources/classes/call_forward.php";
		include "resources/classes/follow_me.php";
		include "resources/classes/do_not_disturb.php";

	//call forward config
		if (permission_exists('call_forward')) {
			$call_forward = new call_forward;
			$call_forward->domain_uuid = $_SESSION['domain_uuid'];
			$call_forward->domain_name = $_SESSION['domain_name'];
			$call_forward->extension_uuid = $extension_uuid;
			$call_forward->accountcode = $accountcode;
			$call_forward->forward_all_destination = $forward_all_destination;
			$call_forward->forward_all_enabled = $forward_all_enabled;
			//$call_forward->set();
			//unset($call_forward);
		}

	//do not disturb (dnd) config
		if (permission_exists('do_not_disturb')) {
			$dnd = new do_not_disturb;
			$dnd->domain_uuid = $_SESSION['domain_uuid'];
			$dnd->domain_name = $_SESSION['domain_name'];
			$dnd->extension_uuid = $extension_uuid;
			$dnd->enabled = $dnd_enabled;
			//$dnd->set();
			//$dnd->user_status();
			//unset($dnd);
		}

	//if follow me is enabled then process it last
		if ($follow_me_enabled == "true") {
			//call forward
				$call_forward->set();
				unset($call_forward);
			//dnd
				$dnd->set();
				$dnd->user_status();
				unset($dnd);
		}

	//follow me config
		if (permission_exists('follow_me')) {
			$follow_me = new follow_me;
			$follow_me->domain_uuid = $_SESSION['domain_uuid'];
			$follow_me->domain_name = $_SESSION['domain_name'];
			$follow_me->extension_uuid = $extension_uuid;
			$follow_me->db_type = $db_type;
			$follow_me->cid_name_prefix = $cid_name_prefix;
			$follow_me->cid_number_prefix = $cid_number_prefix;
			$follow_me->follow_me_enabled = $follow_me_enabled;

			$follow_me->destination_data_1 = $destination_data_1;
			$follow_me->destination_type_1 = $destination_type_1;
			$follow_me->destination_delay_1 = $destination_delay_1;
			$follow_me->destination_prompt_1 = $destination_prompt_1;
			$follow_me->destination_timeout_1 = $destination_timeout_1;

			$follow_me->destination_data_2 = $destination_data_2;
			$follow_me->destination_type_2 = $destination_type_2;
			$follow_me->destination_delay_2 = $destination_delay_2;
			$follow_me->destination_prompt_2 = $destination_prompt_2;
			$follow_me->destination_timeout_2 = $destination_timeout_2;

			$follow_me->destination_data_3 = $destination_data_3;
			$follow_me->destination_type_3 = $destination_type_3;
			$follow_me->destination_delay_3 = $destination_delay_3;
			$follow_me->destination_prompt_3 = $destination_prompt_3;
			$follow_me->destination_timeout_3 = $destination_timeout_3;

			$follow_me->destination_data_4 = $destination_data_4;
			$follow_me->destination_type_4 = $destination_type_4;
			$follow_me->destination_delay_4 = $destination_delay_4;
			$follow_me->destination_prompt_4 = $destination_prompt_4;
			$follow_me->destination_timeout_4 = $destination_timeout_4;

			$follow_me->destination_data_5 = $destination_data_5;
			$follow_me->destination_type_5 = $destination_type_5;
			$follow_me->destination_delay_5 = $destination_delay_5;
			$follow_me->destination_prompt_5 = $destination_prompt_5;
			$follow_me->destination_timeout_5 = $destination_timeout_5;

			if ($follow_me_enabled == "true") {
				if ($follow_me_action == "add") {
					$follow_me_uuid = uuid();

					$sql = "update v_extensions set ";
					$sql .= "follow_me_uuid = '$follow_me_uuid' ";
					$sql .= "where domain_uuid = '$domain_uuid' ";
					$sql .= "and extension_uuid = '$extension_uuid' ";
					$db->exec(check_sql($sql));
					unset($sql);

					$follow_me->follow_me_uuid = $follow_me_uuid;
					$follow_me->add();
					$follow_me->set();
				}
			}
			if ($follow_me_action == "update") {
				$follow_me->follow_me_uuid = $follow_me_uuid;
				$follow_me->update();
				$follow_me->set();
			}
			unset($follow_me);
		}

	//if dnd or call forward are enabled process it last
		if ($follow_me_enabled != "true") {
			if ($forward_all_enabled == "true") {
				//dnd
					$dnd->set();
					$dnd->user_status();
					unset($dnd);
				//call forward
					$call_forward->set();
					unset($call_forward);
			}
			else{
				//call forward
					$call_forward->set();
					unset($call_forward);
				//dnd
					$dnd->set();
					$dnd->user_status();
					unset($dnd);
			}
		}

	//synchronize configuration
		if (is_readable($_SESSION['switch']['extensions']['dir'])) {
			require_once "app/extensions/resources/classes/extension.php";
			$ext = new extension;
			$ext->xml();
			unset($ext);
		}

	//delete extension from memcache
		$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
		if ($fp) {
			$switch_cmd = "memcache delete directory:".$extension."@".$_SESSION['domain_name'];
			$switch_result = event_socket_request($fp, 'api '.$switch_cmd);
		}

	//redirect the user
		$_SESSION["message"] = $text['confirm-update'];
		header("Location: ".PROJECT_PATH."/core/user_settings/user_dashboard.php");
		return;

} //(count($_POST)>0 && strlen($_POST["persistformvar"]) == 0)

//show the header
	require_once "resources/header.php";

//pre-populate the form
	$sql = "select * from v_follow_me ";
	$sql .= "where domain_uuid = '$domain_uuid' ";
	$sql .= "and follow_me_uuid = '$follow_me_uuid' ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	foreach ($result as &$row) {
		$cid_name_prefix = $row["cid_name_prefix"];
		$cid_number_prefix = $row["cid_number_prefix"];
		$follow_me_enabled = $row["follow_me_enabled"];

		$sql = "select * from v_follow_me_destinations ";
		$sql .= "where follow_me_uuid = '$follow_me_uuid' ";
		$sql .= "order by follow_me_order asc ";
		$prep_statement_2 = $db->prepare(check_sql($sql));
		$prep_statement_2->execute();
		$result2 = $prep_statement_2->fetchAll(PDO::FETCH_NAMED);
		$x = 1;
		foreach ($result2 as &$row2) {
			if ($x == 1) {
				$destination_data_1 = $row2["follow_me_destination"];
				$destination_delay_1 = $row2["follow_me_delay"];
				$destination_prompt_1 = $row2["follow_me_prompt"];
				$destination_timeout_1 = $row2["follow_me_timeout"];
			}
			if ($x == 2) {
				$destination_data_2 = $row2["follow_me_destination"];
				$destination_delay_2 = $row2["follow_me_delay"];
				$destination_prompt_2 = $row2["follow_me_prompt"];
				$destination_timeout_2 = $row2["follow_me_timeout"];
			}
			if ($x == 3) {
				$destination_data_3 = $row2["follow_me_destination"];
				$destination_delay_3 = $row2["follow_me_delay"];
				$destination_prompt_3 = $row2["follow_me_prompt"];
				$destination_timeout_3 = $row2["follow_me_timeout"];
			}
			if ($x == 4) {
				$destination_data_4 = $row2["follow_me_destination"];
				$destination_delay_4 = $row2["follow_me_delay"];
				$destination_prompt_4 = $row2["follow_me_prompt"];
				$destination_timeout_4 = $row2["follow_me_timeout"];
			}
			if ($x == 5) {
				$destination_data_5 = $row2["follow_me_destination"];
				$destination_delay_5 = $row2["follow_me_delay"];
				$destination_prompt_5 = $row2["follow_me_prompt"];
				$destination_timeout_5 = $row2["follow_me_timeout"];
			}
			$x++;
		}
		unset ($prep_statement_2);
	}
	unset ($prep_statement);

//set the default
	if (!isset($dnd_enabled)) {
		//set the value from the database
		$dnd_enabled = $do_not_disturb;
	}

//prepare the autocomplete
	echo "<script src=\"".PROJECT_PATH."/resources/jquery/jquery-1.8.3.js\"></script>\n";
	echo "<script src=\"".PROJECT_PATH."/resources/jquery/jquery-ui-1.9.2.min.js\"></script>\n";
	echo "<link rel=\"stylesheet\" href=\"".PROJECT_PATH."/resources/jquery/jquery-ui.css\" />\n";
	echo "<script type=\"text/javascript\">\n";
	echo "\$(function() {\n";
	echo "	var extensions = [\n";

	$sql = "select * from v_extensions ";
	$sql .= "where domain_uuid = '$domain_uuid' ";
	$sql .= "order by extension, number_alias asc ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	foreach ($result as &$row) {
		if (strlen($number_alias) == 0) {
			echo "		\"".$row["extension"]."\",\n";
		}
		else {
			echo "		\"".$row["number_alias"]."\",\n";
		}
	}
	echo "	];\n";
	echo "	\$(\"#destination_data_1\").autocomplete({\n";
	echo "		source: extensions\n";
	echo "	});\n";
	echo "	\$(\"#destination_data_2\").autocomplete({\n";
	echo "		source: extensions\n";
	echo "	});\n";
	echo "	\$(\"#destination_data_3\").autocomplete({\n";
	echo "		source: extensions\n";
	echo "	});\n";
	echo "	\$(\"#destination_data_4\").autocomplete({\n";
	echo "		source: extensions\n";
	echo "	});\n";
	echo "	\$(\"#destination_data_5\").autocomplete({\n";
	echo "		source: extensions\n";
	echo "	});\n";
	echo "});\n";
	echo "</script>\n";

//show the content
	echo "<div align='center'>";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing=''>\n";
	echo "<tr class='border'>\n";
	echo "	<td align=\"center\">\n";
	echo "		<br>";

	echo "<form method='post' name='frm' action=''>\n";
	echo "<table width='100%'  border='0' cellpadding='6' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "<td align='left' width='30%' nowrap='nowrap'>\n";
	echo "	<b>".$text['title']."</b>\n";
	echo "</td>\n";
	echo "<td width='70%' align='right'>\n";
	echo "	<input type='button' class='btn' name='' alt='back' onclick=\"window.location='calls.php'\" value='".$text['button-back']."'>\n";
	echo "	<input type='submit' name='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td align='left' colspan='2'>\n";
	echo "	".$text['description']." $extension.<br /><br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	<strong>".$text['label-call-forward'].":</strong>\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	$on_click = "document.getElementById('follow_me_enabled').checked=true;";
	$on_click .= "document.getElementById('follow_me_disabled').checked=true;";
	$on_click .= "document.getElementById('dnd_enabled').checked=false;";
	$on_click .= "document.getElementById('dnd_disabled').checked=true;";
	if ($forward_all_enabled == "true") {
		echo "	<input type='radio' name='forward_all_enabled' id='forward_all_enabled' onclick=\"$on_click\" value='true' checked='checked'/> ".$text['label-enabled']." \n";
	}
	else {
		echo "	<input type='radio' name='forward_all_enabled' id='forward_all_enabled' onclick=\"$on_click\" value='true' /> ".$text['label-enable']." \n";
	}
	if ($forward_all_enabled == "false" || $forward_all_enabled == "") {
		echo "	<input type='radio' name='forward_all_enabled' id='forward_all_disabled' onclick=\"\" value='false' checked='checked' /> ".$text['label-disabled']." \n";
	}
	else {
		echo "	<input type='radio' name='forward_all_enabled' id='forward_all_disabled' onclick=\"\" value='false' /> ".$text['label-disable']." \n";
	}
	unset($on_click);
	echo "<br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-number'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='forward_all_destination' maxlength='255' value=\"$forward_all_destination\">\n";
	echo "<br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td colspan='2'>\n";
	echo "	<br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	<strong>".$text['label-follow-me'].":</strong>\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	$on_click = "document.getElementById('forward_all_enabled').checked=true;";
	$on_click .= "document.getElementById('forward_all_disabled').checked=true;";
	$on_click .= "document.getElementById('dnd_enabled').checked=false;";
	$on_click .= "document.getElementById('dnd_disabled').checked=true;";
	if ($follow_me_enabled == "true") {
		echo "	<input type='radio' name='follow_me_enabled' id='follow_me_enabled' value='true' onclick=\"$on_click\" checked='checked'/> ".$text['label-enabled']." \n";
	}
	else {
		echo "	<input type='radio' name='follow_me_enabled' id='follow_me_enabled' value='true' onclick=\"$on_click\" /> ".$text['label-enable']." \n";
	}
	if ($follow_me_enabled == "false" || $follow_me_enabled == "") {
		echo "	<input type='radio' name='follow_me_enabled' id='follow_me_disabled' value='false' onclick=\"\" checked='checked' /> ".$text['label-disabled']." \n";
	}
	else {
		echo "	<input type='radio' name='follow_me_enabled' id='follow_me_disabled' value='false' onclick=\"\" /> ".$text['label-disable']." \n";
	}
	unset($on_click);
	echo "<br />\n";
	echo "<br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-destinations'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";

	echo "	<table border='0' cellpadding='2' cellspacing='0'>\n";
	echo "		<tr>\n";
	echo "			<td class='vtable'>".$text['label-destination_number']."</td>\n";
	echo "			<td class='vtable'>".$text['label-destination_delay']."</td>\n";
	echo "			<td class='vtable'>".$text['label-destination_timeout']."</td>\n";
	if (permission_exists('follow_me_prompt')) {
		echo "		<td class='vtable'>".$text['label-destination_prompt']."</td>\n";
	}
	echo "		</tr>\n";

	// 1st destination
	echo "		<tr>\n";
	echo "			<td><input class='formfld' type='text' name='destination_data_1' id='destination_data_1' maxlength='255' value=\"".$destination_data_1."\"></td>\n";
	echo "			<td>\n";
							destination_select('destination_delay_1', $destination_delay_1, '0');
	echo "			</td>\n";
	echo "			<td>\n";
							destination_select('destination_timeout_1', $destination_timeout_1, '30');
	echo "			</td>\n";
	if (permission_exists('follow_me_prompt')) {
		echo "		<td>\n";
		echo "			<select class='formfld' style='width: 90px;' name='destination_prompt_1'>\n";
		echo "				<option value=''></option>\n";
		echo "				<option value='1' ".(($destination_prompt_1)?"selected='selected'":null).">".$text['label-destination_prompt_confirm']."</option>\n";
		//echo "			<option value='2'>".$text['label-destination_prompt_announce]."</option>\n";
		echo "			</select>\n";
		echo "		</td>\n";
	}
	echo "		</tr>\n";

	// 2nd destination
	echo "		<tr>\n";
	echo "			<td><input class='formfld' type='text' name='destination_data_2' id='destination_data_2' maxlength='255' value=\"".$destination_data_2."\"></td>\n";
	echo "			<td>\n";
						destination_select('destination_delay_2', $destination_delay_2, '0');
	echo "			</td>\n";
	echo "			<td>\n";
						destination_select('destination_timeout_2', $destination_timeout_2, '30');
	echo "			</td>\n";
	if (permission_exists('follow_me_prompt')) {
		echo "		<td>\n";
		echo "			<select class='formfld' style='width: 90px;' name='destination_prompt_2'>\n";
		echo "				<option value=''></option>\n";
		echo "				<option value='1' ".(($destination_prompt_2)?"selected='selected'":null).">".$text['label-destination_prompt_confirm']."</option>\n";
		//echo "			<option value='2'>".$text['label-destination_prompt_announce]."</option>\n";
		echo "			</select>\n";
		echo "		</td>\n";
	}
	echo "		</tr>\n";

	// 3rd destination
	echo "		<tr>\n";
	echo "			<td><input class='formfld' type='text' name='destination_data_3' id='destination_data_3' maxlength='255' value=\"".$destination_data_3."\"></td>\n";
	echo "			<td>\n";
						destination_select('destination_delay_3', $destination_delay_3, '0');
	echo "			</td>\n";
	echo "			<td>\n";
						destination_select('destination_timeout_3', $destination_timeout_3, '30');
	echo "			</td>\n";
	if (permission_exists('follow_me_prompt')) {
		echo "		<td>\n";
		echo "			<select class='formfld' style='width: 90px;' name='destination_prompt_3'>\n";
		echo "				<option value=''></option>\n";
		echo "				<option value='1' ".(($destination_prompt_3)?"selected='selected'":null).">".$text['label-destination_prompt_confirm']."</option>\n";
		//echo "			<option value='2'>".$text['label-destination_prompt_announce]."</option>\n";
		echo "			</select>\n";
		echo "		</td>\n";
	}
	echo "		</tr>\n";

	// 4th destination
	echo "		<tr>\n";
	echo "			<td><input class='formfld' type='text' name='destination_data_4' id='destination_data_4' maxlength='255' value=\"".$destination_data_4."\"></td>\n";
	echo "			<td>\n";
						destination_select('destination_delay_4', $destination_delay_4, '0');
	echo "			</td>\n";
	echo "			<td>\n";
						destination_select('destination_timeout_4', $destination_timeout_4, '30');
	echo "			</td>\n";
	if (permission_exists('follow_me_prompt')) {
		echo "		<td>\n";
		echo "			<select class='formfld' style='width: 90px;' name='destination_prompt_4'>\n";
		echo "				<option value=''></option>\n";
		echo "				<option value='1' ".(($destination_prompt_4)?"selected='selected'":null).">".$text['label-destination_prompt_confirm']."</option>\n";
		//echo "			<option value='2'>".$text['label-destination_prompt_announce]."</option>\n";
		echo "			</select>\n";
		echo "		</td>\n";
	}
	echo "		</tr>\n";

	// 5th destination
	echo "		<tr>\n";
	echo "			<td><input class='formfld' type='text' name='destination_data_5' id='destination_data_5' maxlength='255' value=\"".$destination_data_5."\"></td>\n";
	echo "			<td>\n";
						destination_select('destination_delay_5', $destination_delay_5, '0');
	echo "			</td>\n";
	echo "			<td>\n";
						destination_select('destination_timeout_5', $destination_timeout_5, '30');
	echo "			</td>\n";
	if (permission_exists('follow_me_prompt')) {
		echo "		<td>\n";
		echo "			<select class='formfld' style='width: 90px;' name='destination_prompt_5'>\n";
		echo "				<option value=''></option>\n";
		echo "				<option value='1' ".(($destination_prompt_5)?"selected='selected'":null).">".$text['label-destination_prompt_confirm']."</option>\n";
		//echo "			<option value='2'>".$text['label-destination_prompt_announce]."</option>\n";
		echo "			</select>\n";
		echo "		</td>\n";
	}
	echo "		</tr>\n";

	echo "	</table>\n";
	echo "</td>\n";
	echo "</tr>\n";

	if (permission_exists('follow_me_cid_name_prefix')) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-cid-name-prefix'].":\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "  <input class='formfld' type='text' name='cid_name_prefix' maxlength='255' value='$cid_name_prefix'>\n";
		echo "<br />\n";
		echo $text['description-cid-name-prefix']." \n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	if (permission_exists('follow_me_cid_number_prefix')) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-cid-number-prefix'].":\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "  <input class='formfld' type='text' name='cid_number_prefix' maxlength='255' value='$cid_number_prefix'>\n";
		echo "<br />\n";
		echo $text['description-cid-number-prefix']." \n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	echo "<tr>\n";
	echo "<td colspan='2'>\n";
	echo "	<br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	<strong>".$text['label-dnd'].":</strong>\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	$on_click = "document.getElementById('forward_all_enabled').checked=true;";
	$on_click .= "document.getElementById('forward_all_disabled').checked=true;";
	$on_click .= "document.getElementById('follow_me_enabled').checked=true;";
	$on_click .= "document.getElementById('follow_me_disabled').checked=true;";
	if ($dnd_enabled == "true") {
		echo "	<input type='radio' name='dnd_enabled' id='dnd_enabled' value='true' onclick=\"$on_click\" checked='checked'/> ".$text['label-enabled']." \n";
	}
	else {
		echo "	<input type='radio' name='dnd_enabled' id='dnd_enabled' value='true' onclick=\"$on_click\"/> ".$text['label-enable']." \n";
	}
	if ($dnd_enabled == "false" || $dnd_enabled == "") {
		echo "	<input type='radio' name='dnd_enabled' id='dnd_disabled' value='false' onclick=\"\" checked='checked' /> ".$text['label-disabled']." \n";
	}
	else {
		echo "	<input type='radio' name='dnd_enabled' id='dnd_disabled' value='false' onclick=\"\" /> ".$text['label-disable']." \n";
	}
	echo "	<br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td colspan='2'>\n";
	echo "	<br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td colspan='2'>\n";
	echo "	<br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	if ($action == "update") {
		echo "				<input type='hidden' name='id' value='$extension_uuid'>\n";
	}
	echo "				<input type='submit' name='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "		</td>\n";
	echo "	</tr>";
	echo "</table>";
	echo "</form>";

	echo "	</td>";
	echo "	</tr>";
	echo "</table>";
	echo "</div>";

//include the footer
	require_once "resources/footer.php";
?>