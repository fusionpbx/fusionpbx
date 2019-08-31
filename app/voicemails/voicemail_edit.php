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
*/

//includes
	require_once "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('voicemail_add') || permission_exists('voicemail_edit')) {
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
		$voicemail_uuid = $_REQUEST["id"];
	}
	else {
		$action = "add";
	}

//get http variables and set them to php variables
	$referer_path = $_REQUEST["referer_path"];
	$referer_query = $_REQUEST["referer_query"];
	if (count($_POST)>0) {
		//set the variables from the HTTP values
			$voicemail_id = $_POST["voicemail_id"];
			$voicemail_password = $_POST["voicemail_password"];
			$greeting_id = $_POST["greeting_id"];
			$voicemail_options = $_POST["voicemail_options"];
			$voicemail_alternate_greet_id = $_POST["voicemail_alternate_greet_id"];
			$voicemail_mail_to = $_POST["voicemail_mail_to"];
			$voicemail_sms_to = $_POST["voicemail_sms_to"];
			$voicemail_transcription_enabled = $_POST["voicemail_transcription_enabled"];
			$voicemail_file = $_POST["voicemail_file"];
			$voicemail_local_after_email = $_POST["voicemail_local_after_email"];
			$voicemail_enabled = $_POST["voicemail_enabled"];
			$voicemail_description = $_POST["voicemail_description"];
			$voicemail_tutorial = $_POST["voicemail_tutorial"];
		//remove the space
			$voicemail_mail_to = str_replace(" ", "", $voicemail_mail_to);
	}

//unassign the voicemail id copy from the voicemail id
	if ($_GET["a"] == "delete" && is_uuid($voicemail_uuid) && is_uuid($_REQUEST["voicemail_destination_uuid"])) {
		//set the variables
			$voicemail_destination_uuid = $_REQUEST["voicemail_destination_uuid"];
		//build delete array
			$array['voicemail_destinations'][0]['voicemail_destination_uuid'] = $voicemail_destination_uuid;
			$array['voicemail_destinations'][0]['voicemail_uuid'] = $voicemail_uuid;
		//grant temporary permissions
			$p = new permissions;
			$p->add('voicemail_destination_delete', 'temp');
		//execute delete
			$database = new database;
			$database->app_name = 'voicemails';
			$database->app_uuid = 'b523c2d2-64cd-46f1-9520-ca4b4098e044';
			$database->delete($array);
			unset($array);
		//revoke temporary permissions
			$p->delete('voicemail_destination_delete', 'temp');
		//set message
			message::add($text['message-delete']);
		//redirect the browser
			header("Location: voicemail_edit.php?id=".$voicemail_uuid);
			exit;
	}

//assign the voicemail id copy to the voicemail id
	if (is_uuid($voicemail_uuid) && is_uuid($_REQUEST["voicemail_uuid_copy"])) {
		//set the variables
			$voicemail_uuid_copy = $_REQUEST["voicemail_uuid_copy"];
		//build insert array
			$array['voicemail_destinations'][0]['domain_uuid'] = $domain_uuid;
			$array['voicemail_destinations'][0]['voicemail_destination_uuid'] = uuid();
			$array['voicemail_destinations'][0]['voicemail_uuid'] = $voicemail_uuid;
			$array['voicemail_destinations'][0]['voicemail_uuid_copy'] = $voicemail_uuid_copy;
		//grant temporary permissions
			$p = new permissions;
			$p->add('voicemail_destination_add', 'temp');
		//execute insert
			$database = new database;
			$database->app_name = 'voicemails';
			$database->app_uuid = 'b523c2d2-64cd-46f1-9520-ca4b4098e044';
			$database->save($array);
			unset($array);
		//revoke temporary permissions
			$p->delete('voicemail_destination_add', 'temp');
		//set message
			message::add($text['message-add']);
	}

if (count($_POST) > 0 && strlen($_POST["persistformvar"]) == 0) {

	$msg = '';
	if ($action == "update") {
		$voicemail_uuid = $_POST["voicemail_uuid"];
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
			if ($action == "add" && permission_exists('voicemail_add')) {
				//begin insert array
					$voicemail_uuid = uuid();
					$array['voicemails'][0]['voicemail_uuid'] = $voicemail_uuid;
				//set message
					message::add($text['message-add']);
			}

			if ($action == "update" && permission_exists('voicemail_edit')) {
				//begin update array
					$array['voicemails'][0]['voicemail_uuid'] = $voicemail_uuid;
				//set message
					message::add($text['message-update']);
			}

			if (is_array($array) && @sizeof($array) != 0) {
				//add common array fields
					$array['voicemails'][0]['domain_uuid'] = $domain_uuid;
					$array['voicemails'][0]['voicemail_id'] = $voicemail_id;
					$array['voicemails'][0]['voicemail_password'] = $voicemail_password;
					$array['voicemails'][0]['greeting_id'] = $greeting_id != '' ? $greeting_id : null;
					$array['voicemails'][0]['voicemail_alternate_greet_id'] = $voicemail_alternate_greet_id != '' ? $voicemail_alternate_greet_id : null;
					$array['voicemails'][0]['voicemail_mail_to'] = $voicemail_mail_to;
					$array['voicemails'][0]['voicemail_sms_to'] = $voicemail_sms_to;
					$array['voicemails'][0]['voicemail_transcription_enabled'] = $voicemail_transcription_enabled;
					$array['voicemails'][0]['voicemail_tutorial'] = $voicemail_tutorial;
					$array['voicemails'][0]['voicemail_file'] = $voicemail_file;
					if (permission_exists('voicemail_local_after_email')) {
						$array['voicemails'][0]['voicemail_local_after_email'] = $voicemail_local_after_email;
					}
					$array['voicemails'][0]['voicemail_enabled'] = $voicemail_enabled;
					$array['voicemails'][0]['voicemail_description'] = $voicemail_description;
				//execute insert/update
					$database = new database;
					$database->app_name = 'voicemails';
					$database->app_uuid = 'b523c2d2-64cd-46f1-9520-ca4b4098e044';
					$database->save($array);
					unset($array);
			}


			// add voicemail options
				if (sizeof($voicemail_options) > 0) {
					foreach ($voicemail_options as $index => $voicemail_option) {
						if ($voicemail_option['voicemail_option_digits'] == '' || $voicemail_option['voicemail_option_param'] == '') { unset($voicemail_options[$index]); }
					}
					foreach ($voicemail_options as $index => $voicemail_option) {
						if (is_numeric($voicemail_option["voicemail_option_param"])) {
							//if numeric then add tranfer $1 XML domain_name
							$voicemail_option['voicemail_option_action'] = "menu-exec-app";
							$voicemail_option['voicemail_option_param'] = "transfer ".$voicemail_option["voicemail_option_param"]." XML ".$_SESSION['domain_name'];
						}
						else {
							//seperate the action and the param
							$option_array = explode(":", $voicemail_option["voicemail_option_param"]);
							$voicemail_option['voicemail_option_action'] = array_shift($option_array);
							$voicemail_option['voicemail_option_param'] = join(':', $option_array);
						}

						//build insert array
							$voicemail_option_uuid = uuid();
							$array['voicemail_options'][$index]['voicemail_option_uuid'] = $voicemail_option_uuid;
							$array['voicemail_options'][$index]['voicemail_uuid'] = $voicemail_uuid;
							$array['voicemail_options'][$index]['domain_uuid'] = $domain_uuid;
							$array['voicemail_options'][$index]['voicemail_option_digits'] = $voicemail_option['voicemail_option_digits'];
							$array['voicemail_options'][$index]['voicemail_option_action'] = $voicemail_option['voicemail_option_action'];
							$array['voicemail_options'][$index]['voicemail_option_param'] = $voicemail_option['voicemail_option_param'];
							$array['voicemail_options'][$index]['voicemail_option_order'] = $voicemail_option['voicemail_option_order'];
							$array['voicemail_options'][$index]['voicemail_option_description'] = $voicemail_option['voicemail_option_description'];
					}
					if (is_array($array) && @sizeof($array) != 0) {
						//grant temporary permissions
							$p = new permissions;
							$p->add('voicemail_option_add', 'temp');
						//execute inserts
							$database = new database;
							$database->app_name = 'voicemails';
							$database->app_uuid = 'b523c2d2-64cd-46f1-9520-ca4b4098e044';
							$database->save($array);
							unset($array);
						//revoke temporary permissions
							$p->delete('voicemail_option_add', 'temp');
					}
				}

			//redirect user
				if ($action == 'add') {
					header("Location: voicemails.php");
				}
				else if ($action == "update") {
					header("Location: voicemail_edit.php?id=".$voicemail_uuid);
				}
				exit;

		}
}

//initialize the destinations object
	$destination = new destinations;

//pre-populate the form
	if (count($_GET)>0 && is_uuid($_GET["id"]) && $_POST["persistformvar"] != "true") {
		$voicemail_uuid = $_GET["id"];
		$sql = "select * from v_voicemails ";
		$sql .= "where domain_uuid = :domain_uuid ";
		$sql .= "and voicemail_uuid = :voicemail_uuid ";
		$parameters['domain_uuid'] = $domain_uuid;
		$parameters['voicemail_uuid'] = $voicemail_uuid;
		$database = new database;
		$row = $database->select($sql, $parameters, 'row');
		if (is_array($row) && @sizeof($row) != 0) {
			$voicemail_id = $row["voicemail_id"];
			$voicemail_password = $row["voicemail_password"];
			$greeting_id = $row["greeting_id"];
			$voicemail_alternate_greet_id = $row["voicemail_alternate_greet_id"];
			$voicemail_mail_to = $row["voicemail_mail_to"];
			$voicemail_sms_to = $row["voicemail_sms_to"];
			$voicemail_transcription_enabled = $row["voicemail_transcription_enabled"];
			$voicemail_tutorial = $row["voicemail_tutorial"];
			$voicemail_file = $row["voicemail_file"];
			$voicemail_local_after_email = $row["voicemail_local_after_email"];
			$voicemail_enabled = $row["voicemail_enabled"];
			$voicemail_description = $row["voicemail_description"];
		}
		unset($sql, $parameters, $row);
	}
	else {
		$voicemail_file = $_SESSION['voicemail']['voicemail_file']['text'];
		$voicemail_local_after_email = $_SESSION['voicemail']['keep_local']['boolean'];
	}

//remove the spaces
	$voicemail_mail_to = str_replace(" ", "", $voicemail_mail_to);

//set defaults
	if (strlen($voicemail_local_after_email) == 0) { $voicemail_local_after_email = "true"; }
	if (strlen($voicemail_enabled) == 0) { $voicemail_enabled = "true"; }
	if (strlen($voicemail_transcription_enabled) == 0) { $voicemail_transcription_enabled = "false"; }	
	if (strlen($voicemail_tutorial) == 0) { $voicemail_tutorial = "false"; }

//get the greetings list
	$sql = "select * from v_voicemail_greetings ";
	$sql .= "where domain_uuid = :domain_uuid ";
	$sql .= "and voicemail_id = :voicemail_id ";
	$sql .= "order by greeting_name asc ";
	$parameters['domain_uuid'] = $domain_uuid;
	$parameters['voicemail_id'] = $voicemail_id;
	$database = new database;
	$greetings = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//show the header
	require_once "resources/header.php";
	$document['title'] = $text['title-voicemail'];

//password complexity
	$password_complexity = $_SESSION['voicemail']['password_complexity']['boolean'];
	if ($password_complexity == "true") {
		echo "<script>\n";	
		$req['length'] = $_SESSION['voicemail']['password_min_length']['numeric'];
		echo "	function check_password_strength(pwd) {\n";
		echo "		var msg_errors = [];\n";
		//length
		if (is_numeric($req['length']) && $req['length'] != 0) {
			echo "	var re = /.{".$req['length'].",}/;\n"; 
			echo "	if (!re.test(pwd)) { msg_errors.push('".$req['length']."+ ".$text['label-digits']."'); }\n";
		}
		//numberic only
		echo "		var re = /(?=.*[a-zA-Z\W])/;\n";
		echo "		if (re.test(pwd)) { msg_errors.push('".$text['label-numberic_only']."'); }\n";
		//repeating digits
		echo "		var re = /(\d)\\1{2}/;\n";
		echo "		if (re.test(pwd)) { msg_errors.push('".$text['label-password_repeating']."'); }\n";
		//sequential digits
		echo "		var re = /(012|123|345|456|567|678|789|987|876|765|654|543|432|321|210)/;\n";
		echo "		if (re.test(pwd)) { msg_errors.push('".$text['label-password_sequential']."'); }\n";
	
		echo "		if (msg_errors.length > 0) {\n";
		echo "			var msg = '".$text['message-password_requirements'].": ' + msg_errors.join(', ');\n";
		echo "			display_message(msg, 'negative', '6000');\n";
		echo "			return false;\n";
		echo "		}\n";
		echo "		else {\n";
		echo "			return true;\n";
		echo "		}\n";
		echo "	}\n";
		echo "</script>\n";
	}
//show the content
	echo "<form method='post' name='frm' id='frm' action=''>\n";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "<td align='left' width='30%' nowrap='nowrap' valign='top'>";
	echo "	<b>".$text['title-voicemail']."</b>";
	echo "	<br><br>";
	echo "</td>\n";
	echo "<td width='70%' align='right' valign='top'>\n";
	echo "	<input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"javascript:history.back();\" value='".$text['button-back']."'>\n";
	if ($password_complexity == "true") {
		echo "		<input type='button' class='btn' value='".$text['button-save']."' onclick=\"if (check_password_strength(document.getElementById('password').value)) { submit_form(); }\">";
	} else {
		echo "	<input type='button' class='btn' value='".$text['button-save']."' onclick='submit_form();'>\n";
	}
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-voicemail_id']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='voicemail_id' maxlength='255' value='".escape($voicemail_id)."'>\n";
	echo "	<input type='text' style='display: none;' disabled='disabled'>\n"; //help defeat browser auto-fill
	echo "<br />\n";
	echo $text['description-voicemail_id']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-voicemail_password']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input type='password' style='display: none;' disabled='disabled'>\n"; //help defeat browser auto-fill
	echo "	<input class='formfld' type='password' name='voicemail_password' id='password' autocomplete='off' onmouseover=\"this.type='text';\" onfocus=\"this.type='text';\" onmouseout=\"if (!$(this).is(':focus')) { this.type='password'; }\" onblur=\"this.type='password';\" autocomplete='off' maxlength='50' value=\"".escape($voicemail_password)."\">\n";
	echo "<br />\n";
	echo $text['description-voicemail_password']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-voicemail_tutorial']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='voicemail_tutorial' id='voicemail_tutorial'>\n";
	echo "    	<option value='true' ".(($voicemail_tutorial == "true") ? "selected='selected'" : null).">".$text['label-true']."</option>\n";
	echo "    	<option value='false' ".(($voicemail_tutorial == "false") ? "selected='selected'" : null).">".$text['label-false']."</option>\n";
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-voicemail_tutorial']."\n";
	echo "</td>\n";
	echo "</tr>\n";
	
	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-greeting']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='greeting_id'>\n";
	echo "		<option value=''></option>\n";
	if (is_array($greetings) && @sizeof($greetings) != 0) {
		foreach ($greetings as $greeting) {
			$selected = ($greeting['greeting_id'] == $greeting_id) ? 'selected' : null;
			echo "<option value='".escape($greeting['greeting_id'])."' ".escape($selected).">".escape($greeting['greeting_name'])."</option>\n";
		}
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-greeting']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-voicemail_alternate_greet_id']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='voicemail_alternate_greet_id' maxlength='255' value='".escape($voicemail_alternate_greet_id)."'>\n";
	echo "	<br />\n";
	echo "	".$text['description-voicemail_alternate_greet_id']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "	<tr>";
	echo "		<td class='vncell' valign='top'>".$text['label-options']."</td>";
	echo "		<td class='vtable' align='left'>";
	echo "			<table width='59%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "				<tr>\n";
	echo "					<td class='vtable'>".$text['label-option']."</td>\n";
	echo "					<td class='vtable'>".$text['label-destination']."</td>\n";
	echo "					<td class='vtable'>".$text['label-order']."</td>\n";
	echo "					<td class='vtable'>".$text['label-description']."</td>\n";
	echo "					<td></td>\n";
	echo "				</tr>\n";
	if (is_uuid($voicemail_uuid)) {
		$sql = "select * from v_voicemail_options ";
		$sql .= "where domain_uuid = :domain_uuid ";
		$sql .= "and voicemail_uuid = :voicemail_uuid ";
		$sql .= "order by voicemail_option_digits, voicemail_option_order asc ";
		$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
		$parameters['voicemail_uuid'] = $voicemail_uuid;
		$database = new database;
		$result = $database->select($sql, $parameters, 'all');
		if (is_array($result) && @sizeof($result) != 0) {
			foreach($result as $field) {
				$voicemail_option_param = $field['voicemail_option_param'];
				if (strlen(trim($voicemail_option_param)) == 0) {
					$voicemail_option_param = $field['voicemail_option_action'];
				}
				$voicemail_option_param = str_replace("menu-", "", $voicemail_option_param);
				$voicemail_option_param = str_replace("XML", "", $voicemail_option_param);
				$voicemail_option_param = str_replace("transfer", "", $voicemail_option_param);
				$voicemail_option_param = str_replace("bridge", "", $voicemail_option_param);
				$voicemail_option_param = str_replace($_SESSION['domain_name'], "", $voicemail_option_param);
				$voicemail_option_param = str_replace("\${domain_name}", "", $voicemail_option_param);
				$voicemail_option_param = str_replace("\${domain}", "", $voicemail_option_param);
				$voicemail_option_param = ucfirst(trim($voicemail_option_param));
				echo "				<tr>\n";
				echo "					<td class='vtable'>\n";
				echo "						".escape($field['voicemail_option_digits']);
				echo "					</td>\n";
				echo "					<td class='vtable'>\n";
				echo "						".escape($voicemail_option_param)."&nbsp;\n";
				echo "					</td>\n";
				echo "					<td class='vtable'>\n";
				echo "						".escape($field['voicemail_option_order'])."&nbsp;\n";
				echo "					</td>\n";
				echo "					<td class='vtable'>\n";
				echo "						".escape($field['voicemail_option_description'])."&nbsp;\n";
				echo "					</td>\n";
				echo "					<td class='list_control_icons'>";
				echo 						"<a href='voicemail_option_edit.php?id=".escape($field['voicemail_option_uuid'])."&voicemail_uuid=".escape($field['voicemail_uuid'])."' alt='".$text['button-edit']."'>".$v_link_label_edit."</a>";
				echo 						"<a href='voicemail_option_delete.php?id=".escape($field['voicemail_option_uuid'])."&voicemail_uuid=".escape($field['voicemail_uuid'])."' alt='".$text['button-delete']."' onclick=\"return confirm('".$text['confirm-delete']."')\">".$v_link_label_delete."</a>";
				echo "					</td>\n";
				echo "				</tr>\n";
			}
		}
	}
	unset($sql, $parameters, $result, $field);

	for ($c = 0; $c < 1; $c++) {
		echo "				<tr>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "  <input class='formfld' style='width:70px' type='text' name='voicemail_options[".$c."][voicemail_option_digits]' maxlength='255' value='".$voicemail_option_digits."'>\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left' nowrap='nowrap'>\n";
		echo $destination->select('ivr', 'voicemail_options['.$c.'][voicemail_option_param]', '');
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<select name='voicemail_options[".$c."][voicemail_option_order]' class='formfld' style='width:55px'>\n";
		if (strlen(htmlspecialchars($voicemail_option_order))> 0) {
			echo "	<option selected='yes' value='".htmlspecialchars($voicemail_option_order)."'>".htmlspecialchars($voicemail_option_order)."</option>\n";
		}
		$i = 0;
		while ($i <= 999) {
			if (strlen($i) == 1) {
				echo "	<option value='00$i'>00$i</option>\n";
			}
			if (strlen($i) == 2) {
				echo "	<option value='0$i'>0$i</option>\n";
			}
			if (strlen($i) == 3) {
				echo "	<option value='$i'>$i</option>\n";
			}
			$i++;
		}
		echo "	</select>\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<input class='formfld' style='width:100px' type='text' name='voicemail_options[".$c."][voicemail_option_description]' maxlength='255' value=\"".$voicemail_option_description."\">\n";
		echo "</td>\n";

		echo "					<td>\n";
		echo "						<input type='button' class='btn' value=\"".$text['button-add']."\" onclick='submit_form();'>\n";
		echo "					</td>\n";
		echo "				</tr>\n";
	}
	echo "			</table>\n";

	echo "			".$text['description-options']."\n";
	echo "			<br />\n";
	echo "		</td>";
	echo "	</tr>";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-voicemail_mail_to']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='voicemail_mail_to' maxlength='255' value=\"".escape($voicemail_mail_to)."\">\n";
	echo "<br />\n";
	echo $text['description-voicemail_mail_to']."\n";
	echo "</td>\n";
	echo "</tr>\n";
	if(permission_exists('voicemail_sms_edit')) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-voicemail_sms_to']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<input class='formfld' type='text' name='voicemail_sms_to' maxlength='255' value=\"".escape($voicemail_sms_to)."\">\n";
		echo "<br />\n";
		echo $text['description-voicemail_sms_to']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}
	if(permission_exists('voicemail_transcription_edit') && $_SESSION['voicemail']['transcribe_enabled']['boolean'] == "true") {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-voicemail_transcription_enabled']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<select class='formfld' name='voicemail_transcription_enabled' id='voicemail_transcription_enabled'>\n";
		echo "    	<option value='true' ".(($voicemail_transcription_enabled == "true") ? "selected='selected'" : null).">".$text['label-true']."</option>\n";
		echo "    	<option value='false' ".(($voicemail_transcription_enabled == "false") ? "selected='selected'" : null).">".$text['label-false']."</option>\n";
		echo "	</select>\n";
		echo "<br />\n";
		echo $text['description-voicemail_transcription_enabled']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-voicemail_file']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <select class='formfld' name='voicemail_file' id='voicemail_file' onchange=\"if (this.selectedIndex != 2) { document.getElementById('voicemail_local_after_email').selectedIndex = 0; }\">\n";
	echo "    	<option value='' ".(($voicemail_file == "listen") ? "selected='selected'" : null).">".$text['option-voicemail_file_listen']."</option>\n";
	echo "    	<option value='link' ".(($voicemail_file == "link") ? "selected='selected'" : null).">".$text['option-voicemail_file_link']."</option>\n";
	echo "    	<option value='attach' ".(($voicemail_file == "attach") ? "selected='selected'" : null).">".$text['option-voicemail_file_attach']."</option>\n";
	echo "    </select>\n";
	echo "<br />\n";
	echo $text['description-voicemail_file']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	if (permission_exists('voicemail_local_after_email')) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-voicemail_local_after_email']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<select class='formfld' name='voicemail_local_after_email' id='voicemail_local_after_email' onchange=\"if (this.selectedIndex == 1) { document.getElementById('voicemail_file').selectedIndex = 2; }\">\n";
		echo "    	<option value='true' ".(($voicemail_local_after_email == "true") ? "selected='selected'" : null).">".$text['label-true']."</option>\n";
		echo "    	<option value='false' ".(($voicemail_local_after_email == "false") ? "selected='selected'" : null).">".$text['label-false']."</option>\n";
		echo "	</select>\n";
		echo "<br />\n";
		echo $text['description-voicemail_local_after_email']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	if ($action == "update") {
		echo "	<tr>";
		echo "		<td class='vncell' valign='top'>".$text['label-forward_destinations']."</td>";
		echo "		<td class='vtable'>";

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
				v.domain_uuid = :domain_uuid and
				v.voicemail_enabled = 'true' and
				d.voicemail_uuid = :voicemail_uuid
			order by
				v.voicemail_id asc";
		$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
		$parameters['voicemail_uuid'] = $voicemail_uuid;
		$database = new database;
		$result = $database->select($sql, $parameters, 'all');
		if (is_array($result) && @sizeof($result) != 0) {
			echo "		<table width='52%'>\n";
			foreach($result as $field) {
				echo "		<tr>\n";
				echo "			<td class='vtable'>".escape($field['voicemail_id'])."</td>\n";
				echo "			<td>\n";
				echo "				<a href='voicemail_edit.php?id=".escape($voicemail_uuid)."&voicemail_destination_uuid=".escape($field['voicemail_destination_uuid'])."&a=delete' alt='".$text['button-delete']."' onclick=\"return confirm('".$text['confirm-delete']."')\">".$v_link_label_delete."</a>\n";
				echo "			</td>\n";
				echo "		</tr>\n";
				$voicemail_uuids_copied[] = $field['voicemail_uuid_copy'];
			}
			echo "		</table>\n";
			echo "		<br />\n";
		}
		unset($sql, $parameters, $result, $field);

		if (is_array($voicemail_uuids_copied) && @sizeof($voicemail_uuids_copied) != 0) {
			// modify sql to remove already copied voicemail uuids from the list
			foreach ($voicemail_uuids_copied as $x => $voicemail_uuid_copied) {
				if (is_uuid($voicemail_uuid_copied)) {
					$sql_where_and[] = 'v.voicemail_uuid <> :voicemail_uuid_'.$x;
					$parameters['voicemail_uuid_'.$x] = $voicemail_uuid_copied;
				}
			}
			if (is_array($sql_where_and) && @sizeof($sql_where_and) != 0) {
				$sql_where = ' and '.implode(' and ', $sql_where_and);
			}
			unset($voicemail_uuids_copied, $x, $voicemail_uuid_copied, $sql_where_and);
		}

		$sql = "
			select
				v.voicemail_id,
				v.voicemail_uuid
			from
				v_voicemails as v
			where
				v.domain_uuid = :domain_uuid and
				v.voicemail_enabled = 'true' and
				v.voicemail_uuid <> :voicemail_uuid
				".$sql_where."
			order by
				v.voicemail_id asc";
		$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
		$parameters['voicemail_uuid'] = $voicemail_uuid;
		$database = new database;
		$result = $database->select($sql, $parameters, 'all');
		echo "			<select name=\"voicemail_uuid_copy\" class='formfld' style='width: auto;'>\n";
		echo "			<option value=\"\"></option>\n";
		if (is_array($result) && @sizeof($result) != 0) {
			foreach($result as $field) {
				echo "			<option value='".escape($field['voicemail_uuid'])."'>".escape($field['voicemail_id'])."</option>\n";
			}
		}
		unset($sql, $parameters, $result, $field);
		echo "			</select>";
		echo "			<input type='button' class='btn' value=\"".$text['button-add']."\" onclick='submit_form();'>\n";
		echo "			<br>\n";
		echo "			".$text['description-forward_destinations']."\n";
		echo "			<br />\n";
		echo "		</td>";
		echo "	</tr>";
	}

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-voicemail_enabled']."\n";
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
	echo "	".$text['label-voicemail_description']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='voicemail_description' maxlength='255' value=\"".escape($voicemail_description)."\">\n";
	echo "<br />\n";
	echo $text['description-voicemail_description']."\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	if ($action == "update") {
		echo "				<input type='hidden' name='voicemail_uuid' value='".escape($voicemail_uuid)."'>\n";
	}
	$http_referer = parse_url($_SERVER["HTTP_REFERER"]);
	echo "				<input type='hidden' name='referer_path' value='".escape($http_referer['path'])."'>\n";
	echo "				<input type='hidden' name='referer_query' value='".escape($http_referer['query'])."'>\n";
	echo "				<br>";
	if ($password_complexity == "true") {
		echo "			<input type='button' class='btn' value='".$text['button-save']."' onclick=\"if (check_password_strength(document.getElementById('password').value)) { submit_form(); }\">";
	} else {
		echo "			<input type='button' class='btn' value='".$text['button-save']."' onclick='submit_form();'>\n";
	}
	echo "		</td>\n";
	echo "	</tr>";
	echo "</table>";
	echo "<br><br>";
	echo "</form>";

	echo "<script>\n";
//capture enter key to submit form
	echo "	$(window).keypress(function(event){\n";
	echo "		if (event.which == 13) { submit_form(); }\n";
	echo "	});\n";
//hide password fields, change to text, before submit
	echo "	function submit_form() {\n";
	echo "		$('input:password').css('visibility','hidden');\n";
	echo "		$('input:password').attr({type:'text'});\n";
	echo "		$('form#frm').submit();\n";
	echo "	}\n";
	echo "</script>\n";

//include the footer
	require_once "resources/footer.php";

?>