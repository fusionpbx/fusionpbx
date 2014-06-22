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
if (permission_exists('voicemail_add') || permission_exists('voicemail_edit')) {
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
		$voicemail_uuid = check_str($_REQUEST["id"]);
	}
	else {
		$action = "add";
	}

//get http variables and set them to php variables
	$referer_path = check_str($_REQUEST["referer_path"]);
	$referer_query = check_str($_REQUEST["referer_query"]);
	if (count($_POST)>0) {
		//set the variables from the HTTP values
			$voicemail_id = check_str($_POST["voicemail_id"]);
			$voicemail_password = check_str($_POST["voicemail_password"]);
			$greeting_id = check_str($_POST["greeting_id"]);
			$voicemail_mail_to = check_str($_POST["voicemail_mail_to"]);
			$voicemail_attach_file = check_str($_POST["voicemail_attach_file"]);
			$voicemail_local_after_email = check_str($_POST["voicemail_local_after_email"]);
			$voicemail_enabled = check_str($_POST["voicemail_enabled"]);
			$voicemail_description = check_str($_POST["voicemail_description"]);
		//remove the space
			$voicemail_mail_to = str_replace(" ", "", $voicemail_mail_to);
	}

//unassign the voicemail id copy from the voicemail id
	if ($_GET["a"] == "delete" && strlen($voicemail_uuid) > 0 && strlen($_REQUEST["voicemail_destination_uuid"]) > 0) {
		//set the variables
			$voicemail_destination_uuid = check_str($_REQUEST["voicemail_destination_uuid"]);
		//delete the voicemail from the destionations
			$sqld = "
				delete from
					v_voicemail_destinations as d
				where
					d.voicemail_destination_uuid = '".$voicemail_destination_uuid."' and
					d.voicemail_uuid = '".$voicemail_uuid."'";
			$db->exec(check_sql($sqld));
		//redirect the browser
			$_SESSION["message"] = $text['message-delete'];
			header("Location: voicemail_edit.php?id=".$voicemail_uuid);
			return;
	}

//assign the voicemail id copy to the voicemail id
	if (strlen($voicemail_uuid) > 0 && strlen($_REQUEST["voicemail_uuid_copy"]) > 0) {
		//set the variables
			$voicemail_uuid_copy = check_str($_REQUEST["voicemail_uuid_copy"]);
		//assign the user to the extension
			$sqli = "
				insert into
					v_voicemail_destinations
				(
					voicemail_destination_uuid,
					voicemail_uuid,
					voicemail_uuid_copy
				)
				values
				(
					'".uuid()."',
					'".$voicemail_uuid."',
					'".$voicemail_uuid_copy."'
				)";
			$db->exec(check_sql($sqli));
		//redirect the browser
//			$_SESSION["message"] = $text['message-add'];
//			header("Location: voicemail_edit.php?id=".$voicemail_uuid);
//			return;
	}

if (count($_POST) > 0 && strlen($_POST["persistformvar"]) == 0) {

	$msg = '';
	if ($action == "update") {
		$voicemail_uuid = check_str($_POST["voicemail_uuid"]);
	}

	//check for all required data
		//if (strlen($domain_uuid) == 0) { $msg .= $text['message-required']." ".$text['label-domain_uuid']."<br>\n"; }
		//if (strlen($voicemail_uuid) == 0) { $msg .= $text['message-required']." ".$text['label-voicemail_uuid']."<br>\n"; }
		//if (strlen($voicemail_id) == 0) { $msg .= $text['message-required']." ".$text['label-voicemail_id']."<br>\n"; }
		//if (strlen($voicemail_password) == 0) { $msg .= $text['message-required']." ".$text['label-voicemail_password']."<br>\n"; }
		//if (strlen($greeting_id) == 0) { $msg .= $text['message-required']." ".$text['label-greeting_id']."<br>\n"; }
		//if (strlen($voicemail_mail_to) == 0) { $msg .= $text['message-required']." ".$text['label-voicemail_mail_to']."<br>\n"; }
		//if (strlen($voicemail_attach_file) == 0) { $msg .= $text['message-required']." ".$text['label-voicemail_attach_file']."<br>\n"; }
		//if (strlen($voicemail_local_after_email) == 0) { $msg .= $text['message-required']." ".$text['label-voicemail_local_after_email']."<br>\n"; }
		//if (strlen($voicemail_enabled) == 0) { $msg .= $text['message-required']." ".$text['label-voicemail_enabled']."<br>\n"; }
		//if (strlen($voicemail_description) == 0) { $msg .= $text['message-required']." ".$text['label-voicemail_description']."<br>\n"; }
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
			if ($action == "add" && permission_exists('voicemail_add')) {
				$sql = "insert into v_voicemails ";
				$sql .= "(";
				$sql .= "domain_uuid, ";
				$sql .= "voicemail_uuid, ";
				$sql .= "voicemail_id, ";
				$sql .= "voicemail_password, ";
				if (strlen($greeting_id) > 0) {
					$sql .= "greeting_id, ";
				}
				$sql .= "voicemail_mail_to, ";
				$sql .= "voicemail_attach_file, ";
				$sql .= "voicemail_local_after_email, ";
				$sql .= "voicemail_enabled, ";
				$sql .= "voicemail_description ";
				$sql .= ")";
				$sql .= "values ";
				$sql .= "(";
				$sql .= "'$domain_uuid', ";
				$sql .= "'".uuid()."', ";
				$sql .= "'$voicemail_id', ";
				$sql .= "'$voicemail_password', ";
				if (strlen($greeting_id) > 0) {
					$sql .= "'$greeting_id', ";
				}
				$sql .= "'$voicemail_mail_to', ";
				$sql .= "'$voicemail_attach_file', ";
				$sql .= "'$voicemail_local_after_email', ";
				$sql .= "'$voicemail_enabled', ";
				$sql .= "'$voicemail_description' ";
				$sql .= ")";
				$db->exec(check_sql($sql));
				unset($sql);

				$_SESSION["message"] = $text['message-add'];
				header("Location: voicemails.php");
				return;
			} //if ($action == "add")

			if ($action == "update" && permission_exists('voicemail_edit')) {
				$sql = "update v_voicemails set ";
				$sql .= "voicemail_id = '$voicemail_id', ";
				$sql .= "voicemail_password = '$voicemail_password', ";
				if (strlen($greeting_id) > 0) {
					$sql .= "greeting_id = '$greeting_id', ";
				}
				else {
					$sql .= "greeting_id = null, ";
				}
				$sql .= "voicemail_mail_to = '$voicemail_mail_to', ";
				$sql .= "voicemail_attach_file = '$voicemail_attach_file', ";
				$sql .= "voicemail_local_after_email = '$voicemail_local_after_email', ";
				$sql .= "voicemail_enabled = '$voicemail_enabled', ";
				$sql .= "voicemail_description = '$voicemail_description' ";
				$sql .= "where domain_uuid = '$domain_uuid' ";
				$sql .= "and voicemail_uuid = '$voicemail_uuid'";
				$db->exec(check_sql($sql));
				unset($sql);

				$_SESSION["message"] = $text['message-update'];
				if ($referer_path == "/app/voicemails/voicemail_messages.php") {
					header("Location: voicemail_messages.php?".$referer_query);
				}
				else {
					header("Location: voicemail_edit.php?id=".$voicemail_uuid);
				}
				return;
			} //if ($action == "update")
		} //if ($_POST["persistformvar"] != "true")
} //(count($_POST)>0 && strlen($_POST["persistformvar"]) == 0)

//pre-populate the form
	if (count($_GET)>0 && $_POST["persistformvar"] != "true") {
		$voicemail_uuid = check_str($_GET["id"]);
		$sql = "select * from v_voicemails ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$sql .= "and voicemail_uuid = '$voicemail_uuid' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		foreach ($result as &$row) {
			$voicemail_id = $row["voicemail_id"];
			$voicemail_password = $row["voicemail_password"];
			$greeting_id = $row["greeting_id"];
			$voicemail_mail_to = $row["voicemail_mail_to"];
			$voicemail_attach_file = $row["voicemail_attach_file"];
			$voicemail_local_after_email = $row["voicemail_local_after_email"];
			$voicemail_enabled = $row["voicemail_enabled"];
			$voicemail_description = $row["voicemail_description"];
			break; //limit to 1 row
		}
		unset ($prep_statement);
	}

//remove the spaces
	$voicemail_mail_to = str_replace(" ", "", $voicemail_mail_to);

//set defaults
	if (strlen($voicemail_attach_file) == 0) { $voicemail_attach_file = "true"; }
	if (strlen($voicemail_local_after_email) == 0) { $voicemail_local_after_email = "true"; }
	if (strlen($voicemail_enabled) == 0) { $voicemail_enabled = "true"; }

//show the header
	require_once "resources/header.php";

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
	echo "<td align='left' width='30%' nowrap='nowrap'><b>".$text['title-voicemail']."</b></td>\n";
	echo "<td width='70%' align='right'>\n";
	echo "	<input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"javascript:history.back();\" value='".$text['button-back']."'>\n";
	echo "	<input type='submit' name='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-voicemail_id'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='voicemail_id' maxlength='255' value='$voicemail_id'>\n";
	echo "<br />\n";
	echo $text['description-voicemail_id']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-voicemail_password'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='password' name='voicemail_password' id='password' onmouseover=\"this.type='text';\" onfocus=\"this.type='text';\" onmouseout=\"if (!$(this).is(':focus')) { this.type='password'; }\" onblur=\"this.type='password';\" autocomplete='off' maxlength='50' value=\"$voicemail_password\">\n";
	echo "<br />\n";
	echo $text['description-voicemail_password']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-greeting_id'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='greeting_id' maxlength='255' value='$greeting_id'>\n";
	echo "<br />\n";
	echo $text['description-greeting_id']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-voicemail_mail_to'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='voicemail_mail_to' maxlength='255' value=\"$voicemail_mail_to\">\n";
	echo "<br />\n";
	echo $text['description-voicemail_mail_to']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-voicemail_attach_file'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='voicemail_attach_file'>\n";
	if ($voicemail_attach_file == "true") {
		echo "	<option value='true' selected='selected'>".$text['label-true']."</option>\n";
	}
	else {
		echo "	<option value='true'>".$text['label-true']."</option>\n";
	}
	if ($voicemail_attach_file == "false") {
		echo "	<option value='false' selected='selected'>".$text['label-false']."</option>\n";
	}
	else {
		echo "	<option value='false'>".$text['label-false']."</option>\n";
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-voicemail_attach_file']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-voicemail_local_after_email'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='voicemail_local_after_email'>\n";
	if ($voicemail_local_after_email == "true") {
		echo "	<option value='true' selected='selected'>".$text['label-true']."</option>\n";
	}
	else {
		echo "	<option value='true'>".$text['label-true']."</option>\n";
	}
	if ($voicemail_local_after_email == "false") {
		echo "	<option value='false' selected='selected'>".$text['label-false']."</option>\n";
	}
	else {
		echo "	<option value='false'>".$text['label-false']."</option>\n";
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-voicemail_local_after_email']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	/*
	//still in development

	if ($action == "update") {
		echo "	<tr>";
		echo "		<td class='vncell' valign='top'>".$text['label-forward_destinations'].":</td>";
		echo "		<td class='vtable'>";

		echo "			<table width='52%'>\n";
		$sql = "
			select
				v.voicemail_id,
				d.voicemail_destination_uuid,
				d.voicemail_uuid_copy
			from
				v_voicemails as v,
				v_voicemail_destinations as d
			where
				d.voicemail_uuid_copy = v.voicemail_uuid and
				v.domain_uuid = '".$_SESSION['domain_uuid']."' and
				v.voicemail_enabled = 'true' and
				d.voicemail_uuid = '".$voicemail_uuid."'
			order by
				v.voicemail_id asc";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		$result_count = count($result);
		foreach($result as $field) {
			echo "			<tr>\n";
			echo "				<td class='vtable'>".$field['voicemail_id']."</td>\n";
			echo "				<td>\n";
			echo "					<a href='voicemail_edit.php?id=".$voicemail_uuid."&voicemail_destination_uuid=".$field['voicemail_destination_uuid']."&a=delete' alt='".$text['button-delete']."' onclick=\"return confirm('".$text['confirm-delete']."')\">$v_link_label_delete</a>\n";
			echo "				</td>\n";
			echo "			</tr>\n";
			$voicemail_uuid_copied[] = $field['voicemail_uuid_copy'];
		}
		echo "			</table>\n";

		if (sizeof($voicemail_uuid_copied) > 0) {
			// modify sql to remove already copied voicemail uuids from the list
			$sql_mod = " and v.voicemail_uuid not in ('".implode("','", $voicemail_uuid_copied)."') ";
		}
		echo "			<br />\n";
		$sql = "
			select
				v.voicemail_id,
				v.voicemail_uuid
			from
				v_voicemails as v
			where
				v.domain_uuid = '".$_SESSION['domain_uuid']."' and
				v.voicemail_enabled = 'true' and
				v.voicemail_uuid <> '".$voicemail_uuid."'
				".$sql_mod."
			order by
				v.voicemail_id asc";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		echo "			<select name=\"voicemail_uuid_copy\" class='formfld' style='width: auto;'>\n";
		echo "			<option value=\"\"></option>\n";
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		foreach($result as $field) {
			echo "			<option value='".$field['voicemail_uuid']."'>".$field['voicemail_id']."</option>\n";
		}
		echo "			</select>";
		echo "			<input type=\"submit\" class='btn' value=\"".$text['button-add']."\">\n";
		unset($sql, $result);
		echo "			<br>\n";
		echo "			".$text['description-forward_destinations']."\n";
		echo "			<br />\n";
		echo "		</td>";
		echo "	</tr>";
	}
	*/

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-voicemail_enabled'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='voicemail_enabled'>\n";
	if ($voicemail_enabled == "true") {
		echo "	<option value='true' selected='selected'>".$text['label-true']."</option>\n";
	}
	else {
		echo "	<option value='true'>".$text['label-true']."</option>\n";
	}
	if ($voicemail_enabled == "false") {
		echo "	<option value='false' selected='selected'>".$text['label-false']."</option>\n";
	}
	else {
		echo "	<option value='false'>".$text['label-false']."</option>\n";
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-voicemail_enabled']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-voicemail_description'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='voicemail_description' maxlength='255' value=\"$voicemail_description\">\n";
	echo "<br />\n";
	echo $text['description-voicemail_description']."\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	if ($action == "update") {
		echo "				<input type='hidden' name='voicemail_uuid' value='$voicemail_uuid'>\n";
	}
	$http_referer = parse_url($_SERVER["HTTP_REFERER"]);
	echo "				<input type='hidden' name='referer_path' value='".$http_referer['path']."'>\n";
	echo "				<input type='hidden' name='referer_query' value='".$http_referer['query']."'>\n";
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