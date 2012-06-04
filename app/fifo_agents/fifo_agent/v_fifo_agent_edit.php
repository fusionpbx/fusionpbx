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
*/
require_once "root.php";
require_once "includes/require.php";
require_once "includes/checkauth.php";
if (if_group("agent") || if_group("admin") || if_group("superadmin")) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//save the uuid to a variable from the http GET 
	$uuid = check_str($_GET["uuid"]);

//POST to PHP variables
	if (count($_POST)>0) {
		//$domain_uuid = check_str($_POST["domain_uuid"]);
		$resolution_code = check_str($_POST["resolution_code"]);
		$transaction_id = check_str($_POST["transaction_id"]);
		$action_item = check_str($_POST["action_item"]);
		$uuid = check_str($_POST["uuid"]);
		$notes = check_str($_POST["notes"]);
		//$fifo_name = check_str($_POST["fifo_name"]);
		//$agent_username = check_str($_POST["agent_username"]);
		//$agent_priority = check_str($_POST["agent_priority"]);
		$agent_status = check_str($_POST["agent_status"]);
		//$agent_last_call = check_str($_POST["agent_last_call"]);
		//$agent_contact_number = check_str($_POST["agent_contact_number"]);
	}

if (count($_POST)>0 && strlen($_POST["persistformvar"]) == 0) {

	$msg = '';
	//check for all required data
		if (strlen($domain_uuid) == 0) { $msg .= "Please provide: domain_uuid<br>\n"; }
		//if (strlen($domain_uuid) == 0) { $msg .= "Please provide: domain_uuid<br>\n"; }
		//if (strlen($resolution_code) == 0) { $msg .= "Please provide: Resolution Code<br>\n"; }
		//if (strlen($transaction_id) == 0) { $msg .= "Please provide: Transaction ID<br>\n"; }
		//if (strlen($action_item) == 0) { $msg .= "Please provide: Action Item<br>\n"; }
		//if (strlen($uuid) == 0) { $msg .= "Please provide: UUID<br>\n"; }
		//if (strlen($notes) == 0) { $msg .= "Please provide: Notes<br>\n"; }
		//if (strlen($fifo_name) == 0) { $msg .= "Please provide: Queue Name<br>\n"; }
		//if (strlen($agent_username) == 0) { $msg .= "Please provide: Username<br>\n"; }
		//if (strlen($agent_priority) == 0) { $msg .= "Please provide: Agent Priority<br>\n"; }
		if (strlen($agent_status) == 0) { $msg .= "Please provide: Status<br>\n"; }
		//if (strlen($agent_last_call) == 0) { $msg .= "Please provide: Last Call<br>\n"; }
		//if (strlen($agent_contact_number) == 0) { $msg .= "Please provide: Contact Number<br>\n"; }
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

	//update the database
		if ($_POST["persistformvar"] != "true") {
			//do not insert {uuid} into the database
				if ($uuid == "{uuid}") { $uuid = ''; }

			//add to the agent call logs
				if (strlen($uuid) > 0) {
					$sql = "insert into v_fifo_agent_call_logs ";
					$sql .= "(";
					$sql .= "domain_uuid, ";
					$sql .= "resolution_code, ";
					$sql .= "transaction_id, ";
					$sql .= "action_item, ";
					$sql .= "uuid, ";
					$sql .= "notes, ";
					$sql .= "add_user, ";
					$sql .= "add_date ";
					$sql .= ")";
					$sql .= "values ";
					$sql .= "(";
					$sql .= "'$domain_uuid', ";
					$sql .= "'$resolution_code', ";
					$sql .= "'$transaction_id', ";
					$sql .= "'$action_item', ";
					$sql .= "'$uuid', ";
					$sql .= "'$notes', ";
					$sql .= "'".$_SESSION["username"]."', ";
					$sql .= "now() ";
					$sql .= ")";
					$db->exec(check_sql($sql));
					unset($sql);
				}

			//update the status
				$sql = "update v_fifo_agents set ";
				$sql .= "domain_uuid = '$domain_uuid', ";
				//$sql .= "fifo_name = '$fifo_name', ";
				//$sql .= "agent_username = '$agent_username', ";
				//$sql .= "agent_priority = '$agent_priority', ";
				$sql .= "agent_status = '$agent_status', ";
				$sql .= "agent_status_epoch = ".time()." ";
				//$sql .= "agent_last_call = '$agent_last_call', ";
				//$sql .= "agent_contact_number = '$agent_contact_number' ";
				$sql .= " where agent_username = '".$_SESSION["username"]."' ";
				$db->exec(check_sql($sql));
				unset($sql);

			//agent status log
				if (strlen($agent_status) > 0) {
					$sql = "insert into v_fifo_agent_status_logs ";
					$sql .= "(";
					$sql .= "domain_uuid, ";
					$sql .= "username, ";
					$sql .= "agent_status, ";
					$sql .= "uuid, ";
					$sql .= "add_date ";
					$sql .= ")";
					$sql .= "values ";
					$sql .= "(";
					$sql .= "'$domain_uuid', ";
					$sql .= "'".$_SESSION["username"]."', ";
					$sql .= "'$agent_status', ";
					$sql .= "'$uuid', ";
					$sql .= "now() ";
					$sql .= ")";
					$db->exec(check_sql($sql));
					unset($sql);
				}

				require_once "includes/header.php";
				echo "<meta http-equiv=\"refresh\" content=\"2;url=v_fifo_agent_edit.php\">\n";
				echo "<div align='center'>\n";
				echo "Update Complete\n";
				echo "</div>\n";
				require_once "includes/footer.php";
				return;
		} //if ($_POST["persistformvar"] != "true") { 

} //(count($_POST)>0 && strlen($_POST["persistformvar"]) == 0)

//pre-populate the form
	if ($_POST["persistformvar"] != "true") {
		$login_status = false;
		$sql = "";
		$sql .= "select * from v_fifo_agents ";
		$sql .= " where agent_username = '".$_SESSION["username"]."' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		foreach ($result as &$row) {
			$domain_uuid = $row["domain_uuid"];
			//$fifo_name = $row["fifo_name"];
			//$agent_username = $row["agent_username"];
			//$agent_priority = $row["agent_priority"];
			$agent_status = $row["agent_status"];
			$agent_last_call = $row["agent_last_call"];
			$agent_last_uuid = $row["agent_last_uuid"];
			//$agent_contact_number = $row["agent_contact_number"];
			$login_status = true;
			break; //limit to 1 row
		}
		unset ($prep_statement);
	}

//format the last call time
	if ($agent_last_call == 0) {
		$agent_last_call_desc = '';
	}
	else {
		$agent_last_call_desc = date("g:i:s a j M Y",$agent_last_call);
	}

//show the content
	require_once "includes/header.php";

//if the agent_status is available and the uuid has been supplied then refrsh the page 
	//until the status changes or until a time out has been reached
	if ($agent_status == '2' && strlen($uuid) > 0 && $uuid != "{uuid}") {
		if (count($_GET["refresh"]) < 10) {
			if (substr($_SERVER["SERVER_PROTOCOL"], 0,5) == "HTTP/") {
				$meta_refresh_url = "http://".$_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"]."&refresh[]=".count($_SERVER["refresh"]);
			}
			else {
				$meta_refresh_url = "https://".$_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"]."&refresh[]=".count($_SERVER["refresh"]);
			}
			echo "<meta http-equiv=\"refresh\" content=\"1;URL=".$meta_refresh_url."\">\n";
		}
	}

	echo "<div align='center'>";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing=''>\n";

	echo "<tr class='border'>\n";
	echo "	<td align=\"left\">\n";
	//echo "	  <br>";


	echo "<form method='post' name='frm' action=''>\n";

	echo "<div align='center'>\n";
	echo "<table width='100%'  border='0' cellpadding='6' cellspacing='0'>\n";

	echo "<tr>\n";
	echo "<td align='left' width='30%' nowrap='nowrap' align='left'><b>Agent</b></td>\n";
	echo "<td width='70%' align='right'>\n";
	if (!$login_status) {
		echo "	<input type='button' class='btn' name='' alt='login' onclick=\"window.location='v_fifo_agent_login.php'\" value='Login'>\n";
	}
	if ($login_status) {
		echo "	<input type='button' class='btn' name='' alt='logout' onclick=\"window.location='v_fifo_agent_logout.php'\" value='Logout'>\n";
	}

	//echo "	<input type='button' class='btn' name='' alt='back' onclick=\"window.location='v_fifo_agents.php'\" value='Back'>\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td align='left' colspan='2'>\n";
	echo "Enables the agent to set their status.<br /><br />\n";
	echo "</td>\n";
	echo "</tr>\n";


		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap>\n";
		echo "	<strong>Username:</strong>\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<strong>".$_SESSION["username"]."</strong>";
		echo "<br />\n";
		echo "\n";
		echo "</td>\n";
		echo "</tr>\n";

		//echo "<tr>\n";
		//echo "<td class='vncell' valign='top' align='left' nowrap>\n";
		//echo "	<strong>Last Call:</strong>\n";
		//echo "</td>\n";
		//echo "<td class='vtable' align='left'>\n";
		//echo 	$agent_last_call_desc;
		//echo "  <input class='formfld' type='text' name='agent_last_call' maxlength='255' value='$agent_last_call'>\n";
		//echo "<br />\n";
		//echo "\n";
		//echo "</td>\n";
		//echo "</tr>\n";

		if ($login_status) {
			echo "<tr>\n";
			echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
			echo "	Status:\n";
			echo "</td>\n";
			echo "<td class='vtable' align='left'>\n";
			//generate the agent status select list
				$sql = "SELECT var_name, var_value FROM v_vars ";
				$sql .= "where domain_uuid = '$domain_uuid' ";
				$sql .= "and var_cat = 'Queues Agent Status' ";
				$sql .= "and var_name not like 'system%' ";
				$prep_statement = $db->prepare(check_sql($sql));
				$prep_statement->execute();
				echo "<select name=\"agent_status\" class='formfld'>\n";
				echo "<option value=\"\"></option>\n";
				$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
				foreach($result as $field) {
					if ($field[var_value] == $agent_status) {
						echo "<option value='".$field[var_value]."' selected='selected'>".$field[var_name]."</option>\n";
					}
					else {
						echo "<option value='".$field[var_value]."'>".$field[var_name]."</option>\n";
					}
				}
				echo "</select>";
				$_SESSION["array_agent_status"] = "";
				if (!is_array($_SESSION["array_agent_status"])) {
					foreach($result as $field) {
						$_SESSION["array_agent_status"][$field[var_value]] = $field[var_name];
					}
				}
				/*
						foreach($result as $field) {
							$_SESSION["array_agent_status"][$field[var_value]] = $field[var_name];
						}

						$x=1;
						foreach($_SESSION["array_agent_status"] as $value) {
							echo "$x $value<br />\n";
							$x++;
						}
				*/
				unset($sql, $result);

			echo "<br />\n";
			echo "Enter the status of the Agent.\n";
			echo "</td>\n";
			echo "</tr>\n";

			echo "<tr>\n";
			echo "<td class='' valign='top' align='left' nowrap>\n";
			echo "	&nbsp;\n";
			echo "</td>\n";
			echo "<td class='' align='left'>\n";
			echo "<br />\n";
			echo "</td>\n";
			echo "</tr>\n";
		}

		/*
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap>\n";
		echo "	Contact Number:\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo 	$agent_contact_number;
		//echo "  <input class='formfld' type='text' name='agent_contact_number' maxlength='255' value='$agent_contact_number'>\n";
		echo "<br />\n";
		//echo "Enter the agent contact number.\n";
		echo "</td>\n";
		echo "</tr>\n";
		*/

		if ($login_status && $agent_status == '9') {
			echo "<tr>\n";
			echo "<td class='vncell' valign='top' align='left' nowrap>\n";
			echo "	Resolution Code:\n";
			echo "</td>\n";
			echo "<td class='vtable' align='left'>\n";
			echo "	<input class='formfld' type='text' name='resolution_code' maxlength='255' value=\"$resolution_code\">\n";
			echo "<br />\n";
			echo "Enter the resolution code.\n";
			echo "</td>\n";
			echo "</tr>\n";

			echo "<tr>\n";
			echo "<td class='vncell' valign='top' align='left' nowrap>\n";
			echo "	Transaction ID:\n";
			echo "</td>\n";
			echo "<td class='vtable' align='left'>\n";
			echo "	<input class='formfld' type='text' name='transaction_id' maxlength='255' value=\"$transaction_id\">\n";
			echo "<br />\n";
			echo "Enter the Transaction ID.\n";
			echo "</td>\n";
			echo "</tr>\n";

			echo "<tr>\n";
			echo "<td class='vncell' valign='top' align='left' nowrap>\n";
			echo "	Action Item:\n";
			echo "</td>\n";
			echo "<td class='vtable' align='left'>\n";
			echo "	<input class='formfld' type='text' name='action_item' maxlength='255' value=\"$action_item\">\n";
			echo "<br />\n";
			echo "Enter the Action Item.\n";
			echo "</td>\n";
			echo "</tr>\n";

			echo "<tr>\n";
			echo "<td class='vncell' valign='top' align='left' nowrap>\n";
			echo "	Notes:\n";
			echo "</td>\n";
			echo "<td class='vtable' align='left'>\n";
			echo "	<textarea class='formfld' name='notes' rows='4'>$notes</textarea>\n";
			echo "<br />\n";
			echo "Enter the notes.\n";
			echo "</td>\n";
			echo "</tr>\n";
		}
		if ($login_status) {
			echo "	<tr>\n";
			echo "		<td colspan='2' align='right'>\n";
			if (strlen($uuid) == 0) {
				$uuid = $agent_last_uuid;
			}
			echo "				<input type='hidden' name='uuid' value=\"$uuid\">\n";
			echo "				<input type='submit' name='submit' class='btn' value='Save'>\n";
			echo "		</td>\n";
			echo "	</tr>";
		}


	echo "</table>";
	echo "</form>";

	echo "	</td>";
	echo "	</tr>";
	echo "</table>";
	echo "</div>";


require_once "includes/footer.php";
?>
