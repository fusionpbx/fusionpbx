<?php
require_once "root.php";
require_once "includes/require.php";
require_once "includes/checkauth.php";
if (permission_exists('call_flow_add') || permission_exists('call_flow_edit')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//action add or update
	if (isset($_REQUEST["id"])) {
		$action = "update";
		$call_flow_uuid = check_str($_REQUEST["id"]);
	}
	else {
		$action = "add";
	}

//get http post variables and set them to php variables
	if (count($_POST)>0) {
		$call_flow_extension = check_str($_POST["call_flow_extension"]);
		$call_flow_feature_code = check_str($_POST["call_flow_feature_code"]);
		$call_flow_status = check_str($_POST["call_flow_status"]);
		$call_flow_app = check_str($_POST["call_flow_app"]);
		$call_flow_pin_number = check_str($_POST["call_flow_pin_number"]);
		$call_flow_data = check_str($_POST["call_flow_data"]);
		$call_flow_anti_app = check_str($_POST["call_flow_anti_app"]);
		$call_flow_anti_data = check_str($_POST["call_flow_anti_data"]);
		$call_flow_description = check_str($_POST["call_flow_description"]);
	}

if (count($_POST)>0 && strlen($_POST["persistformvar"]) == 0) {

	$msg = '';
	if ($action == "update") {
		$call_flow_uuid = check_str($_POST["call_flow_uuid"]);
	}

	//check for all required data
		//if (strlen($call_flow_extension) == 0) { $msg .= "Please provide: Extension<br>\n"; }
		//if (strlen($call_flow_feature_code) == 0) { $msg .= "Please provide: Feature Code<br>\n"; }
		//if (strlen($call_flow_status) == 0) { $msg .= "Please provide: Status<br>\n"; }
		//if (strlen($call_flow_app) == 0) { $msg .= "Please provide: Application<br>\n"; }
		//if (strlen($call_flow_pin_number) == 0) { $msg .= "Please provide: PIN Number<br>\n"; }
		//if (strlen($call_flow_data) == 0) { $msg .= "Please provide: Application Data<br>\n"; }
		//if (strlen($call_flow_anti_app) == 0) { $msg .= "Please provide: Alternate  Application<br>\n"; }
		//if (strlen($call_flow_anti_data) == 0) { $msg .= "Please provide: Application Data<br>\n"; }
		//if (strlen($call_flow_description) == 0) { $msg .= "Please provide: Description<br>\n"; }
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

	//add or update the database
		if ($_POST["persistformvar"] != "true") {
			if ($action == "add" && permission_exists('call_flow_add')) {
				$sql = "insert into v_call_flows ";
				$sql .= "(";
				$sql .= "domain_uuid, ";
				$sql .= "call_flow_uuid, ";
				$sql .= "call_flow_extension, ";
				$sql .= "call_flow_feature_code, ";
				$sql .= "call_flow_status, ";
				$sql .= "call_flow_app, ";
				$sql .= "call_flow_pin_number, ";
				$sql .= "call_flow_data, ";
				$sql .= "call_flow_anti_app, ";
				$sql .= "call_flow_anti_data, ";
				$sql .= "call_flow_description ";
				$sql .= ")";
				$sql .= "values ";
				$sql .= "(";
				$sql .= "'$domain_uuid', ";
				$sql .= "'".uuid()."', ";
				$sql .= "'$call_flow_extension', ";
				$sql .= "'$call_flow_feature_code', ";
				$sql .= "'$call_flow_status', ";
				$sql .= "'$call_flow_app', ";
				$sql .= "'$call_flow_pin_number', ";
				$sql .= "'$call_flow_data', ";
				$sql .= "'$call_flow_anti_app', ";
				$sql .= "'$call_flow_anti_data', ";
				$sql .= "'$call_flow_description' ";
				$sql .= ")";
				$db->exec(check_sql($sql));
				unset($sql);

				require_once "includes/header.php";
				echo "<meta http-equiv=\"refresh\" content=\"2;url=call_flows.php\">\n";
				echo "<div align='center'>\n";
				echo "Add Complete\n";
				echo "</div>\n";
				require_once "includes/footer.php";
				return;
			} //if ($action == "add")

			if ($action == "update" && permission_exists('call_flow_edit')) {
				$sql = "update v_call_flows set ";
				$sql .= "call_flow_extension = '$call_flow_extension', ";
				$sql .= "call_flow_feature_code = '$call_flow_feature_code', ";
				$sql .= "call_flow_status = '$call_flow_status', ";
				$sql .= "call_flow_app = '$call_flow_app', ";
				$sql .= "call_flow_pin_number = '$call_flow_pin_number', ";
				$sql .= "call_flow_data = '$call_flow_data', ";
				$sql .= "call_flow_anti_app = '$call_flow_anti_app', ";
				$sql .= "call_flow_anti_data = '$call_flow_anti_data', ";
				$sql .= "call_flow_description = '$call_flow_description' ";
				$sql .= "where domain_uuid = '$domain_uuid' ";
				$sql .= "and call_flow_uuid = '$call_flow_uuid'";
				$db->exec(check_sql($sql));
				unset($sql);

				require_once "includes/header.php";
				echo "<meta http-equiv=\"refresh\" content=\"2;url=call_flows.php\">\n";
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
		$call_flow_uuid = check_str($_GET["id"]);
		$sql = "select * from v_call_flows ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$sql .= "and call_flow_uuid = '$call_flow_uuid' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll();
		foreach ($result as &$row) {
			$call_flow_extension = $row["call_flow_extension"];
			$call_flow_feature_code = $row["call_flow_feature_code"];
			$call_flow_status = $row["call_flow_status"];
			$call_flow_app = $row["call_flow_app"];
			$call_flow_pin_number = $row["call_flow_pin_number"];
			$call_flow_data = $row["call_flow_data"];
			$call_flow_anti_app = $row["call_flow_anti_app"];
			$call_flow_anti_data = $row["call_flow_anti_data"];
			$call_flow_description = $row["call_flow_description"];
			break; //limit to 1 row
		}
		unset ($prep_statement);
	}

//show the header
	require_once "includes/header.php";

//show the content
	echo "<div align='center'>";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing=''>\n";
	echo "<tr class='border'>\n";
	echo "	<td align=\"left\">\n";
	echo "		<br>";

	echo "<form method='post' name='frm' action=''>\n";
	echo "<div align='center'>\n";
	echo "<table width='100%'  border='0' cellpadding='6' cellspacing='0'>\n";
	echo "<tr>\n";
		echo "<td align='left' width='30%' nowrap='nowrap'><b>Call Flow</b></td>\n";
	echo "<td width='70%' align='right'><input type='button' class='btn' name='' alt='back' onclick=\"window.location='call_flows.php'\" value='Back'></td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	Extension:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='call_flow_extension' maxlength='255' value=\"$call_flow_extension\">\n";
	echo "<br />\n";
	echo "\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	Feature Code:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='call_flow_feature_code' maxlength='255' value=\"$call_flow_feature_code\">\n";
	echo "<br />\n";
	echo "\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	Status:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='call_flow_status'>\n";
	echo "	<option value=''></option>\n";
	if ($call_flow_status == "true") { 
		echo "	<option value='true' SELECTED >on</option>\n";
	}
	else {
		echo "	<option value='true'>on</option>\n";
	}
	if ($call_flow_status == "false") { 
		echo "	<option value='false' SELECTED >off</option>\n";
	}
	else {
		echo "	<option value='false'>off</option>\n";
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo "\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	Application:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='call_flow_app' maxlength='255' value=\"$call_flow_app\">\n";
	echo "<br />\n";
	echo "\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	PIN Number:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='call_flow_pin_number' maxlength='255' value=\"$call_flow_pin_number\">\n";
	echo "<br />\n";
	echo "\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	Application Data:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='call_flow_data' maxlength='255' value=\"$call_flow_data\">\n";
	echo "<br />\n";
	echo "\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	Alternate  Application:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='call_flow_anti_app' maxlength='255' value=\"$call_flow_anti_app\">\n";
	echo "<br />\n";
	echo "\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	Application Data:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='call_flow_anti_data' maxlength='255' value=\"$call_flow_anti_data\">\n";
	echo "<br />\n";
	echo "\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	Description:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='call_flow_description' maxlength='255' value=\"$call_flow_description\">\n";
	echo "<br />\n";
	echo "\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	if ($action == "update") {
		echo "				<input type='hidden' name='call_flow_uuid' value='$call_flow_uuid'>\n";
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

//include the footer
	require_once "includes/footer.php";
?>