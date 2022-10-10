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
	Portions created by the Initial Developer are Copyright (C) 2008-2021
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	Luis Daniel Lucio Quiroz <dlucio@okay.com.mx>
*/

//set the include path
	$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
	set_include_path(parse_ini_file($conf[0])['document.root']);

//includes files
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('dialplan_edit')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//get the uuids
	if (is_uuid($_REQUEST['id'])) {
		$dialplan_uuid = $_REQUEST['id'];
	}
	if (is_uuid($_REQUEST['app_uuid'])) {
		$app_uuid = $_REQUEST['app_uuid'];
	}
	$dialplan_xml = $_REQUEST['dialplan_xml'];

//process the HTTP POST
	if (count($_POST) > 0 && strlen($_POST["persistformvar"]) == 0) {

		//validate the token
			$token = new token;
			if (!$token->validate($_SERVER['PHP_SELF'])) {
				message::add($text['message-invalid_token'],'negative');
				header('Location: dialplans.php');
				exit;
			}

		//get the dialplan xml
			if (is_uuid($dialplan_uuid)) {
				$sql = "select * from v_dialplans ";
				$sql .= "where dialplan_uuid = :dialplan_uuid ";
				$parameters['dialplan_uuid'] = $dialplan_uuid;
				$database = new database;
				$row = $database->select($sql, $parameters, 'row');
				if (is_array($row) && @sizeof($row) != 0) {
					$app_uuid = $row["app_uuid"];
					$dialplan_context = $row["dialplan_context"];
				}
				unset($sql, $parameters, $row);
			}

		//validate the xml
			$dialplan_valid = true;
			if (preg_match("/.*([\"\'])system([\"\']).*>/i", $dialplan_xml)) {
				$dialplan_valid = false;
			}
			if (preg_match("/.*([\"\'])bgsystem([\"\']).*>/i", $dialplan_xml)) {
				$dialplan_valid = false;
			}
			if (preg_match("/.*([\"\'])bg_spawn([\"\']).*>/i", $dialplan_xml)) {
				$dialplan_valid = false;
			}
			if (preg_match("/.*([\"\'])spawn([\"\']).*>/i", $dialplan_xml)) {
				$dialplan_valid = false;
			}
			if (preg_match("/.*([\"\'])spawn_stream([\"\']).*>/i", $dialplan_xml)) {
				$dialplan_valid = false;
			}
			if (preg_match("/.*{system.*/i", $dialplan_xml)) {
				$dialplan_valid = false;
			}
			if (preg_match("/.*{bgsystem.*/i", $dialplan_xml)) {
				$dialplan_valid = false;
			}
			if (preg_match("/.*{bg_spawn.*/i", $dialplan_xml)) {
				$dialplan_valid = false;
			}
			if (preg_match("/.*{spawn.*/i", $dialplan_xml)) {
				$dialplan_valid = false;
			}
			if (preg_match("/.*{spawn_stream.*/i", $dialplan_xml)) {
				$dialplan_valid = false;
			}

		//disable xml entities and load the xml object to test if the xml is valid
			libxml_disable_entity_loader(true);
			preg_match_all('/^\s*<extension.+>(?:[\S\s])+<\/extension>\s*$/mU', $dialplan_xml, $matches);
			foreach($matches as $match) {
				$xml = simplexml_load_string($match[0], 'SimpleXMLElement', LIBXML_NOCDATA);
				if (!$xml) {
					//$errors = libxml_get_errors();
					$dialplan_valid = false;
					break;
				}
			}

		//save the xml to the database
			if ($dialplan_valid) {
				//build the dialplan array
					$x = 0;
					//$array['dialplans'][$x]["domain_uuid"] = $_SESSION['domain_uuid'];
					$array['dialplans'][$x]["dialplan_uuid"] = $dialplan_uuid;
					$array['dialplans'][$x]["dialplan_xml"] =  $dialplan_xml;

				//save to the data
					$database = new database;
					$database->app_name = 'dialplans';
					$database->app_uuid = is_uuid($app_uuid) ? $app_uuid : '742714e5-8cdf-32fd-462c-cbe7e3d655db';
					$database->save($array);
					unset($array);

				//clear the cache
					$cache = new cache;
					if ($dialplan_context == "\${domain_name}" or $dialplan_context == "global") {
						$dialplan_context = "*";
					}
					$cache->delete("dialplan:".$dialplan_context);

				//save the message to a session variable
					message::add($text['message-update']);
			}
			else {
				//save the message to a session variable
					message::add($text['message-failed'], 'negative');
			}

		//redirect the user
			header("Location: dialplan_edit.php?id=".$dialplan_uuid.(is_uuid($app_uuid) ? "&app_uuid=".$app_uuid : null));
			exit;

	}

//get the dialplan xml
	if (is_uuid($dialplan_uuid)) {
		$sql = "select * from v_dialplans ";
		$sql .= "where dialplan_uuid = :dialplan_uuid ";
		$parameters['dialplan_uuid'] = $dialplan_uuid;
		$database = new database;
		$row = $database->select($sql, $parameters, 'row');
		if (is_array($row) && @sizeof($row) != 0) {
			$domain_uuid = $row["domain_uuid"];
			$dialplan_name = $row["dialplan_name"];
			$dialplan_number = $row["dialplan_number"];
			$dialplan_order = $row["dialplan_order"];
			$dialplan_continue = $row["dialplan_continue"];
			$dialplan_context = $row["dialplan_context"];
			$dialplan_xml = $row["dialplan_xml"];
			$dialplan_enabled = $row["dialplan_enabled"];
			$dialplan_description = $row["dialplan_description"];
		}
		unset($sql, $parameters, $row);
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

// load editor preferences/defaults
	$setting_size = $_SESSION["editor"]["font_size"]["text"] != '' ? $_SESSION["editor"]["font_size"]["text"] : '12px';
	$setting_theme = $_SESSION["editor"]["theme"]["text"] != '' ? $_SESSION["editor"]["theme"]["text"] : 'cobalt';
	$setting_invisibles = $_SESSION["editor"]["invisibles"]["boolean"] != '' ? $_SESSION["editor"]["invisibles"]["boolean"] : 'false';
	$setting_indenting = $_SESSION["editor"]["indent_guides"]["boolean"] != '' ? $_SESSION["editor"]["indent_guides"]["boolean"] : 'false';
	$setting_numbering = $_SESSION["editor"]["line_numbers"]["boolean"] != '' ? $_SESSION["editor"]["line_numbers"]["boolean"] : 'true';

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//show the header
	$document['title'] = $text['title-dialplan_edit'].' XML';
	require_once "resources/header.php";

//scripts and styles
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
	echo "		$('#dialplan_xml').val(editor.session.getValue());\n";
	echo "	}\n";

	//load editor value from hidden textarea
	echo "	function load_value() {\n";
	echo "		editor.session.setValue($('#dialplan_xml').val());";
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
	echo "		box-shadow: 0 3px 10px #333;\n";
	echo "		text-align: left;\n";
	echo "		width: 100%;\n";
	echo "		height: 600px;\n";
	echo "		font-size: 12px;\n";
	echo "		}\n";

	echo "</style>\n";

//show the content
	echo "<form method='post' name='frm' id='frm'>\n";

	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-dialplan_edit']." XML</b></div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$_SESSION['theme']['button_icon_back'],'id'=>'btn_back','link'=>'dialplan_edit.php?id='.urlencode($dialplan_uuid).(is_uuid($app_uuid) ? "&app_uuid=".urlencode($app_uuid) : null)]);
	echo button::create(['type'=>'button','label'=>$text['button-save'],'icon'=>$_SESSION['theme']['button_icon_save'],'id'=>'btn_save','style'=>'margin-left: 15px;','onclick'=>"set_value(); $('#frm').submit();"]);
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	echo $text['description-dialplan-edit']."\n";
	echo "<br />\n";

	echo "	<textarea name='dialplan_xml' id='dialplan_xml' style='display: none;'>".$dialplan_xml."</textarea>";
	echo "	<table cellpadding='0' cellspacing='0' border='0' style='width: 100%;'>\n";
	echo "		<tr>\n";
	echo "			<td valign='middle' style='padding: 0 6px;' width='100%'><span id='description'></span></td>\n";
	echo "			<td valign='middle' style='padding: 0;'><img src='resources/images/blank.gif' style='width: 1px; height: 30px; border: none;'></td>\n";
	echo "			<td valign='middle' style='padding-left: 6px;'><img src='resources/images/icon_numbering.png' title='Toggle Line Numbers' class='control' onclick=\"toggle_option('numbering');\"></td>\n";
	echo "			<td valign='middle' style='padding-left: 6px;'><img src='resources/images/icon_invisibles.png' title='Toggle Invisibles' class='control' onclick=\"toggle_option('invisibles');\"></td>\n";
	echo "			<td valign='middle' style='padding-left: 6px;'><img src='resources/images/icon_indenting.png' title='Toggle Indent Guides' class='control' onclick=\"toggle_option('indenting');\"></td>\n";
// 	echo "			<td valign='middle' style='padding-left: 6px;'><img src='resources/images/icon_replace.png' title='Show Find/Replace [Ctrl+H]' class='control' onclick=\"editor.execCommand('replace');\"></td>\n";
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
	echo "	<div id='editor'></div>\n";
	echo "	<br />\n";

	echo "	<input type='hidden' name='app_uuid' value='".escape($app_uuid)."'>\n";
	echo "	<input type='hidden' name='dialplan_uuid' value='".escape($dialplan_uuid)."'>\n";
	echo "	<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";

	echo "</form>\n";

	echo "<script type='text/javascript' src='".PROJECT_PATH."/resources/ace/ace.js' charset='utf-8'></script>\n";
	echo "<script type='text/javascript'>\n";

	//load editor
		echo "	var editor = ace.edit('editor');\n";
		echo "	editor.setOptions({\n";
		echo "		mode: 'ace/mode/xml',\n";
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

//show the footer
	require_once "resources/footer.php";

?>
