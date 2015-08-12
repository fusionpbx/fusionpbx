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
	Portions created by the Initial Developer are Copyright (C) 2008-2015
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	Luis Daniel Lucio Quiroz <dlucio@okay.com.mx>
*/
require_once "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists('follow_me') || permission_exists('call_forward') || permission_exists('do_not_disturb')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

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
			$forward_no_answer_destination = $row["forward_no_answer_destination"];
			$forward_no_answer_enabled = $row["forward_no_answer_enabled"];
			$follow_me_uuid = $row["follow_me_uuid"];
			$forward_caller_id_uuid = $row["forward_caller_id_uuid"];
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
			$forward_busy_enabled = check_str($_POST["forward_busy_enabled"]);
			$forward_busy_destination = check_str($_POST["forward_busy_destination"]);
			$forward_no_answer_enabled = check_str($_POST["forward_no_answer_enabled"]);
			$forward_no_answer_destination = check_str($_POST["forward_no_answer_destination"]);
			$forward_caller_id_uuid = check_str($_POST["forward_caller_id_uuid"]);
			$cid_name_prefix = check_str($_POST["cid_name_prefix"]);
			$cid_number_prefix = check_str($_POST["cid_number_prefix"]);
			$follow_me_enabled = check_str($_POST["follow_me_enabled"]);
			$follow_me_caller_id_uuid = check_str($_POST["follow_me_caller_id_uuid"]);
			$follow_me_ignore_busy = check_str($_POST["follow_me_ignore_busy"]);

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
			if (strlen($forward_busy_destination) > 0) {
			//	$forward_busy_destination = preg_replace("~[^0-9*]~", "",$forward_busy_destination);
			}
			if (strlen($forward_no_answer_destination) > 0) {
			//	$forward_no_answer_destination = preg_replace("~[^0-9*]~", "",$forward_no_answer_destination);
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
			//if (strlen($forward_all_destination) == 0) { $msg .= "Please provide: Forward Number<br>\n"; }
			//if (strlen($forward_busy_enabled) == 0) { $msg .= "Please provide: On Busy<br>\n"; }
			//if (strlen($forward_busy_destination) == 0) { $msg .= "Please provide: Busy Number<br>\n"; }
			//if (strlen($forward_no_answer_enabled) == 0) { $msg .= "Please provide: no_answer<br>\n"; }
			//if (strlen($forward_no_answer_destination) == 0) { $msg .= "Please provide: no_answer Number<br>\n"; }
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
			$call_forward->forward_caller_id_uuid = $forward_caller_id_uuid;
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
			$follow_me->follow_me_caller_id_uuid = $follow_me_caller_id_uuid;
			$follow_me->follow_me_ignore_busy = $follow_me_ignore_busy;

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

	// Forward on busy and no_answer is stored in table and will be used by lua scripts
		$sql = "update v_extensions set ";
		$sql .= "forward_busy_destination = '".$forward_busy_destination."', ";
		$sql .= "forward_busy_enabled = '".$forward_busy_enabled."', ";
		$sql .= "forward_no_answer_destination = '".$forward_no_answer_destination."', ";
		$sql .= "forward_no_answer_enabled = '".$forward_no_answer_enabled."', ";
		$sql .= "forward_caller_id_uuid = ".(($forward_caller_id_uuid != '') ? "'".$forward_caller_id_uuid."' " : "null ");
		$sql .= "where domain_uuid = '".$domain_uuid."' ";
		$sql .= "and extension_uuid = '".$extension_uuid."'";
		$db->exec(check_sql($sql));
		unset($sql);

	//clear the cache
		$cache = new cache;
		$cache->delete("memcache delete directory:".$extension."@".$_SESSION['domain_name']);

	//redirect the user
		$_SESSION["message"] = $text['confirm-update'];
		header("Location: ".$_REQUEST['return_url']);
		return;

} //(count($_POST)>0 && strlen($_POST["persistformvar"]) == 0)

//show the header
	require_once "resources/header.php";

//pre-populate the form
	if ($follow_me_uuid != '') {
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
			$follow_me_caller_id_uuid = $row["follow_me_caller_id_uuid"];
			$follow_me_ignore_busy = $row["follow_me_ignore_busy"];

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
	}

//set the default
	if (!isset($dnd_enabled)) {
		//set the value from the database
		$dnd_enabled = $do_not_disturb;
	}

//prepare the autocomplete
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
	echo "<form method='post' name='frm' action=''>\n";
	echo "<input type='hidden' name='return_url' value='".$_SERVER["HTTP_REFERER"]."'>\n";

	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "<td align='left' width='30%' nowrap='nowrap' valign='top'>\n";
	echo "	<b>".$text['title']."</b>\n";
	echo "</td>\n";
	echo "<td width='70%' align='right' valign='top'>\n";
	echo "	<input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"window.location='".$_SERVER["HTTP_REFERER"]."'\" value='".$text['button-back']."'>\n";
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
	echo "	<strong>".$text['label-call-forward']."</strong>\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	$on_click = "document.getElementById('follow_me_disabled').checked=true;";
	$on_click .= "document.getElementById('dnd_disabled').checked=true;";
	$on_click .= "document.getElementById('forward_all_destination').focus();";
	echo "	<label for='forward_all_disabled'><input type='radio' name='forward_all_enabled' id='forward_all_disabled' onclick=\"\" value='false' ".(($forward_all_enabled == "false" || $forward_all_enabled == "") ? "checked='checked'" : null)." /> ".$text['label-disabled']."</label> \n";
	echo "	<label for='forward_all_enabled'><input type='radio' name='forward_all_enabled' id='forward_all_enabled' onclick=\"$on_click\" value='true' ".(($forward_all_enabled == "true") ? "checked='checked'" : null)." /> ".$text['label-enabled']."</label> \n";
	unset($on_click);
	echo "&nbsp;&nbsp;&nbsp;";
	echo "	<input class='formfld' type='text' name='forward_all_destination' id='forward_all_destination' maxlength='255' placeholder=\"".$text['label-destination']."\" value=\"".$forward_all_destination."\">\n";

	if (permission_exists('follow_me_cid_set')) {
		echo "&nbsp;&nbsp;&nbsp;";
		$sql_forward = "select destination_uuid, destination_number, destination_description from v_destinations where domain_uuid = '$domain_uuid' and destination_type = 'inbound' order by destination_number asc ";
		$prep_statement_forward = $db->prepare(check_sql($sql_forward));
		$prep_statement_forward->execute();
		$result_forward = $prep_statement_forward->fetchAll(PDO::FETCH_ASSOC);
		if (count($result_forward) > 0) {
			echo "<select name='forward_caller_id_uuid' id='forward_caller_id_uuid' class='formfld' >\n";
			echo "	<option value=''>".$text['label-select-cid-number']."</option>\n";
			echo "  <option value='' disabled='disabled'></option>\n";
			foreach ($result_forward as &$row_forward) {
				$selected = $row_forward["destination_uuid"] == $forward_caller_id_uuid ? "selected='selected' " : '';
				echo "<option value='".$row_forward["destination_uuid"]."' ".$selected.">".format_phone($row_forward["destination_number"])." : ".$row_forward["destination_description"]."</option>\n";
			}
			echo "</select>\n";
		}
		unset ($sql_forward, $prep_statement_forward, $result_forward, $row_forward);
	}

	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-on-busy']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	$on_click = "document.getElementById('dnd_disabled').checked=true;";
	$on_click .= "document.getElementById('forward_busy_destination').focus();";
	echo "	<label for='forward_busy_disabled'><input type='radio' name='forward_busy_enabled' id='forward_busy_disabled' onclick=\"\" value='false' ".(($forward_busy_enabled == "false" || $forward_busy_enabled == "") ? "checked='checked'" : null)." /> ".$text['label-disabled']."</label> \n";
	echo "	<label for='forward_busy_enabled'><input type='radio' name='forward_busy_enabled' id='forward_busy_enabled' onclick=\"$on_click\" value='true' ".(($forward_busy_enabled == "true") ? "checked='checked'" : null)."/> ".$text['label-enabled']."</label> \n";
	unset($on_click);
	echo "&nbsp;&nbsp;&nbsp;";
	echo "	<input class='formfld' type='text' name='forward_busy_destination' id='forward_busy_destination' maxlength='255' placeholder=\"".$text['label-destination']."\" value=\"".$forward_busy_destination."\">\n";
	echo "	<br />".$text['description-on-busy'].".\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-no_answer']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	$on_click = "document.getElementById('dnd_disabled').checked=true;";
	$on_click .= "document.getElementById('forward_no_answer_destination').focus();";
	echo "	<label for='forward_no_answer_disabled'><input type='radio' name='forward_no_answer_enabled' id='forward_no_answer_disabled' onclick=\"\" value='false' ".(($forward_no_answer_enabled == "false" || $forward_no_answer_enabled == "") ? "checked='checked'" : null)." /> ".$text['label-disabled']."</label> \n";
	echo "	<label for='forward_no_answer_enabled'><input type='radio' name='forward_no_answer_enabled' id='forward_no_answer_enabled' onclick=\"$on_click\" value='true' ".(($forward_no_answer_enabled == "true") ? "checked='checked'" : null)."/> ".$text['label-enabled']."</label> \n";
	unset($on_click);
	echo "&nbsp;&nbsp;&nbsp;";
	echo "	<input class='formfld' type='text' name='forward_no_answer_destination' id='forward_no_answer_destination' maxlength='255' placeholder=\"".$text['label-destination']."\" value=\"".$forward_no_answer_destination."\">\n";
	echo "	<br />".$text['description-no_answer'].".\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr><td colspan='2'><br /></td></tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	<strong>".$text['label-follow-me']."</strong>\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	$on_click = "document.getElementById('forward_all_disabled').checked=true;";
	$on_click .= "document.getElementById('dnd_disabled').checked=true; document.getElementById('follow_me_caller_id_uuid').focus();";
	echo "	<label for='follow_me_disabled'><input type='radio' name='follow_me_enabled' id='follow_me_disabled' onclick=\"\" value='false' ".(($follow_me_enabled == "false" || $follow_me_enabled == "") ? "checked='checked'" : null)." /> ".$text['label-disabled']."</label> \n";
	echo "	<label for='follow_me_enabled'><input type='radio' name='follow_me_enabled' id='follow_me_enabled' onclick=\"$on_click\" value='true' ".(($follow_me_enabled == "true") ? "checked='checked'" : null)."/> ".$text['label-enabled']."</label> \n";
	unset($on_click);

	if (permission_exists('follow_me_cid_set')) {
		echo "&nbsp;&nbsp;&nbsp;";
		$sql_follow_me = "select destination_uuid, destination_number, destination_description from v_destinations where domain_uuid = '$domain_uuid' and destination_type = 'inbound' order by destination_number asc ";
		$prep_statement_follow_me = $db->prepare(check_sql($sql_follow_me));
		$prep_statement_follow_me->execute();
		$result_follow_me = $prep_statement_follow_me->fetchAll(PDO::FETCH_ASSOC);
		if (count($result_follow_me) > 0) {
			echo "<select name='follow_me_caller_id_uuid' id='follow_me_caller_id_uuid' class='formfld' >\n";
			echo "	<option value=''>".$text['label-select-cid-number']."</option>\n";
			echo "	<option value='' disabled='disabled'></option>\n";
			foreach ($result_follow_me as &$row_follow_me) {
				$selected = $row_follow_me["destination_uuid"] == $follow_me_caller_id_uuid ? "selected='selected'" : '';
				echo "<option value='".$row_follow_me["destination_uuid"]."' ".$selected.">".format_phone($row_follow_me["destination_number"])." : ".$row_follow_me["destination_description"]."</option>\n";
			}
			echo "</select>\n";
		}
		unset ($sql_follow_me, $prep_statement_follow_me, $result_follow_me, $row_follow_me);
	}

	echo "	<br /><br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-destinations']."\n";
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
	echo "			<td><input class='formfld' style='min-width: 135px;' type='text' name='destination_data_1' id='destination_data_1' maxlength='255' value=\"".$destination_data_1."\"></td>\n";
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
	echo "			<td><input class='formfld' style='min-width: 135px;' type='text' name='destination_data_2' id='destination_data_2' maxlength='255' value=\"".$destination_data_2."\"></td>\n";
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
	echo "			<td><input class='formfld' style='min-width: 135px;' type='text' name='destination_data_3' id='destination_data_3' maxlength='255' value=\"".$destination_data_3."\"></td>\n";
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
	echo "			<td><input class='formfld' style='min-width: 135px;' type='text' name='destination_data_4' id='destination_data_4' maxlength='255' value=\"".$destination_data_4."\"></td>\n";
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
	echo "			<td><input class='formfld' style='min-width: 135px;' type='text' name='destination_data_5' id='destination_data_5' maxlength='255' value=\"".$destination_data_5."\"></td>\n";
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

	echo "		<tr>\n";
	echo "			<td class='vncell' valign='top' align='left' nowrap='nowrap'>";
	echo 				$text['label-ignore-busy'];
	echo "			</td>\n";
	echo "			<td class='vtable' align='left'>\n";
	echo "				<select class='formfld' name='follow_me_ignore_busy'>\n";
	echo "					<option value='true' " . ($follow_me_ignore_busy == 'true' ? "selected='selected'" : '') . ">True</option>\n";
	echo "					<option value='false'" . ($follow_me_ignore_busy == 'true' ? '' : "selected='selected'") . ">False</option>\n";
	echo "				</select>\n";
	echo "				<br> Interrupt call if one of destination are busy\n";
	echo "			</td>\n";
	echo "		</tr>\n";

	if (permission_exists('follow_me_cid_name_prefix')) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-cid-name-prefix']."\n";
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
		echo "	".$text['label-cid-number-prefix']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "  <input class='formfld' type='text' name='cid_number_prefix' maxlength='255' value='$cid_number_prefix'>\n";
		echo "<br />\n";
		echo $text['description-cid-number-prefix']." \n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	echo "<tr><td colspan='2'><br /></td></tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	<strong>".$text['label-dnd']."</strong>\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	$on_click = "document.getElementById('forward_all_disabled').checked=true;";
	$on_click .= "document.getElementById('forward_busy_disabled').checked=true;";
	$on_click .= "document.getElementById('forward_no_answer_disabled').checked=true;";
	$on_click .= "document.getElementById('follow_me_disabled').checked=true;";
	echo "	<label for='dnd_disabled'><input type='radio' name='dnd_enabled' id='dnd_disabled' value='false' onclick=\"\" ".(($dnd_enabled == "false" || $dnd_enabled == "") ? "checked='checked'" : null)." /> ".$text['label-disabled']."</label> \n";
	echo "	<label for='dnd_enabled'><input type='radio' name='dnd_enabled' id='dnd_enabled' value='true' onclick=\"$on_click\" ".(($dnd_enabled == "true") ? "checked='checked'" : null)." /> ".$text['label-enabled']."</label> \n";
	echo "	<br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	if ($action == "update") {
		echo "		<input type='hidden' name='id' value='$extension_uuid'>\n";
	}
	echo "			<br>";
	echo "			<input type='submit' name='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "		</td>\n";
	echo "	</tr>";
	echo "</table>";
	echo "<br><br>";
	echo "</form>";

//include the footer
	require_once "resources/footer.php";
?>