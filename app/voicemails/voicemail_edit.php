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
 Portions created by the Initial Developer are Copyright (C) 2008-2020
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

//initialize the destinations object
	$destination = new destinations;

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

		//process the http post data by submitted action
			if ($_POST['action'] != '' && is_uuid($_POST['voicemail_uuid'])) {
				$array[0]['checked'] = 'true';
				$array[0]['uuid'] = $_POST['voicemail_uuid'];

				switch ($_POST['action']) {
					case 'delete':
						if (permission_exists('voicemail_delete')) {
							$obj = new voicemail;
							$obj->voicemail_delete($array);
						}
						break;
				}

				header('Location: voicemails.php');
				exit;
			}

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
			$voicemail_destination = $_POST["voicemail_destination"];
			$voicemail_enabled = $_POST["voicemail_enabled"];
			$voicemail_description = $_POST["voicemail_description"];
			$voicemail_tutorial = $_POST["voicemail_tutorial"];
			$voicemail_options_delete = $_POST["voicemail_options_delete"];
			$voicemail_destinations_delete = $_POST["voicemail_destinations_delete"];
		//remove the space
			$voicemail_mail_to = str_replace(" ", "", $voicemail_mail_to);
	}

//process the data
	if (count($_POST) > 0 && strlen($_POST["persistformvar"]) == 0) {

		$msg = '';
		if ($action == "update") {
			$voicemail_uuid = $_POST["voicemail_uuid"];
		}

		//validate the token
			$token = new token;
			if (!$token->validate($_SERVER['PHP_SELF'])) {
				message::add($text['message-invalid_token'],'negative');
				header('Location: voicemails.php');
				exit;
			}

		//check for all required data
			$msg = '';
			if (!is_numeric($voicemail_id)) { $msg .= $text['message-required']." ".$text['label-voicemail_id']."<br>\n"; }
			if (trim($voicemail_password) == '') { $msg .= $text['message-required']." ".$text['label-voicemail_password']."<br>\n"; }
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

				//get a new voicemail_uuid
					if ($action == "add" && permission_exists('voicemail_add')) {
						$voicemail_uuid = uuid();
						//If adding a mailbox, set the default transcribe behavior
						$voicemail_transcription_enabled = $_SESSION['voicemail']['transcription_enabled_default']['boolean'];
					}

				//add common array fields
					$array['voicemails'][0]['domain_uuid'] = $domain_uuid;
					$array['voicemails'][0]['voicemail_uuid'] = $voicemail_uuid;
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

				//create permissions object
					$p = new permissions;

				//add voicemail options
					if (permission_exists('voicemail_option_add') && sizeof($voicemail_options) > 0) {
						foreach ($voicemail_options as $x => $voicemail_option) {
							if ($voicemail_option['voicemail_option_digits'] == '' || $voicemail_option['voicemail_option_param'] == '') { unset($voicemail_options[$x]); }
						}
						foreach ($voicemail_options as $x => $voicemail_option) {
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
								$array['voicemail_options'][$x]['voicemail_option_uuid'] = uuid();
								$array['voicemail_options'][$x]['voicemail_uuid'] = $voicemail_uuid;
								$array['voicemail_options'][$x]['domain_uuid'] = $domain_uuid;
								$array['voicemail_options'][$x]['voicemail_option_digits'] = $voicemail_option['voicemail_option_digits'];
								$array['voicemail_options'][$x]['voicemail_option_action'] = $voicemail_option['voicemail_option_action'];
								if ($destination->valid(preg_replace('/\s/', ':', $voicemail_option['voicemail_option_param'], 1))) {
									$array['voicemail_options'][$x]['voicemail_option_param'] = $voicemail_option['voicemail_option_param'];
								}
								$array['voicemail_options'][$x]['voicemail_option_order'] = $voicemail_option['voicemail_option_order'];
								$array['voicemail_options'][$x]['voicemail_option_description'] = $voicemail_option['voicemail_option_description'];
						}
						if (is_array($array['voicemail_options']) && @sizeof($array['voicemail_options']) != 0) {
							//grant temporary permission
								$p->add('voicemail_option_add', 'temp');
						}
					}

				//add voicemail destination
					if (permission_exists('voicemail_forward') && is_uuid($voicemail_destination)) {
						$array['voicemail_destinations'][0]['domain_uuid'] = $domain_uuid;
						$array['voicemail_destinations'][0]['voicemail_destination_uuid'] = uuid();
						$array['voicemail_destinations'][0]['voicemail_uuid'] = $voicemail_uuid;
						$array['voicemail_destinations'][0]['voicemail_uuid_copy'] = $voicemail_destination;

						if (is_array($array['voicemail_destinations']) && @sizeof($array['voicemail_destinations']) != 0) {
							//grant temporary permission
								$p->add('voicemail_destination_add', 'temp');
						}
					}

				//execute insert/update
					$database = new database;
					$database->app_name = 'voicemails';
					$database->app_uuid = 'b523c2d2-64cd-46f1-9520-ca4b4098e044';
					$database->save($array);
					unset($array);

				//revoke any temporary permissions granted
					$p->delete('voicemail_option_add', 'temp');
					$p->delete('voicemail_destination_add', 'temp');

				//remove checked voicemail options
					if (
						$action == 'update'
						&& permission_exists('voicemail_option_delete')
						&& is_array($voicemail_options_delete)
						&& @sizeof($voicemail_options_delete) != 0
						) {
						$obj = new voicemail;
						$obj->voicemail_uuid = $voicemail_uuid;
						$obj->voicemail_options_delete($voicemail_options_delete);
					}

				//remove checked voicemail destinations
					if (
						$action == 'update'
						&& permission_exists('voicemail_forward')
						&& is_array($voicemail_destinations_delete)
						&& @sizeof($voicemail_destinations_delete) != 0
						) {
						$obj = new voicemail;
						$obj->voicemail_uuid = $voicemail_uuid;
						$obj->voicemail_destinations_delete($voicemail_destinations_delete);
					}

				//clear the destinations session array
					if (isset($_SESSION['destinations']['array'])) {
						unset($_SESSION['destinations']['array']);
					}

				//set message
					if ($action == "add" && permission_exists('voicemail_add')) {
						message::add($text['message-add']);
					}
					if ($action == "update" && permission_exists('voicemail_edit')) {
						message::add($text['message-update']);
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
	if (strlen($voicemail_transcription_enabled) == 0) { $voicemail_transcription_enabled = $_SESSION['voicemail']['transcription_enabled_default']['boolean'] ?: "false"; }	
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

//get the voicemail options
	if ($action == 'update' && is_uuid($voicemail_uuid)) {
		$sql = "select * from v_voicemail_options ";
		$sql .= "where domain_uuid = :domain_uuid ";
		$sql .= "and voicemail_uuid = :voicemail_uuid ";
		$sql .= "order by voicemail_option_digits, voicemail_option_order asc ";
		$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
		$parameters['voicemail_uuid'] = $voicemail_uuid;
		$database = new database;
		$voicemail_options = $database->select($sql, $parameters, 'all');
		unset($sql, $parameters);

		$show_option_delete = false;
		if (is_array($voicemail_options) && @sizeof($voicemail_options) != 0) {
			foreach ($voicemail_options as $x => $field) {
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
				$voicemail_options[$x]['voicemail_option_param'] = $voicemail_option_param;
				unset($voicemail_option_param);
			}
			$show_option_delete = true;
		}
	}

//get the assigned voicemail destinations
	if ($action == 'update' && is_uuid($voicemail_uuid)) {
		$sql = "select v.voicemail_id, d.voicemail_destination_uuid, d.voicemail_uuid_copy ";
		$sql .= "from v_voicemails as v, v_voicemail_destinations as d ";
		$sql .= "where d.voicemail_uuid_copy = v.voicemail_uuid and ";
		$sql .= "v.domain_uuid = :domain_uuid and ";
		$sql .= "v.voicemail_enabled = 'true' and ";
		$sql .= "d.voicemail_uuid = :voicemail_uuid ";
		$sql .= "order by v.voicemail_id asc";
		$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
		$parameters['voicemail_uuid'] = $voicemail_uuid;
		$database = new database;
		$voicemail_destinations_assigned = $database->select($sql, $parameters, 'all');
		if (is_array($voicemail_destinations_assigned) && @sizeof($voicemail_destinations_assigned) != 0) {
			foreach ($voicemail_destinations_assigned as $field) {
				$voicemail_destinations[] = "'".$field['voicemail_uuid_copy']."'";
			}
		}
		unset($sql, $parameters);
	}

//get the available voicemail destinations
	$sql = "select v.voicemail_id, v.voicemail_uuid ";
	$sql .= "from v_voicemails as v ";
	$sql .= "where v.domain_uuid = :domain_uuid and ";
	$sql .= "v.voicemail_enabled = 'true' ";
	if (is_uuid($voicemail_uuid)) {
		$sql .= "and v.voicemail_uuid <> :voicemail_uuid ";
	}
	if (is_array($voicemail_destinations) && @sizeof($voicemail_destinations) != 0) {
		$sql .= "and v.voicemail_uuid not in (".implode(',', $voicemail_destinations).") ";
	}
	$sql .= "order by v.voicemail_id asc";
	$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	if (is_uuid($voicemail_uuid)) {
		$parameters['voicemail_uuid'] = $voicemail_uuid;
	}
	$database = new database;
	$voicemail_destinations_available = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters, $voicemail_destinations);

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//include the header
	$document['title'] = $text['title-voicemail'];
	require_once "resources/header.php";

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

//set the location for the back button
	if (permission_exists('voicemail_view')) {
		$back_button_location = "voicemails.php";
	}
	else {
		$back_button_location = "voicemail_messages.php?voicemail_uuid=".urlencode($voicemail_uuid);
	}

//show the content
	echo "<form method='post' name='frm' id='frm'>\n";

	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-voicemail']."</b></div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$_SESSION['theme']['button_icon_back'],'id'=>'btn_back','link'=>$back_button_location]);
	if ($action == "update" && (permission_exists('voicemail_delete') || permission_exists('voicemail_option_delete'))) {
		echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$_SESSION['theme']['button_icon_delete'],'name'=>'btn_delete','style'=>'margin-left: 15px;','onclick'=>"modal_open('modal-delete','btn_delete');"]);
	}
	echo button::create(['type'=>'button','label'=>$text['button-save'],'icon'=>$_SESSION['theme']['button_icon_save'],'id'=>'btn_save','style'=>'margin-left: 15px;','onclick'=>($password_complexity == "true" ? "if (check_password_strength(document.getElementById('password').value)) { submit_form(); } else { this.blur(); return false; }" : 'submit_form();')]);
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	if ($action == "update" && (permission_exists('voicemail_delete') || permission_exists('voicemail_option_delete'))) {
		echo modal::create(['id'=>'modal-delete','type'=>'delete','actions'=>button::create(['type'=>'submit','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_delete','style'=>'float: right; margin-left: 15px;','collapse'=>'never','name'=>'action','value'=>'delete','onclick'=>"modal_close();"])]);
	}

	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";

	echo "<tr>\n";
	echo "<td width='30%' class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-voicemail_id']."\n";
	echo "</td>\n";
	echo "<td width='70%' class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='voicemail_id' maxlength='255' autocomplete='new-password' value='".escape($voicemail_id)."'>\n";
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
	echo "	<input class='formfld' type='password' name='voicemail_password' id='password' autocomplete='new-password' onmouseover=\"this.type='text';\" onfocus=\"this.type='text';\" onmouseout=\"if (!$(this).is(':focus')) { this.type='password'; }\" onblur=\"this.type='password';\" autocomplete='off' maxlength='50' value=\"".escape($voicemail_password)."\">\n";
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
	echo "		<option value=''>".$text['label-default']."</option>\n";
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

	if (permission_exists('voicemail_option_add') || permission_exists('voicemail_option_edit')) {
		echo "	<tr>";
		echo "		<td class='vncell' valign='top'>".$text['label-options']."</td>";
		echo "		<td class='vtable' align='left'>";
		echo "			<table border='0' cellpadding='0' cellspacing='0'>\n";
		echo "				<tr>\n";
		echo "					<td class='vtable' style='text-align: center;'>".$text['label-option']."</td>\n";
		echo "					<td class='vtable'>".$text['label-destination']."</td>\n";
		echo "					<td class='vtable' style='text-align: center;'>".$text['label-order']."</td>\n";
		echo "					<td class='vtable'>".$text['label-description']."</td>\n";
		if ($show_option_delete && permission_exists('voicemail_option_delete')) {
			echo "					<td class='vtable edit_delete_checkbox_all' onmouseover=\"swap_display('delete_label_options', 'delete_toggle_options');\" onmouseout=\"swap_display('delete_label_options', 'delete_toggle_options');\">\n";
			echo "						<span id='delete_label_options'>".$text['label-delete']."</span>\n";
			echo "						<span id='delete_toggle_options'><input type='checkbox' id='checkbox_all_options' name='checkbox_all' onclick=\"edit_all_toggle('options');\"></span>\n";
			echo "					</td>\n";
		}
		echo "				</tr>\n";
		if ($action == 'update' && is_array($voicemail_options) && @sizeof($voicemail_options) != 0) {
			foreach ($voicemail_options as $x => $field) {
				echo "				<tr>\n";
				echo "					<td class='vtable' style='text-align: center;'>".escape($field['voicemail_option_digits'])."</td>\n";
				echo "					<td class='vtable'>".escape($field['voicemail_option_param'])."</td>\n";
				echo "					<td class='vtable' style='text-align: center;'>".escape($field['voicemail_option_order'])."</td>\n";
				echo "					<td class='vtable'>".escape($field['voicemail_option_description'])."</td>\n";
				if ($show_option_delete && permission_exists('voicemail_option_delete')) {
					echo "				<td class='vtable' style='text-align: center; padding-bottom: 3px;'>";
					if (is_uuid($field['voicemail_option_uuid'])) {
						echo "					<input type='checkbox' name='voicemail_options_delete[".$x."][checked]' value='true' class='chk_delete checkbox_options' onclick=\"edit_delete_action('options');\">\n";
						echo "					<input type='hidden' name='voicemail_options_delete[".$x."][uuid]' value='".escape($field['voicemail_option_uuid'])."' />\n";
					}
					echo "				</td>\n";
				}
				echo "				</tr>\n";
			}
		}
		unset($voicemail_options, $field);

		for ($c = 0; $c < 1; $c++) {
			echo "<tr>\n";
			echo "	<td class='vtable' style='border-bottom: none;' align='left'>\n";
			echo "		<input class='formfld' style='width: 50px; text-align: center;' type='text' name='voicemail_options[".$c."][voicemail_option_digits]' maxlength='255' value='".$voicemail_option_digits."'>\n";
			echo "	</td>\n";
			echo "	<td class='vtable' style='border-bottom: none;' align='left' nowrap='nowrap'>\n";
			echo 		$destination->select('ivr', 'voicemail_options['.$c.'][voicemail_option_param]', '');
			echo "	</td>\n";
			echo "	<td class='vtable' style='border-bottom: none;' align='left'>\n";
			echo "		<select name='voicemail_options[".$c."][voicemail_option_order]' class='formfld' style='width:55px'>\n";
			if (strlen(htmlspecialchars($voicemail_option_order))> 0) {
				echo "		<option selected='yes' value='".htmlspecialchars($voicemail_option_order)."'>".htmlspecialchars($voicemail_option_order)."</option>\n";
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
			echo "		</select>\n";
			echo "	</td>\n";
			echo "	<td class='vtable' style='border-bottom: none;' align='left'>\n";
			echo "		<input class='formfld' style='width:100px' type='text' name='voicemail_options[".$c."][voicemail_option_description]' maxlength='255' value=\"".$voicemail_option_description."\">\n";
			echo "	</td>\n";

			echo "	<td></td>\n";
			echo "</tr>\n";
		}

		echo "			</table>\n";

		echo "			".$text['description-options']."\n";
		echo "			<br />\n";
		echo "		</td>";
		echo "	</tr>";
	}

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

	if (permission_exists('voicemail_sms_edit') && file_exists($_SERVER['DOCUMENT_ROOT'].PROJECT_PATH.'/app/sms/')) {
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

	if (permission_exists('voicemail_transcription_edit') && $_SESSION['voicemail']['transcribe_enabled']['boolean'] == "true") {
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
	//disable as doesn't work without post-login redirect
	//echo "    <option value='' ".(($voicemail_file == "listen") ? "selected='selected'" : null).">".$text['option-voicemail_file_listen']."</option>\n";
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
		echo "	<select class='formfld' name='voicemail_local_after_email' id='voicemail_local_after_email' onchange=\"if (this.selectedIndex == 1) { document.getElementById('voicemail_file').selectedIndex = 1; }\">\n";
		echo "    	<option value='true' ".(($voicemail_local_after_email == "true") ? "selected='selected'" : null).">".$text['label-true']."</option>\n";
		echo "    	<option value='false' ".(($voicemail_local_after_email == "false") ? "selected='selected'" : null).">".$text['label-false']."</option>\n";
		echo "	</select>\n";
		echo "<br />\n";
		echo $text['description-voicemail_local_after_email']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	if (permission_exists('voicemail_forward')) {
		echo "	<tr>";
		echo "		<td class='vncell' valign='top'>".$text['label-forward_destinations']."</td>";
		echo "		<td class='vtable'>";
		echo "			<table border='0' cellpadding='0' cellspacing='0'>\n";

		if (is_array($voicemail_destinations_assigned) && @sizeof($voicemail_destinations_assigned) != 0) {
			echo "				<tr>\n";
			echo "					<td class='vtable'>".$text['label-destination']."</td>\n";
			echo "					<td class='vtable edit_delete_checkbox_all' onmouseover=\"swap_display('delete_label_destinations', 'delete_toggle_destinations');\" onmouseout=\"swap_display('delete_label_destinations', 'delete_toggle_destinations');\">\n";
			echo "						<span id='delete_label_destinations'>".$text['label-delete']."</span>\n";
			echo "						<span id='delete_toggle_destinations'><input type='checkbox' id='checkbox_all_destinations' name='checkbox_all' onclick=\"edit_all_toggle('destinations');\"></span>\n";
			echo "					</td>\n";
			echo "				</tr>\n";
			foreach ($voicemail_destinations_assigned as $x => $field) {
				echo "				<tr>\n";
				echo "					<td class='vtable'>".escape($field['voicemail_id'])."</td>\n";
				echo "					<td class='vtable' style='text-align: center; padding-bottom: 3px;'>";
				echo "						<input type='checkbox' name='voicemail_destinations_delete[".$x."][checked]' value='true' class='chk_delete checkbox_destinations' onclick=\"edit_delete_action('destinations');\">\n";
				echo "						<input type='hidden' name='voicemail_destinations_delete[".$x."][uuid]' value='".escape($field['voicemail_destination_uuid'])."' />\n";
				echo "					</td>\n";
				echo "				</tr>\n";
			}
		}
		unset($voicemail_destinations_assigned, $field);

		if (is_array($voicemail_destinations_available) && @sizeof($voicemail_destinations_available) != 0) {
			echo "	<tr>\n";
			echo "		<td class='vtable' style='border-bottom: none;' colspan='2'>\n";
			echo "			<select name='voicemail_destination' class='formfld'>\n";
			echo "				<option value=''></option>\n";
			foreach ($voicemail_destinations_available as $field) {
				echo "			<option value='".escape($field['voicemail_uuid'])."'>".escape($field['voicemail_id'])."</option>\n";
			}
			echo "			</select>";
			if ($action == 'update') {
				echo button::create(['type'=>'button','label'=>$text['button-add'],'icon'=>$_SESSION['theme']['button_icon_add'],'collapse'=>'never','onclick'=>'submit_form();']);
			}
			echo "		</td>\n";
			echo "	<tr>\n";
		}
		unset($voicemail_destinations_available, $field);

		echo "			</table>\n";

		echo "			".$text['description-forward_destinations']."\n";
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

	echo "</table>";
	echo "<br><br>";

	if ($action == "update") {
		echo "<input type='hidden' name='voicemail_uuid' value='".escape($voicemail_uuid)."'>\n";
	}
	$http_referer = parse_url($_SERVER["HTTP_REFERER"]);
	echo "<input type='hidden' name='referer_path' value='".escape($http_referer['path'])."'>\n";
	echo "<input type='hidden' name='referer_query' value='".escape($http_referer['query'])."'>\n";
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";

	echo "</form>";

	echo "<script>\n";
//hide password fields before submit
	echo "	function submit_form() {\n";
	echo "		hide_password_fields();\n";
	echo "		$('form#frm').submit();\n";
	echo "	}\n";
	echo "</script>\n";

//include the footer
	require_once "resources/footer.php";

?>