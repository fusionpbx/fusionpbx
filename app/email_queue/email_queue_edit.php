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
	Portions created by the Initial Developer are Copyright (C) 2022-2024
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes files
	require_once dirname(__DIR__, 2) . "/resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('email_queue_add') || permission_exists('email_queue_edit')) {
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
	if (!empty($_REQUEST["id"]) && is_uuid($_REQUEST["id"])) {
		$action = "update";
		$email_queue_uuid = $_REQUEST["id"];
		$id = $_REQUEST["id"];
	}
	else {
		$action = "add";
	}

//get http post variables and set them to php variables
	if (!empty($_POST) && is_array($_POST)) {
		$email_date = $_POST["email_date"];
		$email_from = $_POST["email_from"];
		$email_to = $_POST["email_to"];
		$email_subject = $_POST["email_subject"];
		$email_body = $_POST["email_body"];
		$email_status = $_POST["email_status"];
		$email_retry_count = $_POST["email_retry_count"];
		//$email_action_before = $_POST["email_action_before"];
		$email_action_after = $_POST["email_action_after"];
		$email_response = $_POST["email_response"];
	}

//process the user data and save it to the database
	if (count($_POST) > 0 && empty($_POST["persistformvar"])) {

		//validate the token
			$token = new token;
			if (!$token->validate($_SERVER['PHP_SELF'])) {
				message::add($text['message-invalid_token'],'negative');
				header('Location: email_queue.php');
				exit;
			}

		//process the http post data by submitted action
			if (!empty($_POST['action']) && !empty($_POST['action'])) {

				//prepare the array(s)
				//send the array to the database class
				switch ($_POST['action']) {
					case 'copy':
						if (permission_exists('email_queue_add')) {
							$obj = new database;
							$obj->copy($array);
						}
						break;
					case 'delete':
						if (permission_exists('email_queue_delete')) {
							$obj = new database;
							$obj->delete($array);
						}
						break;
					case 'toggle':
						if (permission_exists('email_queue_update')) {
							$obj = new database;
							$obj->toggle($array);
						}
						break;
				}

				//redirect the user
				if (in_array($_POST['action'], array('copy', 'delete', 'toggle'))) {
					header('Location: email_queue_edit.php?id='.$id);
					exit;
				}
			}

		//check for all required data
			$msg = '';
			//if (empty($email_date)) { $msg .= $text['message-required']." ".$text['label-email_date']."<br>\n"; }
			//if (empty($email_from)) { $msg .= $text['message-required']." ".$text['label-email_from']."<br>\n"; }
			//if (empty($email_to)) { $msg .= $text['message-required']." ".$text['label-email_to']."<br>\n"; }
			//if (empty($email_subject)) { $msg .= $text['message-required']." ".$text['label-email_subject']."<br>\n"; }
			//if (empty($email_body)) { $msg .= $text['message-required']." ".$text['label-email_body']."<br>\n"; }
			//if (empty($email_status)) { $msg .= $text['message-required']." ".$text['label-email_status']."<br>\n"; }
			if (!empty($msg) && empty($_POST["persistformvar"])) {
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

		//parse email addresses to single string csv string
			if (isset($email_to) && substr_count($email_to, "\n") != 0) {
				$email_to_lines = explode("\n", $email_to);
				if (is_array($email_to_lines) && @sizeof($email_to_lines) != 0) {
					foreach ($email_to_lines as $email_to_line) {
						if (substr_count($email_to_line, ',') != 0) {
							$email_to_array = explode(',', $email_to_line);
							if (is_array($email_to_array) && @sizeof($email_to_array) != 0) {
								foreach ($email_to_array as $email_to_address) {
									if (valid_email(trim($email_to_address))) {
										$email_to_addresses[] = strtolower(trim($email_to_address));
									}
								}
							}
						}
						else {
							if (valid_email(trim($email_to_line))) {
								$email_to_addresses[] = strtolower(trim($email_to_line));
							}
						}
					}
				}
			}
			else {
				if (isset($email_to) && substr_count($email_to, ',') != 0) {
					$email_to_array = explode(',', $email_to);
					if (is_array($email_to_array) && @sizeof($email_to_array) != 0) {
						foreach ($email_to_array as $email_to_address) {
							if (valid_email(trim($email_to_address))) {
								$email_to_addresses[] = strtolower(trim($email_to_address));
							}
						}
					}
				}
			}
			if (!empty($email_to_addresses) && is_array($email_to_addresses) && @sizeof($email_to_addresses) != 0) {
				$email_to = implode(',', $email_to_addresses);
				unset($email_to_array, $email_to_addresses);
			}

		//add the email_queue_uuid
			if (!is_uuid($_POST["email_queue_uuid"])) {
				$email_queue_uuid = uuid();
			}

		//prepare the array
			$array['email_queue'][0]['email_queue_uuid'] = $email_queue_uuid;
			$array['email_queue'][0]['domain_uuid'] = $_SESSION['domain_uuid'];
			$array['email_queue'][0]['email_date'] = $email_date;
			$array['email_queue'][0]['email_from'] = $email_from;
			$array['email_queue'][0]['email_to'] = $email_to;
			$array['email_queue'][0]['email_subject'] = $email_subject;
			$array['email_queue'][0]['email_body'] = $email_body;
			$array['email_queue'][0]['email_status'] = $email_status;
			$array['email_queue'][0]['email_retry_count'] = $email_retry_count;
			//$array['email_queue'][0]['email_action_before'] = $email_action_before;
			$array['email_queue'][0]['email_action_after'] = $email_action_after;
			$array['email_queue'][0]['email_response'] = $email_response;

		//save the data
			$database = new database;
			$database->app_name = 'email queue';
			$database->app_uuid = '5befdf60-a242-445f-91b3-2e9ee3e0ddf7';
			$database->save($array);

		//redirect the user
			if (isset($action)) {
				if ($action == "add") {
					$_SESSION["message"] = $text['message-add'];
				}
				if ($action == "update") {
					$_SESSION["message"] = $text['message-update'];
				}
				//header('Location: email_queue.php');
				header('Location: email_queue_edit.php?id='.urlencode($email_queue_uuid));
				return;
			}
	}

//pre-populate the form
	if (!empty($_GET) && is_array($_GET) && (empty($_POST["persistformvar"]) || $_POST["persistformvar"] != "true")) {
		$sql = "select * from v_email_queue ";
		$sql .= "where email_queue_uuid = :email_queue_uuid ";
		//$sql .= "and domain_uuid = :domain_uuid ";
		//$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
		$parameters['email_queue_uuid'] = $email_queue_uuid;
		$database = new database;
		$row = $database->select($sql, $parameters, 'row');
		if (is_array($row) && @sizeof($row) != 0) {
			$email_date = $row["email_date"];
			$email_from = $row["email_from"];
			$email_to = $row["email_to"];
			$email_subject = $row["email_subject"];
			$email_body = $row["email_body"];
			$email_status = $row["email_status"];
			$email_retry_count = $row["email_retry_count"];
			//$email_action_before = $row["email_action_before"];
			$email_response = $row["email_response"];
			$email_action_after = $row["email_action_after"];
		}
		unset($sql, $parameters, $row);
	}

//load editor preferences/defaults
	$setting_size = !empty($_SESSION["editor"]["font_size"]["text"]) ? $_SESSION["editor"]["font_size"]["text"] : '12px';
	$setting_theme = !empty($_SESSION["editor"]["theme"]["text"]) ? $_SESSION["editor"]["theme"]["text"] : 'cobalt';
	$setting_invisibles = !empty($_SESSION["editor"]["invisibles"]["boolean"]) ? $_SESSION["editor"]["invisibles"]["boolean"] : 'false';
	$setting_indenting = !empty($_SESSION["editor"]["indent_guides"]["boolean"]) ? $_SESSION["editor"]["indent_guides"]["boolean"] : 'false';
	$setting_numbering = !empty($_SESSION["editor"]["line_numbers"]["boolean"]) ? $_SESSION["editor"]["line_numbers"]["boolean"] : 'true';

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//show the header
	$document['title'] = $text['title-email_queue'];
	require_once "resources/header.php";

	echo "<script language='JavaScript' type='text/javascript'>\n";

	echo "	function toggle_option(opt) {\n";
	echo "		switch (opt) {\n";
	echo "			case 'numbering':\n";
	echo "				toggle_option_do('showLineNumbers');\n";
	echo "				toggle_option_do('fadeFoldWidgets');\n";
	echo "				break;\n";
	echo "			case 'invisibles':\n";
	echo "				toggle_option_do('showInvisibles');\n";
	echo "				break;\n";
	echo "			case 'indenting':\n";
	echo "				toggle_option_do('displayIndentGuides');\n";
	echo "				break;\n";
	echo "		}\n";
	echo "		focus_editor();\n";
	echo "	}\n";

	echo "	function toggle_option_do(opt_name) {\n";
	echo "		var opt_val = editor.getOption(opt_name);\n";
	echo "		editor.setOption(opt_name, ((opt_val) ? false : true));\n";
	echo "	}\n";

	echo "	function focus_editor() {\n";
	echo "		editor.focus();\n";
	echo "	}\n";

	//copy the value from the editor on submit
	echo "	function set_value() {\n";
	echo "		$('#email_body').val(editor.session.getValue());\n";
	echo "	}\n";

	//load editor value from hidden textarea
	echo "	function load_value() {\n";
	echo "		editor.session.setValue($('#email_body').val());";
	echo "	}\n";

	echo "</script>\n";

	echo "<style>\n";
	echo "	div#editor {\n";
	echo "		text-align: left;\n";
	echo "		width: 100%;\n";
	echo "		height: 300px;\n";
	echo "		font-size: 12px;\n";
	echo "		}\n";
	echo "</style>\n";

//show the content
	echo "<form name='frm' id='frm' method='post' action=''>\n";
	echo "<input class='formfld' type='hidden' name='email_queue_uuid' value='".escape($email_queue_uuid)."'>\n";

	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-email_queue']."</b></div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$_SESSION['theme']['button_icon_back'],'id'=>'btn_back','collapse'=>'hide-xs','style'=>'margin-right: 15px;','link'=>'email_queue.php']);
	if ($action == 'update') {
		if (permission_exists('_add')) {
			echo button::create(['type'=>'button','label'=>$text['button-copy'],'icon'=>$_SESSION['theme']['button_icon_copy'],'id'=>'btn_copy','name'=>'btn_copy','style'=>'display: none;','onclick'=>"modal_open('modal-copy','btn_copy');"]);
		}
		if (permission_exists('_delete')) {
			echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$_SESSION['theme']['button_icon_delete'],'id'=>'btn_delete','name'=>'btn_delete','style'=>'display: none; margin-right: 15px;','onclick'=>"modal_open('modal-delete','btn_delete');"]);
		}
	}
	echo button::create(['type'=>'submit','label'=>$text['button-save'],'icon'=>$_SESSION['theme']['button_icon_save'],'id'=>'btn_save','collapse'=>'hide-xs']);
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	echo $text['title_description-email_queue']."\n";
	echo "<br /><br />\n";

	if ($action == 'update') {
		if (permission_exists('email_queue_add')) {
			echo modal::create(['id'=>'modal-copy','type'=>'copy','actions'=>button::create(['type'=>'submit','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_copy','style'=>'float: right; margin-left: 15px;','collapse'=>'never','name'=>'action','value'=>'copy','onclick'=>"modal_close();"])]);
		}
		if (permission_exists('email_queue_delete')) {
			echo modal::create(['id'=>'modal-delete','type'=>'delete','actions'=>button::create(['type'=>'submit','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_delete','style'=>'float: right; margin-left: 15px;','collapse'=>'never','name'=>'action','value'=>'delete','onclick'=>"modal_close();"])]);
		}
	}

	echo "<div class='card'>\n";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-email_date']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "  <input class='formfld' type='text' name='email_date' maxlength='255' value='".escape($email_date)."'>\n";
	echo "<br />\n";
	echo $text['description-email_date']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-email_from']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<input class='formfld' type='text' name='email_from' maxlength='255' value='".escape($email_from)."'>\n";
	echo "<br />\n";
	echo $text['description-email_from']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-email_to']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	if (isset($email_to) && substr_count($email_to, ',') != 0) {
		echo "	<textarea class='formfld' style='width: 450px; height: 100px;' name='email_to'>";
		$email_to_array = explode(',', $email_to);
		if (is_array($email_to_array) && @sizeof($email_to_array) != 0) {
			foreach ($email_to_array as $email_to_address) {
				echo escape($email_to_address)."\n";
			}
		}
		echo "</textarea>\n";
	}
	else {
		echo "	<input class='formfld' type='text' name='email_to' maxlength='255' value='".escape($email_to)."'>\n";
	}
	echo "<br />\n";
	echo $text['description-email_to']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-email_subject']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<input class='formfld' type='text' name='email_subject' maxlength='255' value='".escape($email_subject)."'>\n";
	echo "<br />\n";
	echo $text['description-email_subject']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-email_body']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<textarea class='formfld' name='email_body' id='email_body' style='display: none;'>".$email_body."</textarea>\n";
	echo "	<div id='editor'></div>\n";
	echo "	<table cellpadding='0' cellspacing='0' border='0' style='float: right; padding-top: 5px;'>\n";
	echo "		<tr>\n";
	echo "			<td valign='middle' style='padding-left: 6px;'><i class='fas fa-list-ul fa-lg ace_control' title=\"".$text['label-toggle_line_numbers']."\" onclick=\"toggle_option('numbering');\"></i></td>\n";
	echo "			<td valign='middle' style='padding-left: 6px;'><i class='fas fa-eye-slash fa-lg ace_control' title=\"".$text['label-toggle_invisibles']."\" onclick=\"toggle_option('invisibles');\"></i></td>\n";
	echo "			<td valign='middle' style='padding-left: 6px;'><i class='fas fa-indent fa-lg ace_control' title=\"".$text['label-toggle_indent_guides']."\" onclick=\"toggle_option('indenting');\"></i></td>\n";
	echo "			<td valign='middle' style='padding-left: 6px;'><i class='fas fa-search fa-lg ace_control' title=\"".$text['label-find_replace']."\" onclick=\"editor.execCommand('replace');\"></i></td>\n";
	echo "			<td valign='middle' style='padding-left: 6px;'><i class='fas fa-chevron-down fa-lg ace_control' title=\"".$text['label-go_to_line']."\" onclick=\"editor.execCommand('gotoline');\"></i></td>\n";
	echo "			<td valign='middle' style='padding-left: 15px;'>\n";
	echo "				<select id='size' class='formfld' onchange=\"document.getElementById('editor').style.fontSize = this.options[this.selectedIndex].value; focus_editor();\">\n";
	$sizes = explode(',','9px,10px,11px,12px,14px,16px,18px,20px');
	if (!in_array($setting_size, $sizes)) {
		echo "				<option value='".$setting_size."'>".escape($setting_size)."</option>\n";
		echo "				<option value='' disabled='disabled'></option>\n";
	}
	foreach ($sizes as $size) {
		$selected = $size == $setting_size ? 'selected' : null;
		echo "				<option value='".$size."' ".$selected.">".escape($size)."</option>\n";
	}
	echo "				</select>\n";
	echo "			</td>\n";
	echo "			<td valign='middle' style='padding-left: 4px; padding-right: 0px;'>\n";
	$themes['Light']['chrome']= 'Chrome';
	$themes['Light']['clouds']= 'Clouds';
	$themes['Light']['crimson_editor']= 'Crimson Editor';
	$themes['Light']['dawn']= 'Dawn';
	$themes['Light']['dreamweaver']= 'Dreamweaver';
	$themes['Light']['eclipse']= 'Eclipse';
	$themes['Light']['github']= 'GitHub';
	$themes['Light']['iplastic']= 'IPlastic';
	$themes['Light']['solarized_light']= 'Solarized Light';
	$themes['Light']['textmate']= 'TextMate';
	$themes['Light']['tomorrow']= 'Tomorrow';
	$themes['Light']['xcode']= 'XCode';
	$themes['Light']['kuroir']= 'Kuroir';
	$themes['Light']['katzenmilch']= 'KatzenMilch';
	$themes['Light']['sqlserver']= 'SQL Server';
	$themes['Dark']['ambiance']= 'Ambiance';
	$themes['Dark']['chaos']= 'Chaos';
	$themes['Dark']['clouds_midnight']= 'Clouds Midnight';
	$themes['Dark']['cobalt']= 'Cobalt';
	$themes['Dark']['idle_fingers']= 'idle Fingers';
	$themes['Dark']['kr_theme']= 'krTheme';
	$themes['Dark']['merbivore']= 'Merbivore';
	$themes['Dark']['merbivore_soft']= 'Merbivore Soft';
	$themes['Dark']['mono_industrial']= 'Mono Industrial';
	$themes['Dark']['monokai']= 'Monokai';
	$themes['Dark']['pastel_on_dark']= 'Pastel on dark';
	$themes['Dark']['solarized_dark']= 'Solarized Dark';
	$themes['Dark']['terminal']= 'Terminal';
	$themes['Dark']['tomorrow_night']= 'Tomorrow Night';
	$themes['Dark']['tomorrow_night_blue']= 'Tomorrow Night Blue';
	$themes['Dark']['tomorrow_night_bright']= 'Tomorrow Night Bright';
	$themes['Dark']['tomorrow_night_eighties']= 'Tomorrow Night 80s';
	$themes['Dark']['twilight']= 'Twilight';
	$themes['Dark']['vibrant_ink']= 'Vibrant Ink';
	echo "				<select id='theme' class='formfld' onchange=\"editor.setTheme('ace/theme/' + this.options[this.selectedIndex].value); focus_editor();\">\n";
	foreach ($themes as $optgroup => $theme) {
		echo "				<optgroup label='".$optgroup."'>\n";
		foreach ($theme as $value => $label) {
			$selected = strtolower($label) == strtolower($setting_theme) ? 'selected' : null;
			echo "				<option value='".$value."' ".$selected.">".escape($label)."</option>\n";
		}
		echo "				</optgroup>\n";
	}

	echo "				</select>\n";
	echo "			</td>\n";
	echo "		</tr>\n";
	echo "	</table>\n";
	echo "<br />\n";
	echo $text['description-email_body']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-email_status']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<select class='formfld' name='email_status'>\n";
	echo "		<option value='waiting' ".($email_status == 'waiting' ? "selected='selected'" : null).">".ucwords($text['label-waiting'])."</option>\n";
	echo "		<option value='trying' ".($email_status == 'trying' ? "selected='selected'" : null).">".ucwords($text['label-trying'])."</option>\n";
	echo "		<option value='sent' ".($email_status == 'sent' ? "selected='selected'" : null).">".ucwords($text['label-sent'])."</option>\n";
	echo "		<option value='failed' ".($email_status == 'failed' ? "selected='selected'" : null).">".ucwords($text['label-failed'])."</option>\n";
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-email_status']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-email_retry_count']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<input class='formfld' type='text' name='email_retry_count' maxlength='255' value='".escape($email_retry_count)."'>\n";
	echo "<br />\n";
	echo $text['description-email_retry_count']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	//echo "<tr>\n";
	//echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	//echo "	".$text['label-email_action_before']."\n";
	//echo "</td>\n";
	//echo "<td class='vtable' style='position: relative;' align='left'>\n";
	//echo "	<input class='formfld' type='text' name='email_action_before' maxlength='255' value='".escape($email_action_before)."'>\n";
	//echo "<br />\n";
	//echo $text['description-email_action_before']."\n";
	//echo "</td>\n";
	//echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-email_action_after']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<input class='formfld' type='text' name='email_action_after' maxlength='255' value='".escape($email_action_after)."'>\n";
	echo "<br />\n";
	echo $text['description-email_action_after']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	if ($_SESSION['email_queue']['save_response']['boolean'] == 'true') {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-email_response']."\n";
		echo "</td>\n";
		echo "<td class='vtable' style='position: relative;' align='left'>\n";
		echo "	<textarea class='formfld' style='width: 450px; height: 100px;' name='email_response'>".$email_response."</textarea>\n";
		echo "<br />\n";
		echo ($text['description-email_response'] ?? '')."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	echo "</table>";
	echo "</div>";
	echo "<br /><br />";

	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";

	echo "</form>";


	echo "<script type='text/javascript' src='".PROJECT_PATH."/resources/ace/ace.js' charset='utf-8'></script>\n";
	echo "<script type='text/javascript'>\n";

//load editor
	echo "	var editor = ace.edit('editor');\n";
	echo "	editor.setOptions({\n";
	echo "		mode: 'ace/mode/html',\n";
	echo "		theme: 'ace/theme/'+document.getElementById('theme').options[document.getElementById('theme').selectedIndex].value,\n";
	echo "		selectionStyle: 'text',\n";
	echo "		cursorStyle: 'smooth',\n";
	echo "		showInvisibles: ".$setting_invisibles.",\n";
	echo "		displayIndentGuides: ".$setting_indenting.",\n";
	echo "		showLineNumbers: ".$setting_numbering.",\n";
	echo "		showGutter: true,\n";
	echo "		scrollPastEnd: true,\n";
	echo "		fadeFoldWidgets: ".$setting_numbering.",\n";
	echo "		showPrintMargin: false,\n";
	echo "		highlightGutterLine: false,\n";
	echo "		useSoftTabs: false\n";
	echo "		});\n";
	echo "	document.getElementById('editor').style.fontSize='".$setting_size."';\n";
	echo "	focus_editor();\n";

//load value into editor
	echo "	load_value();\n";

//remove certain keyboard shortcuts
	echo "	editor.commands.bindKey('Ctrl-T', null);\n"; //disable transpose letters - prefer new browser tab
	echo "	editor.commands.bindKey('Ctrl-F', null);\n"; //disable find - control broken with bootstrap
	echo "	editor.commands.bindKey('Ctrl-H', null);\n"; //disable replace - control broken with bootstrap

	echo "</script>\n";

//include the footer
	require_once "resources/footer.php";

?>