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
 Portions created by the Initial Developer are Copyright (C) 2008-2014
 the Initial Developer. All Rights Reserved.

 Contributor(s):
 Mark J Crane <markjcrane@fusionpbx.com>
 Luis Daniel Lucio Quiroz <dlucio@okay.com.mx>
*/
require_once "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists('contact_setting_edit') || permission_exists('contact_setting_add')) {
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
		$contact_setting_uuid = check_str($_REQUEST["id"]);
	}
	else {
		$action = "add";
	}

if (strlen($_GET["contact_uuid"]) > 0) {
	$contact_uuid = check_str($_GET["contact_uuid"]);
}
$domain_uuid = $_SESSION['domain_uuid'];

//get http post variables and set them to php variables
	if (count($_POST) > 0) {
		$contact_setting_category = strtolower(check_str($_POST["contact_setting_category"]));
		$contact_setting_subcategory = strtolower(check_str($_POST["contact_setting_subcategory"]));
		$contact_setting_name = strtolower(check_str($_POST["contact_setting_name"]));
		$contact_setting_value = check_str($_POST["contact_setting_value"]);
		$contact_setting_order = check_str($_POST["contact_setting_order"]);
		$contact_setting_enabled = strtolower(check_str($_POST["contact_setting_enabled"]));
		$contact_setting_description = check_str($_POST["contact_setting_description"]);
	}

if (count($_POST) > 0 && strlen($_POST["persistformvar"]) == 0) {

	$msg = '';
	if ($action == "update") {
		$contact_setting_uuid = check_str($_POST["contact_setting_uuid"]);
	}

	//check for all required data
		//if (strlen($domain_setting_category) == 0) { $msg .= $text['message-required'].$text['label-category']."<br>\n"; }
		//if (strlen($domain_setting_subcategory) == 0) { $msg .= $text['message-required'].$text['label-subcategory']."<br>\n"; }
		//if (strlen($domain_setting_name) == 0) { $msg .= $text['message-required'].$text['label-type']."<br>\n"; }
		//if (strlen($domain_setting_value) == 0) { $msg .= $text['message-required'].$text['label-value']."<br>\n"; }
		//if (strlen($domain_setting_order) == 0) { $msg .= $text['message-required'].$text['label-order']."<br>\n"; }
		//if (strlen($domain_setting_enabled) == 0) { $msg .= $text['message-required'].$text['label-enabled']."<br>\n"; }
		//if (strlen($domain_setting_description) == 0) { $msg .= $text['message-required'].$text['label-description']."<br>\n"; }
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
			$contact_setting_order = ($contact_setting_order != '') ? $contact_setting_order : 'null';

			//add the domain
				if ($action == "add" && permission_exists('domain_setting_add')) {
					$sql = "insert into v_contact_settings ";
					$sql .= "(";
					$sql .= "contact_setting_uuid, ";
					$sql .= "contact_uuid, ";
					$sql .= "domain_uuid, ";
					$sql .= "contact_setting_category, ";
					$sql .= "contact_setting_subcategory, ";
					$sql .= "contact_setting_name, ";
					$sql .= "contact_setting_value, ";
					$sql .= "contact_setting_order, ";
					$sql .= "contact_setting_enabled, ";
					$sql .= "contact_setting_description ";
					$sql .= ")";
					$sql .= "values ";
					$sql .= "(";
					$sql .= "'".uuid()."', ";
					$sql .= "'$contact_uuid', ";
					$sql .= "'$domain_uuid', ";
					$sql .= "'$contact_setting_category', ";
					$sql .= "'$contact_setting_subcategory', ";
					$sql .= "'$contact_setting_name', ";
					$sql .= "'$contact_setting_value', ";
					$sql .= "$contact_setting_order, ";
					$sql .= "'$contact_setting_enabled', ";
					$sql .= "'$contact_setting_description' ";
					$sql .= ")";
					$db->exec(check_sql($sql));
					unset($sql);
				} //if ($action == "add")

			//update the domain
				if ($action == "update") {
					$sql = "update v_contact_settings set ";
					$sql .= "contact_setting_category = '$contact_setting_category', ";
					$sql .= "contact_setting_subcategory = '$contact_setting_subcategory', ";
					$sql .= "contact_setting_name = '$contact_setting_name', ";
					$sql .= "contact_setting_value = '$contact_setting_value', ";
					$sql .= "contact_setting_order = $contact_setting_order, ";
					$sql .= "contact_setting_enabled = '$contact_setting_enabled', ";
					$sql .= "contact_setting_description = '$contact_setting_description' ";
					$sql .= "where contact_uuid = '$contact_uuid' ";
					$sql .= "and contact_setting_uuid = '$contact_setting_uuid'";
					$db->exec(check_sql($sql));
					unset($sql);
				} //if ($action == "update")

			//redirect the browser
				if ($action == "update") {
					$_SESSION["message"] = $text['message-update'];
				}
				if ($action == "add") {
					$_SESSION["message"] = $text['message-add'];
				}
				header("Location: contact_edit.php?id=".$contact_uuid);
				return;
		} //if ($_POST["persistformvar"] != "true")
} //(count($_POST)>0 && strlen($_POST["persistformvar"]) == 0)

//pre-populate the form
	if (count($_GET)>0 && $_POST["persistformvar"] != "true") {
		$contact_setting_uuid = check_str($_GET["id"]);
		$sql = "select * from v_contact_settings ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$sql .= "and contact_setting_uuid = '$contact_setting_uuid' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		foreach ($result as &$row) {
			$contact_setting_category = $row["contact_setting_category"];
			$contact_setting_subcategory = $row["contact_setting_subcategory"];
			$contact_setting_name = $row["contact_setting_name"];
			$contact_setting_value = $row["contact_setting_value"];
			$contact_setting_order = $row["contact_setting_order"];
			$contact_setting_enabled = $row["contact_setting_enabled"];
			$contact_setting_description = $row["contact_setting_description"];
			break; //limit to 1 row
		}
		unset ($prep_statement);
	}

//show the header
	require_once "resources/header.php";
	if ($action == "update") {
		$document['title'] = $text['title-contact_setting_edit'];
	}
	elseif ($action == "add") {
		$document['title'] = $text['title-contact_setting_add'];
	}

//show the content
	echo "<form method='post' name='frm' action=''>\n";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "<td valign='top' align='left' width='30%' nowrap='nowrap'><b>";
	if ($action == "update") {
		echo $text['header-contact_setting_edit'];
	}
	if ($action == "add") {
		echo $text['header-contact_setting_add'];
	}
	echo "</b></td>\n";
	echo "<td valign='top' width='70%' align='right'>";
	echo "	<input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"window.location='contact_edit.php?id=$contact_uuid'\" value='".$text['button-back']."'>";
	echo "	<input type='submit' name='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td align='left' colspan='2'>\n";
	if ($action == "update") {
		echo $text['description-contact_setting_edit'];
	}
	if ($action == "add") {
		echo $text['description-contact_setting_add'];
	}
	echo "<br /><br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-contact_setting_category']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='contact_setting_category' maxlength='255' value=\"".$contact_setting_category."\" required='required'>\n";
	echo "<br />\n";
	echo $text['description-contact_setting_category']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-contact_setting_subcategory']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='contact_setting_subcategory' maxlength='255' value=\"".$contact_setting_subcategory."\">\n";
	echo "<br />\n";
	echo $text['description-contact_setting_subcategory']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-contact_setting_type']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='contact_setting_name' maxlength='255' value=\"".$contact_setting_name."\">\n";
	echo "<br />\n";
	echo $text['description-contact_setting_type']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-contact_setting_value']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	$category = $row['contact_setting_category'];
	$subcategory = $row['contact_setting_subcategory'];
	$name = $row['contact_setting_name'];
	echo "	<input class='formfld' type='text' name='contact_setting_value' maxlength='255' value=\"".$row['contact_setting_value']."\">\n";
	echo "<br />\n";
	echo $text['description-contact_setting_value']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	if ($name == "array") {
		echo "<tr>\n";
		echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap' width='30%'>\n";
		echo "    ".$text['label-order']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<select name='contact_setting_order' class='formfld'>\n";
		$i=0;
		while($i<=999) {
			$selected = ($i == $contact_setting_order) ? "selected" : null;
			if (strlen($i) == 1) {
				echo "		<option value='00$i' ".$selected.">00$i</option>\n";
			}
			if (strlen($i) == 2) {
				echo "		<option value='0$i' ".$selected.">0$i</option>\n";
			}
			if (strlen($i) == 3) {
				echo "		<option value='$i' ".$selected.">$i</option>\n";
			}
			$i++;
		}
		echo "	</select>\n";
		echo "	<br />\n";
		echo $text['description-order']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-enabled']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <select class='formfld' name='contact_setting_enabled'>\n";
	if ($contact_setting_enabled == "true") {
		echo "    <option value='true' selected='selected'>".$text['label-true']."</option>\n";
	}
	else {
		echo "    <option value='true'>".$text['label-true']."</option>\n";
	}
	if ($contact_setting_enabled == "false") {
		echo "    <option value='false' selected='selected'>".$text['label-false']."</option>\n";
	}
	else {
		echo "    <option value='false'>".$text['label-false']."</option>\n";
	}
	echo "    </select>\n";
	echo "<br />\n";
	echo $text['description-enabled']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-description']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='contact_setting_description' maxlength='255' value=\"$contact_setting_description\">\n";
	echo "<br />\n";
	echo $text['description-description']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	echo "			<br>";
	echo "			<input type='hidden' name='contact_uuid' value='$contact_uuid'>\n";
	if ($action == "update") {
		echo "		<input type='hidden' name='contact_setting_uuid' value='$contact_setting_uuid'>\n";
	}
	echo "			<input type='submit' name='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "		</td>\n";
	echo "	</tr>";
	echo "</table>";
	echo "<br><br>";
	echo "</form>";

//include the footer
	require_once "resources/footer.php";
?>
