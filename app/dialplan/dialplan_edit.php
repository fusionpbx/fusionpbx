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
if (permission_exists('dialplan_add') 
	|| permission_exists('dialplan_edit') 
	|| permission_exists('inbound_route_add') 
	|| permission_exists('inbound_route_edit')
	|| permission_exists('outbound_route_add') 
	|| permission_exists('outbound_route_edit')
	|| permission_exists('time_conditions_add') 
	|| permission_exists('time_conditions_edit')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//set the action as an add or an update
	if (isset($_REQUEST["id"])) {
		$action = "update";
		$dialplan_uuid = check_str($_REQUEST["id"]);
	}
	else {
		$action = "add";
	}

//get the app uuid
	$app_uuid = check_str($_REQUEST["app_uuid"]);

//get the http post values and set them as php variables
	if (count($_POST)>0) {
		$dialplan_name = check_str($_POST["dialplan_name"]);
		$dialplan_number = check_str($_POST["dialplan_number"]);
		$dialplan_order = check_str($_POST["dialplan_order"]);
		$dialplan_continue = check_str($_POST["dialplan_continue"]);
		if (strlen($dialplan_continue) == 0) { $dialplan_continue = "false"; }
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
		if (strlen($dialplan_name) == 0) { $msg .= "Please provide: Extension Name<br>\n"; }
		if (strlen($dialplan_order) == 0) { $msg .= "Please provide: Order<br>\n"; }
		if (strlen($dialplan_continue) == 0) { $msg .= "Please provide: Continue<br>\n"; }
		if (strlen($dialplan_context) == 0) { $msg .= "Please provide: Context<br>\n"; }
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

	//remove the invalid characters from the extension name
		$dialplan_name = str_replace(" ", "_", $dialplan_name);
		$dialplan_name = str_replace("/", "", $dialplan_name);

	//add or update the database
		if ($_POST["persistformvar"] != "true") {
			if ($action == "add" && permission_exists('dialplan_add')) {
				//add the data into the database
					$dialplan_context = $_SESSION['context'];
					$dialplan_uuid = uuid();
					$sql = "insert into v_dialplans ";
					$sql .= "(";
					$sql .= "domain_uuid, ";
					$sql .= "dialplan_uuid, ";
					$sql .= "app_uuid, ";
					$sql .= "dialplan_name, ";
					$sql .= "dialplan_number, ";
					$sql .= "dialplan_order, ";
					$sql .= "dialplan_continue, ";
					$sql .= "dialplan_context, ";
					$sql .= "dialplan_enabled, ";
					$sql .= "dialplan_description ";
					$sql .= ")";
					$sql .= "values ";
					$sql .= "(";
					$sql .= "'".$_SESSION['domain_uuid']."', ";
					$sql .= "'$dialplan_uuid', ";
					$sql .= "'742714e5-8cdf-32fd-462c-cbe7e3d655db', ";
					$sql .= "'$dialplan_name', ";
					$sql .= "'$dialplan_number', ";
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

				//redirect the user
					require_once "includes/header.php";
					switch ($app_uuid) {
						case "c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4":
							//inbound routes
							echo "<meta http-equiv=\"refresh\" content=\"2;url=".PROJECT_PATH."/app/dialplan/dialplans.php?app_uuid=$app_uuid\">\n";
							break;
						case "8c914ec3-9fc0-8ab5-4cda-6c9288bdc9a3":
							//outbound routes
							echo "<meta http-equiv=\"refresh\" content=\"2;url=".PROJECT_PATH."/app/dialplan/dialplans.php?app_uuid=$app_uuid\">\n";
							break;
						case "4b821450-926b-175a-af93-a03c441818b1":
							//time conditions
							echo "<meta http-equiv=\"refresh\" content=\"2;url=".PROJECT_PATH."/app/dialplan/dialplans.php?app_uuid=$app_uuid\">\n";
							break;
						default:
							echo "<meta http-equiv=\"refresh\" content=\"2;url=".PROJECT_PATH."/app/dialplan/dialplans.php\">\n";
							break;
					}
					echo "<div align='center'>\n";
					echo "Add Complete\n";
					echo "</div>\n";
					require_once "includes/footer.php";
					return;
			} //if ($action == "add")

			if ($action == "update" && permission_exists('dialplan_edit')) {
				//update the database
					$sql = "update v_dialplans set ";
					$sql .= "dialplan_name = '$dialplan_name', ";
					$sql .= "dialplan_number = '$dialplan_number', ";
					$sql .= "dialplan_order = '$dialplan_order', ";
					$sql .= "dialplan_continue = '$dialplan_continue', ";
					$sql .= "dialplan_context = '$dialplan_context', ";
					$sql .= "dialplan_enabled = '$dialplan_enabled', ";
					$sql .= "dialplan_description = '$dialplan_description' ";
					$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
					$sql .= "and dialplan_uuid = '$dialplan_uuid'";
					$db->exec(check_sql($sql));
					unset($sql);

				//delete the dialplan context from memcache
					$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
					if ($fp) {
						$switch_cmd = "memcache delete dialplan:".$dialplan_context."@".$_SESSION['domain_name'];
						$switch_result = event_socket_request($fp, 'api '.$switch_cmd);
					}

				//synchronize the xml config
					save_dialplan_xml();

				//redirect the user
					require_once "includes/header.php";
					switch ($app_uuid) {
						case "c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4":
							//inbound routes
							echo "<meta http-equiv=\"refresh\" content=\"2;url=".PROJECT_PATH."/app/dialplan/dialplans.php?app_uuid=$app_uuid\">\n";
							break;
						case "8c914ec3-9fc0-8ab5-4cda-6c9288bdc9a3":
							//outbound routes
							echo "<meta http-equiv=\"refresh\" content=\"2;url=".PROJECT_PATH."/app/dialplan/dialplans.php?app_uuid=$app_uuid\">\n";
							break;
						case "4b821450-926b-175a-af93-a03c441818b1":
							//time conditions
							echo "<meta http-equiv=\"refresh\" content=\"2;url=".PROJECT_PATH."/app/dialplan/dialplans.php?app_uuid=$app_uuid\">\n";
							break;
						default:
							echo "<meta http-equiv=\"refresh\" content=\"2;url=".PROJECT_PATH."/app/dialplan/dialplans.php\">\n";
							break;
					}
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
		$sql = "select * from v_dialplans ";
		$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
		$sql .= "and dialplan_uuid = '$dialplan_uuid' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		foreach ($result as &$row) {
			$app_uuid = $row["app_uuid"];
			$dialplan_name = $row["dialplan_name"];
			$dialplan_number = $row["dialplan_number"];
			$dialplan_order = $row["dialplan_order"];
			$dialplan_continue = $row["dialplan_continue"];
			$dialplan_context = $row["dialplan_context"];
			$dialplan_enabled = $row["dialplan_enabled"];
			$dialplan_description = $row["dialplan_description"];
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
	echo "	<tr>\n";
	echo "		<td align='left' width='30%'>\n";
	echo"			<span class=\"vexpl\"><strong>Dialplan</strong></span><br />\n";
	echo "    </td>\n";
	echo "    <td width='70%' align='right'>\n";
	echo "		<input type='button' class='btn' name='' alt='copy' onclick=\"if (confirm('Do you really want to copy this?')){window.location='dialplan_copy.php?id=".$row['dialplan_uuid']."';}\" value='Copy'>\n";
	switch ($app_uuid) {
		case "c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4":
			//inbound routes
			echo "		<input type='button' class='btn' name='' alt='back' onclick=\"window.location='dialplans.php?app_uuid=$app_uuid'\" value='Back'>\n";
			break;
		case "8c914ec3-9fc0-8ab5-4cda-6c9288bdc9a3":
			//outbound routes
			echo "		<input type='button' class='btn' name='' alt='back' onclick=\"window.location='dialplans.php?app_uuid=$app_uuid'\" value='Back'>\n";
			break;
		case "4b821450-926b-175a-af93-a03c441818b1":
			//time conditions
			echo "		<input type='button' class='btn' name='' alt='back' onclick=\"window.location='dialplans.php?app_uuid=$app_uuid'\" value='Back'>\n";
			break;
		default:
			echo "		<input type='button' class='btn' name='' alt='back' onclick=\"window.location='dialplans.php'\" value='Back'>\n";
			break;
	}
	echo "	</td>\n";
	echo "  </tr>\n";
	echo "  <tr>\n";
	echo "    <td align='left' colspan='2'>\n";
	echo "        Dialplan Include general settings. \n";
	echo "        \n";
	echo "    </td>\n";
	echo "  </tr>\n";
	echo "</table>";
	echo "<br />\n";

	echo "<table width='100%'  border='0' cellpadding='6' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    Name:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='dialplan_name' maxlength='255' value=\"".htmlspecialchars($dialplan_name)."\">\n";
	echo "<br />\n";
	echo "\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    Number:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='dialplan_number' maxlength='255' value=\"".htmlspecialchars($dialplan_number)."\">\n";
	echo "<br />\n";
	echo "\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    Context:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='dialplan_context' maxlength='255' value=\"$dialplan_context\">\n";
	echo "<br />\n";
	echo "\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    Continue:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <select class='formfld' name='dialplan_continue'>\n";
	echo "    <option value=''></option>\n";
	if ($dialplan_continue == "true") { 
		echo "    <option value='true' selected='selected'>true</option>\n";
	}
	else {
		echo "    <option value='true'>true</option>\n";
	}
	if ($dialplan_continue == "false") { 
		echo "    <option value='false' selected='selected'>false</option>\n";
	}
	else {
		echo "    <option value='false'>false</option>\n";
	}
	echo "    </select>\n";
	echo "<br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
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
	echo "<br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
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
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    Description:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <textarea class='formfld' name='dialplan_description' rows='4'>".htmlspecialchars($dialplan_description)."</textarea>\n";
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

	//dialplan details
	if ($action == "update") {
		echo "<div align='center'>";
		echo "<table width='100%' border='0' cellpadding='0' cellspacing='2'>\n";
		echo "<tr class='border'>\n";
		echo "	<td align=\"center\">\n";

		echo "<table width=\"100%\" border=\"0\" cellpadding=\"6\" cellspacing=\"0\">\n";
		echo "  <tr>\n";
		echo "    <td align='left'><p><span class=\"vexpl\"><span class=\"red\"><strong>Conditions and Actions<br />\n";
		echo "        </strong></span>\n";
		echo "        The following conditions, actions and anti-actions are used in the dialplan to direct \n";
		echo "        call flow. Each is processed in order that it is given. \n";
		echo "        Use as many conditions, actions or anti-actions as needed. \n";
		echo "        </span></p></td>\n";
		echo "  </tr>\n";
		echo "</table>";
		echo "<br />\n";

		$sql = "select * from v_dialplan_details ";
		$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
		$sql .= "and dialplan_uuid = '$dialplan_uuid' ";
		$sql .= "order by dialplan_detail_group asc, dialplan_detail_order asc";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		$result_count = count($result);
		unset ($prep_statement, $sql);

		//create a new array that is sorted into groups and put the tags in order conditions, actions, anti-actions
			$x = 0;
			$details = '';
			//conditions
				foreach($result as $row) {
					if ($row['dialplan_detail_tag'] == "condition") {
						$group = $row['dialplan_detail_group'];
						foreach ($row as $key => $val) {
							$details[$group][$x][$key] = $val;
						}
					}
					$x++;
				}
			//regex
				foreach($result as $row) {
					if ($row['dialplan_detail_tag'] == "regex") {
						$group = $row['dialplan_detail_group'];
						foreach ($row as $key => $val) {
							$details[$group][$x][$key] = $val;
						}
					}
					$x++;
				}
			//actions
				foreach($result as $row) {
					if ($row['dialplan_detail_tag'] == "action") {
						$group = $row['dialplan_detail_group'];
						foreach ($row as $key => $val) {
							$details[$group][$x][$key] = $val;
						}
					}
					$x++;
				}
			//anti-actions
				foreach($result as $row) {
					if ($row['dialplan_detail_tag'] == "anti-action") {
						$group = $row['dialplan_detail_group'];
						foreach ($row as $key => $val) {
							$details[$group][$x][$key] = $val;
						}
					}
					$x++;
				}
			unset($result);

		//define the alternating row styles
			$c = 0;
			$row_style["0"] = "row_style0";
			$row_style["1"] = "row_style1";

		//display the results
			echo "<div align='center'>\n";
			echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
			echo "<tr>\n";
			echo "<th align='center' width='90px;'>Tag</th>\n";
			echo "<th align='center' width='150px;'>Type</th>\n";
			echo "<th align='center' width='70%'>Data</th>\n";
			echo "<th align='center'>Order</th>\n";
			//echo "<th align='center'>Group</th>\n";
			echo "<td align='right' width='42'>\n";
			echo "	<a href='dialplan_details_edit.php?id2=".$dialplan_uuid."' alt='add'>$v_link_label_add</a>\n";
			echo "</td>\n";
			echo "<tr>\n";

			if ($result_count > 0) {
				$x = 0;
				foreach($details as $group) {
					if ($x > 0) {
						echo "<tr>\n";
						echo "<td colspan='6'>\n";
						echo "	<table width='100%' cellpadding='0' cellspacing='0'>\n";
						echo "	<tr>\n";
						echo "		<td width='33.3%' nowrap='nowrap'>&nbsp;</td>\n";
						echo "		<td width='33.3%' align='center' nowrap='nowrap'>$paging_controls</td>\n";
						echo "		<td width='33.3%' align='right'>\n";
						echo "			<a href='dialplan_details_edit.php?id2=".$dialplan_uuid."' alt='add'>$v_link_label_add</a>\n";
						echo "		</td>\n";
						echo "	</tr>\n";
						echo "	</table>\n";
						echo "</td>\n";
						echo "</tr>\n";
						echo "</table>";
						echo "</div>";
						echo "<br><br>";

						echo "<div align='center'>\n";
						echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
						echo "<tr>\n";
						echo "<th align='center' width='90px;'>Tag</th>\n";
						echo "<th align='center' width='150px;'>Type</th>\n";
						echo "<th align='center' width='70%'>Data</th>\n";
						echo "<th align='center'>Order</th>\n";
						//echo "<th align='center'>Group</th>\n";
						echo "<td align='right' width='42'>\n";
						echo "	<a href='dialplan_details_edit.php?id2=".$dialplan_uuid."' alt='add'>$v_link_label_add</a>\n";
						echo "</td>\n";
						echo "<tr>\n";
					}

					foreach($group as $row) {
						echo "<tr >\n";
						echo "	<td valign='top' class='".$row_style[$c]."'>&nbsp;&nbsp;".$row['dialplan_detail_tag']."</td>\n";
						echo "	<td valign='top' class='".$row_style[$c]."'>&nbsp;&nbsp;".$row['dialplan_detail_type']."</td>\n";
						echo "	<td valign='top' class='".$row_style[$c]."'>&nbsp;&nbsp;".wordwrap($row['dialplan_detail_data'],180,"<br>",1)."</td>\n";
						echo "	<td valign='top' class='".$row_style[$c]."'>&nbsp;&nbsp;".$row['dialplan_detail_order']."</td>\n";
						//echo "	<td valign='top' class='".$row_style[$c]."'>&nbsp;&nbsp;".$row['dialplan_detail_group']."</td>\n";
						echo "	<td valign='top' align='right' nowrap='nowrap'>\n";
						echo "		<a href='dialplan_details_edit.php?id=".$row['dialplan_detail_uuid']."&id2=".$dialplan_uuid."' alt='edit'>$v_link_label_edit</a>\n";
						echo "		<a href='dialplan_details_delete.php?id=".$row['dialplan_detail_uuid']."&id2=".$dialplan_uuid."' alt='delete' onclick=\"return confirm('Do you really want to delete this?')\">$v_link_label_delete</a>\n";
						echo "	</td>\n";
						echo "</tr>\n";
					}
					if ($c==0) { $c=1; } else { $c=0; }
					$x++;
				} //end foreach
				unset($sql, $result, $row_count);
			} //end if results

			echo "<tr>\n";
			echo "<td colspan='6'>\n";
			echo "	<table width='100%' cellpadding='0' cellspacing='0'>\n";
			echo "	<tr>\n";
			echo "		<td width='33.3%' nowrap='nowrap'>&nbsp;</td>\n";
			echo "		<td width='33.3%' align='center' nowrap='nowrap'>$paging_controls</td>\n";
			echo "		<td width='33.3%' align='right'>\n";
			echo "			<a href='dialplan_details_edit.php?id2=".$dialplan_uuid."' alt='add'>$v_link_label_add</a>\n";
			echo "		</td>\n";
			echo "	</tr>\n";
			echo "	</table>\n";
			echo "</td>\n";
			echo "</tr>\n";
			echo "</table>";
			echo "</div>";
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