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
	Portions created by the Initial Developer are Copyright (C) 2008-2019
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	James Rose <james.o.rose@gmail.com>
*/

//includes
	include "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//permissions
	if (permission_exists('sql_query')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

// load editor preferences/defaults
	$setting_size = ($_SESSION["editor"]["font_size"]["text"] != '') ? $_SESSION["editor"]["font_size"]["text"] : '12px';
	$setting_theme = ($_SESSION["editor"]["theme"]["text"] != '') ? $_SESSION["editor"]["theme"]["text"] : 'cobalt';
	$setting_invisibles = ($_SESSION["editor"]["invisibles"]["boolean"] != '') ? $_SESSION["editor"]["invisibles"]["boolean"] : 'false';
	$setting_indenting = ($_SESSION["editor"]["indent_guides"]["boolean"] != '') ? $_SESSION["editor"]["indent_guides"]["boolean"] : 'false';
	$setting_numbering = ($_SESSION["editor"]["line_numbers"]["boolean"] != '') ? $_SESSION["editor"]["line_numbers"]["boolean"] : 'true';

//get the html values and set them as variables
	$code = trim($_POST["code"]);
	$command = trim($_POST["command"]);

//check the captcha
	$command_authorized = false;
	if (strlen($code) > 0) {
		if (strtolower($_SESSION['captcha']) == strtolower($code)) {
			$command_authorized = true;
		}
	}

//set editor moder
	$mode = 'sql';

//show the header
	require_once "resources/header.php";
	$document['title'] = $text['title-command'];

//pdo database connection
	require_once "sql_query_pdo.php";

//scripts and styles
	?>
	<script language="JavaScript" type="text/javascript">
		function submit_check() {
			document.getElementById('command').value = editor.getSession().getValue();
			if (document.getElementById('mode').value == 'sql') {
				$('#frm').prop('target', 'iframe').prop('action', 'sql_query_result.php?code='+ document.getElementById('code').value);
			}
			else {
				if (document.getElementById('command').value == '') {
					focus_editor();
					return false;
				}
				$('#frm').prop('target', '').prop('action', '');
			}
			return true;
		}

		function toggle_option(opt) {
			switch (opt) {
				case 'numbering': 	toggle_option_do('showLineNumbers'); toggle_option_do('fadeFoldWidgets'); break;
				case 'invisibles':	toggle_option_do('showInvisibles'); break;
				case 'indenting':	toggle_option_do('displayIndentGuides'); break;
			}
			focus_editor();
		}

		function toggle_option_do(opt_name) {
			var opt_val = editor.getOption(opt_name);
			editor.setOption(opt_name, ((opt_val) ? false : true));
		}

		function insert_clip(before, after) {
			var selected_text = editor.session.getTextRange(editor.getSelectionRange());
			editor.insert(before + selected_text + after);
			focus_editor();
		}

		function focus_editor() {
			editor.focus();
		}

		function set_handler(handler) {
			switch (handler) {
				case 'sql':
					document.getElementById('description').innerHTML = "<?php echo $text['description-sql'];?>";
					editor.getSession().setMode('ace/mode/sql');
					$('#mode option[value=sql]').prop('selected',true);
					$('#response').hide();
					break;
				default:
					break;
			}
			focus_editor();
		}

		function reset_editor() {
			editor.getSession().setValue('');
			$('#iframe').prop('src','');
			focus_editor();
		}
	</script>
	<style>
		img.control {
			cursor: pointer;
			width: auto;
			height: 23px;
			border: none;
			opacity: 0.5;
			}

		img.control:hover {
			opacity: 1.0;
			}

		div#editor {
			box-shadow: 0 3px 10px #333;
			text-align: left;
			width: 100%;
			height: calc(100% - 30px);
			font-size: 12px;
			}
	</style>

<?php

//generate the captcha image
	$_SESSION['captcha'] = generate_password(7, 2);
	$captcha = new captcha;
	$captcha->code = $_SESSION['captcha'];
	$image_base64 = $captcha->image_base64();

//show the header
	echo "<form method='post' name='frm' id='frm' action='exec.php' style='margin: 0;' onsubmit='return submit_check();'>\n";
	echo "<table cellpadding='0' cellspacing='0' border='0' width='100%'>";
	echo "	<tr>";
	echo "		<td valign='top' align='left' width='50%'>";
	echo "			<b>".$text['label-sql_query']."</b>\n";
	echo "		</td>";
	echo "		<td valign='top' align='right' nowrap='nowrap'>";

	//add the captcha
	echo "				<img src=\"data:image/png;base64, ".$image_base64."\" /><input type='text' class='txt' style='width: 150px; margin-left: 15px;' name='code' id='code' value=''>\n";
	echo "				&nbsp; &nbsp; &nbsp;\n";

	//sql controls
	echo "				<span class='sql_controls'>";
	//echo "					".$text['label-table']."<br />";
	echo "					<select name='table_name' id='table_name' class='formfld'>\n";
	echo "						<option value=''></option>\n";
	switch ($db_type) {
		case 'sqlite': $sql = "select name from sqlite_master where type='table' order by name;"; break;
		case 'pgsql': $sql = "select table_name as name from information_schema.tables where table_schema='public' and table_type='BASE TABLE' order by table_name"; break;
		case 'mysql': $sql = "show tables"; break;
	}
	$database = new database;
	$result = $database->select($sql, null, 'all');
	if (is_array($result) && @sizeof($result) != 0) {
		foreach ($result as &$row) {
			$row = array_values($row);
			echo "					<option value='".escape($row[0])."'>".escape($row[0])."</option>\n";
		}
	}
	unset($sql, $result, $row);
	echo "					</select>\n";
	//echo "					<br /><br />\n";
	//echo "					".$text['label-result_type']."<br />";
	echo "					<select name='sql_type' id='sql_type' class='formfld'>\n";
	echo "						<option value=''>".$text['option-result_type_view']."</option>\n";
	echo "						<option value='csv'>".$text['option-result_type_csv']."</option>\n";
	echo "						<option value='inserts'>".$text['option-result_type_insert']."</option>\n";
	echo "					</select>\n";
	echo "				</span>";

	echo "				<input type='button' class='btn' style='margin-top: 0px;' title=\"".$text['button-execute']." [Ctrl+Enter]\" value=\"    ".$text['button-execute']."    \" onclick=\"$('form#frm').submit();\">";
	echo "				<input type='button' class='btn' style='margin-top: 0px;' title=\"\" value=\"    ".$text['button-reset']."    \" onclick=\"reset_editor();\">";

	echo "		</td>\n";
	echo "	</tr>\n";
	echo "	<tr>\n";
	echo "		<td colspan='2'>\n";
	echo 			$text['description-sql_query']."\n";
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "</table>";
	echo "<br>";

//html form
	echo "<input type='hidden' name='id' value='".escape($_REQUEST['id'])."'>\n"; //sql db id
	echo "<textarea name='command' id='command' style='display: none;'></textarea>";
	echo "<table cellpadding='0' cellspacing='0' border='0' style='width: 100%;'>\n";
	echo "	<tr>";
	echo "		<td style='width: 280px;' valign='top' nowrap>";

	echo "			<table cellpadding='0' cellspacing='0' border='0' width='100%' height='100%'>";
	if (permission_exists('edit_view') && file_exists($_SERVER["PROJECT_ROOT"]."/app/edit/")) {
		echo "			<tr>";
		echo "				<td valign='top' height='100%'>";
		echo "					<iframe id='clip_list' src='".PROJECT_PATH."/app/edit/clip_list.php' style='border: none; border-top: 1px solid #ccc; border-bottom: 1px solid #ccc; height: calc(100% - 2px); width: calc(100% - 15px);'></iframe>\n";
		echo "				</td>";
		echo "			</tr>";
	}
	echo "			</table>";

	echo "		</td>";
	echo "		<td valign='top' style='height: 400px;'>"
	?>
	<table cellpadding='0' cellspacing='0' border='0' style='width: 100%;'>
		<tr>
			<td valign='middle' style='padding: 0 6px;' width='100%'><span id='description'><?php echo $text['description-'.$handler]; ?></span></td>
			<td valign='middle' style='padding: 0;'><img src='resources/images/blank.gif' style='width: 1px; height: 30px; border: none;'></td>
			<td valign='middle' style='padding-left: 6px;'><img src='resources/images/icon_numbering.png' title='Toggle Line Numbers' class='control' onclick="toggle_option('numbering');"></td>
			<td valign='middle' style='padding-left: 6px;'><img src='resources/images/icon_invisibles.png' title='Toggle Invisibles' class='control' onclick="toggle_option('invisibles');"></td>
			<td valign='middle' style='padding-left: 6px;'><img src='resources/images/icon_indenting.png' title='Toggle Indent Guides' class='control' onclick="toggle_option('indenting');"></td>
			<!--<td valign='middle' style='padding-left: 6px;'><img src='resources/images/icon_replace.png' title='Show Find/Replace [Ctrl+H]' class='control' onclick="editor.execCommand('replace');"></td>-->
			<td valign='middle' style='padding-left: 6px;'><img src='resources/images/icon_goto.png' title='Show Go To Line' class='control' onclick="editor.execCommand('gotoline');"></td>
			<td valign='middle' style='padding-left: 10px;'>
				<select id='mode' style='height: 23px;' onchange="editor.getSession().setMode((this.options[this.selectedIndex].value == 'php') ? {path:'ace/mode/php', inline:true} : 'ace/mode/' + this.options[this.selectedIndex].value); focus_editor();">
					<?php
					$modes['php'] = 'PHP';
					$modes['css'] = 'CSS';
					$modes['html'] = 'HTML';
					$modes['javascript'] = 'JS';
					$modes['json'] = 'JSON';
					$modes['ini'] = 'Conf';
					$modes['lua'] = 'Lua';
					$modes['text'] = 'Text';
					$modes['xml'] = 'XML';
					$modes['sql'] = 'SQL';
					foreach ($modes as $value => $label) {
						$selected = $value == $mode ? 'selected' : null;
						echo "<option value='".$value."' ".$selected.">".escape($label)."</option>\n";
					}
					?>
				</select>
			</td>
			<td valign='middle' style='padding-left: 4px;'>
				<select id='size' style='height: 23px;' onchange="document.getElementById('editor').style.fontSize = this.options[this.selectedIndex].value; focus_editor();">
					<?php
					$sizes = explode(',','9px,10px,11px,12px,14px,16px,18px,20px');
					if (!in_array($setting_size, $sizes)) {
						echo "<option value='".$setting_size."'>".escape($setting_size)."</option>\n";
						echo "<option value='' disabled='disabled'></option>\n";
					}
					foreach ($sizes as $size) {
						$selected = ($size == $setting_size) ? 'selected' : null;
						echo "<option value='".$size."' ".$selected.">".escape($size)."</option>\n";
					}
					?>
				</select>
			</td>
			<td valign='middle' style='padding-left: 4px; padding-right: 0px;'>
				<select id='theme' style='height: 23px;' onchange="editor.setTheme('ace/theme/' + this.options[this.selectedIndex].value); focus_editor();">
					<?php
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
					?>
				</select>
			</td>
		</tr>
	</table>
	<div id='editor'><?php echo $command; ?></div>

	<?php
	echo "		</td>";
	echo "	</tr>\n";
	echo "</table>";
	echo "</form>";
	echo "<br /><br />";
	?>

	<script type="text/javascript" src="<?php echo PROJECT_PATH; ?>/resources/ace/ace.js" charset="utf-8"></script>
	<script type="text/javascript">
		//load ace editor
			var editor = ace.edit("editor");
			editor.setOptions({
				mode: 'ace/mode/<?php echo $mode;?>',
				theme: 'ace/theme/'+document.getElementById('theme').options[document.getElementById('theme').selectedIndex].value,
				selectionStyle: 'text',
				cursorStyle: 'smooth',
				showInvisibles: <?php echo $setting_invisibles;?>,
				displayIndentGuides: <?php echo $setting_indenting;?>,
				showLineNumbers: <?php echo $setting_numbering;?>,
				showGutter: true,
				scrollPastEnd: true,
				fadeFoldWidgets: <?php echo $setting_numbering;?>,
				showPrintMargin: false,
				highlightGutterLine: false,
				useSoftTabs: false
				});
			<?php if ($mode == 'php') { ?>
				editor.getSession().setMode({path:'ace/mode/php', inline:true});
			<?php } ?>
			document.getElementById('editor').style.fontSize='<?php echo escape($setting_size);?>';
			focus_editor();

		//keyboard shortcut to execute command
			<?php key_press('ctrl+enter', 'down', 'window', null, null, "$('form#frm').submit();", false); ?>

		//remove certain keyboard shortcuts
			editor.commands.bindKey("Ctrl-T", null); //disable transpose letters - prefer new browser tab
			editor.commands.bindKey("Ctrl-F", null); //disable find - control broken with bootstrap
			editor.commands.bindKey("Ctrl-H", null); //disable replace - control broken with bootstrap
	</script>

<?php

//sql result
	echo "<span id='sql_response'>";
	//echo "<b>".$text['label-results']."</b>\n";
	//echo "<br /><br />\n";
	echo "<iframe name='iframe' id='iframe' style='width: calc(100% - 3px); height: 500px; background-color: #fff; border: 0px solid #c0c0c0;'></iframe>\n";
	echo "</span>";

//show the footer
	require_once "resources/footer.php";

?>
