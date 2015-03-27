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
	if (isset($_REQUEST["id"])) {
		$action = "update";
		$contact_address_uuid = check_str($_REQUEST["id"]);
	}
	else {
		$action = "add";
	}

if (strlen($_GET["contact_uuid"]) > 0) {
	$contact_uuid = check_str($_GET["contact_uuid"]);
}

//get http post variables and set them to php variables
	if (count($_POST)>0) {
		$address_type = check_str($_POST["address_type"]);
		$address_label = check_str($_POST["address_label"]);
		$address_label_custom = check_str($_POST["address_label_custom"]);
		$address_street = check_str($_POST["address_street"]);
		$address_extended = check_str($_POST["address_extended"]);
		$address_community = check_str($_POST["address_community"]);
		$address_locality = check_str($_POST["address_locality"]);
		$address_region = check_str($_POST["address_region"]);
		$address_postal_code = check_str($_POST["address_postal_code"]);
		$address_country = check_str($_POST["address_country"]);
		$address_latitude = check_str($_POST["address_latitude"]);
		$address_longitude = check_str($_POST["address_longitude"]);
		$address_primary = check_str($_POST["address_primary"]);
		$address_description = check_str($_POST["address_description"]);

		//use custom label if set
		$address_label = ($address_label_custom != '') ? $address_label_custom : $address_label;
	}

if (count($_POST)>0 && strlen($_POST["persistformvar"]) == 0) {

	$msg = '';
	if ($action == "update") {
		$contact_address_uuid = check_str($_POST["contact_address_uuid"]);
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
		if ($address_primary) {
			$sql = "update v_contact_addresses set address_primary = 0 ";
			$sql .= "where domain_uuid = '".$domain_uuid."' ";
			$sql .= "and contact_uuid = '".$contact_uuid."' ";
			$db->exec(check_sql($sql));
			unset($sql);
		}

		if ($action == "add") {
			$contact_address_uuid = uuid();
			$sql = "insert into v_contact_addresses ";
			$sql .= "(";
			$sql .= "domain_uuid, ";
			$sql .= "contact_uuid, ";
			$sql .= "contact_address_uuid, ";
			$sql .= "address_type, ";
			$sql .= "address_label, ";
			$sql .= "address_street, ";
			$sql .= "address_extended, ";
			$sql .= "address_community, ";
			$sql .= "address_locality, ";
			$sql .= "address_region, ";
			$sql .= "address_postal_code, ";
			$sql .= "address_country, ";
			$sql .= "address_latitude, ";
			$sql .= "address_longitude, ";
			$sql .= "address_primary, ";
			$sql .= "address_description ";
			$sql .= ")";
			$sql .= "values ";
			$sql .= "(";
			$sql .= "'".$_SESSION['domain_uuid']."', ";
			$sql .= "'".$contact_uuid."', ";
			$sql .= "'".$contact_address_uuid."', ";
			$sql .= "'".$address_type."', ";
			$sql .= "'".$address_label."', ";
			$sql .= "'".$address_street."', ";
			$sql .= "'".$address_extended."', ";
			$sql .= "'".$address_community."', ";
			$sql .= "'".$address_locality."', ";
			$sql .= "'".$address_region."', ";
			$sql .= "'".$address_postal_code."', ";
			$sql .= "'".$address_country."', ";
			$sql .= "'".$address_latitude."', ";
			$sql .= "'".$address_longitude."', ";
			$sql .= (($address_primary) ? 1 : 0).", ";
			$sql .= "'".$address_description."' ";
			$sql .= ")";
			$db->exec(check_sql($sql));
			unset($sql);

			$_SESSION["message"] = $text['message-add'];
			header("Location: contact_edit.php?id=".$contact_uuid);
			return;
		} //if ($action == "add")

		if ($action == "update") {
			$sql = "update v_contact_addresses set ";
			$sql .= "contact_uuid = '".$contact_uuid."', ";
			$sql .= "address_type = '".$address_type."', ";
			$sql .= "address_label = '".$address_label."', ";
			$sql .= "address_street = '".$address_street."', ";
			$sql .= "address_extended = '".$address_extended."', ";
			$sql .= "address_community = '".$address_community."', ";
			$sql .= "address_locality = '".$address_locality."', ";
			$sql .= "address_region = '".$address_region."', ";
			$sql .= "address_postal_code = '".$address_postal_code."', ";
			$sql .= "address_country = '".$address_country."', ";
			$sql .= "address_latitude = '".$address_latitude."', ";
			$sql .= "address_longitude = '".$address_longitude."', ";
			$sql .= "address_primary = ".(($address_primary) ? 1 : 0).", ";
			$sql .= "address_description = '".$address_description."' ";
			$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
			$sql .= "and contact_address_uuid = '".$contact_address_uuid."'";
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
		$contact_address_uuid = $_GET["id"];
		$sql = "select * from v_contact_addresses ";
		$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
		$sql .= "and contact_address_uuid = '$contact_address_uuid' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		foreach ($result as &$row) {
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
			break; //limit to 1 row
		}
		unset ($prep_statement);
	}

//show the header
	require_once "resources/header.php";
	if ($action == "update") {
		$document['title'] = $text['title-contact_addresses-edit'];
	}
	else if ($action == "add") {
		$document['title'] = $text['title-contact_addresses-add'];
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
		echo $text['header-contact_addresses-edit'];
	}
	else if ($action == "add") {
		echo $text['header-contact_addresses-add'];
	}
	echo "</b></td>\n";
	echo "<td align='right' valign='top'>";
	echo "	<input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"window.location='contact_edit.php?id=$contact_uuid'\" value='".$text['button-back']."'>";
	echo "	<input type='submit' name='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "</table>\n";

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
	echo "	<textarea class='formfld' name='address_street' style='margin-bottom: 3px;'>$address_street</textarea><br>\n";
	echo "	<input class='formfld' type='text' name='address_extended' maxlength='255' value=\"$address_extended\">\n";
	echo "<br />\n";
	echo $text['description-address_address']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-address_community']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='address_community' maxlength='255' value=\"$address_community\">\n";
	echo "<br />\n";
	echo $text['description-address_community']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-address_locality']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='address_locality' maxlength='255' value=\"$address_locality\">\n";
	echo "<br />\n";
	echo $text['description-address_locality']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-address_region']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='address_region' maxlength='255' value=\"$address_region\">\n";
	echo "<br />\n";
	echo $text['description-address_region']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-address_postal_code']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='address_postal_code' maxlength='255' value=\"$address_postal_code\">\n";
	echo "<br />\n";
	echo $text['description-address_postal_code']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-address_country']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='address_country' maxlength='255' value=\"$address_country\">\n";
	echo "<br />\n";
	echo $text['description-address_country']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-address_latitude']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='number' name='address_latitude' maxlength='255' min='-90' max='90' value=\"$address_latitude\">\n";
	echo "<br />\n";
	echo $text['description-address_latitude']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-address_longitude']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='number' name='address_longitude' maxlength='255' min='-180' max='180' value=\"$address_longitude\">\n";
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
	echo "	<input class='formfld' type='text' name='address_description' maxlength='255' value=\"$address_description\">\n";
	echo "<br />\n";
	echo $text['description-address_description']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	echo "			<br>\n";
	echo "			<input type='hidden' name='contact_uuid' value='$contact_uuid'>\n";
	if ($action == "update") {
		echo "		<input type='hidden' name='contact_address_uuid' value='$contact_address_uuid'>\n";
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
