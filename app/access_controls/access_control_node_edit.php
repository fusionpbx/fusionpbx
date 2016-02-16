<?php
require_once "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists('access_control_node_add') || permission_exists('access_control_node_edit')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//action add or update
	if (isset($_REQUEST["id"])) {
		$action = "update";
		$access_control_node_uuid = check_str($_REQUEST["id"]);
	}
	else {
		$action = "add";
	}

//set the parent uuid
	if (strlen($_GET["access_control_uuid"]) > 0) {
		$access_control_uuid = check_str($_GET["access_control_uuid"]);
	}

//get http post variables and set them to php variables
	if (count($_POST)>0) {
		$node_type = check_str($_POST["node_type"]);
		$node_cidr = check_str($_POST["node_cidr"]);
		$node_domain = check_str($_POST["node_domain"]);
		$node_description = check_str($_POST["node_description"]);
	}

if (count($_POST)>0 && strlen($_POST["persistformvar"]) == 0) {

	$msg = '';
	if ($action == "update") {
		$access_control_node_uuid = check_str($_POST["access_control_node_uuid"]);
	}

	//check for all required data
		if (strlen($node_type) == 0) { $msg .= $text['message-required']." ".$text['label-node_type']."<br>\n"; }
		//if (strlen($node_cidr) == 0) { $msg .= $text['message-required']." ".$text['label-node_cidr']."<br>\n"; }
		//if (strlen($node_domain) == 0) { $msg .= $text['message-required']." ".$text['label-node_domain']."<br>\n"; }
		//if (strlen($node_description) == 0) { $msg .= $text['message-required']." ".$text['label-node_description']."<br>\n"; }
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
		if ($_POST["persistformvar"] != "true") {
			if ($action == "add" && permission_exists('access_control_node_add')) {
				$sql = "insert into v_access_control_nodes ";
				$sql .= "(";
				$sql .= "access_control_node_uuid, ";
				$sql .= "access_control_uuid, ";
				$sql .= "node_type, ";
				$sql .= "node_cidr, ";
				$sql .= "node_domain, ";
				$sql .= "node_description ";
				$sql .= ")";
				$sql .= "values ";
				$sql .= "(";
				$sql .= "'".uuid()."', ";
				$sql .= "'$access_control_uuid', ";
				$sql .= "'$node_type', ";
				$sql .= "'$node_cidr', ";
				$sql .= "'$node_domain', ";
				$sql .= "'$node_description' ";
				$sql .= ")";
				$db->exec(check_sql($sql));
				unset($sql);

				remove_config_from_cache('configuration:acl.conf');
				$_SESSION['message'] = $text['message-add'];
				header('Location: access_control_edit.php?id='.$access_control_uuid);
				return;

			} //if ($action == "add")

			if ($action == "update" && permission_exists('access_control_node_edit')) {
				$sql = "update v_access_control_nodes set ";
				$sql .= "access_control_uuid = '$access_control_uuid', ";
				$sql .= "node_type = '$node_type', ";
				$sql .= "node_cidr = '$node_cidr', ";
				$sql .= "node_domain = '$node_domain', ";
				$sql .= "node_description = '$node_description' ";
				$sql .= "where access_control_node_uuid = '$access_control_node_uuid'";
				$db->exec(check_sql($sql));
				unset($sql);

				remove_config_from_cache('configuration:acl.conf');
				$_SESSION['message'] = $text['message-update'];
				header('Location: access_control_edit.php?id='.$access_control_uuid);
				return;

			} //if ($action == "update")
		} //if ($_POST["persistformvar"] != "true") 
} //(count($_POST)>0 && strlen($_POST["persistformvar"]) == 0)

//pre-populate the form
	if (count($_GET)>0 && $_POST["persistformvar"] != "true") {
		$access_control_node_uuid = check_str($_GET["id"]);
		$sql = "select * from v_access_control_nodes ";
		$sql .= "where access_control_node_uuid = '$access_control_node_uuid' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		foreach ($result as &$row) {
			$node_type = $row["node_type"];
			$node_cidr = $row["node_cidr"];
			$node_domain = $row["node_domain"];
			$node_description = $row["node_description"];
			break; //limit to 1 row
		}
		unset ($prep_statement);
	}

//show the header
	require_once "resources/header.php";

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
	echo "<td align='left' width='30%' nowrap='nowrap'><b>".$text['title-access_control_node']."</b></td>\n";
	echo "<td width='70%' align='right'>\n";
	echo "	<input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"window.location='access_control_edit.php?id=$access_control_uuid'\" value='".$text['button-back']."'>";
	echo "	<input type='submit' name='submit' class='btn' value='".$text['button-save']."'>";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-node_type']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='node_type'>\n";
	echo "	<option value=''></option>\n";
	if ($node_type == "allow") { 
		echo "	<option value='allow' selected='selected'>".$text['label-allow']."</option>\n";
	}
	else {
		echo "	<option value='allow'>".$text['label-allow']."</option>\n";
	}
	if ($node_type == "deny") { 
		echo "	<option value='deny' selected='selected'>".$text['label-deny']."</option>\n";
	}
	else {
		echo "	<option value='deny'>".$text['label-deny']."</option>\n";
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-node_type']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-node_cidr']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='node_cidr' maxlength='255' value=\"$node_cidr\">\n";
	echo "<br />\n";
	echo $text['description-node_cidr']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-node_domain']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='node_domain' maxlength='255' value=\"$node_domain\">\n";
	echo "<br />\n";
	echo $text['description-node_domain']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-node_description']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='node_description' maxlength='255' value=\"$node_description\">\n";
	echo "<br />\n";
	echo $text['description-node_description']."\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	echo "				<input type='hidden' name='access_control_uuid' value='$access_control_uuid'>\n";
	if ($action == "update") {
		echo "				<input type='hidden' name='access_control_node_uuid' value='$access_control_node_uuid'>\n";
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