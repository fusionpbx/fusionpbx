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
	$language = new text;
	$text = $language->get();

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
		$call_block_uuid = check_str($_REQUEST["id"]);
	}
	else {
		$action = "add";
	}

//get http post variables and set them to php variables
	if (count($_POST)>0) {
		$call_block_name = check_str($_POST["call_block_name"]);
		$call_block_number = check_str($_POST["call_block_number"]);
		$call_block_action = check_str($_POST["call_block_action"]);
		$call_block_enabled = check_str($_POST["call_block_enabled"]);
	}

if (count($_POST)>0 && strlen($_POST["persistformvar"]) == 0) {

	$msg = '';
	if ($action == "update") {
		//$call_block_uuid = check_str($_POST["call_block_uuid"]);
	}

	//check for all required data
		if (strlen($call_block_name) == 0) { $msg .= $text['label-provide-name']."<br>\n"; }
		if ($action == "add") {
			if (strlen($call_block_number) == 0) { $msg .= $text['label-provide-number']."<br>\n"; }
		}
		if (strlen($call_block_enabled) == 0) { $msg .= $text['label-provide-enabled']."<br>\n"; }
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

			if ($action == "add" || $action == "update") {
				//ensure call block is enabled in the dialplan
				$sql = "update v_dialplans set ";
				$sql .= "dialplan_enabled = 'true' ";
				$sql .= "where ";
				$sql .= "app_uuid = 'b1b31930-d0ee-4395-a891-04df94599f1f' and ";
				$sql .= "domain_uuid = '".$domain_uuid."' and ";
				$sql .= "dialplan_enabled <> 'true' ";
				$db->exec(check_sql($sql));
				unset($sql);
			}

			if ($action == "add") {
				$sql = "insert into v_call_block ";
				$sql .= "(";
				$sql .= "domain_uuid, ";
				$sql .= "call_block_uuid, ";
				$sql .= "call_block_name, ";
				$sql .= "call_block_number, ";
				$sql .= "call_block_count, ";
				$sql .= "call_block_action, ";
				$sql .= "call_block_enabled, ";
				$sql .= "date_added ";
				$sql .= ") ";
				$sql .= "values ";
				$sql .= "(";
				$sql .= "'".$_SESSION['domain_uuid']."', ";
				$sql .= "'".uuid()."', ";
				$sql .= "'$call_block_name', ";
				$sql .= "'$call_block_number', ";
				$sql .= "0, ";
				$sql .= "'$call_block_action', ";
				$sql .= "'$call_block_enabled', ";
				$sql .= "'".time()."' ";
				$sql .= ")";
				$db->exec(check_sql($sql));
				unset($sql);

				$_SESSION["message"] = $text['label-add-complete'];
				header("Location: call_block.php");
				return;
			} //if ($action == "add")

			if ($action == "update") {
				$sql = " select c.call_block_number, d.domain_name from v_call_block as c ";
				$sql  .= "JOIN v_domains as d ON c.domain_uuid=d.domain_uuid ";
				$sql .= "where c.domain_uuid = '".$_SESSION['domain_uuid']."' ";
				$sql .= "and c.call_block_uuid = '$call_block_uuid'";

				$prep_statement = $db->prepare(check_sql($sql));
				$prep_statement->execute();
				$result = $prep_statement->fetchAll();
				$result_count = count($result);
				if ($result_count > 0) {
					$call_block_number = $result[0]["call_block_number"];
					$domain_name = $result[0]["domain_name"];

					//clear the cache
					$cache = new cache;
					$cache->delete("app:call_block:".$domain_name.":".$call_block_number);
				}
				unset ($prep_statement, $sql);

				$sql = "update v_call_block set ";
				$sql .= "call_block_name = '$call_block_name', ";
				$sql .= "call_block_number = '$call_block_number', ";
				$sql .= "call_block_action = '$call_block_action', ";
				$sql .= "call_block_enabled = '$call_block_enabled' ";
				$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
				$sql .= "and call_block_uuid = '$call_block_uuid'";
				$db->exec(check_sql($sql));
				unset($sql);

				$_SESSION["message"] = $text['label-update-complete'];
				header("Location: call_block.php");
				return;
			} //if ($action == "update")
		} //if ($_POST["persistformvar"] != "true")
} //(count($_POST)>0 && strlen($_POST["persistformvar"]) == 0)

//pre-populate the form
	if (count($_GET)>0 && $_POST["persistformvar"] != "true") {
		$call_block_uuid = $_GET["id"];
		$sql = "select * from v_call_block ";
		$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
		$sql .= "and call_block_uuid = '$call_block_uuid' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll();
		foreach ($result as &$row) {
			$call_block_name = $row["call_block_name"];
			$call_block_number = $row["call_block_number"];
			$call_block_action = $row["call_block_action"];
			$blocked_call_destination = $row["blocked_call_destination"];
			$call_block_enabled = $row["call_block_enabled"];
			break; //limit to 1 row
		}
		unset ($prep_statement, $sql);
	}

//show the header
	require_once "resources/header.php";

//show the content
	echo "<script type=\"text/javascript\" language=\"JavaScript\">\n";
	echo "	function call_block_recent(cdr_uuid, cur_name) {\n";
	echo "		var new_name = prompt('".$text['prompt-block_recent_name']."', cur_name);\n";
	echo "		if (new_name != null) {\n";
	echo "			block_name = (new_name != '') ? new_name : cur_name;\n";
	echo "			document.location.href='call_block_cdr_add.php?cdr_id=' + cdr_uuid + '&name=' + escape(block_name)\n";
	echo "		}\n";
	echo "	}\n";
	echo "</script>";

	// Show last 5-10 calls first, with add button

	echo "<form method='post' name='frm' action=''>\n";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	if ($action == "add") {
		echo "<td align='left' width='30%' nowrap='nowrap'><b>".$text['label-edit-add']."</b></td>\n";
	}
	if ($action == "update") {
		echo "<td align='left' width='30%' nowrap='nowrap'><b>".$text['label-edit-edit']."</b></td>\n";
	}
	echo "<td width='70%' align='right'>";
	echo "	<input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"window.location='call_block.php'\" value='".$text['button-back']."'>";
	echo "	<input type='submit' name='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "</td>\n";
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
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-number']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='call_block_number' maxlength='255' value=\"$call_block_number\" required='required'>\n";
	echo "<br />\n";
	echo $text['description-number']."\n";
	echo "<br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-name']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='call_block_name' maxlength='255' value=\"$call_block_name\" required='required'>\n";
	echo "<br />\n";
	echo $text['description-name']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-action']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='call_block_action'>\n";
	$pieces = explode(" ", $call_block_action);
	$action = $pieces[0];
	$extension = $pieces[2];
	if ($action == "Reject") {
		echo "	<option value='Reject' selected='selected'>".$text['label-reject']."</option>\n";
	}
	else {
		echo "   <option value='Reject' >".$text['label-reject']."</option>\n";
	}
	if ($action == "Busy") {
		echo "	<option value='Busy' selected='selected'>".$text['label-busy']."</option>\n";
	}
	else {
		echo "	<option value='Busy'>".$text['label-busy']."</option>\n";
	}
	call_block_get_extensions($extension);
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-action']."\n";
	echo "\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-enabled']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='call_block_enabled'>\n";
	echo "		<option value='true' ".(($call_block_enabled == "true") ? "selected" : null).">".$text['label-true']."</option>\n";
	echo "		<option value='false' ".(($call_block_enabled == "false") ? "selected" : null).">".$text['label-false']."</option>\n";
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-enable']."\n";
	echo "\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	if ($action == "update") {
		echo "		<input type='hidden' name='call_block_uuid' value='$call_block_uuid'>\n";
	}
	echo "			<br>";
	echo "			<input type='submit' name='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "		</td>\n";
	echo "	</tr>";
	echo "</table>";
	echo "<br><br>";
	echo "</form>";


//get recent calls from the db (if not editing an existing call block record)
	if (!isset($_REQUEST["id"])) {
		$sql = "select caller_id_number, caller_id_name, start_epoch, direction, hangup_cause, duration, billsec, uuid from v_xml_cdr ";
		$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
		$sql .= "and direction != 'outbound' ";
		$sql .= "order by start_stamp DESC ";
		$sql .= "limit 20 ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll();
		$result_count = count($result);
		unset ($prep_statement);

		echo "<b>".$text['label-edit-add-recent']."</b>";
		echo "<br><br>";
		echo "<table class='tr_hover' width='100%' cellpadding='0' cellspacing='0' border='0'>\n";
		echo "<th style='width: 25px;'>&nbsp;</th>\n";
		echo th_order_by('caller_id_name', $text['label-name'], $order_by, $order);
		echo th_order_by('caller_id_number', $text['label-number'], $order_by, $order);
		echo th_order_by('start_stamp', $text['label-called-on'], $order_by, $order);
		echo th_order_by('duration', $text['label-duration'], $order_by, $order);
		echo "<td>&nbsp;</td>\n";
		echo "</tr>";
		$c = 0;
		$row_style["0"] = "row_style0";
		$row_style["1"] = "row_style1";

		if ($result_count > 0) {
			foreach($result as $row) {
				$tr_onclick = " onclick=\"call_block_recent('".$row['uuid']."','".urlencode($row['caller_id_name'])."');\" ";
				if (strlen($row['caller_id_number']) >= 7) {
					if (defined('TIME_24HR') && TIME_24HR == 1) {
						$tmp_start_epoch = date("j M Y H:i:s", $row['start_epoch']);
					} else {
						$tmp_start_epoch = date("j M Y h:i:sa", $row['start_epoch']);
					}
					echo "<tr>\n";
					if (
						file_exists($_SERVER["DOCUMENT_ROOT"]."/themes/".$_SESSION['domain']['template']['name']."/images/icon_cdr_inbound_missed.png") &&
						file_exists($_SERVER["DOCUMENT_ROOT"]."/themes/".$_SESSION['domain']['template']['name']."/images/icon_cdr_inbound_connected.png") &&
						file_exists($_SERVER["DOCUMENT_ROOT"]."/themes/".$_SESSION['domain']['template']['name']."/images/icon_cdr_local_failed.png") &&
						file_exists($_SERVER["DOCUMENT_ROOT"]."/themes/".$_SESSION['domain']['template']['name']."/images/icon_cdr_local_connected.png")
						) {
						echo "	<td valign='top' class='".$row_style[$c]."' style='text-align: center;'>";
						switch ($row['direction']) {
							case "inbound" :
								if ($row['billsec'] == 0)
									echo "<img src='/themes/".$_SESSION['domain']['template']['name']."/images/icon_cdr_inbound_missed.png' style='border: none;' alt='".$text['label-inbound']." ".$text['label-missed']."'>\n";
								else
									echo "<img src='/themes/".$_SESSION['domain']['template']['name']."/images/icon_cdr_inbound_connected.png' style='border: none;' alt='".$text['label-inbound']."'>\n";
								break;
							case "local" :
								if ($row['billsec'] == 0)
									echo "<img src='/themes/".$_SESSION['domain']['template']['name']."/images/icon_cdr_local_failed.png' style='border: none;' alt='".$text['label-local']." ".$text['label-failed']."'>\n";
								else
									echo "<img src='/themes/".$_SESSION['domain']['template']['name']."/images/icon_cdr_local_connected.png' style='border: none;' alt='".$text['label-local']."'>\n";
								break;
						}
						echo "	</td>\n";
					}
					else {
						echo "	<td class='".$row_style[$c]."'>&nbsp;</td>";
					}
					echo "	<td valign='top' class='".$row_style[$c]."' ".$tr_onclick.">";
					echo 	$row['caller_id_name'].' ';
					echo "	</td>\n";
					echo "	<td valign='top' class='".$row_style[$c]."' ".$tr_onclick.">";
					if (is_numeric($row['caller_id_number'])) {
						echo 	format_phone($row['caller_id_number']).' ';
					}
					else {
						echo 	$row['caller_id_number'].' ';
					}
					echo "	</td>\n";
					echo "	<td valign='top' class='".$row_style[$c]."' ".$tr_onclick.">".$tmp_start_epoch."</td>\n";
					$seconds = ($row['hangup_cause']=="ORIGINATOR_CANCEL") ? $row['duration'] : $row['billsec'];  //If they cancelled, show the ring time, not the bill time.
					echo "	<td valign='top' class='".$row_style[$c]."' ".$tr_onclick.">".gmdate("G:i:s", $seconds)."</td>\n";
					echo "	<td class='list_control_icons' ".((!(if_group("admin") || if_group("superadmin"))) ? "style='width: 25px;'" : null).">";
					if (if_group("admin") || if_group("superadmin")) {
						echo "	<a href='".PROJECT_PATH."/app/xml_cdr/xml_cdr_details.php?uuid=".$row['uuid']."' alt='".$text['button-view']."'>".$v_link_label_view."</a>";
					}
					echo 		"<a href='javascript:void(0);' onclick=\"call_block_recent('".$row['uuid']."','".urlencode($row['caller_id_name'])."');\" alt='".$text['button-add']."'>".$v_link_label_add."</a>";
					echo "  </td>";
					echo "</tr>\n";
					if ($c==0) { $c=1; } else { $c=0; }
				}
			} //end foreach
			unset($sql, $result, $row_count);

			echo "</table>";
			echo "<br><br>";

		} //end if results
		else {
			echo "</table>";
			echo "<br><br>";
			echo "<br><br>";
		}

	}
// end of Display Last 5-10 Calls

//include the footer
	require_once "resources/footer.php";
?>
