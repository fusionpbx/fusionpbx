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
if (permission_exists('contact_phone_edit') || permission_exists('contact_phone_add')) {
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
		$contact_phone_uuid = check_str($_REQUEST["id"]);
	}
	else {
		$action = "add";
	}

if (strlen($_GET["contact_uuid"]) > 0) {
	$contact_uuid = check_str($_GET["contact_uuid"]);
}

//get http post variables and set them to php variables
	if (count($_POST)>0) {
		$phone_type_voice = check_str($_POST["phone_type_voice"]);
		$phone_type_fax = check_str($_POST["phone_type_fax"]);
		$phone_type_video = check_str($_POST["phone_type_video"]);
		$phone_type_text = check_str($_POST["phone_type_text"]);
		$phone_label = check_str($_POST["phone_label"]);
		$phone_label_custom = check_str($_POST["phone_label_custom"]);
		$phone_number = check_str($_POST["phone_number"]);
		$phone_extension = check_str($_POST["phone_extension"]);
		$phone_primary = check_str($_POST["phone_primary"]);
		$phone_description = check_str($_POST["phone_description"]);

		//remove any phone number formatting
		$phone_number = preg_replace('{\D}', '', $phone_number);

		//use custom label if set
		$phone_label = ($phone_label_custom != '') ? $phone_label_custom : $phone_label;
	}

//process the form data
	if (count($_POST) > 0 && strlen($_POST["persistformvar"]) == 0) {

		//set thge uuid
			if ($action == "update") {
				$contact_phone_uuid = check_str($_POST["contact_phone_uuid"]);
			}

		//check for all required data
			$msg = '';
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

				//update last modified
				$sql = "update v_contacts set ";
				$sql .= "last_mod_date = now(), ";
				$sql .= "last_mod_user = '".$_SESSION['username']."' ";
				$sql .= "where domain_uuid = '".$domain_uuid."' ";
				$sql .= "and contact_uuid = '".$contact_uuid."' ";
				$db->exec(check_sql($sql));
				unset($sql);

				//if primary, unmark other primary numbers
				if ($phone_primary) {
					$sql = "update v_contact_phones set phone_primary = 0 ";
					$sql .= "where domain_uuid = '".$domain_uuid."' ";
					$sql .= "and contact_uuid = '".$contact_uuid."' ";
					$db->exec(check_sql($sql));
					unset($sql);
				}

				if ($action == "add") {
					$contact_phone_uuid = uuid();
					$sql = "insert into v_contact_phones ";
					$sql .= "(";
					$sql .= "domain_uuid, ";
					$sql .= "contact_uuid, ";
					$sql .= "contact_phone_uuid, ";
					$sql .= "phone_type_voice, ";
					$sql .= "phone_type_fax, ";
					$sql .= "phone_type_video, ";
					$sql .= "phone_type_text, ";
					$sql .= "phone_label, ";
					$sql .= "phone_number, ";
					$sql .= "phone_extension, ";
					$sql .= "phone_primary, ";
					$sql .= "phone_description ";
					$sql .= ")";
					$sql .= "values ";
					$sql .= "(";
					$sql .= "'".$domain_uuid."', ";
					$sql .= "'".$contact_uuid."', ";
					$sql .= "'".$contact_phone_uuid."', ";
					$sql .= (($phone_type_voice) ? 1 : 'null').", ";
					$sql .= (($phone_type_fax) ? 1 : 'null').", ";
					$sql .= (($phone_type_video) ? 1 : 'null').", ";
					$sql .= (($phone_type_text) ? 1 : 'null').", ";
					$sql .= "'".$phone_label."', ";
					$sql .= "'".$phone_number."', ";
					$sql .= "'".$phone_extension."', ";
					$sql .= (($phone_primary) ? 1 : 0).", ";
					$sql .= "'".$phone_description."' ";
					$sql .= ")";
					$db->exec(check_sql($sql));
					unset($sql);

					$_SESSION["message"] = $text['message-add'];
					header("Location: contact_edit.php?id=".$contact_uuid);
					return;
				} //if ($action == "add")

				if ($action == "update") {
					$sql = "update v_contact_phones set ";
					$sql .= "contact_uuid = '$contact_uuid', ";
					$sql .= "phone_type_voice = ".(($phone_type_voice) ? 1 : 'null').", ";
					$sql .= "phone_type_fax = ".(($phone_type_fax) ? 1 : 'null').", ";
					$sql .= "phone_type_video = ".(($phone_type_video) ? 1 : 'null').", ";
					$sql .= "phone_type_text = ".(($phone_type_text) ? 1 : 'null').", ";
					$sql .= "phone_label = '".$phone_label."', ";
					$sql .= "phone_number = '".$phone_number."', ";
					$sql .= "phone_extension = '".$phone_extension."', ";
					$sql .= "phone_primary = ".(($phone_primary) ? 1 : 0).", ";
					$sql .= "phone_description = '".$phone_description."' ";
					$sql .= "where domain_uuid = '".$domain_uuid."' ";
					$sql .= "and contact_phone_uuid = '".$contact_phone_uuid."'";
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
		$contact_phone_uuid = $_GET["id"];
		$sql = "select * from v_contact_phones ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$sql .= "and contact_phone_uuid = '$contact_phone_uuid' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		foreach ($result as &$row) {
			$phone_type_voice = $row["phone_type_voice"];
			$phone_type_fax = $row["phone_type_fax"];
			$phone_type_video = $row["phone_type_video"];
			$phone_type_text = $row["phone_type_text"];
			$phone_label = $row["phone_label"];
			$phone_number = $row["phone_number"];
			$phone_extension = $row["phone_extension"];
			$phone_primary = $row["phone_primary"];
			$phone_description = $row["phone_description"];
		}
		unset ($prep_statement);
	}

//show the header
	require_once "resources/header.php";
	if ($action == "update") {
		$document['title'] = $text['title-contact_phones-edit'];
	}
	else if ($action == "add") {
		$document['title'] = $text['title-contact_phones-add'];
	}

//javascript to toggle input/select boxes
	echo "<script type='text/javascript'>\n";
	echo "	function toggle_custom(field) {\n";
	echo "		$('#'+field).toggle();\n";
	echo "		document.getElementById(field).selectedIndex = 0;\n";
	echo "		document.getElementById(field+'_custom').value = '';\n";
	echo "		$('#'+field+'_custom').toggle();\n";
	echo "		if ($('#'+field+'_custom').is(':visible')) { $('#'+field+'_custom').focus(); } else { $('#'+field).focus(); }\n";
	echo "	}";
	echo "</script>";

//show the content
	echo "<form method='post' name='frm' action=''>\n";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "<td align='left' valign='top' nowrap='nowrap'><b>";
	if ($action == "update") {
		echo $text['header-contact_phones-edit'];
	}
	else if ($action == "add") {
		echo $text['header-contact_phones-add'];
	}
	echo "</b></td>\n";
	echo "<td align='right' valign='top'>";
	echo "	<input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"window.location='contact_edit.php?id=$contact_uuid'\" value='".$text['button-back']."'>";
	echo "	<input type='submit' name='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "</table>\n";
	echo "<br>\n";

	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "<td width='30%' class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-phone_label']."\n";
	echo "</td>\n";
	echo "<td width='70%' class='vtable' align='left'>\n";
	if (is_array($_SESSION["contact"]["phone_label"])) {
		sort($_SESSION["contact"]["phone_label"]);
		foreach($_SESSION["contact"]["phone_label"] as $row) {
			$phone_label_options[] = "<option value='".$row."' ".(($row == $phone_label) ? "selected='selected'" : null).">".$row."</option>";
		}
		$phone_label_found = (in_array($phone_label, $_SESSION["contact"]["phone_label"])) ? true : false;
	}
	else {
		$selected[$phone_label] = "selected";
		$default_labels[] = $text['option-work'];
		$default_labels[] = $text['option-home'];
		$default_labels[] = $text['option-mobile'];
		$default_labels[] = $text['option-main'];
		$default_labels[] = $text['option-fax'];
		$default_labels[] = $text['option-pager'];
		$default_labels[] = $text['option-voicemail'];
		$default_labels[] = $text['option-text'];
		$default_labels[] = $text['option-other'];
		foreach ($default_labels as $default_label) {
			$phone_label_options[] = "<option value='".$default_label."' ".$selected[$default_label].">".$default_label."</option>";
		}
		$phone_label_found = (in_array($phone_label, $default_labels)) ? true : false;
	}
	echo "	<select class='formfld' ".((!$phone_label_found && $phone_label != '') ? "style='display: none;'" : null)." name='phone_label' id='phone_label' onchange=\"getElementById('phone_label_custom').value='';\">\n";
	echo "		<option value=''></option>\n";
	echo 		(is_array($phone_label_options)) ? implode("\n", $phone_label_options) : null;
	echo "	</select>\n";
	echo "	<input type='text' class='formfld' ".(($phone_label_found || $phone_label == '') ? "style='display: none;'" : null)." name='phone_label_custom' id='phone_label_custom' value=\"".((!$phone_label_found) ? htmlentities($phone_label) : null)."\">\n";
	echo "	<input type='button' id='btn_toggle_type' class='btn' alt='".$text['button-back']."' value='&#9665;' onclick=\"toggle_custom('phone_label');\">\n";
	echo "<br />\n";
	echo $text['description-phone_label']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-phone_type']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<label><input type='checkbox' name='phone_type_voice' id='phone_type_voice' value='1' ".(($phone_type_voice) ? "checked='checked'" : null)."> ".$text['label-voice']."</label>&nbsp;\n";
	echo "	<label><input type='checkbox' name='phone_type_fax' id='phone_type_fax' value='1' ".(($phone_type_fax) ? "checked='checked'" : null)."> ".$text['label-fax']."</label>&nbsp;\n";
	echo "	<label><input type='checkbox' name='phone_type_video' id='phone_type_video' value='1' ".(($phone_type_video) ? "checked='checked'" : null)."> ".$text['label-video']."</label>&nbsp;\n";
	echo "	<label><input type='checkbox' name='phone_type_text' id='phone_type_text' value='1' ".(($phone_type_text) ? "checked='checked'" : null)."> ".$text['label-text']."</label>\n";
	echo "<br />\n";
	echo $text['description-phone_type']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-phone_number']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='phone_number' maxlength='255' min='0' step='1' value=\"$phone_number\">\n";
	echo "<br />\n";
	echo $text['description-phone_number']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-phone_extension']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='number' name='phone_extension' min='0' step='1' maxlength='255' value=\"$phone_extension\">\n";
	echo "<br />\n";
	echo $text['description-phone_extension']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-primary']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='phone_primary' id='phone_primary'>\n";
	echo "		<option value='0'>".$text['option-false']."</option>\n";
	echo "		<option value='1' ".(($phone_primary) ? "selected" : null).">".$text['option-true']."</option>\n";
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-phone_primary']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-phone_description']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='phone_description' maxlength='255' value=\"$phone_description\">\n";
	echo "<br />\n";
	echo $text['description-phone_description']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	echo "			<br>\n";
	echo "			<input type='hidden' name='contact_uuid' value='$contact_uuid'>\n";
	if ($action == "update") {
		echo "		<input type='hidden' name='contact_phone_uuid' value='$contact_phone_uuid'>\n";
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
