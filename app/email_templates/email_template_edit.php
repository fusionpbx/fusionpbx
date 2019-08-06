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
 Portions created by the Initial Developer are Copyright (C) 2018
 the Initial Developer. All Rights Reserved.
*/

//includes
	require_once "root.php";
	require_once "resources/require.php";

//check permissions
	require_once "resources/check_auth.php";
	if (permission_exists('email_template_add') || permission_exists('email_template_edit')) {
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
		$email_template_uuid = $_REQUEST["id"];
	}
	else {
		$action = "add";
	}

//get http post variables and set them to php variables
	if (is_array($_POST)) {
		$domain_uuid = $_POST["domain_uuid"];
		$template_language = $_POST["template_language"];
		$template_category = $_POST["template_category"];
		$template_subcategory = $_POST["template_subcategory"];
		$template_subject = $_POST["template_subject"];
		$template_body = $_POST["template_body"];
		$template_type = $_POST["template_type"];
		$template_enabled = $_POST["template_enabled"];
		$template_description = $_POST["template_description"];
	}

//process the user data and save it to the database
	if (count($_POST) > 0 && strlen($_POST["persistformvar"]) == 0) {

		//get the uuid from the POST
			if ($action == "update") {
				$email_template_uuid = $_POST["email_template_uuid"];
			}

		//check for all required data
			$msg = '';
			if (strlen($template_language) == 0) { $msg .= $text['message-required']." ".$text['label-template_language']."<br>\n"; }
			if (strlen($template_category) == 0) { $msg .= $text['message-required']." ".$text['label-template_category']."<br>\n"; }
			//if (strlen($template_subcategory) == 0) { $msg .= $text['message-required']." ".$text['label-template_subcategory']."<br>\n"; }
			if (strlen($template_subject) == 0) { $msg .= $text['message-required']." ".$text['label-template_subject']."<br>\n"; }
			if (strlen($template_body) == 0) { $msg .= $text['message-required']." ".$text['label-template_body']."<br>\n"; }
			//if (strlen($domain_uuid) == 0) { $msg .= $text['message-required']." ".$text['label-domain_uuid']."<br>\n"; }
			//if (strlen($template_type) == 0) { $msg .= $text['message-required']." ".$text['label-template_type']."<br>\n"; }
			if (strlen($template_enabled) == 0) { $msg .= $text['message-required']." ".$text['label-template_enabled']."<br>\n"; }
			//if (strlen($template_description) == 0) { $msg .= $text['message-required']." ".$text['label-template_description']."<br>\n"; }
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

		//add the email_template_uuid
			if (!is_uuid($_POST["email_template_uuid"])) {
				$email_template_uuid = uuid();
				$_POST["email_template_uuid"] = $email_template_uuid;
			}

		//prepare the array
			$array['email_templates'][0] = $_POST;

		//save to the data
			$database = new database;
			$database->app_name = 'email_templates';
			$database->app_uuid = '8173e738-2523-46d5-8943-13883befd2fd';
			if (strlen($email_template_uuid) > 0) {
				$database->uuid($email_template_uuid);
			}
			$database->save($array);
			$message = $database->message;

		//redirect the user
			if (isset($action)) {
				if ($action == "add") {
					message::add($text['message-add']);
				}
				if ($action == "update") {
					message::add($text['message-update']);
				}
				header('Location: email_template_edit.php?id='.escape($email_template_uuid));
				exit;
			}
	}

//pre-populate the form
	if (is_array($_GET) && $_POST["persistformvar"] != "true") {
		$email_template_uuid = $_GET["id"];
		$sql = "select * from v_email_templates ";
		$sql .= "where email_template_uuid = :email_template_uuid ";
		//$sql .= "and domain_uuid = :domain_uuid ";
		$parameters['email_template_uuid'] = $email_template_uuid;
		//$parameters['domain_uuid'] = $domain_uuid;
		$database = new database;
		$row = $database->select($sql, $parameters, 'row');
		if (is_array($row) && @sizeof($row) != 0) {
			$domain_uuid = $row["domain_uuid"];
			$template_language = $row["template_language"];
			$template_category = $row["template_category"];
			$template_subcategory = $row["template_subcategory"];
			$template_subject = $row["template_subject"];
			$template_body = $row["template_body"];
			$template_type = $row["template_type"];
			$template_enabled = $row["template_enabled"];
			$template_description = $row["template_description"];
		}
		unset($sql, $parameters, $row);
	}

//show the header
	require_once "resources/header.php";

//show the content
	echo "<form name='frm' id='frm' method='post' action=''>\n";
	echo "<table width='100%'  border='0' cellpadding='0' cellspacing='0'>\n";

	echo "<tr>\n";
	echo "<td align='left' width='30%' nowrap='nowrap' valign='top'><b>".$text['title-email_template']."</b><br><br></td>\n";
	echo "<td width='70%' align='right' valign='top'>\n";
	echo "	<input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"window.location='email_templates.php'\" value='".$text['button-back']."'>";
	echo "	<input type='submit' class='btn' value='".$text['button-save']."'>";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-template_language']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<input class='formfld' type='text' name='template_language' maxlength='255' value=\"".escape($template_language)."\">\n";
	echo "<br />\n";
	echo $text['description-template_language']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-template_category']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<input class='formfld' type='text' name='template_category' maxlength='255' value=\"".escape($template_category)."\">\n";
	echo "<br />\n";
	echo $text['description-template_category']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-template_subcategory']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<input class='formfld' type='text' name='template_subcategory' maxlength='255' value=\"".escape($template_subcategory)."\">\n";
	echo "<br />\n";
	echo $text['description-template_subcategory']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-template_subject']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<input class='formfld' type='text' name='template_subject' maxlength='255' value=\"".escape($template_subject)."\">\n";
	echo "<br />\n";
	echo $text['description-template_subject']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-template_body']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<textarea class='formfld' name='template_body' style='min-width: 100%; height: 350px; font-family: monospace;'>".escape($template_body)."</textarea>\n";
	echo "<br />\n";
	echo $text['description-template_body']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-domain']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<select class='formfld' name='domain_uuid'>\n";
	if (!is_uuid($domain_uuid)) {
		echo "		<option value='' selected='selected'>".$text['label-global']."</option>\n";
	}
	else {
		echo "		<option value=''>".$text['label-global']."</option>\n";
	}
	foreach ($_SESSION['domains'] as $row) {
		if ($row['domain_uuid'] == $domain_uuid) {
			echo "		<option value='".escape($row['domain_uuid'])."' selected='selected'>".escape($row['domain_name'])."</option>\n";
		}
		else {
			echo "		<option value='".escape($row['domain_uuid'])."'>".escape($row['domain_name'])."</option>\n";
		}
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-domain_uuid']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-template_type']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<input class='formfld' type='text' name='template_type' maxlength='255' value=\"".escape($template_type)."\">\n";
	echo "<br />\n";
	echo $text['description-template_type']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-template_enabled']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<select class='formfld' name='template_enabled'>\n";
	if ($template_enabled == "true") {
		echo "		<option value='true' selected='selected'>".$text['label-true']."</option>\n";
	}
	else {
		echo "		<option value='true'>".$text['label-true']."</option>\n";
	}
	if ($template_enabled == "false") {
		echo "		<option value='false' selected='selected'>".$text['label-false']."</option>\n";
	}
	else {
		echo "		<option value='false'>".$text['label-false']."</option>\n";
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-template_enabled']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-template_description']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<input class='formfld' type='text' name='template_description' maxlength='255' value=\"".escape($template_description)."\">\n";
	echo "<br />\n";
	echo $text['description-template_description']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	if ($action == "update") {
		echo "				<input type='hidden' name='email_template_uuid' value='".escape($email_template_uuid)."'>\n";
	}
	echo "				<input type='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "		</td>\n";
	echo "	</tr>";
	echo "</table>";
	echo "</form>";
	echo "<br /><br />";

//include the footer
	require_once "resources/footer.php";

?>
