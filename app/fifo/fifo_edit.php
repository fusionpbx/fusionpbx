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
require_once "includes/require.php";
require_once "includes/checkauth.php";
require_once "includes/paging.php";
if (permission_exists('fifo_add') || permission_exists('fifo_edit')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//set the action to an add or an update
	if (isset($_REQUEST["id"])) {
		$action = "update";
		$dialplan_uuid = check_str($_REQUEST["id"]);
	}
	else {
		$action = "add";
	}

//get http values and set them as variables
	if (count($_POST)>0) {
		//$domain_uuid = check_str($_POST["domain_uuid"]);
		$dialplan_name = check_str($_POST["dialplan_name"]);
		$dialplan_order = check_str($_POST["dialplan_order"]);
		$dialplan_continue = check_str($_POST["dialplan_continue"]);
		$dialplan_context = check_str($_POST["dialplan_context"]);
		$dialplan_enabled = check_str($_POST["dialplan_enabled"]);
		$dialplan_description = check_str($_POST["dialplan_description"]);
	}

if (count($_POST)>0 && strlen($_POST["persistformvar"]) == 0) {

	$msg = '';
	if ($action == "update") {
		$dialplan_uuid = check_str($_POST["dialplan_uuid"]);
	}

	//check for all required data
		if (strlen($domain_uuid) == 0) { $msg .= "Please provide: domain_uuid<br>\n"; }
		if (strlen($dialplan_name) == 0) { $msg .= "Please provide: Extension Name<br>\n"; }
		if (strlen($dialplan_order) == 0) { $msg .= "Please provide: Order<br>\n"; }
		//if (strlen($dialplan_context) == 0) { $msg .= "Please provide: Context<br>\n"; }
		if (strlen($dialplan_enabled) == 0) { $msg .= "Please provide: Enabled<br>\n"; }
		//if (strlen($dialplan_description) == 0) { $msg .= "Please provide: Description<br>\n"; }
		if (strlen($msg) > 0 && strlen($_POST["persistformvar"]) == 0) {
			require_once "includes/header.php";
			require_once "includes/persistformvar.php";
			echo "<div align='center'>\n";
			echo "<table><tr><td>\n";
			echo $msg."<br />";
			echo "</td></tr></table>\n";
			persistformvar($_POST);
			echo "</div>\n";
			require_once "includes/footer.php";
			return;
		}

	//set the default dialplan_continue to false
		if (strlen($dialplan_continue) == 0) { $dialplan_continue = 'false'; }

	//add or update the database
		if ($_POST["persistformvar"] != "true") {
			if ($action == "add" && permission_exists('fifo_add')) {
				$dialplan_context = $_SESSION['context'];
				$dialplan_uuid = uuid();
				$sql = "insert into v_dialplans ";
				$sql .= "(";
				$sql .= "domain_uuid, ";
				$sql .= "dialplan_uuid, ";
				$sql .= "app_uuid, ";
				$sql .= "dialplan_name, ";
				$sql .= "dialplan_order, ";
				$sql .= "dialplan_continue, ";
				$sql .= "dialplan_context, ";
				$sql .= "dialplan_enabled, ";
				$sql .= "dialplan_description ";
				$sql .= ")";
				$sql .= "values ";
				$sql .= "(";
				$sql .= "'$domain_uuid', ";
				$sql .= "'$dialplan_uuid', ";
				$sql .= "'16589224-c876-aeb3-f59f-523a1c0801f7', ";
				$sql .= "'$dialplan_name', ";
				$sql .= "'$dialplan_order', ";
				$sql .= "'$dialplan_continue', ";
				$sql .= "'$dialplan_context', ";
				$sql .= "'$dialplan_enabled', ";
				$sql .= "'$dialplan_description' ";
				$sql .= ")";
				$db->exec(check_sql($sql));
				unset($sql);

				//synchronize the xml config
				save_dialplan_xml();

				//delete the dialplan context from memcache
				$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
				if ($fp) {
					$switch_cmd = "memcache delete dialplan:".$_SESSION["context"]."@".$_SESSION['domain_name'];
					$switch_result = event_socket_request($fp, 'api '.$switch_cmd);
				}

				require_once "includes/header.php";
				echo "<meta http-equiv=\"refresh\" content=\"2;url=fifo.php\">\n";
				echo "<div align='center'>\n";
				echo "Add Complete\n";
				echo "</div>\n";
				require_once "includes/footer.php";
				return;
			} //if ($action == "add")

			if ($action == "update" && permission_exists('fifo_edit')) {
				$sql = "update v_dialplans set ";
				$sql .= "domain_uuid = '$domain_uuid', ";
				$sql .= "dialplan_name = '$dialplan_name', ";
				$sql .= "dialplan_order = '$dialplan_order', ";
				$sql .= "dialplan_continue = '$dialplan_continue', ";
				$sql .= "dialplan_context = '$dialplan_context', ";
				$sql .= "dialplan_enabled = '$dialplan_enabled', ";
				$sql .= "dialplan_description = '$dialplan_description' ";
				$sql .= "where domain_uuid = '$domain_uuid' ";
				$sql .= "and dialplan_uuid = '$dialplan_uuid'";
				$db->exec(check_sql($sql));
				unset($sql);

				//synchronize the xml config
				save_dialplan_xml();

				//delete the dialplan context from memcache
				$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
				if ($fp) {
					$switch_cmd = "memcache delete dialplan:".$_SESSION["context"]."@".$_SESSION['domain_name'];
					$switch_result = event_socket_request($fp, 'api '.$switch_cmd);
				}

				require_once "includes/header.php";
				echo "<meta http-equiv=\"refresh\" content=\"2;url=fifo.php\">\n";
				echo "<div align='center'>\n";
				echo "Update Complete\n";
				echo "</div>\n";
				require_once "includes/footer.php";
				return;
			} //if ($action == "update")
		} //if ($_POST["persistformvar"] != "true")
} //(count($_POST)>0 && strlen($_POST["persistformvar"]) == 0)

//pre-populate the form
	if (count($_GET)>0 && $_POST["persistformvar"] != "true") {
		$dialplan_uuid = $_GET["id"];
		$sql = "";
		$sql .= "select * from v_dialplans ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$sql .= "and dialplan_uuid = '$dialplan_uuid' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		foreach ($result as &$row) {
			$dialplan_name = $row["dialplan_name"];
			$dialplan_order = $row["dialplan_order"];
			$dialplan_continue = $row["dialplan_continue"];
			$dialplan_context = $row["dialplan_context"];
			$dialplan_enabled = $row["dialplan_enabled"];
			$dialplan_description = $row["dialplan_description"];
			break; //limit to 1 row
		}
		unset ($prep_statement);
	}

//show the header
	require_once "includes/header.php";

//show the content
	echo "<div align='center'>";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='2'>\n";
	echo "<tr class='border'>\n";
	echo "	<td align=\"left\">\n";
	echo "      <br>";

	echo "<form method='post' name='frm' action=''>\n";
	echo "<div align='center'>\n";

	echo "<table width=\"100%\" border=\"0\" cellpadding=\"1\" cellspacing=\"0\">\n";
	echo "  <tr>\n";
	echo "    <td align='left' width='30%'><p><span class=\"vexpl\"><span class=\"red\">\n";
	echo "        <strong>Queues</strong><br />\n";
	echo "        </span>\n";
	echo "    </td>\n";
	echo "    <td width='70%' align='right'><input type='button' class='btn' name='' alt='back' onclick=\"window.location='fifo.php'\" value='Back'></td>\n";
	echo "  </tr>\n";
	echo "  <tr>\n";
	echo "    <td align='left' colspan='2'>\n";
	echo "			Queues are used to setup waiting lines for callers. Also known as FIFO Queues.\n";
	echo "        </span></p>\n";
	echo "    </td>\n";
	echo "  </tr>\n";
	echo "</table>";
	echo "<br />\n";

	echo "<table width='100%'  border='0' cellpadding='6' cellspacing='0'>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "    Name:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='dialplan_name' maxlength='255' value=\"$dialplan_name\">\n";
	echo "<br />\n";
	echo "\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "    Order:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "              <select name='dialplan_order' class='formfld'>\n";
	//echo "              <option></option>\n";
	if (strlen(htmlspecialchars($dialplan_order))> 0) {
		echo "              <option selected='yes' value='".htmlspecialchars($dialplan_order)."'>".htmlspecialchars($dialplan_order)."</option>\n";
	}
	$i=0;
	while($i<=999) {
	  if (strlen($i) == 1) {
		echo "              <option value='00$i'>00$i</option>\n";
	  }
	  if (strlen($i) == 2) {
		echo "              <option value='0$i'>0$i</option>\n";
	  }
	  if (strlen($i) == 3) {
		echo "              <option value='$i'>$i</option>\n";
	  }

	  $i++;
	}
	echo "              </select>\n";
	//echo "  <input class='formfld' type='text' name='dialplan_order' maxlength='255' value='$dialplan_order'>\n";
	echo "<br />\n";
	echo "\n";
	echo "</td>\n";
	echo "</tr>\n";

	//echo "<tr>\n";
	//echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	//echo "    Context:\n";
	//echo "</td>\n";
	//echo "<td class='vtable' align='left'>\n";
	//echo "    <input class='formfld' type='text' name='dialplan_context' maxlength='255' value=\"$dialplan_context\">\n";
	//echo "<br />\n";
	//echo "\n";
	//echo "</td>\n";
	//echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "    Continue:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <select class='formfld' name='dialplan_continue'>\n";
	echo "    <option value=''></option>\n";
	if ($dialplan_continue == "true") { 
		echo "    <option value='true' SELECTED >true</option>\n";
	}
	else {
		echo "    <option value='true'>true</option>\n";
	}
	if ($dialplan_continue == "false") { 
		echo "    <option value='false' SELECTED >false</option>\n";
	}
	else {
		echo "    <option value='false'>false</option>\n";
	}
	echo "    </select>\n";
	echo "<br />\n";
	echo "Extension Continue in most cases is false.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "    Enabled:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <select class='formfld' name='dialplan_enabled'>\n";
	echo "    <option value=''></option>\n";
	if ($dialplan_enabled == "true") { 
		echo "    <option value='true' SELECTED >true</option>\n";
	}
	else {
		echo "    <option value='true'>true</option>\n";
	}
	if ($dialplan_enabled == "false") { 
		echo "    <option value='false' SELECTED >false</option>\n";
	}
	else {
		echo "    <option value='false'>false</option>\n";
	}
	echo "    </select>\n";
	echo "<br />\n";
	echo "\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "    Description:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <textarea class='formfld' name='dialplan_description' rows='4'>$dialplan_description</textarea>\n";
	echo "<br />\n";
	echo "\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	if ($action == "update") {
		echo "				<input type='hidden' name='dialplan_uuid' value='$dialplan_uuid'>\n";
	}
	echo "				<input type='submit' name='submit' class='btn' value='Save'>\n";
	echo "		</td>\n";
	echo "	</tr>";
	echo "</table>";
	echo "</form>";

	echo "	</td>";
	echo "	</tr>";
	echo "</table>";
	echo "</div>";

	//v_dialplan_details
		if ($action == "update" && permission_exists('fifo_edit')) {
			echo "<div align='center'>";
			echo "<table width='100%' border='0' cellpadding='0' cellspacing='2'>\n";

			echo "<tr class='border'>\n";
			echo "	<td align=\"center\">\n";
			echo "      <br>";

			echo "<table width=\"100%\" border=\"0\" cellpadding=\"6\" cellspacing=\"0\">\n";
			echo "  <tr>\n";
			echo "    <td align='left'><p><span class=\"vexpl\"><span class=\"red\"><strong>Conditions and Actions<br />\n";
			echo "        </strong></span>\n";
			echo "        The following conditions, actions and anti-actions are used in the dialplan to direct \n";
			echo "        call flow. Each is processed in order until you reach the action dialplan_detail_tag which tells what action to perform. \n";
			echo "        You are not limited to only one condition or action dialplan_detail_tag for a given extension.\n";
			echo "        </span></p></td>\n";
			echo "  </tr>\n";
			echo "</table>";
			echo "<br />\n";

			$sql = "";
			$sql .= " select * from v_dialplan_details ";
			$sql .= " where domain_uuid = '$domain_uuid' ";
			$sql .= " and dialplan_uuid = '$dialplan_uuid' ";
			$sql .= " and dialplan_detail_tag = 'condition' ";
			$sql .= " order by dialplan_detail_order asc";
			$prep_statement = $db->prepare(check_sql($sql));
			$prep_statement->execute();
			$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
			$result_count = count($result);
			unset ($prep_statement, $sql);

			$c = 0;
			$row_style["0"] = "row_style0";
			$row_style["1"] = "row_style1";

			echo "<div align='center'>\n";
			echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";

			echo "<tr>\n";
			echo "<th align='center'>Tag</th>\n";
			echo "<th align='center'>Type</th>\n";
			echo "<th align='center'>Data</th>\n";
			echo "<th align='center'>Order</th>\n";
			echo "<td align='right' width='42'>\n";
			if (permission_exists('fifo_add')) {
				echo "	<a href='fifo_detail_edit.php?id2=".$dialplan_uuid."' alt='add'>$v_link_label_add</a>\n";
			}
			echo "</td>\n";
			echo "<tr>\n";

			if ($result_count > 0) {
				foreach($result as $row) {
					echo "<tr >\n";
					echo "	<td valign='top' class='".$row_style[$c]."'>&nbsp;&nbsp;".$row[dialplan_detail_tag]."</td>\n";
					echo "	<td valign='top' class='".$row_style[$c]."'>&nbsp;&nbsp;".$row[dialplan_detail_type]."</td>\n";
					echo "	<td valign='top' class='".$row_style[$c]."'>&nbsp;&nbsp;".$row[dialplan_detail_data]."</td>\n";
					echo "	<td valign='top' class='".$row_style[$c]."'>&nbsp;&nbsp;".$row[dialplan_detail_order]."</td>\n";
					echo "	<td valign='top' align='right'>\n";
					if (permission_exists('fifo_edit')) {
						echo "		<a href='fifo_detail_edit.php?id=".$row[dialplan_detail_uuid]."&id2=".$dialplan_uuid."' alt='edit'>$v_link_label_edit</a>\n";
					}
					if (permission_exists('fifo_delete')) {
						echo "		<a href='fifo_detail_delete.php?id=".$row[dialplan_detail_uuid]."&id2=".$dialplan_uuid."' alt='delete' onclick=\"return confirm('Do you really want to delete this?')\">$v_link_label_delete</a>\n";
					}
					echo "	</td>\n";
					echo "</tr>\n";
					if ($c==0) { $c=1; } else { $c=0; }
				} //end foreach
				unset($sql, $result, $row_count);
			} //end if results

		//v_dialplan_details dialplan_detail_tag: action
			$sql = "";
			$sql .= " select * from v_dialplan_details ";
			$sql .= " where domain_uuid = '$domain_uuid' ";
			$sql .= " and dialplan_uuid = '$dialplan_uuid' ";
			$sql .= " and dialplan_detail_tag = 'action' ";
			$sql .= " order by dialplan_detail_order asc";
			$prep_statement = $db->prepare(check_sql($sql));
			$prep_statement->execute();
			$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
			$result_count = count($result);
			unset ($prep_statement, $sql);
			if ($result_count > 0) {
				foreach($result as $row) {
					echo "<tr >\n";
					echo "	<td valign='top' class='".$row_style[$c]."'>&nbsp;&nbsp;".$row[dialplan_detail_tag]."</td>\n";
					echo "	<td valign='top' class='".$row_style[$c]."'>&nbsp;&nbsp;".$row[dialplan_detail_type]."</td>\n";
					echo "	<td valign='top' class='".$row_style[$c]."'>&nbsp;&nbsp;".$row[dialplan_detail_data]."</td>\n";
					echo "	<td valign='top' class='".$row_style[$c]."'>&nbsp;&nbsp;".$row[dialplan_detail_order]."</td>\n";
					echo "	<td valign='top' align='right'>\n";
					if (permission_exists('fifo_edit')) {
						echo "		<a href='fifo_detail_edit.php?id=".$row[dialplan_detail_uuid]."&id2=".$dialplan_uuid."' alt='edit'>$v_link_label_edit</a>\n";
					}
					if (permission_exists('fifo_delete')) {
						echo "		<a href='fifo_detail_delete.php?id=".$row[dialplan_detail_uuid]."&id2=".$dialplan_uuid."' alt='delete' onclick=\"return confirm('Do you really want to delete this?')\">$v_link_label_delete</a>\n";
					}
					echo "	</td>\n";
					echo "</tr>\n";
					if ($c==0) { $c=1; } else { $c=0; }
				} //end foreach
				unset($sql, $result, $row_count);
			} //end if results

		//v_dialplan_details dialplan_detail_tag: anti-action
			$sql = "";
			$sql .= " select * from v_dialplan_details ";
			$sql .= " where domain_uuid = '$domain_uuid' ";
			$sql .= " and dialplan_uuid = '$dialplan_uuid' ";
			$sql .= " and dialplan_detail_tag = 'anti-action' ";
			$sql .= " order by dialplan_detail_order asc";
			$prep_statement = $db->prepare(check_sql($sql));
			$prep_statement->execute();
			$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
			$result_count = count($result);
			unset ($prep_statement, $sql);
			if ($result_count == 0) {
				//no results
			}
			else { //received results
				foreach($result as $row) {
					echo "<tr >\n";
					echo "	<td valign='top' class='".$row_style[$c]."'>&nbsp;&nbsp;".$row[dialplan_detail_tag]."</td>\n";
					echo "	<td valign='top' class='".$row_style[$c]."'>&nbsp;&nbsp;".$row[dialplan_detail_type]."</td>\n";
					echo "	<td valign='top' class='".$row_style[$c]."'>&nbsp;&nbsp;".$row[dialplan_detail_data]."</td>\n";
					echo "	<td valign='top' class='".$row_style[$c]."'>&nbsp;&nbsp;".$row[dialplan_detail_order]."</td>\n";
					echo "	<td valign='top' align='right'>\n";
					if (permission_exists('fifo_edit')) {
						echo "		<a href='fifo_detail_edit.php?id=".$row[dialplan_detail_uuid]."&id2=".$dialplan_uuid."' alt='edit'>$v_link_label_edit</a>\n";
					}
					if (permission_exists('fifo_delete')) {
						echo "		<a href='fifo_detail_delete.php?id=".$row[dialplan_detail_uuid]."&id2=".$dialplan_uuid."' alt='delete' onclick=\"return confirm('Do you really want to delete this?')\">$v_link_label_delete</a>\n";
					}
					echo "	</td>\n";
					echo "</tr>\n";
					if ($c==0) { $c=1; } else { $c=0; }
				} //end foreach
				unset($sql, $result, $row_count);
			} //end if results

			echo "<tr>\n";
			echo "<td colspan='5'>\n";
			echo "	<table width='100%' cellpadding='0' cellspacing='0'>\n";
			echo "	<tr>\n";
			echo "		<td width='33.3%' nowrap>&nbsp;</td>\n";
			echo "		<td width='33.3%' align='center' nowrap>$paging_controls</td>\n";
			echo "		<td width='33.3%' align='right'>\n";
			if (permission_exists('fifo_add')) {
				echo "			<a href='fifo_details_edit.php?id2=".$dialplan_uuid."' alt='add'>$v_link_label_add</a>\n";
			}
			echo "		</td>\n";
			echo "	</tr>\n";
			echo "	</table>\n";
			echo "</td>\n";
			echo "</tr>\n";

			echo "</table>";
			echo "</div>";
			echo "<br><br>";
			echo "<br><br>";

			echo "</td>";
			echo "</tr>";
			echo "</table>";
			echo "</div>";
			echo "<br><br>";
		} //end if update

//show the footer
	require_once "includes/footer.php";
?>