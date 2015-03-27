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
	Luis Daniel Lucio Quiroz <dlucio@okay.com.mx>
*/
require_once "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists('contact_url_edit') || permission_exists('contact_url_add')) {
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
		$contact_url_uuid = check_str($_REQUEST["id"]);
	}
	else {
		$action = "add";
	}

if (strlen($_GET["contact_uuid"]) > 0) {
	$contact_uuid = check_str($_GET["contact_uuid"]);
}

//get http post variables and set them to php variables
	if (count($_POST)>0) {
		$url_label = check_str($_POST["url_label"]);
		$url_label_custom = check_str($_POST["url_label_custom"]);
		$url_address = check_str($_POST["url_address"]);
		$url_primary = check_str($_POST["url_primary"]);
		$url_description = check_str($_POST["url_description"]);

		//use custom label if set
		$url_label = ($url_label_custom != '') ? $url_label_custom : $url_label;
	}

if (count($_POST)>0 && strlen($_POST["persistformvar"]) == 0) {

	$msg = '';
	if ($action == "update") {
		$contact_url_uuid = check_str($_POST["contact_url_uuid"]);
	}

	//check for all required data
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

		//if primary, unmark other primary numbers
		if ($url_primary) {
			$sql = "update v_contact_urls set url_primary = 0 ";
			$sql .= "where domain_uuid = '".$domain_uuid."' ";
			$sql .= "and contact_uuid = '".$contact_uuid."' ";
			$db->exec(check_sql($sql));
			unset($sql);
		}

		if ($action == "add") {
			$contact_url_uuid = uuid();
			$sql = "insert into v_contact_urls ";
			$sql .= "(";
			$sql .= "domain_uuid, ";
			$sql .= "contact_uuid, ";
			$sql .= "contact_url_uuid, ";
			$sql .= "url_label, ";
			$sql .= "url_address, ";
			$sql .= "url_primary, ";
			$sql .= "url_description ";
			$sql .= ")";
			$sql .= "values ";
			$sql .= "(";
			$sql .= "'".$_SESSION['domain_uuid']."', ";
			$sql .= "'".$contact_uuid."', ";
			$sql .= "'".$contact_url_uuid."', ";
			$sql .= "'".$url_label."', ";
			$sql .= "'".$url_address."', ";
			$sql .= (($url_primary) ? 1 : 0).", ";
			$sql .= "'".$url_description."' ";
			$sql .= ")";
			$db->exec(check_sql($sql));
			unset($sql);

			$_SESSION["message"] = $text['message-add'];
			header("Location: contact_edit.php?id=".$contact_uuid);
			return;
		} //if ($action == "add")

		if ($action == "update") {
			$sql = "update v_contact_urls set ";
			$sql .= "contact_uuid = '".$contact_uuid."', ";
			$sql .= "url_label = '".$url_label."', ";
			$sql .= "url_address = '".$url_address."', ";
			$sql .= "url_primary = ".(($url_primary) ? 1 : 0).", ";
			$sql .= "url_description = '".$url_description."' ";
			$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
			$sql .= "and contact_url_uuid = '".$contact_url_uuid."'";
			$db->exec(check_sql($sql));
			unset($sql);

			$_SESSION["message"] = $text['message-update'];
			header("Location: contact_edit.php?id=".$contact_uuid);
			return;
		} //if ($action == "update")
	} //if ($_POST["persistformvar"] != "true")
} //(count($_POST)>0 && strlen($_POST["persistformvar"]) == 0)

//pre-populate the form
	if (count($_GET)>0 && $_POST["persistformvar"] != "true") {
		$contact_url_uuid = $_GET["id"];
		$sql = "select * from v_contact_urls ";
		$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
		$sql .= "and contact_url_uuid = '".$contact_url_uuid."' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		foreach ($result as &$row) {
			$url_label = $row["url_label"];
			$url_address = $row["url_address"];
			$url_primary = $row["url_primary"];
			$url_description = $row["url_description"];
			break; //limit to 1 row
		}
		unset ($prep_statement);
	}

//show the header
	require_once "resources/header.php";
	if ($action == "update") {
		$document['title'] = $text['title-contact_url-edit'];
	}
	else if ($action == "add") {
		$document['title'] = $text['title-contact_url-add'];
	}

//javascript to toggle input/select boxes
	echo "<script type='text/javascript'>";
	echo "	function toggle_custom(field) {";
	echo "		$('#'+field).toggle();";
	echo "		document.getElementById(field).selectedIndex = 0;";
	echo "		document.getElementById(field+'_custom').value = '';";
	echo "		$('#'+field+'_custom').toggle();";
	echo "		if ($('#'+field+'_custom').is(':visible')) { $('#'+field+'_custom').focus(); } else { $('#'+field).focus(); }";
	echo "	}";
	echo "</script>";

//show the content
	echo "<form method='post' name='frm' action=''>\n";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "<td align='left' valign='top' nowrap='nowrap'><b>";
	if ($action == "update") {
		echo $text['header-contact_url-edit'];
	}
	else if ($action == "add") {
		echo $text['header-contact_url-add'];
	}
	echo "</b></td>\n";
	echo "<td align='right' valign='top'>";
	echo "	<input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"window.location='contact_edit.php?id=".$contact_uuid."'\" value='".$text['button-back']."'>";
	echo "	<input type='submit' name='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "</table>\n";

	if ($action == "update") {
		echo $text['description-contact_url-edit'];
	}
	else if ($action == "add") {
		echo $text['description-contact_url-add'];
	}
	echo "<br /><br />\n";

	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "<td width='30%' class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-url_label']."\n";
	echo "</td>\n";
	echo "<td width='70%' class='vtable' align='left'>\n";
	if (is_array($_SESSION["contact"]["url_label"])) {
		sort($_SESSION["contact"]["url_label"]);
		foreach($_SESSION["contact"]["url_label"] as $row) {
			$url_label_options[] = "<option value='".$row."' ".(($row == $url_label) ? "selected='selected'" : null).">".$row."</option>";
		}
		$url_label_found = (in_array($url_label, $_SESSION["contact"]["url_label"])) ? true : false;
	}
	else {
		$selected[$url_label] = "selected";
		$default_labels[] = $text['option-work'];
		$default_labels[] = $text['option-personal'];
		$default_labels[] = $text['option-other'];
		foreach ($default_labels as $default_label) {
			$url_label_options[] = "<option value='".$default_label."' ".$selected[$default_label].">".$default_label."</option>";
		}
		$url_label_found = (in_array($url_label, $default_labels)) ? true : false;
	}
	echo "	<select class='formfld' ".((!$url_label_found && $url_label != '') ? "style='display: none;'" : null)." name='url_label' id='url_label' onchange=\"getElementById('url_label_custom').value='';\">\n";
	echo "		<option value=''></option>\n";
	echo 		(is_array($url_label_options)) ? implode("\n", $url_label_options) : null;
	echo "	</select>\n";
	echo "	<input type='text' class='formfld' ".(($url_label_found || $url_label == '') ? "style='display: none;'" : null)." name='url_label_custom' id='url_label_custom' value=\"".((!$url_label_found) ? htmlentities($url_label) : null)."\">\n";
	echo "	<input type='button' id='btn_toggle_label' class='btn' alt='".$text['button-back']."' value='&#9665;' onclick=\"toggle_custom('url_label');\">\n";
	echo "<br />\n";
	echo $text['description-url_label']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-url_address']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='url' name='url_address' maxlength='255' value=\"".$url_address."\" placeholder='http://...'>\n";
	echo "<br />\n";
	echo $text['description-url_address']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-primary']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='url_primary' id='url_primary'>\n";
	echo "		<option value='0'>".$text['option-false']."</option>\n";
	echo "		<option value='1' ".(($url_primary) ? "selected" : null).">".$text['option-true']."</option>\n";
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-url_primary']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-url_description']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='url_description' maxlength='255' value=\"".$url_description."\">\n";
	echo "<br />\n";
	echo $text['description-url_description']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	echo "			<br>\n";
	echo "			<input type='hidden' name='contact_uuid' value='$contact_uuid'>\n";
	if ($action == "update") {
		echo "		<input type='hidden' name='contact_url_uuid' value='".$contact_url_uuid."'>\n";
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
