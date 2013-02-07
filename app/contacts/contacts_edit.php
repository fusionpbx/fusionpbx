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
require_once "includes/require.php";
require_once "includes/checkauth.php";
if (permission_exists('contacts_edit')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//action add or update
	if (isset($_REQUEST["id"])) {
		$action = "update";
		$contact_uuid = check_str($_REQUEST["id"]);
	}
	else {
		$action = "add";
	}

//get http post variables and set them to php variables
	if (count($_POST)>0) {
		$contact_type = check_str($_POST["contact_type"]);
		$contact_organization = check_str($_POST["contact_organization"]);
		$contact_name_given = check_str($_POST["contact_name_given"]);
		$contact_name_family = check_str($_POST["contact_name_family"]);
		$contact_nickname = check_str($_POST["contact_nickname"]);
		$contact_title = check_str($_POST["contact_title"]);
		$contact_category = check_str($_POST["contact_category"]);
		$contact_role = check_str($_POST["contact_role"]);
		$contact_email = check_str($_POST["contact_email"]);
		$contact_url = check_str($_POST["contact_url"]);
		$contact_time_zone = check_str($_POST["contact_time_zone"]);
		$contact_note = check_str($_POST["contact_note"]);
	}

if (count($_POST)>0 && strlen($_POST["persistformvar"]) == 0) {

	$msg = '';
	if ($action == "update") {
		$contact_uuid = check_str($_POST["contact_uuid"]);
	}

	//check for all required data
		//if (strlen($domain_uuid) == 0) { $msg .= "Please provide: domain_uuid<br>\n"; }
		//if (strlen($contact_type) == 0) { $msg .= "Please provide: Type<br>\n"; }
		//if (strlen($contact_organization) == 0) { $msg .= "Please provide: Organization<br>\n"; }
		//if (strlen($contact_name_given) == 0) { $msg .= "Please provide: First Name<br>\n"; }
		//if (strlen($contact_name_family) == 0) { $msg .= "Please provide: Last Name<br>\n"; }
		//if (strlen($contact_nickname) == 0) { $msg .= "Please provide: Nickname<br>\n"; }
		//if (strlen($contact_title) == 0) { $msg .= "Please provide: Title<br>\n"; }
		//if (strlen($contact_role) == 0) { $msg .= "Please provide: Role<br>\n"; }
		//if (strlen($contact_email) == 0) { $msg .= "Please provide: Email<br>\n"; }
		//if (strlen($contact_url) == 0) { $msg .= "Please provide: URL<br>\n"; }
		//if (strlen($contact_time_zone) == 0) { $msg .= "Please provide: Time Zone<br>\n"; }
		//if (strlen($contact_note) == 0) { $msg .= "Please provide: Notes<br>\n"; }
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
			$contact_uuid = uuid();
			$sql = "insert into v_contacts ";
			$sql .= "(";
			$sql .= "domain_uuid, ";
			$sql .= "contact_uuid, ";
			$sql .= "contact_type, ";
			$sql .= "contact_organization, ";
			$sql .= "contact_name_given, ";
			$sql .= "contact_name_family, ";
			$sql .= "contact_nickname, ";
			$sql .= "contact_title, ";
			$sql .= "contact_category, ";
			$sql .= "contact_role, ";
			$sql .= "contact_email, ";
			$sql .= "contact_url, ";
			$sql .= "contact_time_zone, ";
			$sql .= "contact_note ";
			$sql .= ")";
			$sql .= "values ";
			$sql .= "(";
			$sql .= "'".$_SESSION['domain_uuid']."', ";
			$sql .= "'$contact_uuid', ";
			$sql .= "'$contact_type', ";
			$sql .= "'$contact_organization', ";
			$sql .= "'$contact_name_given', ";
			$sql .= "'$contact_name_family', ";
			$sql .= "'$contact_nickname', ";
			$sql .= "'$contact_title', ";
			$sql .= "'$contact_category', ";
			$sql .= "'$contact_role', ";
			$sql .= "'$contact_email', ";
			$sql .= "'$contact_url', ";
			$sql .= "'$contact_time_zone', ";
			$sql .= "'$contact_note' ";
			$sql .= ")";
			$db->exec(check_sql($sql));
			unset($sql);

			require_once "includes/header.php";
			echo "<meta http-equiv=\"refresh\" content=\"2;url=contacts.php\">\n";
			echo "<div align='center'>\n";
			echo "Add Complete\n";
			echo "</div>\n";
			require_once "includes/footer.php";
			return;
		} //if ($action == "add")

		if ($action == "update") {
			$sql = "update v_contacts set ";
			$sql .= "contact_type = '$contact_type', ";
			$sql .= "contact_organization = '$contact_organization', ";
			$sql .= "contact_name_given = '$contact_name_given', ";
			$sql .= "contact_name_family = '$contact_name_family', ";
			$sql .= "contact_nickname = '$contact_nickname', ";
			$sql .= "contact_title = '$contact_title', ";
			$sql .= "contact_category = '$contact_category', ";
			$sql .= "contact_role = '$contact_role', ";
			$sql .= "contact_email = '$contact_email', ";
			$sql .= "contact_url = '$contact_url', ";
			$sql .= "contact_time_zone = '$contact_time_zone', ";
			$sql .= "contact_note = '$contact_note' ";
			$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
			$sql .= "and contact_uuid = '$contact_uuid' ";
			$db->exec(check_sql($sql));
			unset($sql);

			require_once "includes/header.php";
			echo "<meta http-equiv=\"refresh\" content=\"2;url=contacts.php\">\n";
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
		$contact_uuid = $_GET["id"];
		$sql = "select * from v_contacts ";
		$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
		$sql .= "and contact_uuid = '$contact_uuid' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		foreach ($result as &$row) {
			$contact_type = $row["contact_type"];
			$contact_organization = $row["contact_organization"];
			$contact_name_given = $row["contact_name_given"];
			$contact_name_family = $row["contact_name_family"];
			$contact_nickname = $row["contact_nickname"];
			$contact_title = $row["contact_title"];
			$contact_category = $row["contact_category"];
			$contact_role = $row["contact_role"];
			$contact_email = $row["contact_email"];
			$contact_url = $row["contact_url"];
			$contact_time_zone = $row["contact_time_zone"];
			$contact_note = $row["contact_note"];
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
	echo "		<br>";

	echo "<div align='center'>\n";
	echo "<table width='100%' border='0' cellpadding='6' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "<td align='left' width='30%' nowrap='nowrap'><b>Contact</b></td>\n";
	echo "<td width='70%' align='right'>\n";
	echo "	<input type='button' class='btn' name='' alt='qr code' onclick=\"window.location='contacts_vcard.php?id=$contact_uuid&type=image'\" value='QR Code'>\n";
	echo "	<input type='button' class='btn' name='' alt='vcard' onclick=\"window.location='contacts_vcard.php?id=$contact_uuid&type=download'\" value='vCard'>\n";
	if ($action == "update" && is_dir($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/app/invoices')) {
		echo "	<input type='button' class='btn' name='' alt='invoice' onclick=\"window.location='".PROJECT_PATH."/app/invoices/v_invoices.php?id=$contact_uuid'\" value='Invoices'>\n";
	}
	echo "	<input type='button' class='btn' name='' alt='back' onclick=\"window.location='contacts.php?".$_GET["query_string"]."'\" value='Back'>\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td align=\"left\" colspan='2'>\n";
	echo "The contact is a list of individuals and organizations.<br /><br />\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "</table>\n";

	echo "<table border='0' cellpadding='3' cellspacing='3' width='100%'>\n";
	echo "<tr>\n";
	echo "<td width='50%' class='vncell' valign='top' align='left' nowrap='nowrap'>\n";

		echo "<form method='post' name='frm' action=''>\n";
		echo "<table border='0' width='100%'>\n";
		echo "<tr>\n";
		echo "	<td><strong>User Information</strong></td>\n";
		echo "	<td>&nbsp;</td>\n";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	Type:\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		
		if (is_array($_SESSION["contact"]["role"])) {
			sort($_SESSION["contact"]["role"]);
			echo "	<select class='formfld' style='width:85%;' name='contact_type'>\n";
			echo "	<option value=''></option>\n";
			foreach($_SESSION["contact"]["type"] as $row) {
				if ($row == $contact_type) { 
					echo "	<option value='".$row."' selected='selected'>".$row."</option>\n";
				}
				else {
					echo "	<option value='".$row."'>".$row."</option>\n";
				}
			}
			echo "	</select>\n";
		}
		else {
			echo "	<select class='formfld' style='width:85%;' name='contact_type'>\n";
			echo "	<option value=''></option>\n";
			if ($contact_type == "customer") { 
				echo "	<option value='customer' selected='selected' >Customer</option>\n";
			}
			else {
				echo "	<option value='customer'>Customer</option>\n";
			}
			if ($contact_type == "contractor") { 
				echo "	<option value='contractor' selected='selected' >Contractor</option>\n";
			}
			else {
				echo "	<option value='contractor'>Contractor</option>\n";
			}
			if ($contact_type == "friend") { 
				echo "	<option value='friend' selected='selected' >Friend</option>\n";
			}
			else {
				echo "	<option value='friend'>Friend</option>\n";
			}
			if ($contact_type == "lead") { 
				echo "	<option value='lead' selected='selected' >Lead</option>\n";
			}
			else {
				echo "	<option value='lead'>Lead</option>\n";
			}
			if ($contact_type == "member") { 
				echo "	<option value='member' selected='selected' >Member</option>\n";
			}
			else {
				echo "	<option value='member'>Member</option>\n";
			}
			if ($contact_type == "family") { 
				echo "	<option value='family' selected='selected' >Family</option>\n";
			}
			else {
				echo "	<option value='family'>Family</option>\n";
			}
			if ($contact_type == "subscriber") { 
				echo "	<option value='subscriber' selected='selected' >Subscriber</option>\n";
			}
			else {
				echo "	<option value='subscriber'>Subscriber</option>\n";
			}
			if ($contact_type == "supplier") { 
				echo "	<option value='supplier' selected='selected' >Supplier</option>\n";
			}
			else {
				echo "	<option value='supplier'>Supplier</option>\n";
			}
			if ($contact_type == "provider") { 
				echo "	<option value='provider' selected='selected' >Provider</option>\n";
			}
			else {
				echo "	<option value='provider'>Provider</option>\n";
			}
			if ($contact_type == "user") { 
				echo "	<option value='user' selected='selected' >User</option>\n";
			}
			else {
				echo "	<option value='user'>User</option>\n";
			}
			if ($contact_type == "volunteer") { 
				echo "	<option value='volunteer' selected='selected' >Volunteer</option>\n";
			}
			else {
				echo "	<option value='volunteer'>Volunteer</option>\n";
			}
			echo "	</select>\n";
		}
		echo "<br />\n";
		echo "Select the contact type.\n";
		echo "</td>\n";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	Organization:\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<input class='formfld' style='width:85%;' type='text' name='contact_organization' maxlength='255' value=\"$contact_organization\">\n";
		echo "<br />\n";
		echo "Enter the organization.\n";
		echo "</td>\n";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	First Name:\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<input class='formfld' style='width:85%;' type='text' name='contact_name_given' maxlength='255' value=\"$contact_name_given\">\n";
		echo "<br />\n";
		echo "Enter the given name.\n";
		echo "</td>\n";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	Last Name:\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<input class='formfld' style='width:85%;' type='text' name='contact_name_family' maxlength='255' value=\"$contact_name_family\">\n";
		echo "<br />\n";
		echo "Enter the family name.\n";
		echo "</td>\n";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	Nickname:\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<input class='formfld' style='width:85%;' type='text' name='contact_nickname' maxlength='255' value=\"$contact_nickname\">\n";
		echo "<br />\n";
		echo "Enter the nickname.\n";
		echo "</td>\n";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	Title:\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		if (is_array($_SESSION["contact"]["title"])) {
			sort($_SESSION["contact"]["title"]);
			echo "	<select class='formfld' style='width:85%;' name='contact_title'>\n";
			echo "	<option value=''></option>\n";
			foreach($_SESSION["contact"]["title"] as $row) {
				if ($row == $contact_title) { 
					echo "	<option value='".$row."' selected='selected'>".$row."</option>\n";
				}
				else {
					echo "	<option value='".$row."'>".$row."</option>\n";
				}
			}
			echo "	</select>\n";
		}
		else {
			echo "	<input class='formfld' style='width:85%;' type='text' name='contact_title' maxlength='255' value=\"$contact_title\">\n";
		}
		echo "<br />\n";
		echo "Enter the title.\n";
		echo "</td>\n";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	Category:\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		if (is_array($_SESSION["contact"]["category"])) {
			sort($_SESSION["contact"]["category"]);
			echo "	<select class='formfld' style='width:85%;' name='contact_category'>\n";
			echo "	<option value=''></option>\n";
			foreach($_SESSION["contact"]["category"] as $row) {
				if ($row == $contact_category) { 
					echo "	<option value='".$row."' selected='selected'>".$row."</option>\n";
				}
				else {
					echo "	<option value='".$row."'>".$row."</option>\n";
				}
			}
			echo "	</select>\n";
		}
		else {
			echo "	<input class='formfld' style='width:85%;' type='text' name='contact_category' maxlength='255' value=\"$contact_category\">\n";
		}
		echo "<br />\n";
		echo "Enter the category.\n";
		echo "</td>\n";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	Role:\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		if (is_array($_SESSION["contact"]["role"])) {
			sort($_SESSION["contact"]["role"]);
			echo "	<select class='formfld' style='width:85%;' name='contact_role'>\n";
			echo "	<option value=''></option>\n";
			foreach($_SESSION["contact"]["role"] as $row) {
				if ($row == $contact_role) { 
					echo "	<option value='".$row."' selected='selected'>".$row."</option>\n";
				}
				else {
					echo "	<option value='".$row."'>".$row."</option>\n";
				}
			}
			echo "	</select>\n";
		}
		else {
			echo "	<input class='formfld' style='width:85%;' type='text' name='contact_role' maxlength='255' value=\"$contact_role\">\n";
		}
		echo "<br />\n";
		echo "Enter the role.\n";
		echo "</td>\n";
		echo "</tr>\n";

		//echo "<tr>\n";
		//echo "<td><strong>Contact Information</strong></td>\n";
		//echo "<td>&nbsp;</td>\n";
		//echo "<tr>\n";

		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	Email:\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<input class='formfld' style='width:85%;' type='text' name='contact_email' maxlength='255' value=\"$contact_email\">\n";
		echo "<br />\n";
		echo "Enter the email address.\n";
		echo "</td>\n";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	URL:\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "  <input class='formfld' style='width:85%;' type='text' name='contact_url' maxlength='255' value='$contact_url'>\n";
		echo "<br />\n";
		echo "Enter the website address.\n";
		echo "</td>\n";
		echo "</tr>\n";

		//echo "<tr>\n";
		//echo "<td><strong>Additional Information</strong></td>\n";
		//echo "<td>&nbsp;</td>\n";
		//echo "<tr>\n";

		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	Time Zone:\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<input class='formfld' style='width:85%;' type='text' name='contact_time_zone' maxlength='255' value=\"$contact_time_zone\">\n";
		echo "<br />\n";
		echo "Enter the time zone.\n";
		echo "</td>\n";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	Notes:\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "  <input class='formfld' style='width:85%;' type='text' name='contact_note' maxlength='255' value='$contact_note'>\n";
		echo "<br />\n";
		echo "Enter the notes.\n";
		echo "</td>\n";
		echo "</tr>\n";
		echo "	<tr>\n";
		echo "		<td colspan='2' align='right'>\n";
		if ($action == "update") {
			echo "				<input type='hidden' name='contact_uuid' value='$contact_uuid'>\n";
		}
		echo "				<input type='submit' name='submit' class='btn' value='Save'>\n";
		echo "		</td>\n";
		echo "	</tr>";
		echo "</table>";
		echo "</form>";

	echo "</td>\n";
	echo "<td width='50%' class='' valign='top' align='center'>\n";
		//echo "	<img src='contacts_vcard.php?id=$contact_uuid&type=image' width='90%'><br /><br />\n";
		if ($action == "update") {
			require "contact_phones.php";
			require "contact_addresses.php";
			require "contact_notes.php";
		}

	echo "</td>\n";
	echo "</tr>\n";
	echo "</table>\n";

	if ($action == "update") {
		echo "<br/>\n";
		
	}

	echo "	</td>";
	echo "	</tr>";
	echo "</table>";
	echo "</div>";

//include the footer
	require_once "includes/footer.php";
?>