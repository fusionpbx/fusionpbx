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
	if (permission_exists('contact_address_edit') || permission_exists('contact_address_add')) {
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
		$contact_address_uuid = $_REQUEST["id"];
	}
	else {
		$action = "add";
	}

//get the contact uuid
	if (is_uuid($_GET["contact_uuid"])) {
		$contact_uuid = $_GET["contact_uuid"];
	}

//get http post variables and set them to php variables
	if (count($_POST)>0) {
		$address_type = $_POST["address_type"];
		$address_label = $_POST["address_label"];
		$address_label_custom = $_POST["address_label_custom"];
		$address_street = $_POST["address_street"];
		$address_extended = $_POST["address_extended"];
		$address_community = $_POST["address_community"];
		$address_locality = $_POST["address_locality"];
		$address_region = $_POST["address_region"];
		$address_postal_code = $_POST["address_postal_code"];
		$address_country = $_POST["address_country"];
		$address_latitude = $_POST["address_latitude"];
		$address_longitude = $_POST["address_longitude"];
		$address_primary = $_POST["address_primary"];
		$address_description = $_POST["address_description"];

		//use custom label if set
		$address_label = $address_label_custom != '' ? $address_label_custom : $address_label;
	}

//process the form data
	if (count($_POST) > 0 && strlen($_POST["persistformvar"]) == 0) {

		//set the uuid
			if ($action == "update") {
				$contact_address_uuid = $_POST["contact_address_uuid"];
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

				//if primary, unmark other primary addresses
					if ($email_primary) {
						$sql = "update v_contact_addresses set address_primary = 0 ";
						$sql .= "where domain_uuid = :domain_uuid ";
						$sql .= "and contact_uuid = :contact_uuid ";
						$parameters['domain_uuid'] = $domain_uuid;
						$parameters['contact_uuid'] = $contact_uuid;
						$database = new database;
						$database->execute($sql, $parameters);
						unset($sql, $parameters);
					}

				if ($action == "add" && permission_exists('contact_address_add')) {
					$contact_address_uuid = uuid();
					$array['contact_addresses'][0]['contact_address_uuid'] = $contact_address_uuid;

					message::add($text['message-add']);
				}

				if ($action == "update" && permission_exists('contact_address_edit')) {
					$array['contact_addresses'][0]['contact_address_uuid'] = $contact_address_uuid;

					message::add($text['message-update']);
				}

				if (is_array($array) && @sizeof($array) != 0) {
					$array['contact_addresses'][0]['domain_uuid'] = $_SESSION['domain_uuid'];
					$array['contact_addresses'][0]['contact_uuid'] = $contact_uuid;
					$array['contact_addresses'][0]['address_type'] = $address_type;
					$array['contact_addresses'][0]['address_label'] = $address_label;
					$array['contact_addresses'][0]['address_street'] = $address_street;
					$array['contact_addresses'][0]['address_extended'] = $address_extended;
					$array['contact_addresses'][0]['address_community'] = $address_community;
					$array['contact_addresses'][0]['address_locality'] = $address_locality;
					$array['contact_addresses'][0]['address_region'] = $address_region;
					$array['contact_addresses'][0]['address_postal_code'] = $address_postal_code;
					$array['contact_addresses'][0]['address_country'] = $address_country;
					$array['contact_addresses'][0]['address_latitude'] = $address_latitude;
					$array['contact_addresses'][0]['address_longitude'] = $address_longitude;
					$array['contact_addresses'][0]['address_primary'] = $address_primary ? 1 : 0;
					$array['contact_addresses'][0]['address_description'] = $address_description;

					$database = new database;
					$database->app_name = 'contacts';
					$database->app_uuid = '04481e0e-a478-c559-adad-52bd4174574c';
					$database->save($array);
					unset($array);
				}

				header("Location: contact_edit.php?id=".$contact_uuid);
				exit;

			}
	}

//pre-populate the form
	if (count($_GET)>0 && $_POST["persistformvar"] != "true") {
		$contact_address_uuid = $_GET["id"];
		$sql = "select * from v_contact_addresses ";
		$sql .= "where domain_uuid = :domain_uuid ";
		$sql .= "and contact_address_uuid = :contact_address_uuid ";
		$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
		$parameters['contact_address_uuid'] = $contact_address_uuid;
		$database = new database;
		$row = $database->select($sql, $parameters, 'row');
		if (is_array($row) && @sizeof($row) != 0) {
			$address_type = $row["address_type"];
			$address_label = $row["address_label"];
			$address_street = $row["address_street"];
			$address_extended = $row["address_extended"];
			$address_community = $row["address_community"];
			$address_locality = $row["address_locality"];
			$address_region = $row["address_region"];
			$address_postal_code = $row["address_postal_code"];
			$address_country = $row["address_country"];
			$address_latitude = $row["address_latitude"];
			$address_longitude = $row["address_longitude"];
			$address_primary = $row["address_primary"];
			$address_description = $row["address_description"];
		}
		unset($sql, $parameters, $row);
	}

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//show the header
	if ($action == "update") {
		$document['title'] = $text['title-contact_addresses-edit'];
	}
	else if ($action == "add") {
		$document['title'] = $text['title-contact_addresses-add'];
	}
	require_once "resources/header.php";

//javascript to toggle input/select boxes
	echo "<script type='text/javascript'>";
	echo "	function toggle_custom(field) {";
	echo "		$('#'+field).toggle();";
	echo "		document.getElementById(field).selectedIndex = 0;";
	echo "		document.getElementById(field+'_custom').value = '';";
	echo "		$('#'+field+'_custom').toggle();";
	echo "		if ($('#'+field+'_custom').is(':visible')) { $('#'+field+'_custom').trigger('focus'); } else { $('#'+field).trigger('focus'); }";
	echo "	}";
	echo "</script>";

//show the content
	echo "<form method='post' name='frm' id='frm'>\n";

	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'>";
	if ($action == "update") {
		echo "<b>".$text['header-contact_addresses-edit']."</b>";
	}
	else if ($action == "add") {
		echo "<b>".$text['header-contact_addresses-add']."</b>";
	}
	echo "	</div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$_SESSION['theme']['button_icon_back'],'id'=>'btn_back','style'=>'margin-right: 15px;','link'=>'contact_edit.php?id='.urlencode($contact_uuid)]);
	echo button::create(['type'=>'submit','label'=>$text['button-save'],'icon'=>$_SESSION['theme']['button_icon_save'],'id'=>'btn_save']);
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	if ($action == "update") {
		echo $text['description-contact_addresses-edit'];
	}
	else if ($action == "add") {
		echo $text['description-contact_addresses-add'];
	}
	echo "<br /><br />\n";

	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";

	echo "<tr>\n";
	echo "<td width='30%' class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-address_label']."\n";
	echo "</td>\n";
	echo "<td width='70%' class='vtable' align='left'>\n";
	if (is_array($_SESSION["contact"]["address_label"])) {
		sort($_SESSION["contact"]["address_label"]);
		foreach($_SESSION["contact"]["address_label"] as $row) {
			$address_label_options[] = "<option value='".$row."' ".(($row == $address_label) ? "selected='selected'" : null).">".$row."</option>";
		}
		$address_label_found = (in_array($address_label, $_SESSION["contact"]["address_label"])) ? true : false;
	}
	else {
		$selected[$address_label] = "selected";
		$default_labels[] = $text['option-work'];
		$default_labels[] = $text['option-home'];
		$default_labels[] = $text['option-mailing'];
		$default_labels[] = $text['option-physical'];
		$default_labels[] = $text['option-shipping'];
		$default_labels[] = $text['option-billing'];
		$default_labels[] = $text['option-other'];
		foreach ($default_labels as $default_label) {
			$address_label_options[] = "<option value='".$default_label."' ".$selected[$default_label].">".$default_label."</option>";
		}
		$address_label_found = (in_array($address_label, $default_labels)) ? true : false;
	}
	echo "	<select class='formfld' ".((!$address_label_found && $address_label != '') ? "style='display: none;'" : null)." name='address_label' id='address_label' onchange=\"getElementById('address_label_custom').value='';\">\n";
	echo "		<option value=''></option>\n";
	echo 		(is_array($address_label_options)) ? implode("\n", $address_label_options) : null;
	echo "	</select>\n";
	echo "	<input type='text' class='formfld' ".(($address_label_found || $address_label == '') ? "style='display: none;'" : null)." name='address_label_custom' id='address_label_custom' value=\"".((!$address_label_found) ? htmlentities($address_label) : null)."\">\n";
	echo "	<input type='button' id='btn_toggle_label' class='btn' alt='".$text['button-back']."' value='&#9665;' onclick=\"toggle_custom('address_label');\">\n";
	echo "<br />\n";
	echo $text['description-address_label']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-address_type']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='address_type' id='address_type'>\n";
	echo "		<option value=''></option>\n";
	$vcard_address_types = array(
		'work' => $text['option-work'],
		'home' => $text['option-home'],
		'dom' => $text['option-dom'],
		'intl' => $text['option-intl'],
		'postal' => $text['option-postal'],
		'parcel' => $text['option-parcel'],
		'pref' => $text['option-pref']
		);
	foreach ($vcard_address_types as $vcard_address_type_value => $vcard_address_type_label) {
		echo "	<option value='".$vcard_address_type_value."' ".(($address_type == $vcard_address_type_value) ? "selected" : null).">".$vcard_address_type_label."</option>\n";
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-address_type']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-address_address']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<textarea class='formfld' name='address_street' style='margin-bottom: 3px;'>".$address_street."</textarea><br>\n";
	echo "	<input class='formfld' type='text' name='address_extended' maxlength='255' value=\"".escape($address_extended)."\">\n";
	echo "<br />\n";
	echo $text['description-address_address']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-address_community']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='address_community' maxlength='255' value=\"".escape($address_community)."\">\n";
	echo "<br />\n";
	echo $text['description-address_community']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-address_locality']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='address_locality' maxlength='255' value=\"".escape($address_locality)."\">\n";
	echo "<br />\n";
	echo $text['description-address_locality']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-address_region']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='address_region' maxlength='255' value=\"".escape($address_region)."\">\n";
	echo "<br />\n";
	echo $text['description-address_region']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-address_postal_code']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='address_postal_code' maxlength='255' value=\"".escape($address_postal_code)."\">\n";
	echo "<br />\n";
	echo $text['description-address_postal_code']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-address_country']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='address_country' maxlength='255' value=\"".escape($address_country)."\">\n";
	echo "<br />\n";
	echo $text['description-address_country']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-address_latitude']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='number' name='address_latitude' maxlength='255' min='-90' max='90' step='0.0000001' value=\"".escape($address_latitude)."\">\n";
	echo "<br />\n";
	echo $text['description-address_latitude']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-address_longitude']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='number' name='address_longitude' maxlength='255' min='-180' max='180' step='0.0000001' value=\"".escape($address_longitude)."\">\n";
	echo "<br />\n";
	echo $text['description-address_longitude']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-primary']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='address_primary' id='address_primary'>\n";
	echo "		<option value='0'>".$text['option-false']."</option>\n";
	echo "		<option value='1' ".(($address_primary) ? "selected" : null).">".$text['option-true']."</option>\n";
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-address_primary']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-address_description']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='address_description' maxlength='255' value=\"".escape($address_description)."\">\n";
	echo "<br />\n";
	echo $text['description-address_description']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "</table>";
	echo "<br><br>";

	echo "<input type='hidden' name='contact_uuid' value='".escape($contact_uuid)."'>\n";
	if ($action == "update") {
		echo "<input type='hidden' name='contact_address_uuid' value='".escape($contact_address_uuid)."'>\n";
	}
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";

	echo "</form>";

//include the footer
	require_once "resources/footer.php";

?>