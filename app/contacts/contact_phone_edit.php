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
	Portions created by the Initial Developer are Copyright (C) 2008-2018
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	Luis Daniel Lucio Quiroz <dlucio@okay.com.mx>
*/

//includes
	require_once "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";
	
//check permissions
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
	if (is_uuid($_REQUEST["id"])) {
		$action = "update";
		$contact_phone_uuid = $_REQUEST["id"];
	}
	else {
		$action = "add";
	}

//get the uuid
	if (is_uuid($_GET["contact_uuid"])) {
		$contact_uuid = $_GET["contact_uuid"];
	}

//get http post variables and set them to php variables
	if (is_array($_POST) && @sizeof($_POST) != 0) {
		$phone_type_voice = $_POST["phone_type_voice"];
		$phone_type_fax = $_POST["phone_type_fax"];
		$phone_type_video = $_POST["phone_type_video"];
		$phone_type_text = $_POST["phone_type_text"];
		$phone_label = $_POST["phone_label"];
		$phone_label_custom = $_POST["phone_label_custom"];
		$phone_speed_dial = $_POST["phone_speed_dial"];
		$phone_number = $_POST["phone_number"];
		$phone_extension = $_POST["phone_extension"];
		$phone_primary = $_POST["phone_primary"];
		$phone_description = $_POST["phone_description"];

		//remove any phone number formatting
		$phone_number = preg_replace('{(?!^\+)[\D]}', '', $phone_number);

		//use custom label if set
		$phone_label = ($phone_label_custom != '') ? $phone_label_custom : $phone_label;
	}

//process the form data
	if (is_array($_POST) && @sizeof($_POST) != 0 && strlen($_POST["persistformvar"]) == 0) {

		//set thge uuid
			if ($action == "update") {
				$contact_phone_uuid = $_POST["contact_phone_uuid"];
			}

		//validate the token
			$token = new token;
			if (!$token->validate($_SERVER['PHP_SELF'])) {
				message::add($text['message-invalid_token'],'negative');
				header('Location: contacts.php');
				exit;
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
					$array['contacts'][0]['contact_uuid'] = $contact_uuid;
					$array['contacts'][0]['domain_uuid'] = $domain_uuid;
					$array['contacts'][0]['last_mod_date'] = 'now()';
					$array['contacts'][0]['last_mod_user'] = $_SESSION['username'];

					$p = new permissions;
					$p->add('contact_edit', 'temp');

					$database = new database;
					$database->app_name = 'contacts';
					$database->app_uuid = '04481e0e-a478-c559-adad-52bd4174574c';
					$database->save($array);
					unset($array);

					$p->delete('contact_edit', 'temp');

				//if primary, unmark other primary numbers
					if ($phone_primary) {
						$sql = "update v_contact_phones set phone_primary = 0 ";
						$sql .= "where domain_uuid = :domain_uuid ";
						$sql .= "and contact_uuid = :contact_uuid ";
						$parameters['domain_uuid'] = $domain_uuid;
						$parameters['contact_uuid'] = $contact_uuid;
						$database = new database;
						$database->execute($sql, $parameters);
						unset($sql, $parameters);
					}

				//add the phone
					if ($action == "add" && permission_exists('contact_phone_add')) {
						$contact_phone_uuid = uuid();
						$array['contact_phones'][0]['contact_phone_uuid'] = $contact_phone_uuid;

						message::add($text['message-add']);
					}

				//update the phone
					if ($action == "update" && permission_exists('contact_phone_edit')) {
						$array['contact_phones'][0]['contact_phone_uuid'] = $contact_phone_uuid;

						message::add($text['message-update']);
					}

				//execute
					if (is_array($array) && @sizeof($array) != 0) {
						$array['contact_phones'][0]['contact_uuid'] = $contact_uuid;
						$array['contact_phones'][0]['domain_uuid'] = $domain_uuid;
						$array['contact_phones'][0]['phone_type_voice'] = $phone_type_voice ? 1 : null;
						$array['contact_phones'][0]['phone_type_fax'] = $phone_type_fax ? 1 : null;
						$array['contact_phones'][0]['phone_type_video'] = $phone_type_video ? 1 : null;
						$array['contact_phones'][0]['phone_type_text'] = $phone_type_text ? 1 : null;
						$array['contact_phones'][0]['phone_label'] = $phone_label;
						$array['contact_phones'][0]['phone_speed_dial'] = $phone_speed_dial;
						$array['contact_phones'][0]['phone_number'] = $phone_number;
						$array['contact_phones'][0]['phone_extension'] = $phone_extension;
						$array['contact_phones'][0]['phone_primary'] = $phone_primary ? 1 : 0;
						$array['contact_phones'][0]['phone_description'] = $phone_description;

						$database = new database;
						$database->app_name = 'contacts';
						$database->app_uuid = '04481e0e-a478-c559-adad-52bd4174574c';
						$database->save($array);
						unset($array);
					}

				//redirect
					header("Location: contact_edit.php?id=".escape($contact_uuid));
					exit;

			}
	}

//pre-populate the form
	if (count($_GET)>0 && $_POST["persistformvar"] != "true") {
		$contact_phone_uuid = $_GET["id"];
		$sql = "select * from v_contact_phones ";
		$sql .= "where domain_uuid = :domain_uuid ";
		$sql .= "and contact_phone_uuid = :contact_phone_uuid ";
		$parameters['domain_uuid'] = $domain_uuid;
		$parameters['contact_phone_uuid'] = $contact_phone_uuid;
		$database = new database;
		$row = $database->select($sql, $parameters, 'row');
		if (is_array($row) && @sizeof($row) != 0) {
			$phone_type_voice = $row["phone_type_voice"];
			$phone_type_fax = $row["phone_type_fax"];
			$phone_type_video = $row["phone_type_video"];
			$phone_type_text = $row["phone_type_text"];
			$phone_label = $row["phone_label"];
			$phone_speed_dial = $row["phone_speed_dial"];
			$phone_number = $row["phone_number"];
			$phone_extension = $row["phone_extension"];
			$phone_primary = $row["phone_primary"];
			$phone_description = $row["phone_description"];
		}
		unset($sql, $parameters, $row);
	}

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//show the header
	if ($action == "update") {
		$document['title'] = $text['title-contact_phones-edit'];
	}
	else if ($action == "add") {
		$document['title'] = $text['title-contact_phones-add'];
	}
	require_once "resources/header.php";

//javascript to toggle input/select boxes
	echo "<script type='text/javascript'>\n";
	echo "	function toggle_custom(field) {\n";
	echo "		$('#'+field).toggle();\n";
	echo "		document.getElementById(field).selectedIndex = 0;\n";
	echo "		document.getElementById(field+'_custom').value = '';\n";
	echo "		$('#'+field+'_custom').toggle();\n";
	echo "		if ($('#'+field+'_custom').is(':visible')) { $('#'+field+'_custom').trigger('focus'); } else { $('#'+field).trigger('focus'); }\n";
	echo "	}";
	echo "</script>";

//show the content
	echo "<form method='post' name='frm' id='frm'>\n";

	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'>";
	if ($action == "update") {
		echo "<b>".$text['header-contact_phones-edit']."</b>";
	}
	else if ($action == "add") {
		echo "<b>".$text['header-contact_phones-add']."</b>";
	}
	echo "	</div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$_SESSION['theme']['button_icon_back'],'id'=>'btn_back','style'=>'margin-right: 15px;','link'=>'contact_edit.php?id='.urlencode($contact_uuid)]);
	echo button::create(['type'=>'submit','label'=>$text['button-save'],'icon'=>$_SESSION['theme']['button_icon_save'],'id'=>'btn_save']);
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

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
	echo "	".$text['label-phone_speed_dial']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='phone_speed_dial' maxlength='255' min='0' step='1' value=\"".escape($phone_speed_dial)."\">\n";
	echo "<br />\n";
	echo $text['description-phone_speed_dial']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-phone_number']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='phone_number' maxlength='255' min='0' step='1' value=\"".escape($phone_number)."\">\n";
	echo "<br />\n";
	echo $text['description-phone_number']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-phone_extension']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='number' name='phone_extension' min='0' step='1' maxlength='255' value=\"".escape($phone_extension)."\">\n";
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
	echo "	<input class='formfld' type='text' name='phone_description' maxlength='255' value=\"".escape($phone_description)."\">\n";
	echo "<br />\n";
	echo $text['description-phone_description']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "</table>";
	echo "<br><br>";

	echo "<input type='hidden' name='contact_uuid' value='".escape($contact_uuid)."'>\n";
	if ($action == "update") {
		echo "<input type='hidden' name='contact_phone_uuid' value='".escape($contact_phone_uuid)."'>\n";
	}
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";

	echo "</form>";

//include the footer
	require_once "resources/footer.php";

?>