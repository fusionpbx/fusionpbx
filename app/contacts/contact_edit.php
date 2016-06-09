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
	Portions created by the Initial Developer are Copyright (C) 2008-2016
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/
require_once "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists('contact_view')) {
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
		$contact_uuid = check_str($_REQUEST["id"]);
	}
	else {
		$action = "add";
	}

//get http post variables and set them to php variables
	if (count($_POST) > 0) {
		$user_uuid = check_str($_POST["user_uuid"]);
		$group_uuid = $_POST['group_uuid'];
		$contact_type = check_str($_POST["contact_type"]);
		$contact_organization = check_str($_POST["contact_organization"]);
		$contact_name_prefix = check_str($_POST["contact_name_prefix"]);
		$contact_name_given = check_str($_POST["contact_name_given"]);
		$contact_name_middle = check_str($_POST["contact_name_middle"]);
		$contact_name_family = check_str($_POST["contact_name_family"]);
		$contact_name_suffix = check_str($_POST["contact_name_suffix"]);
		$contact_nickname = check_str($_POST["contact_nickname"]);
		$contact_title = check_str($_POST["contact_title"]);
		$contact_category = check_str($_POST["contact_category"]);
		$contact_role = check_str($_POST["contact_role"]);
		$contact_time_zone = check_str($_POST["contact_time_zone"]);
		$contact_note = check_str($_POST["contact_note"]);
	}

//process the form data
	if (count($_POST) > 0 && strlen($_POST["persistformvar"]) == 0) {

		//set the uuid
			if ($action == "update") {
				$contact_uuid = check_str($_POST["contact_uuid"]);
			}

		//check for all required data
			$msg = '';
			//if (strlen($contact_type) == 0) { $msg .= $text['message-required'].$text['label-contact_type']."<br>\n"; }
			//if (strlen($contact_organization) == 0) { $msg .= $text['message-required'].$text['label-contact_organization']."<br>\n"; }
			//if (strlen($contact_name_prefix) == 0) { $msg .= $text['message-required'].$text['label-contact_name_prefix']."<br>\n"; }
			//if (strlen($contact_name_given) == 0) { $msg .= $text['message-required'].$text['label-contact_name_given']."<br>\n"; }
			//if (strlen($contact_name_middle) == 0) { $msg .= $text['message-required'].$text['label-contact_name_middle']."<br>\n"; }
			//if (strlen($contact_name_family) == 0) { $msg .= $text['message-required'].$text['label-contact_name_family']."<br>\n"; }
			//if (strlen($contact_name_suffix) == 0) { $msg .= $text['message-required'].$text['label-contact_name_suffix']."<br>\n"; }
			//if (strlen($contact_nickname) == 0) { $msg .= $text['message-required'].$text['label-contact_nickname']."<br>\n"; }
			//if (strlen($contact_title) == 0) { $msg .= $text['message-required'].$text['label-contact_title']."<br>\n"; }
			//if (strlen($contact_role) == 0) { $msg .= $text['message-required'].$text['label-contact_role']."<br>\n"; }
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

				//add the contact
					if ($action == "add" && permission_exists('contact_add')) {
						$contact_uuid = uuid();
						$sql = "insert into v_contacts ";
						$sql .= "( ";
						$sql .= "domain_uuid, ";
						$sql .= "contact_uuid, ";
						$sql .= "contact_type, ";
						$sql .= "contact_organization, ";
						$sql .= "contact_name_prefix, ";
						$sql .= "contact_name_given, ";
						$sql .= "contact_name_middle, ";
						$sql .= "contact_name_family, ";
						$sql .= "contact_name_suffix, ";
						$sql .= "contact_nickname, ";
						$sql .= "contact_title, ";
						$sql .= "contact_category, ";
						$sql .= "contact_role, ";
						$sql .= "contact_time_zone, ";
						$sql .= "contact_note, ";
						$sql .= "last_mod_date, ";
						$sql .= "last_mod_user ";
						$sql .= ") ";
						$sql .= "values ";
						$sql .= "( ";
						$sql .= "'".$_SESSION['domain_uuid']."', ";
						$sql .= "'".$contact_uuid."', ";
						$sql .= "'".$contact_type."', ";
						$sql .= "'".$contact_organization."', ";
						$sql .= "'".$contact_name_prefix."', ";
						$sql .= "'".$contact_name_given."', ";
						$sql .= "'".$contact_name_middle."', ";
						$sql .= "'".$contact_name_family."', ";
						$sql .= "'".$contact_name_suffix."', ";
						$sql .= "'".$contact_nickname."', ";
						$sql .= "'".$contact_title."', ";
						$sql .= "'".$contact_category."', ";
						$sql .= "'".$contact_role."', ";
						$sql .= "'".$contact_time_zone."', ";
						$sql .= "'".$contact_note."', ";
						$sql .= "now(), ";
						$sql .= "'".$_SESSION['username']."' ";
						$sql .= ")";
						$db->exec(check_sql($sql));
						unset($sql);

						$_SESSION["message"] = $text['message-add'];
						$location = "contact_edit.php?id=".$contact_uuid;
					} //if ($action == "add")

				//update the contact
					if ($action == "update" && permission_exists('contact_edit')) {
						$sql = "update v_contacts set ";
						$sql .= "contact_type = '".$contact_type."', ";
						$sql .= "contact_organization = '".$contact_organization."', ";
						$sql .= "contact_name_prefix = '".$contact_name_prefix."', ";
						$sql .= "contact_name_given = '".$contact_name_given."', ";
						$sql .= "contact_name_middle = '".$contact_name_middle."', ";
						$sql .= "contact_name_family = '".$contact_name_family."', ";
						$sql .= "contact_name_suffix = '".$contact_name_suffix."', ";
						$sql .= "contact_nickname = '".$contact_nickname."', ";
						$sql .= "contact_title = '".$contact_title."', ";
						$sql .= "contact_category = '".$contact_category."', ";
						$sql .= "contact_role = '".$contact_role."', ";
						$sql .= "contact_time_zone = '".$contact_time_zone."', ";
						$sql .= "contact_note = '".$contact_note."', ";
						$sql .= "last_mod_date = now(), ";
						$sql .= "last_mod_user = '".$_SESSION['username']."' ";
						$sql .= "where domain_uuid = '".$domain_uuid."' ";
						$sql .= "and contact_uuid = '".$contact_uuid."' ";
						$db->exec(check_sql($sql));
						unset($sql);

						$_SESSION["message"] = $text['message-update'];
						$location = "contact_edit.php?id=".$contact_uuid;
					} //if ($action == "update")

				//assign the contact to the user that added the contact
					if ($action == "add" && !permission_exists('contact_user_add')) {
						$user_uuid = $_SESSION["user_uuid"];
					}

				//add user to contact users table
					if ($user_uuid != '') {
						$contact_user_uuid = uuid();
						$sql = "insert into v_contact_users ";
						$sql .= "(";
						$sql .= "domain_uuid, ";
						$sql .= "contact_user_uuid, ";
						$sql .= "contact_uuid, ";
						$sql .= "user_uuid ";
						$sql .= ") ";
						$sql .= "values ";
						$sql .= "(";
						$sql .= "'$domain_uuid', ";
						$sql .= "'$contact_user_uuid', ";
						$sql .= "'$contact_uuid', ";
						$sql .= "'$user_uuid' ";
						$sql .= ")";
						if (permission_exists('contact_user_add')) {
							$db->exec(check_sql($sql));
						}
						elseif ($action == "add") {
							//add the contact to the user that created it
							$db->exec(check_sql($sql));
						}
						unset($sql);
					}

				//assign the contact to the group
					if ($group_uuid != '' && permission_exists('contact_group_add')) {
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

				//handle redirect
					if ($_POST['submit'] == $text['button-add']) {
						$location = "contact_edit.php?id=".$contact_uuid;
					}

				//redirect the browser
					header("Location: ".$location);
					return;

			} //if ($_POST["persistformvar"] != "true")
	} //(count($_POST)>0 && strlen($_POST["persistformvar"]) == 0)

//pre-populate the form
	if (count($_GET) > 0 && $_POST["persistformvar"] != "true") {
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
			$contact_name_prefix = $row["contact_name_prefix"];
			$contact_name_given = $row["contact_name_given"];
			$contact_name_middle = $row["contact_name_middle"];
			$contact_name_family = $row["contact_name_family"];
			$contact_name_suffix = $row["contact_name_suffix"];
			$contact_nickname = $row["contact_nickname"];
			$contact_title = $row["contact_title"];
			$contact_category = $row["contact_category"];
			$contact_role = $row["contact_role"];
			$contact_time_zone = $row["contact_time_zone"];
			$contact_note = $row["contact_note"];
		}
		unset ($prep_statement, $sql);
	}

//get the users array
	$sql = "SELECT * FROM v_users ";
	$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
	$sql .= "order by username asc ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$users = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	unset($prep_statement, $sql);

//get the users assigned to this contact
	$sql = "SELECT u.username, u.user_uuid, a.contact_user_uuid FROM v_contacts as c, v_users as u, v_contact_users as a ";
	$sql .= "where c.contact_uuid = '".$contact_uuid."' ";
	$sql .= "and c.domain_uuid = '".$_SESSION['domain_uuid']."' ";
	$sql .= "and u.user_uuid = a.user_uuid ";
	$sql .= "and c.contact_uuid = a.contact_uuid ";
	$sql .= "order by u.username asc ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$contact_users = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	unset($prep_statement, $sql);

//show the header
	require_once "resources/header.php";
	if ($action == "update") {
		$document['title'] = $text['title-contact-edit'];
	}
	else if ($action == "add") {
		$document['title'] = $text['title-contact-add'];
	}

//qr code generation
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
	if (isset($_SESSION['theme']['qr_image'])) {
		echo "<img id='img-buffer' src='".$_SESSION["theme"]["qr_image"]["text"]."' style='display: none;'>";
	}
	else {
		echo "<img id='img-buffer' src='".PROJECT_PATH."/themes/".$_SESSION["domain"]["template"]["name"]."/images/qr_code.png' style='display: none;'>";
	}

//show the content
	echo "<form method='post' name='frm' action=''>\n";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "<td valign='top' align='left' width='30%' nowrap='nowrap'><b>";
	switch ($action) {
		case "add" : 	echo $text['header-contact-add'];	break;
		case "update" :	echo $text['header-contact-edit'];	break;
	}
	echo "</b></td>\n";
	echo "<td valign='top' width='70%' align='right'>\n";
	echo "	<input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"window.location='contacts.php?".$_GET["query_string"]."'\" value='".$text['button-back']."'>\n";
	if ($action == "update") {
		if (permission_exists('contact_time_add')) {
			//detect timer state (and start time)
			$sql = "select ";
			$sql .= "time_start ";
			$sql .= "from v_contact_times ";
			$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
			$sql .= "and user_uuid = '".$_SESSION['user']['user_uuid']."' ";
			$sql .= "and contact_uuid = '".$contact_uuid."' ";
			$sql .= "and time_start is not null ";
			$sql .= "and time_stop is null ";
			$prep_statement = $db->prepare(check_sql($sql));
			$prep_statement->execute();
			$result = $prep_statement->fetch(PDO::FETCH_NAMED);
			if ($result['time_start'] != '') {
				$time_start = $result['time_start'];
				$btn_mod = "style='background-color: #3693df; background-image: none;'";
			}
			unset ($sql, $prep_statement, $result);
			echo "	<input type='button' class='btn' ".$btn_mod." alt='".$text['button-timer']."' ".(($time_start != '') ? "title='".$time_start."'" : null)." onclick=\"window.open('contact_timer.php?domain_uuid=".$domain_uuid."&contact_uuid=".$contact_uuid."','contact_time_".$contact_uuid."','width=300, height=375, top=30, left='+(screen.width - 350)+', menubar=no, scrollbars=no, status=no, toolbar=no, resizable=no');\" value='".$text['button-timer']."'>\n";
		}
		echo "	<input type='button' class='btn' name='' alt='".$text['button-qr_code']."' onclick=\"$('#qr_code_container').fadeIn(400);\" value='".$text['button-qr_code']."'>\n";
		echo "	<input type='button' class='btn' name='' alt='".$text['button-vcard']."' onclick=\"window.location='contacts_vcard.php?id=".$contact_uuid."&type=download'\" value='".$text['button-vcard']."'>\n";
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

	echo "<table border='0' cellpadding='0' cellspacing='0' width='100%'>\n";
	echo "<tr>\n";
	echo "<td width='40%' valign='top' align='left' nowrap='nowrap'>\n";

		echo "<table border='0' cellpadding='0' cellspacing='0' width='100%'>\n";
		echo "<tr>\n";
		echo "<td width='30%' class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-contact_type']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		if (is_array($_SESSION["contact"]["type"])) {
			sort($_SESSION["contact"]["type"]);
			echo "	<select class='formfld' name='contact_type'>\n";
			echo "		<option value=''></option>\n";
			foreach($_SESSION["contact"]["type"] as $row) {
				echo "	<option value='".$row."' ".(($row == $contact_type) ? "selected='selected'" : null).">".$row."</option>\n";
			}
			echo "	</select>\n";
		}
		else {
			echo "	<select class='formfld' name='contact_type'>\n";
			echo "		<option value=''></option>\n";
			echo "		<option value='customer' ".(($contact_type == "customer") ? "selected='selected'" : null).">".$text['option-contact_type_customer']."</option>\n";
			echo "		<option value='contractor' ".(($contact_type == "contractor") ? "selected='selected'" : null).">".$text['option-contact_type_contractor']."</option>\n";
			echo "		<option value='friend' ".(($contact_type == "friend") ? "selected='selected'" : null).">".$text['option-contact_type_friend']."</option>\n";
			echo "		<option value='lead' ".(($contact_type == "lead") ? "selected='selected'" : null).">".$text['option-contact_type_lead']."</option>\n";
			echo "		<option value='member' ".(($contact_type == "member") ? "selected='selected'" : null).">".$text['option-contact_type_member']."</option>\n";
			echo "		<option value='family' ".(($contact_type == "family") ? "selected='selected'" : null).">".$text['option-contact_type_family']."</option>\n";
			echo "		<option value='subscriber' ".(($contact_type == "subscriber") ? "selected='selected'" : null).">".$text['option-contact_type_subscriber']."</option>\n";
			echo "		<option value='supplier' ".(($contact_type == "supplier") ? "selected='selected'" : null).">".$text['option-contact_type_supplier']."</option>\n";
			echo "		<option value='provider' ".(($contact_type == "provider") ? "selected='selected'" : null).">".$text['option-contact_type_provider']."</option>\n";
			echo "		<option value='user' ".(($contact_type == "user") ? "selected='selected'" : null).">".$text['option-contact_type_user']."</option>\n";
			echo "		<option value='volunteer' ".(($contact_type == "volunteer") ? "selected='selected'" : null).">".$text['option-contact_type_volunteer']."</option>\n";
			echo "	</select>\n";
		}
//		echo "<br />\n";
//		echo $text['description-contact_type']."\n";
		echo "</td>\n";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-contact_organization']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<input class='formfld' type='text' name='contact_organization' maxlength='255' value=\"$contact_organization\">\n";
// 		echo "<br />\n";
// 		echo $text['description-contact_organization']."\n";
		echo "</td>\n";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-contact_name_prefix']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<input class='formfld' type='text' name='contact_name_prefix' maxlength='255' value=\"$contact_name_prefix\">\n";
// 		echo "<br />\n";
// 		echo $text['description-contact_name_prefix']."\n";
		echo "</td>\n";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-contact_name_given']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<input class='formfld' type='text' name='contact_name_given' maxlength='255' value=\"$contact_name_given\">\n";
// 		echo "<br />\n";
// 		echo $text['description-contact_name_given']."\n";
		echo "</td>\n";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-contact_name_middle']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<input class='formfld' type='text' name='contact_name_middle' maxlength='255' value=\"$contact_name_middle\">\n";
// 		echo "<br />\n";
// 		echo $text['description-contact_name_middle']."\n";
		echo "</td>\n";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-contact_name_family']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<input class='formfld' type='text' name='contact_name_family' maxlength='255' value=\"$contact_name_family\">\n";
// 		echo "<br />\n";
// 		echo $text['description-contact_name_family']."\n";
		echo "</td>\n";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-contact_name_suffix']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<input class='formfld' type='text' name='contact_name_suffix' maxlength='255' value=\"$contact_name_suffix\">\n";
// 		echo "<br />\n";
// 		echo $text['description-contact_name_suffix']."\n";
		echo "</td>\n";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-contact_nickname']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<input class='formfld' type='text' name='contact_nickname' maxlength='255' value=\"$contact_nickname\">\n";
// 		echo "<br />\n";
// 		echo $text['description-contact_nickname']."\n";
		echo "</td>\n";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-contact_title']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		if (is_array($_SESSION["contact"]["title"])) {
			sort($_SESSION["contact"]["title"]);
			echo "	<select class='formfld' name='contact_title'>\n";
			echo "	<option value=''></option>\n";
			foreach($_SESSION["contact"]["title"] as $row) {
				echo "	<option value='".$row."' ".(($row == $contact_title) ? "selected='selected'" : null).">".$row."</option>\n";
			}
			echo "	</select>\n";
		}
		else {
			echo "	<input class='formfld' type='text' name='contact_title' maxlength='255' value=\"$contact_title\">\n";
		}
// 		echo "<br />\n";
// 		echo $text['description-contact_title']."\n";
		echo "</td>\n";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-contact_category']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		if (is_array($_SESSION["contact"]["category"])) {
			sort($_SESSION["contact"]["category"]);
			echo "	<select class='formfld' name='contact_category'>\n";
			echo "	<option value=''></option>\n";
			foreach($_SESSION["contact"]["category"] as $row) {
				echo "	<option value='".$row."' ".(($row == $contact_category) ? "selected='selected'" : null).">".$row."</option>\n";
			}
			echo "	</select>\n";
		}
		else {
			echo "	<input class='formfld' type='text' name='contact_category' maxlength='255' value=\"$contact_category\">\n";
		}
// 		echo "<br />\n";
// 		echo $text['description-contact_category']."\n";
		echo "</td>\n";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-contact_role']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		if (is_array($_SESSION["contact"]["role"])) {
			sort($_SESSION["contact"]["role"]);
			echo "	<select class='formfld' name='contact_role'>\n";
			echo "	<option value=''></option>\n";
			foreach($_SESSION["contact"]["role"] as $row) {
				echo "	<option value='".$row."' ".(($row == $contact_role) ? "selected='selected'" : null).">".$row."</option>\n";
			}
			echo "	</select>\n";
		}
		else {
			echo "	<input class='formfld' type='text' name='contact_role' maxlength='255' value=\"$contact_role\">\n";
		}
// 		echo "<br />\n";
// 		echo $text['description-contact_role']."\n";
		echo "</td>\n";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-contact_time_zone']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<input class='formfld' type='text' name='contact_time_zone' maxlength='255' value=\"$contact_time_zone\">\n";
// 		echo "<br />\n";
// 		echo $text['description-contact_time_zone']."\n";
		echo "</td>\n";
		echo "</tr>\n";

		if (permission_exists('contact_user_edit')) {
			echo "	<tr>";
			echo "		<td class='vncell' valign='top'>".$text['label-users']."</td>";
			echo "		<td class='vtable' align='left'>";
			if ($action == "update") {
				echo "			<table border='0' style='width : 235px;'>\n";
				foreach($contact_users as $field) {
					echo "			<tr>\n";
					echo "				<td class='vtable'>".$field['username']."</td>\n";
					echo "				<td style='width: 25px;' align='right'>\n";
					if (permission_exists('contact_user_delete')) {
						echo "					<a href='contact_user_delete.php?id=".$field['contact_user_uuid']."&contact_uuid=".$contact_uuid."' alt='delete' onclick=\"return confirm(".$text['confirm-delete'].")\">$v_link_label_delete</a>\n";
					}
					echo "				</td>\n";
					echo "			</tr>\n";
				}
				echo "			</table>\n";
			}
			echo "			<br />\n";
			if (permission_exists('contact_user_add')) {
				echo "			<select name=\"user_uuid\" class='formfld' style='width: auto;'>\n";
				echo "			<option value=\"\"></option>\n";
				foreach($users as $field) {
					echo "			<option value='".$field['user_uuid']."'>".$field['username']."</option>\n";
				}
				echo "			</select>";
				if ($action == "update") {
					echo "			<input type=\"submit\" class='btn' value=\"".$text['button-add']."\">\n";
				}
				unset($users);
				echo "			<br>\n";
				echo "			".$text['description-users']."\n";
			}
			echo "		</td>";
			echo "	</tr>";
		}
		echo "</table>";

		if (permission_exists('contact_group_view')) {
			$contact_shared = 'true';
			echo "<div id='div_groups' ".(($contact_shared != 'true') ? "style='display: none;'" : null).">\n";
			echo "<table border='0' cellpadding='0' cellspacing='0' width='100%'>\n";
			echo "<tr>";
			echo "	<td width='30%' class='vncell' valign='top'>".$text['label-groups']."</td>";
			echo "	<td width='70%' class='vtable'>";
			$sql = "select ";
			$sql .= "g.*, ";
			$sql .= "cg.contact_group_uuid ";
			$sql .= "from ";
			$sql .= "v_groups as g, ";
			$sql .= "v_contact_groups as cg ";
			$sql .= "where ";
			$sql .= "cg.group_uuid = g.group_uuid ";
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
							echo "	<a href='contact_group_delete.php?id=".$field['contact_group_uuid']."&contact_uuid=".$contact_uuid."' alt='".$text['button-delete']."' onclick=\"return confirm('".$text['confirm-delete']."')\">$v_link_label_delete</a>\n";
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
				$sql .= "or domain_uuid is null ";
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
		echo "	".$text['label-contact_note']."\n";
		echo "</td>\n";
		echo "<td width='70%' class='vtable' align='left'>\n";
		echo "  <textarea class='formfld' style='width: 100%; height: 80px;' name='contact_note'>".$contact_note."</textarea>\n";
// 		echo "<br />\n";
// 		echo $text['description-contact_note']."\n";
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
		echo "<td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>";
		echo "<td width='60%' class='' valign='top' align='center'>\n";
		//echo "	<img src='contacts_vcard.php?id=$contact_uuid&type=image' width='90%'><br /><br />\n";
		if (permission_exists('contact_phone_view')) { require "contact_phones.php"; }
		if (permission_exists('contact_address_view')) { require "contact_addresses.php"; }
		if (permission_exists('contact_email_view')) { require "contact_emails.php"; }
		if (permission_exists('contact_url_view')) { require "contact_urls.php"; }
		if (permission_exists('contact_extension_view')) { require "contact_extensions.php"; }
		if (permission_exists('contact_relation_view')) { require "contact_relations.php"; }
		if (permission_exists('contact_note_view')) { require "contact_notes.php"; }
		if (permission_exists('contact_time_view')) { require "contact_times.php"; }
		if (permission_exists('contact_setting_view')) { require "contact_settings.php"; }
		echo "</td>\n";
	}

	echo "</tr>\n";
	echo "</table>\n";
	echo "<br><br>";
	echo "</form>";

//include the footer
	require_once "resources/footer.php";

?>