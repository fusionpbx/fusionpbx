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

//set the include path
	$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
	set_include_path(parse_ini_file($conf[0])['document.root']);

//includes files
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
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
	if (count($_POST) > 0) {
		$domain_uuid = $_POST["domain_uuid"];
		$template_language = $_POST["template_language"];
		$template_category = $_POST["template_category"];
		$template_subcategory = $_POST["template_subcategory"];
		$template_subject = $_POST["template_subject"];
		$template_body = $_POST["template_body"];
		$template_type = $_POST["template_type"];
		$template_enabled = $_POST["template_enabled"] ?: 'false';
		$template_description = $_POST["template_description"];
	}

//process the user data and save it to the database
	if (count($_POST) > 0 && strlen($_POST["persistformvar"]) == 0) {

		//get the uuid from the POST
			if ($action == "update") {
				$email_template_uuid = $_POST["email_template_uuid"];
			}

		//validate the token
			$token = new token;
			if (!$token->validate($_SERVER['PHP_SELF'])) {
				message::add($text['message-invalid_token'],'negative');
				header('Location: email_templates.php');
				exit;
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
			}

		//prepare the array
			$array['email_templates'][0]['domain_uuid'] = $domain_uuid;
			$array['email_templates'][0]['email_template_uuid'] = $email_template_uuid;
			$array['email_templates'][0]['template_language'] = $template_language;
			$array['email_templates'][0]['template_category'] = $template_category;
			$array['email_templates'][0]['template_subcategory'] = $template_subcategory;
			$array['email_templates'][0]['template_subject'] = $template_subject;
			$array['email_templates'][0]['template_body'] = $template_body;
			$array['email_templates'][0]['template_type'] = $template_type;
			$array['email_templates'][0]['template_enabled'] = $template_enabled;
			$array['email_templates'][0]['template_description'] = $template_description;

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

//set the defaults
	if (strlen($template_enabled) == 0) { $template_enabled = 'true'; }

//load editor preferences/defaults
	$setting_size = $_SESSION["editor"]["font_size"]["text"] != '' ? $_SESSION["editor"]["font_size"]["text"] : '12px';
	$setting_theme = $_SESSION["editor"]["theme"]["text"] != '' ? $_SESSION["editor"]["theme"]["text"] : 'cobalt';
	$setting_invisibles = $_SESSION["editor"]["invisibles"]["boolean"] != '' ? $_SESSION["editor"]["invisibles"]["boolean"] : 'false';
	$setting_indenting = $_SESSION["editor"]["indent_guides"]["boolean"] != '' ? $_SESSION["editor"]["indent_guides"]["boolean"] : 'false';
	$setting_numbering = $_SESSION["editor"]["line_numbers"]["boolean"] != '' ? $_SESSION["editor"]["line_numbers"]["boolean"] : 'true';

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//include the header
	$document['title'] = $text['title-email_template'];
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
	echo "		$('#template_body').val(editor.session.getValue());\n";
	echo "	}\n";

	//load editor value from hidden textarea
	echo "	function load_value() {\n";
	echo "		editor.session.setValue($('#template_body').val());";
	echo "	}\n";

	echo "</script>\n";

	echo "<style>\n";

	echo "	img.control {\n";
	echo "		cursor: pointer;\n";
	echo "		width: auto;\n";
	echo "		height: 23px;\n";
	echo "		border: none;\n";
	echo "		opacity: 0.5;\n";
	echo "		}\n";

	echo "	img.control:hover {\n";
	echo "		opacity: 1.0;\n";
	echo "		}\n";

	echo "	div#editor {\n";
	//echo "	box-shadow: 0 3px 10px #333;\n";
	echo "		text-align: left;\n";
	echo "		width: 100%;\n";
	echo "		height: 600px;\n";
	echo "		font-size: 12px;\n";
	echo "		}\n";

	echo "</style>\n";

//show the content
	echo "<form name='frm' id='frm' method='post'>\n";

	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-email_template']."</b></div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$_SESSION['theme']['button_icon_back'],'id'=>'btn_back','style'=>'margin-right: 15px;','link'=>'email_templates.php']);
	echo button::create(['type'=>'button','label'=>$text['button-save'],'icon'=>$_SESSION['theme']['button_icon_save'],'id'=>'btn_save','onclick'=>"set_value(); $('#frm').submit();"]);
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";

	echo "<tr>\n";
	echo "<td width='30%' class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-template_language']."\n";
	echo "</td>\n";
	echo "<td width='70%' class='vtable' style='position: relative;' align='left'>\n";
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
	echo "	<textarea class='formfld' name='template_body' id='template_body' style='display: none;'>".$template_body."</textarea>\n";
	echo "	<div id='editor'></div>\n";
	echo "	<table cellpadding='0' cellspacing='0' border='0' style='float: right; padding-top: 5px;'>\n";
	echo "		<tr>\n";
	echo "			<td valign='middle' style='padding-left: 6px;'><img src='resources/images/icon_numbering.png' title='Toggle Line Numbers' class='control' onclick=\"toggle_option('numbering');\"></td>\n";
	echo "			<td valign='middle' style='padding-left: 6px;'><img src='resources/images/icon_invisibles.png' title='Toggle Invisibles' class='control' onclick=\"toggle_option('invisibles');\"></td>\n";
	echo "			<td valign='middle' style='padding-left: 6px;'><img src='resources/images/icon_indenting.png' title='Toggle Indent Guides' class='control' onclick=\"toggle_option('indenting');\"></td>\n";
 	echo "			<td valign='middle' style='padding-left: 6px;'><img src='resources/images/icon_replace.png' title='Show Find/Replace [Ctrl+H]' class='control' onclick=\"editor.execCommand('replace');\"></td>\n";
	echo "			<td valign='middle' style='padding-left: 6px;'><img src='resources/images/icon_goto.png' title='Show Go To Line' class='control' onclick=\"editor.execCommand('gotoline');\"></td>\n";
	echo "			<td valign='middle' style='padding-left: 4px;'>\n";
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
	echo "				<select id='theme' class='formfld' onchange=\"editor.setTheme('ace/theme/' + this.options[this.selectedIndex].value); focus_editor();\">\n";
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
	foreach ($themes as $optgroup => $theme) {
		echo "<optgroup label='".$optgroup."'>\n";
		foreach ($theme as $value => $label) {
			$selected = strtolower($label) == strtolower($setting_theme) ? 'selected' : null;
			echo "<option value='".$value."' ".$selected.">".escape($label)."</option>\n";
		}
		echo "</optgroup>\n";
	}

	echo "				</select>\n";
	echo "			</td>\n";
	echo "		</tr>\n";
	echo "	</table>\n";
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
	echo "	<select class='formfld' name='template_type'>\n";
	echo "		<option value='html'>HTML</option>\n";
	echo "		<option value='text' ".($template_type == 'text' ? "selected='selected'" : null).">".$text['label-template_text']."</option>\n";
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-template_type']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-template_enabled']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	if (substr($_SESSION['theme']['input_toggle_style']['text'], 0, 6) == 'switch') {
		echo "	<label class='switch'>\n";
		echo "		<input type='checkbox' id='template_enabled' name='template_enabled' value='true' ".($template_enabled == 'true' ? "checked='checked'" : null).">\n";
		echo "		<span class='slider'></span>\n";
		echo "	</label>\n";
	}
	else {
		echo "	<select class='formfld' id='template_enabled' name='template_enabled'>\n";
		echo "		<option value='true' ".($template_enabled == 'true' ? "selected='selected'" : null).">".$text['option-true']."</option>\n";
		echo "		<option value='false' ".($template_enabled == 'false' ? "selected='selected'" : null).">".$text['option-false']."</option>\n";
		echo "	</select>\n";
	}
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

	echo "</table>";
	echo "<br /><br />";

	if ($action == "update") {
		echo "<input type='hidden' name='email_template_uuid' value='".escape($email_template_uuid)."'>\n";
	}
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
