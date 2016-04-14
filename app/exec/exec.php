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
	James Rose <james.o.rose@gmail.com>
*/

//includes
	include "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//permissions
	if (permission_exists('exec_view')) {
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
	$setting_preview = ($_SESSION["editor"]["live_preview"]["boolean"] != '') ? $_SESSION["editor"]["live_preview"]["boolean"] : 'true';

//get the html values and set them as variables
	$handler = ($_REQUEST["handler"] != '') ? trim($_REQUEST["handler"]) : ((permission_exists('exec_switch')) ? 'switch' : null);
	$cmd = trim($_POST["cmd"]);

//set editor moder
	switch ($handler) {
		case 'php': $mode = 'php'; break;
		case 'sql': $mode = 'sql'; break;
		default: $mode = 'text';
	}

//show the header
	require_once "resources/header.php";
	$document['title'] = $text['title-command'];

//pdo voicemail database connection
	if (permission_exists('exec_sql')) {
		require_once "sql_query_pdo.php";
	}

//scripts and styles
	?>
	<script language="JavaScript" type="text/javascript">
		function submit_check() {
			document.getElementById('cmd').value = editor.getSession().getValue();
			if (document.getElementById('mode').value == 'sql') {
				$('#frm').prop('target', 'iframe').prop('action', 'sql_query_result.php');
				$('#sql_response').show();
			}
			else {
				if (document.getElementById('cmd').value == '') {
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
				<?php if (permission_exists('exec_switch')) { ?>
					case 'switch':
						document.getElementById('description').innerHTML = "<?php echo $text['description-switch'];?>";
						editor.getSession().setMode('ace/mode/text');
						$('#mode option[value=text]').prop('selected',true);
						<?php if (permission_exists('exec_sql')) { ?>
							$('.sql_controls').hide();
							document.getElementById('sql_type').selectedIndex = 0;
							document.getElementById('table_name').selectedIndex = 0;
							$('#iframe').prop('src','');
							$('#sql_response').hide();
						<?php } ?>
						break;
				<?php } ?>
				<?php if (permission_exists('exec_php')) { ?>
					case 'php':
						document.getElementById('description').innerHTML = "<?php echo $text['description-php'];?>";
						editor.getSession().setMode({path:'ace/mode/php', inline:true}); //highlight without opening tag
						$('#mode option[value=php]').prop('selected',true);
						<?php if (permission_exists('exec_sql')) { ?>
							$('.sql_controls').hide();
							document.getElementById('sql_type').selectedIndex = 0;
							document.getElementById('table_name').selectedIndex = 0;
							$('#iframe').prop('src','');
							$('#sql_response').hide();
						<?php } ?>
						break;
				<?php } ?>
				<?php if (permission_exists('exec_command')) { ?>
					case 'shell':
						document.getElementById('description').innerHTML = "<?php echo $text['description-shell'];?>";
						editor.getSession().setMode('ace/mode/text');
						$('#mode option[value=text]').prop('selected',true);
						<?php if (permission_exists('exec_sql')) { ?>
							$('.sql_controls').hide();
							document.getElementById('sql_type').selectedIndex = 0;
							document.getElementById('table_name').selectedIndex = 0;
							$('#iframe').prop('src','');
							$('#sql_response').hide();
						<?php } ?>
						break;
				<?php } ?>
				<?php if (permission_exists('exec_sql')) { ?>
					case 'sql':
						document.getElementById('description').innerHTML = "<?php echo $text['description-sql'];?>";
						editor.getSession().setMode('ace/mode/sql');
						$('#mode option[value=sql]').prop('selected',true);
						$('.sql_controls').show();
						break;
				<?php } ?>
				default:
					break;
			}
			focus_editor();
		}

		function reset_editor() {
			editor.getSession().setValue('');
			$('#cmd').val('');
			$('#response').hide();
			<?php if (permission_exists('exec_sql')) { ?>
				$('#iframe').prop('src','');
				$('#sql_response').hide();
			<?php } ?>
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

//show the header
	echo "<form method='post' name='frm' id='frm' action='exec.php' style='margin: 0;' onsubmit='return submit_check();'>\n";
	echo "<table cellpadding='0' cellspacing='0' border='0' width='100%'>";
	echo "	<tr>";
	echo "		<td valign='top' align='left' width='50%'>";
	echo "			<b>".$text['label-execute']."</b>\n";
	echo "		</td>";
	echo "		<td valign='top' align='right' nowrap='nowrap'>";

	if (permission_exists('exec_switch') || permission_exists('exec_php') || permission_exists('exec_command') || permission_exists('exec_sql')) {
		echo "				<select name='handler' id='handler' class='formfld' style='width:100px;' onchange=\"handler=this.value;set_handler(this.value);\">\n";
		echo "						<option value=''></option>\n";
		if (permission_exists('exec_switch')) { echo "<option value='switch' ".(($handler == 'switch') ? "selected='selected'" : null).">".$text['label-switch']."</option>\n"; }
		if (permission_exists('exec_php')) { echo "<option value='php' ".(($handler == 'php') ? "selected='selected'" : null).">".$text['label-php']."</option>\n"; }
		if (permission_exists('exec_command')) { echo "<option value='shell' ".(($handler == 'shell') ? "selected='selected'" : null).">".$text['label-shell']."</option>\n"; }
		if (permission_exists('exec_sql')) { echo "<option value='sql' ".(($handler == 'sql') ? "selected='selected'" : null).">".$text['label-sql']."</option>\n"; }
		echo "				</select>\n";
	}

	//sql controls
	if (permission_exists('exec_sql')) {
		echo "				<span class='sql_controls' ".(($handler != 'sql') ? "style='display: none;'" : null).">";
		//echo "					".$text['label-table']."<br />";
		echo "					<select name='table_name' id='table_name' class='formfld'>\n";
		echo "						<option value=''></option>\n";
		switch ($db_type) {
			case 'sqlite': $sql = "select name from sqlite_master where type='table' order by name;"; break;
			case 'pgsql': $sql = "select table_name as name from information_schema.tables where table_schema='public' and table_type='BASE TABLE' order by table_name"; break;
			case 'mysql': $sql = "show tables"; break;
		}
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		foreach ($result as &$row) {
			$row = array_values($row);
			echo "					<option value='".$row[0]."'>".$row[0]."</option>\n";
		}
		echo "					</select>\n";
		//echo "					<br /><br />\n";
		//echo "					".$text['label-result_type']."<br />";
		echo "					<select name='sql_type' id='sql_type' class='formfld'>\n";
		echo "						<option value=''>".$text['option-result_type_view']."</option>\n";
		echo "						<option value='csv'>".$text['option-result_type_csv']."</option>\n";
		echo "						<option value='inserts'>".$text['option-result_type_insert']."</option>\n";
		echo "					</select>\n";
		echo "				</span>";
	}
	echo "					<input type='button' class='btn' style='margin-top: 0px;' title=\"".$text['button-execute']." [Ctrl+Enter]\" value=\"    ".$text['button-execute']."    \" onclick=\"$('form#frm').submit();\">";
	echo "					<input type='button' class='btn' style='margin-top: 0px;' title=\"\" value=\"    ".$text['button-reset']."    \" onclick=\"reset_editor();\">";

	if (permission_exists('exec_sql')) {
		echo "			<span class='sql_controls' ".(($handler != 'sql') ? "style='display: none;'" : null).">";
		//echo "				<input type='button' class='btn' alt='".$text['button-select_database']."' onclick=\"document.location.href='sql_query_db.php'\" value='".$text['button-select_database']."'>\n";
		if (permission_exists('exec_sql_backup')) {
			echo "			<input type='button' class='btn' alt='".$text['button-backup']."' onclick=\"document.location.href='sql_backup.php".((strlen($_REQUEST['id']) > 0) ? "?id=".$_REQUEST['id'] : null)."'\" value='".$text['button-backup']."'>\n";
		}
		echo "			</span>";
	}
	echo "		</td>";
	echo "	</tr>";
	echo "	<tr><td colspan='2'>\n";
	echo 			$text['description-execute']."\n";
	echo "	</tr>\n";
	echo "</table>";
	echo "<br>";

//html form
	echo "<input type='hidden' name='id' value='".$_REQUEST['id']."'>\n"; //sql db id
	echo "<textarea name='cmd' id='cmd' style='display: none;'></textarea>";
	echo "<table cellpadding='0' cellspacing='0' border='0' style='width: 100%;'>\n";
	echo "	<tr>";
	echo "		<td style='width: 210px;' valign='top' nowrap>";

	echo "			<table cellpadding='0' cellspacing='0' border='0' width='100%' height='100%'>";
	if (permission_exists('script_editor_view') && file_exists($_SERVER["PROJECT_ROOT"]."/app/edit/")) {
		echo "			<tr>";
		echo "				<td valign='top' height='100%'>";
		echo "					<iframe id='clip_list' src='".PROJECT_PATH."/app/edit/cliplist.php' style='border: none; border-top: 1px solid #ccc; border-bottom: 1px solid #ccc; height: calc(100% - 2px); width: calc(100% - 15px);'></iframe>\n";
		echo "				</td>";
		echo "			</tr>";
	}
	echo "			</table>";

	echo "		</td>";
	echo "		<td valign='top' style='height: 300px;'>"
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
						if ($setting_preview == 'true') {
							$preview = "onmouseover=\"editor.getSession().setMode(".(($value == 'php') ? "{path:'ace/mode/php', inline:true}" : "'ace/mode/' + this.value").");\"";
						}
						$selected = ($value == $mode) ? 'selected' : null;
						echo "<option value='".$value."' ".$selected." ".$preview.">".$label."</option>\n";
					}
					?>
				</select>
			</td>
			<td valign='middle' style='padding-left: 4px;'>
				<select id='size' style='height: 23px;' onchange="document.getElementById('editor').style.fontSize = this.options[this.selectedIndex].value; focus_editor();">
					<?php
					$sizes = explode(',','9px,10px,11px,12px,14px,16px,18px,20px');
					$preview = ($setting_preview == 'true') ? "onmouseover=\"document.getElementById('editor').style.fontSize = this.value;\"" : null;
					if (!in_array($setting_size, $sizes)) {
						echo "<option value='".$setting_size."' ".$preview.">".$setting_size."</option>\n";
						echo "<option value='' disabled='disabled'></option>\n";
					}
					foreach ($sizes as $size) {
						$selected = ($size == $setting_size) ? 'selected' : null;
						echo "<option value='".$size."' ".$selected." ".$preview.">".$size."</option>\n";
					}
					?>
				</select>
			</td>
			<td valign='middle' style='padding-left: 4px; padding-right: 0px;'>
				<select id='theme' style='height: 23px;' onchange="editor.setTheme('ace/theme/' + this.options[this.selectedIndex].value); focus_editor();">
					<?php
					$themes['Bright']['chrome']= 'Chrome';
					$themes['Bright']['clouds']= 'Clouds';
					$themes['Bright']['crimson_editor']= 'Crimson Editor';
					$themes['Bright']['dawn']= 'Dawn';
					$themes['Bright']['dreamweaver']= 'Dreamweaver';
					$themes['Bright']['eclipse']= 'Eclipse';
					$themes['Bright']['github']= 'GitHub';
					$themes['Bright']['iplastic']= 'IPlastic';
					$themes['Bright']['solarized_light']= 'Solarized Light';
					$themes['Bright']['textmate']= 'TextMate';
					$themes['Bright']['tomorrow']= 'Tomorrow';
					$themes['Bright']['xcode']= 'XCode';
					$themes['Bright']['kuroir']= 'Kuroir';
					$themes['Bright']['katzenmilch']= 'KatzenMilch';
					$themes['Bright']['sqlserver']= 'SQL Server';
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
					$preview = ($setting_preview == 'true') ? "onmouseover=\"editor.setTheme('ace/theme/' + this.value);\"" : null;
					foreach ($themes as $optgroup => $theme) {
						echo "<optgroup label='".$optgroup."'>\n";
						foreach ($theme as $value => $label) {
							$selected = (strtolower($label) == strtolower($setting_theme)) ? 'selected' : null;
							echo "<option value='".$value."' ".$selected." ".$preview.">".$label."</option>\n";
						}
						echo "</optgroup>\n";
					}
					?>
				</select>
			</td>
		</tr>
	</table>
	<div id='editor'><?php echo htmlentities($cmd); ?></div>

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
			document.getElementById('editor').style.fontSize='<?php echo $setting_size;?>';
			focus_editor();

		//keyboard shortcut to execute command
			<?php key_press('ctrl+enter', 'down', 'window', null, null, "$('form#frm').submit();", false); ?>

		//remove certain keyboard shortcuts
			editor.commands.bindKey("Ctrl-T", null); //disable transpose letters - prefer new browser tab
			editor.commands.bindKey("Ctrl-F", null); //disable find - control broken with bootstrap
			editor.commands.bindKey("Ctrl-H", null); //disable replace - control broken with bootstrap
	</script>

<?php

//show the result
	if (count($_POST) > 0) {
		if ($cmd != '') {
			switch ($handler) {
				case 'shell':
					if (permission_exists('exec_command')) {
						$result = htmlentities(shell_exec($cmd));
					}
					break;
				case 'php':
					if (permission_exists('exec_php')) {
						ob_start();
						eval($cmd);
						$result = ob_get_contents();
						ob_end_clean();
					}
					break;
				case 'switch':
					if (permission_exists('exec_switch')) {
						$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
						if ($fp) { $result = htmlentities(event_socket_request($fp, 'api '.$cmd)); }
					}
					break;
			}
			if ($result != '') {
				echo "<span id='response'>";
				echo "<b>".$text['label-response']."</b>\n";
				echo "<br /><br />\n";
				echo ($handler == 'switch') ? "<textarea style='width: 100%; height: 450px; font-family: monospace; padding: 15px;' wrap='off'>".$result."</textarea>\n" : "<pre>".$result."</pre>";
				echo "</span>";
			}
		}
	}

//sql result
	if (permission_exists('exec_sql')) {
		echo "<span id='sql_response' style='display: none;'>";
		echo "<b>".$text['label-results']."</b>\n";
		echo "<br /><br />\n";
		echo "<iframe name='iframe' id='iframe' style='width: calc(100% - 3px); height: 500px; background-color: #fff; border: 1px solid #c0c0c0;'></iframe>\n";
		echo "</span>";
	}

//show the footer
	require_once "resources/footer.php";

?>