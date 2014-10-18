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

//handle removal of contact group
	if ($_GET['a'] == 'delete') {
		$contact_uuid = $_GET["id"];
		$contact_group_uuid = $_GET["cgid"];
		$sql = "delete from v_contact_groups ";
		$sql .= "where contact_uuid = '".$contact_uuid."' ";
		$sql .= "and contact_group_uuid = '".$contact_group_uuid."' ";
		$db->exec(check_sql($sql));
		unset($sql);

		$_SESSION["message"] = $text['message-update'];
		header("Location: contact_edit.php?id=".$contact_uuid);
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
		//if (strlen($contact_type) == 0) { $msg .= $text['message-required'].$text['label-contact_type']."<br>\n"; }
		//if (strlen($contact_organization) == 0) { $msg .= $text['message-required'].$text['label-contact_organization']."<br>\n"; }
		//if (strlen($contact_name_given) == 0) { $msg .= $text['message-required'].$text['label-contact_name_given']."<br>\n"; }
		//if (strlen($contact_name_family) == 0) { $msg .= $text['message-required'].$text['label-contact_name_family']."<br>\n"; }
		//if (strlen($contact_nickname) == 0) { $msg .= $text['message-required'].$text['label-contact_nickname']."<br>\n"; }
		//if (strlen($contact_title) == 0) { $msg .= $text['message-required'].$text['label-contact_title']."<br>\n"; }
		//if (strlen($contact_role) == 0) { $msg .= $text['message-required'].$text['label-contact_role']."<br>\n"; }
		//if (strlen($contact_email) == 0) { $msg .= $text['message-required'].$text['label-contact_email']."<br>\n"; }
		//if (strlen($contact_url) == 0) { $msg .= $text['message-required'].$text['label-contact_url']."<br>\n"; }
		//if (strlen($contact_time_zone) == 0) { $msg .= $text['message-required'].$text['label-contact_time_zone']."<br>\n"; }
		//if (strlen($contact_note) == 0) { $msg .= $text['message-required'].$text['label-contact_note']."<br>\n"; }
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
			$contact_uuid = uuid();
			$sql = "insert into v_contacts ";
			$sql .= "( ";
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
			$sql .= ") ";
			$sql .= "values ";
			$sql .= "( ";
			$sql .= "'".$_SESSION['domain_uuid']."', ";
			$sql .= "'".$contact_uuid."', ";
			$sql .= "'".$contact_type."', ";
			$sql .= "'".$contact_organization."', ";
			$sql .= "'".$contact_name_given."', ";
			$sql .= "'".$contact_name_family."', ";
			$sql .= "'".$contact_nickname."', ";
			$sql .= "'".$contact_title."', ";
			$sql .= "'".$contact_category."', ";
			$sql .= "'".$contact_role."', ";
			$sql .= "'".$contact_email."', ";
			$sql .= "'".$contact_url."', ";
			$sql .= "'".$contact_time_zone."', ";
			$sql .= "'".$contact_note."' ";
			$sql .= ")";
			$db->exec(check_sql($sql));
			unset($sql);

			$_SESSION["message"] = $text['message-add'];
			$location = "contacts.php";
		} //if ($action == "add")

		//if contact is shared, remove contact group record containing user's uuid
		if ($_POST['contact_shared'] == 'true') {
			$sql = "delete from v_contact_groups ";
			$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
			$sql .= "and contact_uuid = '".$contact_uuid."' ";
			$sql .= "and group_uuid = '".$_SESSION["user_uuid"]."' ";
			$prep_statement = $db->prepare(check_sql($sql));
			$prep_statement->execute();
			unset($prep_statement, $sql);
			$group_uuid = $_POST['group_uuid'];
		}
		//if private contact, delete any groups currently assigned, set group uuid to user's uuid
		else {
			$sql = "delete from v_contact_groups ";
			$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
			$sql .= "and contact_uuid = '".$contact_uuid."' ";
			$prep_statement = $db->prepare(check_sql($sql));
			$prep_statement->execute();
			unset($prep_statement, $sql);
			$group_uuid = $_SESSION["user_uuid"];
		}

		//handle insertion of contact group (or private contact, if not shared)
		if ($group_uuid != '') {
			$sql = "insert into v_contact_groups ";
			$sql .= "( ";
			$sql .= "contact_group_uuid, ";
			$sql .= "domain_uuid, ";
			$sql .= "contact_uuid, ";
			$sql .= "group_uuid ";
			$sql .= ") ";
			$sql .= "values ";
			$sql .= "( ";
			$sql .= "'".uuid()."', ";
			$sql .= "'".$domain_uuid."', ";
			$sql .= "'".$contact_uuid."', ";
			$sql .= "'".$group_uuid."' ";
			$sql .= ") ";
			$db->exec(check_sql($sql));
			unset($sql);
		}

		if ($action == "update") {
			$sql = "update v_contacts set ";
			$sql .= "contact_type = '".$contact_type."', ";
			$sql .= "contact_organization = '".$contact_organization."', ";
			$sql .= "contact_name_given = '".$contact_name_given."', ";
			$sql .= "contact_name_family = '".$contact_name_family."', ";
			$sql .= "contact_nickname = '".$contact_nickname."', ";
			$sql .= "contact_title = '".$contact_title."', ";
			$sql .= "contact_category = '".$contact_category."', ";
			$sql .= "contact_role = '".$contact_role."', ";
			$sql .= "contact_email = '".$contact_email."', ";
			$sql .= "contact_url = '".$contact_url."', ";
			$sql .= "contact_time_zone = '".$contact_time_zone."', ";
			$sql .= "contact_note = '".$contact_note."' ";
			$sql .= "where domain_uuid = '".$domain_uuid."' ";
			$sql .= "and contact_uuid = '".$contact_uuid."' ";
			$db->exec(check_sql($sql));
			unset($sql);

			$_SESSION["message"] = $text['message-update'];
			$location = "contacts.php";
		} //if ($action == "update")

		//handle redirect
		if ($_POST['submit'] == $text['button-add']) {
			$group_uuid = $_POST['group_uuid'];
			//insert
			$location = "contact_edit.php?id=".$contact_uuid;
		}

		header("Location: ".$location);
		return;

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
	require_once "resources/header.php";
	if ($action == "update") {
		$document['title'] = $text['title-contact-edit'];
	}
	else if ($action == "add") {
		$document['title'] = $text['title-contact-add'];
	}

// qr code generation
	$_GET['type'] = "text";
	$qr_vcard = true;
	include "contacts_vcard.php";
	echo "<input type='hidden' id='qr_vcard' value=\"".$qr_vcard."\">";
	echo "<style>";
	echo "	#qr_code_container {";
	echo "		z-index: 999999; ";
	echo "		position: absolute; ";
	echo "		left: 0px; ";
	echo "		top: 0px; ";
	echo "		right: 0px; ";
	echo "		bottom: 0px; ";
	echo "		text-align: center; ";
	echo "		vertical-align: middle;";
	echo "	}";
	echo "	#qr_code {";
	echo "		display: block; ";
	echo "		width: 650px; ";
	echo "		height: 650px; ";
	echo "		-webkit-box-shadow: 0px 1px 20px #888; ";
	echo "		-moz-box-shadow: 0px 1px 20px #888; ";
	echo "		box-shadow: 0px 1px 20px #888;";
	echo "	}";
	echo "</style>";
	echo "<script src='".PROJECT_PATH."/resources/jquery/jquery.qrcode-0.8.0.min.js'></script>";
	echo "<script language='JavaScript' type='text/javascript'>";
	echo "	$(document).ready(function() {";
	echo "		$(window).load(function() {";
	echo "			$('#qr_code').qrcode({ ";
	echo "				render: 'canvas', ";
	echo "				minVersion: 6, ";
	echo "				maxVersion: 40, ";
	echo "				ecLevel: 'H', ";
	echo "				size: 650, ";
	echo "				radius: 0.2, ";
	echo "				quiet: 6, ";
	echo "				background: '#fff', ";
	echo "				mode: 4, ";
	echo "				mSize: 0.2, ";
	echo "				mPosX: 0.5, ";
	echo "				mPosY: 0.5, ";
	echo "				image: $('#img-buffer')[0], ";
	echo "				text: document.getElementById('qr_vcard').value ";
	echo "			});";
	echo "		});";
	echo "	});";
	echo "</script>";
	echo "<img id='img-buffer' src='".PROJECT_PATH."/themes/".$_SESSION["domain"]["template"]["name"]."/images/qr_code.png' style='display: none;'>";

//show the content
	echo "<form method='post' name='frm' action=''>\n";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "<td align='left' width='30%' nowrap='nowrap'><b>";
	switch ($action) {
		case "add" : 	echo $text['header-contact-add'];	break;
		case "update" :	echo $text['header-contact-edit'];	break;
	}
	echo "</b></td>\n";
	echo "<td width='70%' align='right'>\n";
	echo "	<input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"window.location='contacts.php?".$_GET["query_string"]."'\" value='".$text['button-back']."'>\n";
	if ($action == "update") {
		echo "	<input type='button' class='btn' name='' alt='".$text['button-qr_code']."' onclick=\"$('#qr_code_container').fadeIn(400);\" value='".$text['button-qr_code']."'>\n";
		echo "	<input type='button' class='btn' name='' alt='".$text['button-vcard']."' onclick=\"window.location='contacts_vcard.php?id=$contact_uuid&type=download'\" value='".$text['button-vcard']."'>\n";
	}
	if ($action == "update" && is_dir($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/app/invoices')) {
		echo "	<input type='button' class='btn' name='' alt='".$text['button-invoices']."' onclick=\"window.location='".PROJECT_PATH."/app/invoices/invoices.php?id=$contact_uuid'\" value='".$text['button-invoices']."'>\n";
	}
	if ($action == "update" && is_dir($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/app/certificates')) {
		echo "	<input type='button' class='btn' name='' alt='".$text['button-certificate']."' onclick=\"window.location='".PROJECT_PATH."/app/certificates/index.php?name=".urlencode($contact_name_given." ".$contact_name_family)."'\" value='".$text['button-certificate']."'>\n";
	}
	echo "	<input type='submit' name='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td align=\"left\" colspan='2'>\n";
	switch ($action) {
		case "add" :	echo $text['description-contact-add'];	break;
		case "update" :	echo $text['description-contact-edit'];	break;
	}
	echo "<br /><br />\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "</table>\n";

	echo "<table border='0' cellpadding='3' cellspacing='3' width='100%'>\n";
	echo "<tr>\n";
	echo "<td width='40%' valign='top' align='left' nowrap='nowrap'>\n";

		echo "<table border='0' cellpadding='0' cellspacing='0' width='100%'>\n";
		echo "<tr>\n";
		echo "<td width='30%' class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-contact_type'].":\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";

		if (is_array($_SESSION["contact"]["role"])) {
			sort($_SESSION["contact"]["role"]);
			echo "	<select class='formfld' name='contact_type'>\n";
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
			echo "	<select class='formfld' name='contact_type'>\n";
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
		echo $text['description-contact_type']."\n";
		echo "</td>\n";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-contact_organization'].":\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<input class='formfld' type='text' name='contact_organization' maxlength='255' value=\"$contact_organization\">\n";
		echo "<br />\n";
		echo $text['description-contact_organization']."\n";
		echo "</td>\n";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-contact_name_given'].":\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<input class='formfld' type='text' name='contact_name_given' maxlength='255' value=\"$contact_name_given\">\n";
		echo "<br />\n";
		echo $text['description-contact_name_given']."\n";
		echo "</td>\n";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-contact_name_family'].":\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<input class='formfld' type='text' name='contact_name_family' maxlength='255' value=\"$contact_name_family\">\n";
		echo "<br />\n";
		echo $text['description-contact_name_family']."\n";
		echo "</td>\n";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-contact_nickname'].":\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<input class='formfld' type='text' name='contact_nickname' maxlength='255' value=\"$contact_nickname\">\n";
		echo "<br />\n";
		echo $text['description-contact_nickname']."\n";
		echo "</td>\n";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-contact_title'].":\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		if (is_array($_SESSION["contact"]["title"])) {
			sort($_SESSION["contact"]["title"]);
			echo "	<select class='formfld' name='contact_title'>\n";
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
			echo "	<input class='formfld' type='text' name='contact_title' maxlength='255' value=\"$contact_title\">\n";
		}
		echo "<br />\n";
		echo $text['description-contact_title']."\n";
		echo "</td>\n";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-contact_category'].":\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		if (is_array($_SESSION["contact"]["category"])) {
			sort($_SESSION["contact"]["category"]);
			echo "	<select class='formfld' name='contact_category'>\n";
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
			echo "	<input class='formfld' type='text' name='contact_category' maxlength='255' value=\"$contact_category\">\n";
		}
		echo "<br />\n";
		echo $text['description-contact_category']."\n";
		echo "</td>\n";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-contact_role'].":\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		if (is_array($_SESSION["contact"]["role"])) {
			sort($_SESSION["contact"]["role"]);
			echo "	<select class='formfld' name='contact_role'>\n";
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
			echo "	<input class='formfld' type='text' name='contact_role' maxlength='255' value=\"$contact_role\">\n";
		}
		echo "<br />\n";
		echo $text['description-contact_role']."\n";
		echo "</td>\n";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-contact_email'].":\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<input class='formfld' type='text' name='contact_email' maxlength='255' value=\"$contact_email\">\n";
		echo "<br />\n";
		echo $text['description-contact_email']."\n";
		echo "</td>\n";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-contact_url'].":\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "  <input class='formfld' type='text' name='contact_url' maxlength='255' value='$contact_url'>\n";
		echo "<br />\n";
		echo $text['description-contact_url']."\n";
		echo "</td>\n";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-contact_time_zone'].":\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<input class='formfld' type='text' name='contact_time_zone' maxlength='255' value=\"$contact_time_zone\">\n";
		echo "<br />\n";
		echo $text['description-contact_time_zone']."\n";
		echo "</td>\n";
		echo "</tr>\n";

		//determine if contact is shared or private
		if ($action == 'update') {
			$sql = "select count(*) as num_rows from v_contact_groups ";
			$sql .= "where domain_uuid = '".$domain_uuid."' ";
			$sql .= "and contact_uuid = '".$contact_uuid."' ";
			$sql .= "and group_uuid = '".$_SESSION["user_uuid"]."' ";
			$prep_statement = $db->prepare(check_sql($sql));
			$prep_statement->execute();
			$row = $prep_statement->fetch(PDO::FETCH_ASSOC);
			$contact_shared = ($row['num_rows'] > 0) ? 'false' : 'true';
			unset ($sql, $prep_statement, $row);
		}
		else {
			//private by default on contact add
			$contact_shared = 'false';
		}
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-shared'].":\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<select class='formfld' name='contact_shared' id='contact_shared' ".((permission_exists('contact_group_view')) ? "onchange=\"$('#div_groups').slideToggle('400');\"" : null).">\n";
		echo "		<option value='false'>".$text['option-false']."</option>\n";
		echo "		<option value='true' ".(($contact_shared == 'true') ? "selected" : null).">".$text['option-true']."</option>\n";
		echo "	</select>\n";
		echo "<br />\n";
		echo $text['description-shared']."\n";
		echo "</td>\n";
		echo "</tr>\n";
		echo "</table>";

		if (permission_exists('contact_group_view')) {
			echo "<div id='div_groups' ".(($contact_shared != 'true') ? "style='display: none;'" : null).">\n";
			echo "<table border='0' cellpadding='0' cellspacing='0' width='100%'>\n";
			echo "<tr>";
			echo "	<td width='30%' class='vncell' valign='top'>".$text['label-groups'].":</td>";
			echo "	<td width='70%' class='vtable'>";
			$sql = "select ";
			$sql .= "g.*, ";
			$sql .= "cg.contact_group_uuid ";
			$sql .= "from ";
			$sql .= "v_groups as g, ";
			$sql .= "v_contact_groups as cg ";
			$sql .= "where ";
			$sql .= "cg.group_uuid = g.group_uuid ";
			$sql .= "and g.domain_uuid = '".$domain_uuid."' ";
			$sql .= "and cg.domain_uuid = '".$domain_uuid."' ";
			$sql .= "and cg.contact_uuid = '".$contact_uuid."' ";
			$sql .= "and cg.group_uuid <> '".$_SESSION["user_uuid"]."' ";
			$sql .= "order by g.group_name asc ";
			$prep_statement = $db->prepare(check_sql($sql));
			$prep_statement->execute();
			$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
			$result_count = count($result);
			if ($result_count > 0) {
				echo "	<table width='52%'>\n";
				foreach($result as $field) {
					if (strlen($field['group_name']) > 0) {
						echo "<tr>\n";
						echo "	<td class='vtable'>".$field['group_name']."</td>\n";
						echo "	<td>\n";
						if (permission_exists('contact_group_delete') || if_group("superadmin")) {
							echo "	<a href='contact_edit.php?id=".$contact_uuid."&cgid=".$field['contact_group_uuid']."&a=delete' alt='".$text['button-delete']."' onclick=\"return confirm('".$text['confirm-delete']."')\">$v_link_label_delete</a>\n";
						}
						echo "	</td>\n";
						echo "</tr>\n";
						$assigned_groups[] = $field['group_uuid'];
					}
				}
				echo "	</table>\n";
				echo "	<br />\n";
			}
			unset($sql, $prep_statement, $result, $field);

			if (permission_exists('contact_group_add') || if_group("superadmin")) {
				$sql = "select * from v_groups ";
				$sql .= "where domain_uuid = '".$domain_uuid."' ";
				if (sizeof($assigned_groups) > 0) {
					$sql .= "and group_uuid not in ('".implode("','",$assigned_groups)."') ";
				}
				$sql .= "order by group_name asc ";
				$prep_statement = $db->prepare(check_sql($sql));
				$prep_statement->execute();
				$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
				$result_count = count($result);
				if ($result_count > 0) {
					echo "	<select name='group_uuid' class='formfld' style='width: auto; margin-right: 3px;'>\n";
					echo "		<option value=''></option>\n";
					foreach($result as $field) {
						if ($field['group_name'] == "superadmin" && !if_group("superadmin")) { continue; }	//only show superadmin group to superadmins
						if ($field['group_name'] == "admin" && (!if_group("superadmin") && !if_group("admin"))) { continue; }	//only show admin group to admins
						echo "<option value='".$field['group_uuid']."'>".$field['group_name']."</option>\n";
					}
					echo "	</select>";

					if ($action == "update") {
						echo "	<input type='submit' name='submit' class='btn' value=\"".$text['button-add']."\">\n";
					}
					echo "<br>";
				}
				unset($sql, $prep_statement, $result, $field);
			}

			echo "		".$text['description-groups']."\n";

			echo "	</td>";
			echo "</tr>";
			echo "</table>\n";
			echo "</div>";
		}

		echo "<table border='0' cellpadding='0' cellspacing='0' width='100%'>\n";
		echo "<tr>\n";
		echo "<td width='30%' class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-contact_note'].":\n";
		echo "</td>\n";
		echo "<td width='70%' class='vtable' align='left'>\n";
		echo "  <input class='formfld' type='text' name='contact_note' maxlength='255' value='$contact_note'>\n";
		echo "<br />\n";
		echo $text['description-contact_note']."\n";
		echo "</td>\n";
		echo "</tr>\n";
		echo "	<tr>\n";
		echo "		<td colspan='2' align='right'>\n";
		if ($action == "update") {
			echo "				<input type='hidden' name='contact_uuid' value='$contact_uuid'>\n";
		}
		echo "			<br>";
		echo "			<input type='submit' name='submit' class='btn' value='".$text['button-save']."'>\n";
		echo "		</td>\n";
		echo "	</tr>";
		echo "</table>";

	echo "</td>\n";

	if ($action == "update") {
		echo "<td>&nbsp;&nbsp;</td>";
		echo "<td width='60%' class='' valign='top' align='center'>\n";
			//echo "	<img src='contacts_vcard.php?id=$contact_uuid&type=image' width='90%'><br /><br />\n";
			require "contact_phones.php";
			require "contact_addresses.php";
			require "contact_extensions.php";
			require "contact_notes.php";
		echo "</td>\n";
	}

	echo "</tr>\n";
	echo "</table>\n";
	echo "</form>";

//include the footer
	require_once "resources/footer.php";
?>