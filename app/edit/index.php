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
include "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists('script_editor_view')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//set the directory
	$_SESSION["app"]["edit"]["dir"] = $_GET["dir"];
	$title = strtoupper($_GET["dir"]);
	unset($mode);
	switch ($_GET["dir"]) {
		case 'xml': $mode['xml'] = 'selected'; break;
		case 'provision': $mode['xml'] = 'selected'; break;
		case 'php': $mode['php'] = 'selected'; break;
		case 'scripts': $mode['lua'] = 'selected'; break;
		case 'grammar': //use default
		default: $mode['text'] = 'selected';
	}
?>

<html>
<head>
	<meta http-equiv='Content-Type' content='text/html; charset=UTF-8' />
	<title><?=$title?></title>
	<script language="JavaScript" type="text/javascript" src="<?php echo PROJECT_PATH; ?>/resources/jquery/jquery-1.11.1.js"></script>
	<script language="JavaScript" type="text/javascript">
		function submit_check() {
			if (document.getElementById('filepath').value != '') {
				document.getElementById('editor_source').value = editor.getSession().getValue();
				return true;
			}
			focus_editor();
			return false;
		}

		function preview_theme(opt) {
			editor.setTheme('ace/theme/' + opt.value);
		}

		function toggle_option(opt) {
			switch (opt) {
				case 'numbering': 	toggle_option_do('showGutter'); break;
				case 'invisibles':	toggle_option_do('showInvisibles'); break;
				case 'indenting':	toggle_option_do('displayIndentGuides'); break;
			}
			focus_editor();
		}

		function toggle_option_do(opt_name) {
			var opt_val = editor.getOption(opt_name);
			editor.setOption(opt_name, ((opt_val) ? false : true));
		}

		function toggle_sidebar() {
			var td_sidebar = document.getElementById('sidebar');
			td_sidebar.style.display = (td_sidebar.style.display == '') ? 'none' : '';
			focus_editor();
		}

		function insert_clip(before, after) {
			var selected_text = editor.session.getTextRange(editor.getSelectionRange());
			editor.insert(before + selected_text + after);
			focus_editor();
		}

		function focus_editor() {
			editor.focus();
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
			box-shadow: 0 5px 15px #333;
			}

	</style>
</head>
<body style='padding: 0; margin: 0; overflow: hidden;'>
<table id='frame' cellpadding='0' cellspacing='0' border='0' style="height: 100%; width: 100%;">
	<tr>
		<td id='sidebar' valign='top' style="width: 300px; height: 100%;">
			<iframe id='file_list' src='filelist.php' style='border: none; height: 65%; width: 100%;'></iframe><br>
			<iframe id='clip_list' src='cliplist.php' style='border: none; border-top: 1px solid #ccc; height: calc(35% - 1px); width: 100%;'></iframe>
		</td>
		<td align='right' valign='top' style='height: 100%;'>
			<form style='margin: 0;' name='frm_edit' id='frm_edit' method='post' target='proc' action='filesave.php' onsubmit="return submit_check();">
			<textarea name='content' id='editor_source' style='display: none;'></textarea>
			<input type='hidden' name='filepath' id='filepath' value=''>
			<table cellpadding='0' cellspacing='0' border='0' style='width: 100%;'>
				<tr>
					<td valign='middle'><img src='resources/images/icon_save.png' title='Save Changes [Ctrl+S]' class='control' onclick="$('form#frm_edit').submit();";></td>
					<td align='left' valign='middle' width='100%' style='padding: 0 4px 0 6px;'><input id='current_file' type='text' disabled='disabled' style='height: 23px; width: 100%; color: #000;'></td>
					<td style='padding: 0;'><img src='resources/images/blank.gif' style='width: 1px; height: 30px; border: none;'></td>
					<td valign='middle' style='padding-left: 6px;'><img src='resources/images/icon_sidebar.png' title='Toggle Side Bar [Ctrl+Q]' class='control' onclick="toggle_sidebar();"></td>
					<td valign='middle' style='padding-left: 6px;'><img src='resources/images/icon_numbering.png' title='Toggle Line Numbers' class='control' onclick="toggle_option('numbering');"></td>
					<td valign='middle' style='padding-left: 6px;'><img src='resources/images/icon_invisibles.png' title='Toggle Invisibles' class='control' onclick="toggle_option('invisibles');"></td>
					<td valign='middle' style='padding-left: 6px;'><img src='resources/images/icon_indenting.png' title='Toggle Indent Guides' class='control' onclick="toggle_option('indenting');"></td>
					<td valign='middle' style='padding-left: 6px;'><img src='resources/images/icon_replace.png' title='Show Find/Replace [Ctrl+H]' class='control' onclick="editor.execCommand('replace');"></td>
					<td valign='middle' style='padding-left: 6px;'><img src='resources/images/icon_goto.png' title='Show Go To Line' class='control' onclick="editor.execCommand('gotoline');"></td>
					<td valign='middle' style='padding-left: 10px;'>
						<select id='mode' style='height: 23px;' onchange="editor.getSession().setMode('ace/mode/' + this.options[this.selectedIndex].value); focus_editor();">
							<option value='php' <?=$mode['php']?>>PHP</option>
							<option value='css'>CSS</option>
							<option value='html'>HTML</option>
							<option value='javascript'>JS</option>
							<option value='json'>JSON</option>
							<option value='ini'>Conf</option>
							<option value='lua' <?=$mode['lua']?>>Lua</option>
							<option value='text' <?=$mode['text']?>>Text</option>
							<option value='xml' <?=$mode['xml']?>>XML</option>
							<option value='sql'>SQL</option>
						</select>
					</td>
					<td valign='middle' style='padding-left: 4px;'>
						<select id='size' style='height: 23px;' onchange="document.getElementById('editor').style.fontSize = this.options[this.selectedIndex].value; focus_editor();">
							<option value='9px'>9px</option>
							<option value='10px'>10px</option>
							<option value='11px'>11px</option>
							<option value='12px' selected>12px</option>
							<option value='14px'>14px</option>
							<option value='16px'>16px</option>
							<option value='18px'>18px</option>
							<option value='20px'>20px</option>
						</select>
					</td>
					<td valign='middle' style='padding-left: 4px; padding-right: 4px;'>
						<select id='theme' style='height: 23px;' onchange="editor.setTheme('ace/theme/' + this.options[this.selectedIndex].value); focus_editor();">
 							<optgroup label="Bright">
 								<option value="chrome" onmouseover="preview_theme(this);">Chrome</option>
								<option value="clouds" onmouseover="preview_theme(this);">Clouds</option>
								<option value="crimson_editor" onmouseover="preview_theme(this);">Crimson Editor</option>
								<option value="dawn" onmouseover="preview_theme(this);">Dawn</option>
								<option value="dreamweaver" onmouseover="preview_theme(this);">Dreamweaver</option>
								<option value="eclipse" onmouseover="preview_theme(this);">Eclipse</option>
								<option value="github" onmouseover="preview_theme(this);">GitHub</option>
								<option value="iplastic" onmouseover="preview_theme(this);">IPlastic</option>
								<option value="solarized_light" onmouseover="preview_theme(this);">Solarized Light</option>
								<option value="textmate" onmouseover="preview_theme(this);">TextMate</option>
								<option value="tomorrow" onmouseover="preview_theme(this);">Tomorrow</option>
								<option value="xcode" onmouseover="preview_theme(this);">XCode</option>
								<option value="kuroir" onmouseover="preview_theme(this);">Kuroir</option>
								<option value="katzenmilch" onmouseover="preview_theme(this);">KatzenMilch</option>
								<option value="sqlserver" onmouseover="preview_theme(this);">SQL Server</option>
							</optgroup>
							<optgroup label="Dark">
								<option value="ambiance" onmouseover="preview_theme(this);">Ambiance</option>
								<option value="chaos" onmouseover="preview_theme(this);">Chaos</option>
								<option value="clouds_midnight" onmouseover="preview_theme(this);">Clouds Midnight</option>
								<option value="cobalt" onmouseover="preview_theme(this);" selected>Cobalt</option>
								<option value="idle_fingers" onmouseover="preview_theme(this);">idle Fingers</option>
								<option value="kr_theme" onmouseover="preview_theme(this);">krTheme</option>
								<option value="merbivore" onmouseover="preview_theme(this);">Merbivore</option>
								<option value="merbivore_soft" onmouseover="preview_theme(this);">Merbivore Soft</option>
								<option value="mono_industrial" onmouseover="preview_theme(this);">Mono Industrial</option>
								<option value="monokai" onmouseover="preview_theme(this);">Monokai</option>
								<option value="pastel_on_dark" onmouseover="preview_theme(this);">Pastel on dark</option>
								<option value="solarized_dark" onmouseover="preview_theme(this);">Solarized Dark</option>
								<option value="terminal" onmouseover="preview_theme(this);">Terminal</option>
								<option value="tomorrow_night" onmouseover="preview_theme(this);">Tomorrow Night</option>
								<option value="tomorrow_night_blue" onmouseover="preview_theme(this);">Tomorrow Night Blue</option>
								<option value="tomorrow_night_bright" onmouseover="preview_theme(this);">Tomorrow Night Bright</option>
								<option value="tomorrow_night_eighties" onmouseover="preview_theme(this);">Tomorrow Night 80s</option>
								<option value="twilight" onmouseover="preview_theme(this);">Twilight</option>
								<option value="vibrant_ink" onmouseover="preview_theme(this);">Vibrant Ink</option>
							</optgroup>
						</select>
					</td>
				</tr>
			</table>
			</form>
			<div id='editor' style="text-align: left; width: 100%; height: calc(100% - 30px); font-size: 12px;"></div>
			<iframe id='proc' name='proc' src='#' style='display: none;'></iframe>
		</td>
	</tr>
</table>

<script type="text/javascript" src="<?php echo PROJECT_PATH; ?>/resources/ace/ace.js" charset="utf-8"></script>
<script type="text/javascript">
	//load ace editor
		var editor = ace.edit("editor");
		editor.setOptions({
			<?php
			foreach ($mode as $lang => $meh) {
				if ($meh == 'selected') { echo "mode: 'ace/mode/".$lang."',\n"; break; }
			}
			?>
			theme: 'ace/theme/cobalt',
			selectionStyle: 'text',
			cursorStyle: 'smooth',
			showInvisibles: false,
			displayIndentGuides: true,
			showLineNumbers: true,
			showGutter: true,
			scrollPastEnd: true,
			fadeFoldWidgets: true,
			showPrintMargin: false,
			highlightGutterLine: false,
			useSoftTabs: false
			});
		focus_editor();

	//keyboard shortcut to save file
		$(window).keypress(function(event) {
			//save file [Ctrl+S]
			if ((event.which == 115 && event.ctrlKey) || (event.which == 19)) {
				$('form#frm_edit').submit();
				return false;
			}
			//open file manager/clip library pane [Ctrl+Q]
			else if ((event.which == 113 && event.ctrlKey) || (event.which == 19)) {
				toggle_sidebar();
				return false;
			}
			//otherwise, default action
			else {
				return true;
			}
		});

	//remove certain keyboard shortcuts
		editor.commands.bindKey("Ctrl-T", null);
</script>


</body>
</html>