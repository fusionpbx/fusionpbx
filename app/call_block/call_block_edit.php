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

	Call Block is written by Gerrit Visser <gerrit308@gmail.com>
*/
require_once "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";

if (permission_exists('call_block_edit') || permission_exists('call_block_add')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
	require_once "app_languages.php";
	foreach($text as $key => $value) {
		$text[$key] = $value[$_SESSION['domain']['language']['code']];
	}

//define the call_block_get_extensions function
	function call_block_get_extensions($select_extension) {
		global $db;

		//list voicemail
		$sql = "select extension, user_context, description from v_extensions ";
		$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
		$sql .= "and enabled = 'true' ";
		$sql .= "order by extension asc ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);

		echo "<optgroup label='Voicemail'>\n";
		foreach ($result as &$row) {
			$extension = $row["extension"];
			$context = $row["user_context"];
			$description = $row["description"];
			if ($extension == $select_extension) $selected = "SELECTED";
			echo "		<option value='Voicemail $context $extension' $selected>".$extension." ".$description."</option>\n";
			$selected = "";
		}
		echo "</optgroup>\n";
	}

//action add or update
	if (isset($_REQUEST["id"])) {
		$action = "update";
		$blocked_caller_uuid = check_str($_REQUEST["id"]);
	}
	else {
		$action = "add";
	}

//get http post variables and set them to php variables
	if (count($_POST)>0) {
		$blocked_caller_name = check_str($_POST["blocked_caller_name"]);
		$blocked_caller_number = check_str($_POST["blocked_caller_number"]);
		$blocked_call_action = check_str($_POST["blocked_call_action"]);
		$block_call_enabled = check_str($_POST["block_call_enabled"]);
	}

if (count($_POST)>0 && strlen($_POST["persistformvar"]) == 0) {

	$msg = '';
	if ($action == "update") {
		//$blocked_caller_uuid = check_str($_POST["blocked_caller_uuid"]);
	}
	
	//check for all required data
		if (strlen($blocked_caller_name) == 0) { $msg .= $text['label-provide-name']."<br>\n"; }
		if ($action == "add") { 
			if (strlen($blocked_caller_number) == 0) { $msg .= $text['label-provide-number']."<br>\n"; }
		}
		if (strlen($block_call_enabled) == 0) { $msg .= $text['label-provide-enabled']."<br>\n"; }
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

	//add or update the database
		if (($_POST["persistformvar"] != "true")>0) {
			if ($action == "add") {
				$sql = "insert into v_call_block ";
				$sql .= "(";
				$sql .= "domain_uuid, ";
				$sql .= "blocked_caller_uuid, ";
				$sql .= "blocked_caller_name, ";
				$sql .= "blocked_caller_number, ";
				$sql .= "blocked_call_count, ";
				$sql .= "blocked_call_action, ";
				$sql .= "block_call_enabled, ";
				$sql .= "date_added ";
				$sql .= ") ";
				$sql .= "values ";
				$sql .= "(";
				$sql .= "'".$_SESSION['domain_uuid']."', ";
				$sql .= "'".uuid()."', ";
				$sql .= "'$blocked_caller_name', ";
				$sql .= "'$blocked_caller_number', ";
				$sql .= "0, ";
				$sql .= "'$blocked_call_action', ";
				$sql .= "'$block_call_enabled', ";
				$sql .= "'".time()."' ";
				$sql .= ")";
				$db->exec(check_sql($sql));
				unset($sql);

				require_once "resources/header.php";
				echo "<meta http-equiv=\"refresh\" content=\"2;url=call_block.php\">\n";
				echo "<div align='center'>\n";
				echo $text['label-add-complete']."\n";
				echo "</div>\n";
				require_once "resources/footer.php";
				return;
			} //if ($action == "add")

			if ($action == "update") {
				$sql = "update v_call_block set ";
				$sql .= "blocked_caller_name = '$blocked_caller_name', ";
				//$sql .= "blocked_caller_number = '$blocked_caller_number', ";
				$sql .= "blocked_call_action = '$blocked_call_action', ";
				$sql .= "block_call_enabled = '$block_call_enabled' ";
				$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
				$sql .= "and blocked_caller_uuid = '$blocked_caller_uuid'";
				$db->exec(check_sql($sql));
				unset($sql);

				require_once "resources/header.php";
				echo "<meta http-equiv=\"refresh\" content=\"2;url=call_block.php\">\n";
				echo "<div align='center'>\n";
				echo $text['label-update-complete']."\n";
				echo "</div>\n";
				require_once "resources/footer.php";
				return;
			} //if ($action == "update")
		} //if ($_POST["persistformvar"] != "true") 
} //(count($_POST)>0 && strlen($_POST["persistformvar"]) == 0)

//pre-populate the form
	if (count($_GET)>0 && $_POST["persistformvar"] != "true") {
		$blocked_caller_uuid = $_GET["id"];
		$sql = "select * from v_call_block ";
		$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
		$sql .= "and blocked_caller_uuid = '$blocked_caller_uuid' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll();
		foreach ($result as &$row) {
			$blocked_caller_name = $row["blocked_caller_name"];
			$blocked_caller_number = $row["blocked_caller_number"];
			$blocked_call_action = $row["blocked_call_action"];
			$blocked_call_destination = $row["blocked_call_destination"];
			$block_call_enabled = $row["block_call_enabled"];
			break; //limit to 1 row
		}
		unset ($prep_statement, $sql);
	}

//show the header
	require_once "resources/header.php";

//show the content
	echo "<div align='center'>";
	// Show last 5-10 calls first, with add button

//get the results from the db
	$sql = "select caller_id_number, caller_id_name, start_epoch, uuid from v_xml_cdr ";
	$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
	$sql .= "and direction != 'outbound' ";
	$sql .= "order by start_stamp DESC ";
	$sql .= "limit 20 ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetchAll();
	$result_count = count($result);
	unset ($prep_statement);

	echo "<table width='100%' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo th_order_by('caller_id_name', $text['label-name'], $order_by, $order);
	echo th_order_by('caller_id_number', $text['label-number'], $order_by, $order);
	echo th_order_by('start_stamp', $text['label-called-on'], $order_by, $order);

	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

	if ($result_count > 0) {
		foreach($result as $row) {
			if (strlen($row['caller_id_number']) >= 7) {
				if (defined('TIME_24HR') && TIME_24HR == 1) {
					$tmp_start_epoch = date("j M Y H:i:s", $row['start_epoch']);
				} else {
					$tmp_start_epoch = date("j M Y h:i:sa", $row['start_epoch']);
				}
				echo "<tr >\n";
				echo "	<td valign='top' class='".$row_style[$c]."'>";
				echo 	$row['caller_id_name'].' ';
				echo "	</td>\n";
				echo "	<td valign='top' class='".$row_style[$c]."'>";
				if (is_numeric($row['caller_id_number'])) {
					echo 	format_phone($row['caller_id_number']).' ';
				}
				else {
					echo 	$row['caller_id_number'].' ';
				}
				echo "	</td>\n";
				echo "	<td valign='top' class='".$row_style[$c]."'>".$tmp_start_epoch."</td>\n";
				echo "	<td valign='top' align='right'>\n";
				echo "		<a href='call_block_cdr_add.php?cdr_id=".$row['uuid']."' alt='add'>$v_link_label_add</a>\n";
				echo "</tr>\n";
				if ($c==0) { $c=1; } else { $c=0; }
			}
		} //end foreach
		unset($sql, $result, $row_count);
	} //end if results

	echo "<tr>\n";
	echo "</tr>\n";
	echo "</table>";
	// end of Display Last 5-10 Calls

	echo "<table width='100%' border='0' cellpadding='0' cellspacing=''>\n";
	echo "<tr class='border'>\n";
	echo "	<td align=\"left\">\n";
	echo "	  <br>";

	echo "<form method='post' name='frm' action=''>\n";
	echo "<div align='center'>\n";
	echo "<table width='100%'  border='0' cellpadding='6' cellspacing='0'>\n";
	echo "<tr>\n";
	if ($action == "add") {
		echo "<td align='left' width='30%' nowrap='nowrap'><b>".$text['label-edit-add']."</b></td>\n";
	}
	if ($action == "update") {
		echo "<td align='left' width='30%' nowrap='nowrap'><b>".$text['label-edit-edit']."</b></td>\n";
	}
	echo "<td width='70%' align='right'><input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"window.location='call_block.php'\" value='".$text['button-back']."'></td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td align='left' colspan='2'>\n";
	if ($action == "add") {
	echo $text['label-add-note']."<br /><br />\n";
	}
	if ($action == "update") {
	echo $text['label-edit-note']."<br /><br />\n";
	}
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	Number:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	if ($action == "add") {
		echo "	<input class='formfld' type='text' name='blocked_caller_number' maxlength='255' value=\"$blocked_caller_number\">\n";
		echo "<br />\n";
		echo $text['label-exact-number']."\n";
	}
	else {
		echo $blocked_caller_number;
	}
	echo "<br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	Name:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='blocked_caller_name' maxlength='255' value=\"$blocked_caller_name\">\n";
	echo "<br />\n";
	echo "Enter the name.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	Action:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='blocked_call_action'>\n";
	echo "	<option value=''></option>\n";
	$pieces = explode(" ", $blocked_call_action);
	$action = $pieces[0];
	$extension = $pieces[2];
	if ($action == "Reject") {
		echo "	<option value='Reject' SELECTED >Reject</option>\n";
	} else {
		echo "   <option value='Reject' >Reject</option>\n";
	}
	if ($action == "Busy") {
		echo "	<option value='Busy' SELECTED >".$text['label-reject']."</option>\n";
	} else {
		echo "   <option value='Busy' >".$text['label-busy']."</option>\n";
	}
	call_block_get_extensions($extension);
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['label-action-message']."\n";
	echo "\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	Enabled:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='block_call_enabled'>\n";
	echo "	<option value=''></option>\n";
	if ($block_call_enabled == "true") { 
		echo "	<option value='true' SELECTED >true</option>\n";
	}
	else {
		echo "	<option value='true'>true</option>\n";
	}
	if ($block_call_enabled == "false") { 
		echo "	<option value='false' SELECTED >".$text['label-true']."</option>\n";
	}
	else {
		echo "	<option value='false'>".$text['label-false']."</option>\n";
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['label-enable-message']."\n";
	echo "\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	if ($action == "update") {
		echo "				<input type='hidden' name='blocked_caller_uuid' value='$blocked_caller_uuid'>\n";
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