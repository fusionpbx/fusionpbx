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
	Copyright (C) 2008-2012 All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/
include "root.php";
require_once "includes/require.php";
require_once "includes/checkauth.php";
if (permission_exists('extension_add') || permission_exists('extension_edit')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//set the action as an add or an update
	if (isset($_REQUEST["id"])) {
		$action = "update";
		$extension_uuid = check_str($_REQUEST["id"]);
	}
	else {
		$action = "add";
	}

//get the http values and set them as php variables
	if (count($_POST)>0) {
		//get the values from the HTTP POST and save them as PHP variables
		$extension_uuid = check_str($_POST["extension_uuid"]);
		$unique_id = check_str($_POST["unique_id"]);
		$vm_password = check_str($_POST["vm_password"]);
		$dial_string = check_str($_POST["dial_string"]);
	}

if (count($_POST)>0 && strlen($_POST["persistformvar"]) == 0) {

	//check for all required data
		//if (strlen($domain_uuid) == 0) { $msg .= "Please provide: domain_uuid<br>\n"; }
		if (strlen($extension_uuid) == 0) { $msg .= "Please provide the extension<br>\n"; }
		if (strlen($unique_id) == 0) { $msg .= "Please provide the unique ID.<br>\n"; }
	//get the number of rows in v_extensions 
		$sql = "select count(*) as num_rows from v_extensions ";
		$sql .= "where unique_id = '".$unique_id."' ";
		$prep_statement = $db->prepare(check_sql($sql));
		if ($prep_statement) {
			$prep_statement->execute();
			$row = $prep_statement->fetch(PDO::FETCH_ASSOC);
			if ($row['num_rows'] > 0) {
				$msg .= "The ID is not unqiue please provide a unique ID.<br>\n";
			}
		}
		unset($prep_statement, $result);
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

	//set the default user context
		if (if_group("superadmin")) {
			//allow a user assigned to super admin to change the user_context
		}
		else {
			//if the user_context was not set then set the default value
			if (strlen($user_context) == 0) { 
				if (count($_SESSION["domains"]) > 1) {
					$user_context = $_SESSION['domain_name'];
				}
				else {
					$user_context = "default";
				}
			}
		}

	//add or update the database
	if ($_POST["persistformvar"] != "true") {
		//update the extension
			if ($action == "add" && permission_exists('extension_edit')) {
				$sql = "update v_extensions set ";
				$sql .= "unique_id = '$unique_id' ";
				$sql .= "where domain_uuid = '$domain_uuid' ";
				$sql .= "and extension_uuid = '$extension_uuid'";
				$db->exec(check_sql($sql));
				unset($sql);
			}

		//update the extension
			if ($action == "update" && permission_exists('extension_edit')) {
				$sql = "update v_extensions set ";
				$sql .= "unique_id = '$unique_id', ";
				if (strlen($vm_password) > 0) {
					$sql .= "vm_password = '$vm_password' ";
				}
				else {
					$sql .= "vm_password = 'user-choose' ";
				}
				$sql .= "where domain_uuid = '$domain_uuid' ";
				$sql .= "and extension_uuid = '$extension_uuid'";
				$db->exec(check_sql($sql));
				unset($sql);
			}

		//get the extension
			$sql = "select * from v_extensions ";
			$sql .= "where domain_uuid = '$domain_uuid' ";
			$sql .= "and extension_uuid = '$extension_uuid' ";
			$prep_statement = $db->prepare(check_sql($sql));
			$prep_statement->execute();
			$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
			foreach ($result as &$row) {
				$extension = $row["extension"];
			}
			unset ($prep_statement);

		//delete extension from memcache
			$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
			if ($fp) {
				$switch_cmd = "memcache delete directory:".$extension."@".$_SESSION['domain_name'];
				$switch_result = event_socket_request($fp, 'api '.$switch_cmd);
			}

		//show the action and redirect the user
			require_once "includes/header.php";
			echo "<meta http-equiv=\"refresh\" content=\"2;url=index.php\">\n";
			echo "<br />\n";
			echo "<div align='center'>\n";
			echo "	<table width='40%'>\n";
			echo "		<tr>\n";
			echo "			<th align='left'>Message</th>\n";
			echo "		</tr>\n";
			echo "		<tr>\n";
			if ($action == "add") {
				echo "			<td class='row_style1'><strong>Add Complete</strong></td>\n";
			}
			if ($action == "update") {
				echo "			<td class='row_style1'><strong>Update Complete</strong></td>\n";
			}
			echo "		</tr>\n";
			echo "	</table>\n";
			echo "<br />\n";
			echo "</div>\n";
			require_once "includes/footer.php";
			return;

	} //if ($_POST["persistformvar"] != "true")
} //(count($_POST)>0 && strlen($_POST["persistformvar"]) == 0)

//pre-populate the form
	if ($_POST["persistformvar"] != "true") {
		//$extension_uuid = $_GET["id"];
		$sql = "select * from v_extensions ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$sql .= "and extension_uuid = '$extension_uuid' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		foreach ($result as &$row) {
			$extension = $row["extension"];
			$dial_string = $row["dial_string"];
			$unique_id = $row["unique_id"];
			$password = $row["password"];
			$vm_password = $row["vm_password"];
			$vm_password = str_replace("#", "", $vm_password); //preserves leading zeros
		}
		unset ($prep_statement);
	}

//set the defaults
	if (strlen($limit_max) == 0) { $limit_max = '5'; }

//begin the page content
	require_once "includes/header.php";

	echo "<script type=\"text/javascript\" language=\"JavaScript\">\n";
	echo "\n";
	echo "function enable_change(enable_over) {\n";
	echo "	var endis;\n";
	echo "	endis = !(document.iform.enable.checked || enable_over);\n";
	echo "	document.iform.range_from.disabled = endis;\n";
	echo "	document.iform.range_to.disabled = endis;\n";
	echo "}\n";
	echo "\n";
	echo "function show_advanced_config() {\n";
	echo "	document.getElementById(\"show_advanced_box\").innerHTML='';\n";
	echo "	aodiv = document.getElementById('show_advanced');\n";
	echo "	aodiv.style.display = \"block\";\n";
	echo "}\n";
	echo "\n";
	echo "function hide_advanced_config() {\n";
	echo "	document.getElementById(\"show_advanced_box\").innerHTML='';\n";
	echo "	aodiv = document.getElementById('show_advanced');\n";
	echo "	aodiv.style.display = \"none\";\n";
	echo "}\n";
	echo "</script>";

	echo "<div align='center'>";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='2'>\n";
	echo "<tr class='border'>\n";
	echo "	<td align=\"left\">\n";
	echo "      <br>";

	echo "<form method='post' name='frm' action=''>\n";
	echo "<div align='center'>\n";
	echo "<table width='100%' border='0' cellpadding='6' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "	<td width='30%' nowrap='nowrap' align='left' valign='top'>\n";
	echo "		<b>Hot Desking</b>\n";
	echo "	</td>\n";
	echo "	<td width='70%' align='right' valign='top'>\n";
	echo "		<input type='submit' name='submit' class='btn' value='Save'>\n";
	echo "		<input type='button' class='btn' name='' alt='back' onclick=\"window.location='index.php'\" value='Back'>\n";
	echo "	</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    Extension:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	if ($action == "add") {
		echo "<select id=\"extension_uuid\" name=\"extension_uuid\" class='formfld' \">\n";
		echo "<option value=''></option>\n";
		$sql = "select extension, extension_uuid, description FROM v_extensions ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$sql .= "order by extension asc ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		$result_count = count($result);
		if ($result_count > 0) {
			foreach($result as $row) {
				if ($extension_uuid == $row['extension_uuid']) {
					echo "<option value=\"".$row['extension_uuid']."\" selected>".$row['extension']." ".$row['description']."</option>\n";
				}
				else {
					echo "<option value=\"".$row['extension_uuid']."\">".$row['extension']." ".$row['description']."</option>\n";
				}
			}
		}
		unset($sql, $result, $result_count);
		echo  "</select><br />\n";
		echo "Select the extension number.\n";
	}
	if ($action == "update") {
		echo "    $extension<br />\n";
		echo "Extension number.\n";
	}
	echo "<br />\n";
	
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    Unique ID:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='unique_id' autocomplete='off' maxlength='255' value=\"$unique_id\">\n";
	echo "<br />\n";
	echo "A unique ID to identify the extension and domain.\n";
	echo "</td>\n";
	echo "</tr>\n";

	if ($action == "update") {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "    Voicemail Password:\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "  <input class='formfld' type='password' name='vm_password' id='vm_password' onfocus=\"document.getElementById('show_vm_password').innerHTML = 'Password: '+document.getElementById('vm_password').value;\" maxlength='255' value='$vm_password'>\n";
		echo "<br />\n";
		echo "<span onclick=\"document.getElementById('show_vm_password').innerHTML = ''\">Enter the voicemail password here. </span><span id='show_vm_password'></span>\n";
		echo "</td>\n";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "    Dial String:\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "    <input class='formfld' type='text' name='dial_string' maxlength='255' value=\"$dial_string\">\n";
		echo "<br />\n";
		echo "Location of the endpoint.\n";
		echo "</td>\n";
		echo "</tr>\n";

		echo "<input type='hidden' name='extension_uuid' maxlength='255' value=\"$extension_uuid\">\n";
	}

	echo "</table>";
	echo "</form>";

	echo "	</td>";
	echo "	</tr>";
	echo "</table>";
	echo "</div>";

require_once "includes/footer.php";
?>