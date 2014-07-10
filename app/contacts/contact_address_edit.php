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
*/
require_once "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists('contact_edit')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
	require_once "app_languages.php";
	foreach($text as $key => $value) {
		$text[$key] = $value[$_SESSION['domain']['language']['code']];
	}

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
		//$address_name = check_str($_POST["address_name"]);
		$address_type = check_str($_POST["address_type"]);
		$address_street = check_str($_POST["address_street"]);
		$address_extended = check_str($_POST["address_extended"]);
		$address_locality = check_str($_POST["address_locality"]);
		$address_region = check_str($_POST["address_region"]);
		$address_postal_code = check_str($_POST["address_postal_code"]);
		$address_country = check_str($_POST["address_country"]);
		$address_latitude = check_str($_POST["address_latitude"]);
		$address_longitude = check_str($_POST["address_longitude"]);
		$address_description = check_str($_POST["address_description"]);
	}

if (count($_POST)>0 && strlen($_POST["persistformvar"]) == 0) {

	$msg = '';
	if ($action == "update") {
		$contact_address_uuid = check_str($_POST["contact_address_uuid"]);
	}

	//check for all required data
		//if (strlen($address_type) == 0) { $msg .= $text['message-required'].$text['label-address_type']."<br>\n"; }
		//if (strlen($address_street) == 0) { $msg .= $text['message-required'].$text['label-address_street']."<br>\n"; }
		//if (strlen($address_extended) == 0) { $msg .= $text['message-required'].$text['label-address_extended']."<br>\n"; }
		//if (strlen($address_locality) == 0) { $msg .= $text['message-required'].$text['label-address_locality']."<br>\n"; }
		//if (strlen($address_region) == 0) { $msg .= $text['message-required'].$text['label-address_region']."<br>\n"; }
		//if (strlen($address_postal_code) == 0) { $msg .= $text['message-required'].$text['label-address_postal_code']."<br>\n"; }
		//if (strlen($address_country) == 0) { $msg .= $text['message-required'].$text['label-address_country']."<br>\n"; }
		//if (strlen($address_latitude) == 0) { $msg .= $text['message-required'].$text['label-address_latitude']."<br>\n"; }
		//if (strlen($address_longitude) == 0) { $msg .= $text['message-required'].$text['label-address_longitude']."<br>\n"; }
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
		if ($action == "add") {
			$contact_address_uuid = uuid();
			$sql = "insert into v_contact_addresses ";
			$sql .= "(";
			$sql .= "domain_uuid, ";
			$sql .= "contact_uuid, ";
			$sql .= "contact_address_uuid, ";
			//$sql .= "address_name, ";
			$sql .= "address_type, ";
			$sql .= "address_street, ";
			$sql .= "address_extended, ";
			$sql .= "address_locality, ";
			$sql .= "address_region, ";
			$sql .= "address_postal_code, ";
			$sql .= "address_country, ";
			$sql .= "address_latitude, ";
			$sql .= "address_longitude, ";
			$sql .= "address_description ";
			$sql .= ")";
			$sql .= "values ";
			$sql .= "(";
			$sql .= "'".$_SESSION['domain_uuid']."', ";
			$sql .= "'$contact_uuid', ";
			$sql .= "'$contact_address_uuid', ";
			//$sql .= "'$address_name', ";
			$sql .= "'$address_type', ";
			$sql .= "'$address_street', ";
			$sql .= "'$address_extended', ";
			$sql .= "'$address_locality', ";
			$sql .= "'$address_region', ";
			$sql .= "'$address_postal_code', ";
			$sql .= "'$address_country', ";
			$sql .= "'$address_latitude', ";
			$sql .= "'$address_longitude', ";
			$sql .= "'$address_description' ";
			$sql .= ")";
			$db->exec(check_sql($sql));
			unset($sql);

			$_SESSION["message"] = $text['message-add'];
			header("Location: contact_edit.php?id=".$contact_uuid);
			return;
		} //if ($action == "add")

		if ($action == "update") {
			$sql = "update v_contact_addresses set ";
			$sql .= "contact_uuid = '$contact_uuid', ";
			//$sql .= "address_name = '$address_name', ";
			$sql .= "address_type = '$address_type', ";
			$sql .= "address_street = '$address_street', ";
			$sql .= "address_extended = '$address_extended', ";
			$sql .= "address_locality = '$address_locality', ";
			$sql .= "address_region = '$address_region', ";
			$sql .= "address_postal_code = '$address_postal_code', ";
			$sql .= "address_country = '$address_country', ";
			$sql .= "address_latitude = '$address_latitude', ";
			$sql .= "address_longitude = '$address_longitude', ";
			$sql .= "address_description = '$address_description' ";
			$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
			$sql .= "and contact_address_uuid = '$contact_address_uuid'";
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
			//$address_name = $row["address_name"];
			$address_type = $row["address_type"];
			$address_street = $row["address_street"];
			$address_extended = $row["address_extended"];
			$address_locality = $row["address_locality"];
			$address_region = $row["address_region"];
			$address_postal_code = $row["address_postal_code"];
			$address_country = $row["address_country"];
			$address_latitude = $row["address_latitude"];
			$address_longitude = $row["address_longitude"];
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
	echo "<td align='left' width='30%' nowrap='nowrap'><b>";
	if ($action == "update") {
		echo $text['header-contact_addresses-edit'];
	}
	else if ($action == "add") {
		echo $text['header-contact_addresses-add'];
	}
	echo "</b></td>\n";
	echo "<td width='70%' align='right'>";
	echo "	<input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"window.location='contact_edit.php?id=$contact_uuid'\" value='".$text['button-back']."'>";
	echo "	<input type='submit' name='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td align='left' colspan='2'>\n";
	if ($action == "update") {
		echo $text['description-contact_addresses-edit'];
	}
	else if ($action == "add") {
		echo $text['description-contact_addresses-add'];
	}
	echo "<br /><br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-address_type'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	if (is_array($_SESSION["contact"]["address_type"])) {
		sort($_SESSION["contact"]["address_type"]);
		echo "	<select class='formfld' style='width:85%;' name='address_type'>\n";
		echo "	<option value=''></option>\n";
		foreach($_SESSION["contact"]["address_type"] as $row) {
			if ($row == $address_type) {
				echo "	<option value='".$row."' selected='selected'>".$row."</option>\n";
			}
			else {
				echo "	<option value='".$row."'>".$row."</option>\n";
			}
		}
		echo "	</select>\n";
	}
	else {
		echo "	<select class='formfld' name='address_type'>\n";
		echo "	<option value=''></option>\n";
		if (strtolower($address_type) == "home") {
			echo "	<option value='home' selected='selected'>".$text['option-home']."</option>\n";
		}
		else {
			echo "	<option value='home'>".$text['option-home']."</option>\n";
		}
		if (strtolower($address_type) == "work") {
			echo "	<option value='work' selected='selected'>".$text['option-work']."</option>\n";
		}
		else {
			echo "	<option value='work'>".$text['option-work']."</option>\n";
		}
		echo "	</select>\n";
	}
	echo "<br />\n";
	echo $text['description-address_type']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-address_street'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='address_street' maxlength='255' value=\"$address_street\">\n";
	echo "<br />\n";
	echo $text['description-address_street']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-address_extended'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='address_extended' maxlength='255' value=\"$address_extended\">\n";
	echo "<br />\n";
	echo $text['description-address_extended']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-address_locality'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='address_locality' maxlength='255' value=\"$address_locality\">\n";
	echo "<br />\n";
	echo $text['description-address_locality']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-address_region'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='address_region' maxlength='255' value=\"$address_region\">\n";
	echo "<br />\n";
	echo $text['description-address_region']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-address_postal_code'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='address_postal_code' maxlength='255' value=\"$address_postal_code\">\n";
	echo "<br />\n";
	echo $text['description-address_postal_code']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-address_country'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='address_country' maxlength='255' value=\"$address_country\">\n";
	echo "<br />\n";
	echo $text['description-address_country']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-address_latitude'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='address_latitude' maxlength='255' value=\"$address_latitude\">\n";
	echo "<br />\n";
	echo $text['description-address_latitude']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-address_longitude'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='address_longitude' maxlength='255' value=\"$address_longitude\">\n";
	echo "<br />\n";
	echo $text['description-address_longitude']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-address_description'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='address_description' maxlength='255' value=\"$address_description\">\n";
	echo "<br />\n";
	echo $text['description-address_description']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	echo "				<input type='hidden' name='contact_uuid' value='$contact_uuid'>\n";
	if ($action == "update") {
		echo "				<input type='hidden' name='contact_address_uuid' value='$contact_address_uuid'>\n";
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