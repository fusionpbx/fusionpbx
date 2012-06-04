<?php
require_once "root.php";
require_once "includes/require.php";
require_once "includes/checkauth.php";
if (if_group("admin") || if_group("superadmin")) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//action add or update
	if (isset($_REQUEST["id"])) {
		$action = "update";
		$destination_uuid = check_str($_REQUEST["id"]);
	}
	else {
		$action = "add";
	}

//get http post variables and set them to php variables
	if (count($_POST)>0) {
		$destination_name = check_str($_POST["destination_name"]);
		$destination_context = check_str($_POST["destination_context"]);
		$destination_extension = check_str($_POST["destination_extension"]);
		$destination_enabled = check_str($_POST["destination_enabled"]);
		$destination_description = check_str($_POST["destination_description"]);
	}

if (count($_POST)>0 && strlen($_POST["persistformvar"]) == 0) {

	$msg = '';
	if ($action == "update") {
		$destination_uuid = check_str($_POST["destination_uuid"]);
	}

	//check for all required data
		//if (strlen($destination_name) == 0) { $msg .= "Please provide: Name<br>\n"; }
		//if (strlen($destination_context) == 0) { $msg .= "Please provide: Context<br>\n"; }
		//if (strlen($destination_extension) == 0) { $msg .= "Please provide: Extension<br>\n"; }
		//if (strlen($destination_enabled) == 0) { $msg .= "Please provide: Enabled<br>\n"; }
		//if (strlen($destination_description) == 0) { $msg .= "Please provide: Description<br>\n"; }
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
			if ($action == "add") {
				$sql = "insert into v_destinations ";
				$sql .= "(";
				$sql .= "domain_uuid, ";
				$sql .= "destination_uuid, ";
				$sql .= "destination_name, ";
				$sql .= "destination_context, ";
				$sql .= "destination_extension, ";
				$sql .= "destination_enabled, ";
				$sql .= "destination_description ";
				$sql .= ")";
				$sql .= "values ";
				$sql .= "(";
				$sql .= "'$domain_uuid', ";
				$sql .= "'".uuid()."', ";
				$sql .= "'$destination_name', ";
				$sql .= "'$destination_context', ";
				$sql .= "'$destination_extension', ";
				$sql .= "'$destination_enabled', ";
				$sql .= "'$destination_description' ";
				$sql .= ")";
				$db->exec(check_sql($sql));
				unset($sql);

				require_once "includes/header.php";
				echo "<meta http-equiv=\"refresh\" content=\"2;url=destinations.php\">\n";
				echo "<div align='center'>\n";
				echo "Add Complete\n";
				echo "</div>\n";
				require_once "includes/footer.php";
				return;
			} //if ($action == "add")

			if ($action == "update") {
				$sql = "update v_destinations set ";
				$sql .= "destination_name = '$destination_name', ";
				$sql .= "destination_context = '$destination_context', ";
				$sql .= "destination_extension = '$destination_extension', ";
				$sql .= "destination_enabled = '$destination_enabled', ";
				$sql .= "destination_description = '$destination_description' ";
				$sql .= "where domain_uuid = '$domain_uuid' ";
				$sql .= "and destination_uuid = '$destination_uuid'";
				$db->exec(check_sql($sql));
				unset($sql);

				require_once "includes/header.php";
				echo "<meta http-equiv=\"refresh\" content=\"2;url=destinations.php\">\n";
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
		$destination_uuid = $_GET["id"];
		$sql = "select * from v_destinations ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$sql .= "and destination_uuid = '$destination_uuid' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll();
		foreach ($result as &$row) {
			$destination_name = $row["destination_name"];
			$destination_context = $row["destination_context"];
			$destination_extension = $row["destination_extension"];
			$destination_enabled = $row["destination_enabled"];
			$destination_description = $row["destination_description"];
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
	echo "	  <br>";

	echo "<form method='post' name='frm' action=''>\n";
	echo "<div align='center'>\n";
	echo "<table width='100%'  border='0' cellpadding='6' cellspacing='0'>\n";
	echo "<tr>\n";
	if ($action == "add") {
		echo "<td align='left' width='30%' nowrap='nowrap'><b>Destination Add</b></td>\n";
	}
	if ($action == "update") {
		echo "<td align='left' width='30%' nowrap='nowrap'><b>Destination Edit</b></td>\n";
	}
	echo "<td width='70%' align='right'><input type='button' class='btn' name='' alt='back' onclick=\"window.location='destinations.php'\" value='Back'></td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td align='left' colspan='2'>\n";
	echo "An alias for a call destination. The destination will use the dialplan to find it its target.<br /><br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	Name:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='destination_name' maxlength='255' value=\"$destination_name\">\n";
	echo "<br />\n";
	echo "Enter the name.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	Context:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='destination_context' maxlength='255' value=\"$destination_context\">\n";
	echo "<br />\n";
	echo "Enter the context.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	Extension:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='destination_extension' maxlength='255' value=\"$destination_extension\">\n";
	echo "<br />\n";
	echo "Enter the extension.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	Enabled:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='destination_enabled'>\n";
	echo "	<option value=''></option>\n";
	if ($destination_enabled == "true") { 
		echo "	<option value='true' SELECTED >true</option>\n";
	}
	else {
		echo "	<option value='true'>true</option>\n";
	}
	if ($destination_enabled == "false") { 
		echo "	<option value='false' SELECTED >false</option>\n";
	}
	else {
		echo "	<option value='false'>false</option>\n";
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo "\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	Description:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='destination_description' maxlength='255' value=\"$destination_description\">\n";
	echo "<br />\n";
	echo "Enter the description.\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	if ($action == "update") {
		echo "				<input type='hidden' name='destination_uuid' value='$destination_uuid'>\n";
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