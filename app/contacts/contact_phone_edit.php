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
		$phone_type = check_str($_POST["phone_type"]);
		$phone_number = check_str($_POST["phone_number"]);
		$phone_extension = check_str($_POST["phone_extension"]);
		$phone_description = check_str($_POST["phone_description"]);

		//remove any phone number formatting
		$phone_number = preg_replace('{\D}', '', $phone_number);
	}

if (count($_POST)>0 && strlen($_POST["persistformvar"]) == 0) {

	$msg = '';
	if ($action == "update") {
		$contact_phone_uuid = check_str($_POST["contact_phone_uuid"]);
	}

	//check for all required data
		//if (strlen($phone_type) == 0) { $msg .= $text['message-required'].$text['label-phone_type']."<br>\n"; }
		//if (strlen($phone_number) == 0) { $msg .= $text['message-required'].$text['label-phone_number']."<br>\n"; }
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
			$contact_phone_uuid = uuid();
			$sql = "insert into v_contact_phones ";
			$sql .= "(";
			$sql .= "domain_uuid, ";
			$sql .= "contact_uuid, ";
			$sql .= "contact_phone_uuid, ";
			$sql .= "phone_type, ";
			$sql .= "phone_number, ";
			$sql .= "phone_extension, ";
			$sql .= "phone_description ";
			$sql .= ")";
			$sql .= "values ";
			$sql .= "(";
			$sql .= "'$domain_uuid', ";
			$sql .= "'$contact_uuid', ";
			$sql .= "'$contact_phone_uuid', ";
			$sql .= "'$phone_type', ";
			$sql .= "'$phone_number', ";
			$sql .= "'$phone_extension', ";
			$sql .= "'$phone_description' ";
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
			$sql .= "phone_type = '$phone_type', ";
			$sql .= "phone_number = '$phone_number', ";
			$sql .= "phone_extension = '$phone_extension', ";
			$sql .= "phone_description = '$phone_description' ";
			$sql .= "where domain_uuid = '$domain_uuid'";
			$sql .= "and contact_phone_uuid = '$contact_phone_uuid'";
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
			$phone_type = $row["phone_type"];
			$phone_number = $row["phone_number"];
			$phone_extension = $row["phone_extension"];
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

//show the content
	echo "<div align='center'>";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing=''>\n";
	echo "<tr class='border'>\n";
	echo "	<td align=\"left\">\n";
	echo "	  <br>";

	echo "<form method='post' name='frm' action=''>\n";
	echo "<div align='center'>\n";
	echo "<table width='100%' align='left' border='0' cellpadding='6' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "<td align='left' width='30%' nowrap='nowrap'><b>";
	if ($action == "update") {
		echo $text['header-contact_phones-edit'];
	}
	else if ($action == "add") {
		echo $text['header-contact_phones-add'];
	}
	echo "</b></td>\n";
	echo "<td width='70%' align='right'>";
	echo "	<input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"window.location='contact_edit.php?id=$contact_uuid'\" value='".$text['button-back']."'>";
	echo "	<input type='submit' name='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td align='left' colspan='2'>\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-phone_type'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	if (is_array($_SESSION["contact"]["phone_type"])) {
		sort($_SESSION["contact"]["phone_type"]);
		echo "	<select class='formfld' style='width:85%;' name='phone_type'>\n";
		echo "	<option value=''></option>\n";
		foreach($_SESSION["contact"]["phone_type"] as $row) {
			if ($row == $phone_type) {
				echo "	<option value='".$row."' selected='selected'>".$row."</option>\n";
			}
			else {
				echo "	<option value='".$row."'>".$row."</option>\n";
			}
		}
		echo "	</select>\n";
	}
	else {
		echo "	<select class='formfld' name='phone_type'>\n";
		echo "	<option value=''></option>\n";
		if ($phone_type == "home") {
			echo "	<option value='home' selected='selected'>Home</option>\n";
		}
		else {
			echo "	<option value='home'>Home</option>\n";
		}
		if ($phone_type == "work") {
			echo "	<option value='work' selected='selected'>Work</option>\n";
		}
		else {
			echo "	<option value='work'>Work</option>\n";
		}
		if ($phone_type == "pref") {
			echo "	<option value='pref' selected='selected'>Pref</option>\n";
		}
		else {
			echo "	<option value='pref'>Pref</option>\n";
		}
		if ($phone_type == "voice") {
			echo "	<option value='voice' selected='selected'>Voice</option>\n";
		}
		else {
			echo "	<option value='voice'>Voice</option>\n";
		}
		if ($phone_type == "fax") {
			echo "	<option value='fax' selected='selected'>Fax</option>\n";
		}
		else {
			echo "	<option value='fax'>Fax</option>\n";
		}
		if ($phone_type == "msg") {
			echo "	<option value='msg' selected='selected'>MSG</option>\n";
		}
		else {
			echo "	<option value='msg'>MSG</option>\n";
		}
		if ($phone_type == "cell") {
			echo "	<option value='cell' selected='selected'>Cell</option>\n";
		}
		else {
			echo "	<option value='cell'>Cell</option>\n";
		}
		if ($phone_type == "pager") {
			echo "	<option value='pager' selected='selected'>Pager</option>\n";
		}
		else {
			echo "	<option value='pager'>Pager</option>\n";
		}
		if ($phone_type == "bbs") {
			echo "	<option value='bbs' selected='selected'>BBS</option>\n";
		}
		else {
			echo "	<option value='bbs'>BBS</option>\n";
		}
		if ($phone_type == "modem") {
			echo "	<option value='modem' selected='selected'>Modem</option>\n";
		}
		else {
			echo "	<option value='modem'>Modem</option>\n";
		}
		if ($phone_type == "car") {
			echo "	<option value='car' selected='selected'>Car</option>\n";
		}
		else {
			echo "	<option value='car'>Car</option>\n";
		}
		if ($phone_type == "isdn") {
			echo "	<option value='isdn' selected='selected'>ISDN</option>\n";
		}
		else {
			echo "	<option value='isdn'>ISDN</option>\n";
		}
		if ($phone_type == "video") {
			echo "	<option value='video' selected='selected'>Video</option>\n";
		}
		else {
			echo "	<option value='video'>Video</option>\n";
		}
		if ($phone_type == "pcs") {
			echo "	<option value='pcs' selected='selected'>PCS</option>\n";
		}
		else {
			echo "	<option value='pcs'>PCS</option>\n";
		}
		if ($phone_type == "iana-token") {
			echo "	<option value='iana-token' selected='selected'>iana-token</option>\n";
		}
		else {
			echo "	<option value='iana-token'>iana-token</option>\n";
		}
		if ($phone_type == "x-name") {
			echo "	<option value='x-name' selected='selected'>x-name</option>\n";
		}
		else {
			echo "	<option value='x-name'>x-name</option>\n";
		}
		echo "	</select>\n";
	}
	echo "<br />\n";
	echo $text['description-phone_type']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-phone_number'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='phone_number' maxlength='255' value=\"$phone_number\">\n";
	echo "<br />\n";
	echo $text['description-phone_number']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-phone_extension'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='phone_extension' maxlength='255' value=\"$phone_extension\">\n";
	echo "<br />\n";
	echo $text['description-phone_extension']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-phone_description'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='phone_description' maxlength='255' value=\"$phone_description\">\n";
	echo "<br />\n";
	echo $text['description-phone_description']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	echo "				<input type='hidden' name='contact_uuid' value='$contact_uuid'>\n";
	if ($action == "update") {
		echo "				<input type='hidden' name='contact_phone_uuid' value='$contact_phone_uuid'>\n";
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