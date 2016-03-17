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
	James Rose <james.o.rose@gmail.com>
*/
include "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists('exec_command_line') || permission_exists('exec_php_command') || permission_exists('exec_switch')) {
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
	$handler = ($_POST["handler"] != '') ? trim($_POST["handler"]) : 'switch';
	$cmd = trim($_POST["cmd"]);

//set editor mode
	switch ($handler) {
		case 'php': $mode = 'php'; break;
		default: $mode = 'text';
	}

//show the header
	require_once "resources/header.php";
	$document['title'] = $text['title-command'];

//scripts and styles
	?>
	<script language="JavaScript" type="text/javascript">
		function submit_check() {
			document.getElementById('cmd').value = editor.getSession().getValue();
			if (document.getElementById('cmd').value == '') {
				focus_editor();
				return false;
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
				case 'switch':
					document.getElementById('description').innerHTML = "<?php echo $text['description-switch'];?>";
					editor.getSession().setMode('ace/mode/text');
					$('#mode option[value=text]').prop('selected',true);
					break;
				case 'php':
					document.getElementById('description').innerHTML = "<?php echo $text['description-php'];?>";
					editor.getSession().setMode('ace/mode/php');
					$('#mode option[value=php]').prop('selected',true);
					break;
				case 'shell':
					document.getElementById('description').innerHTML = "<?php echo $text['description-shell'];?>";
					editor.getSession().setMode('ace/mode/text');
					$('#mode option[value=text]').prop('selected',true);
					break;
			}
			focus_editor();
		}

		function reset_editor() {
			editor.getSession().setValue('');
			$('#cmd').val('');
			$('#response').hide();
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
	echo "<b>".$text['label-execute']."</b>\n";
	echo "<br><br>";
	echo $text['description-execute']."\n";
	echo "<br><br>";

//html form
	echo "<form method='post' name='frm' id='frm' action='' style='margin: 0;' onsubmit='return submit_check();'>\n";
	echo "<textarea name='cmd' id='cmd' style='display: none;'></textarea>";
	echo "<table cellpadding='0' cellspacing='0' border='0' width='100%'>\n";
	echo "	<tr>";
	echo "		<td width='210' valign='top' nowrap>";
	echo "			<table cellpadding='0' cellspacing='0' border='0' width='100%' height='100%'>";
	echo "				<tr>";
	echo "					<td valign='top' height='130'>";
	echo "						<table cellpadding='0' cellspacing='3' border='0'>\n";
	if (permission_exists('exec_switch')) { echo "<tr><td valign='middle'><input type='radio' name='handler' id='handler_switch' value='switch' ".(($handler == 'switch') ? 'checked' : null)." onclick=\"set_handler('switch');\"></td><td valign='bottom' style='padding-top: 3px;'><label for='handler_switch'> ".$text['label-switch']."</label></td></tr>\n"; }
	if (permission_exists('exec_php_command')) { echo "<tr><td valign='middle'><input type='radio' name='handler' id='handler_php' value='php' ".(($handler == 'php') ? 'checked' : null)." onclick=\"set_handler('php');\"></td><td valign='bottom' style='padding-top: 3px;'><label for='handler_php'> ".$text['label-php']."</label></td></tr>\n"; }
	if (permission_exists('exec_command_line')) { echo "<tr><td valign='middle'><input type='radio' name='handler' id='handler_shell' value='shell' ".(($handler == 'shell') ? 'checked' : null)." onclick=\"set_handler('shell');\"></td><td valign='bottom' style='padding-top: 3px;'><label for='handler_shell'> ".$text['label-shell']."</label></td></tr>\n"; }
	echo "						</table>\n";
	echo "						<br />";
	echo "						<input type='button' class='btn' title=\"".$text['button-execute']." [Ctrl + Enter]\" value=\"    ".$text['button-execute']."    \" onclick=\"$('form#frm').submit();\">";
	echo "						&nbsp;&nbsp;&nbsp;&nbsp;<a href='javascript:void(0)' onclick='reset_editor();'>".$text['label-reset']."</a>\n";
	echo "						<br /><br /><br />";
	echo "					</td>";
	echo "				</tr>";
	if (file_exists($_SERVER["PROJECT_ROOT"]."/app/edit/") && permission_exists('script_editor_view')) {
		echo "			<tr>";
		echo "				<td valign='top' height='100%'>";
		echo "					<iframe id='clip_list' src='".PROJECT_PATH."/app/edit/cliplist.php' style='border: none; border-top: 1px solid #ccc; height: 100%; width: calc(100% - 15px);'></iframe>\n";
		echo "				</td>";
		echo "			</tr>";
	}
	echo "			</table>";
	echo "		</td>";
	echo "		<td width='100%' valign='top' style='height: 400px;'>"
	?>
	<table cellpadding='0' cellspacing='0' border='0' style='width: 100%;'>
		<tr>
			<td valign='middle' style='padding: 0 6px;' width='100%'><span id='description'><?php echo $text['description-'.(($handler != '') ? $handler : 'switch')]; ?></span></td>
			<td valign='middle' style='padding: 0;'><img src='resources/images/blank.gif' style='width: 1px; height: 30px; border: none;'></td>
			<td valign='middle' style='padding-left: 6px;'><img src='resources/images/icon_numbering.png' title='Toggle Line Numbers' class='control' onclick="toggle_option('numbering');"></td>
			<td valign='middle' style='padding-left: 6px;'><img src='resources/images/icon_invisibles.png' title='Toggle Invisibles' class='control' onclick="toggle_option('invisibles');"></td>
			<td valign='middle' style='padding-left: 6px;'><img src='resources/images/icon_indenting.png' title='Toggle Indent Guides' class='control' onclick="toggle_option('indenting');"></td>
			<td valign='middle' style='padding-left: 6px;'><img src='resources/images/icon_replace.png' title='Show Find/Replace [Ctrl+H]' class='control' onclick="editor.execCommand('replace');"></td>
			<td valign='middle' style='padding-left: 6px;'><img src='resources/images/icon_goto.png' title='Show Go To Line' class='control' onclick="editor.execCommand('gotoline');"></td>
			<td valign='middle' style='padding-left: 10px;'>
				<select id='mode' style='height: 23px;' onchange="editor.getSession().setMode('ace/mode/' + this.options[this.selectedIndex].value); focus_editor();">
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
					$preview = ($setting_preview == 'true') ? "onmouseover=\"editor.getSession().setMode('ace/mode/' + this.value);\"" : null;
					foreach ($modes as $value => $label) {
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
	<div id='editor'><?php echo $cmd; ?></div>
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
			document.getElementById('editor').style.fontSize='<?php echo $setting_size;?>';
			focus_editor();

		//keyboard shortcuts
			$(window).keypress(function(event) {
				//execute command [Ctrl+Enter]
				if (((event.which == 13 || event.which == 10) && event.ctrlKey) || (event.which == 19)) {
					$('form#frm_edit').submit();
					return false;
				}
				//otherwise, default action
				else {
					return true;
				}
			});
	</script>

<?php

//show the result
	if (count($_POST) > 0) {
		if ($cmd != '') {
			switch ($handler) {
				case 'shell':
					if (permission_exists('exec_command_line')) {
						$result = htmlentities(shell_exec($cmd));
					}
					break;
				case 'php':
					if (permission_exists('exec_php_command')) {
						ob_start();
						eval($cmd);
						$result = ob_get_contents();
						ob_end_clean();
						$result = htmlentities($result);
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

//show the footer
	require_once "resources/footer.php";
?>