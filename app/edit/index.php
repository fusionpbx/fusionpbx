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

//check permissions
	if (permission_exists('edit_view')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//set the directory title and mode
	$_SESSION["app"]["edit"]["dir"] = $_GET["dir"];
	$title = escape($_GET["dir"]);
	unset($mode);
	switch ($_GET["dir"]) {
		case 'xml':
			$title = 'XML';
			$mode = 'xml';
			break;
		case 'provision':
			$title = 'Provision';
			$mode = 'xml';
			break;
		case 'php':
			$title = 'PHP';
			$mode = 'php';
			break;
		case 'scripts':
			$title = 'Scripts';
			$mode = 'lua';
			break;
		case 'grammar':
			$title = 'Grammar';
			$mode = 'xml';
		default: $mode = 'text';
	}

//load editor preferences/defaults
	$setting_size = ($_SESSION["editor"]["font_size"]["text"] != '') ? $_SESSION["editor"]["font_size"]["text"] : '12px';
	$setting_theme = ($_SESSION["editor"]["theme"]["text"] != '') ? $_SESSION["editor"]["theme"]["text"] : 'cobalt';
	$setting_invisibles = ($_SESSION["editor"]["invisibles"]["boolean"] != '') ? $_SESSION["editor"]["invisibles"]["boolean"] : 'false';
	$setting_indenting = ($_SESSION["editor"]["indent_guides"]["boolean"] != '') ? $_SESSION["editor"]["indent_guides"]["boolean"] : 'false';
	$setting_numbering = ($_SESSION["editor"]["line_numbers"]["boolean"] != '') ? $_SESSION["editor"]["line_numbers"]["boolean"] : 'true';

//get and then set the favicon
	if (isset($_SESSION['theme']['favicon']['text'])){
		$favicon = $_SESSION['theme']['favicon']['text'];
	}
	else {
		$favicon = $project_path .'/themes/default/favicon.ico';
	}

//create a token
	$key_name = '/app/edit/'.$mode;
	$_SESSION['keys'][$key_name] = bin2hex(random_bytes(32));
	$_SESSION['token'] = hash_hmac('sha256', $key_name, $_SESSION['keys'][$key_name]);

//generate the captcha image
	$_SESSION['captcha'] = generate_password(7, 2);
	$captcha = new captcha;
	$captcha->code = $_SESSION['captcha'];
	$image_base64 = $captcha->image_base64();

?>

<html>
<head>
	<meta http-equiv='Content-Type' content='text/html; charset=UTF-8' />
	<title><?php echo $title; ?></title>
	<link rel="icon" type="image/x-icon" href="<?php echo $favicon; ?>">
	<script language="JavaScript" type="text/javascript" src="<?php echo PROJECT_PATH; ?>/resources/jquery/jquery-3.4.1.min.js"></script>
	<script src='https://code.jquery.com/jquery-migrate-3.1.0.js'></script>
	<script language="JavaScript" type="text/javascript">
		function submit_check() {
			if (document.getElementById('filepath').value != '') {
				document.getElementById('editor_source').value = editor.getSession().getValue();
				return true;
			}
			focus_editor();
			return false;
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

		function http_request(url, form_data) {
			var http = new XMLHttpRequest();
			http.open('POST', url, true);
			//http.onload = function(e) { ... };
			http.onload = function(e) {
				if (this.status == 200) {
					//data sent successfully
					alert(this.responseText);
				}
				else {
					alert('<?php echo $text['message-problem']; ?>');
				}
			};
			http.send(form_data);
		}

		function save() {
			var form_data = new FormData();
			form_data.append('filepath', document.getElementById('filepath').value);
			form_data.append('content', editor.getSession().getValue());
			form_data.append('token',document.getElementById('token').value);
			form_data.append('mode',"<?php echo $mode; ?>");
			

			http_request('file_save.php', form_data);
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
			<iframe id='file_list' src='file_list.php' style='border: none; height: 65%; width: 100%;'></iframe><br>
			<iframe id='clip_list' src='clip_list.php' style='border: none; border-top: 1px solid #ccc; height: calc(35% - 1px); width: 100%;'></iframe>
		</td>
		<td align='right' valign='top' style='height: 100%;'>
			<form style='margin: 0;' name='frm_edit' id='frm_edit' method='post' action='file_save.php' onsubmit="return submit_check();">
			<textarea name='content' id='editor_source' style='display: none;'></textarea>
			<input type='hidden' name='filepath' id='filepath' value=''>
			<input type='hidden' name='token' id='token' value='<?php echo $_SESSION['token']; ?>'>
			<table cellpadding='0' cellspacing='0' border='0' style='width: 100%;'>
				<tr>
					<td valign='middle'><img src='resources/images/icon_save.png' title='Save Changes [Ctrl+S]' class='control' onclick="save();";></td>
					<td align='left' valign='middle' width='100%' style='padding: 0 15px 0 6px;'><input id='current_file' type='text' style='height: 23px; width: 100%;'></td>
					<!--
					<td style='padding: 0;'><img src="data:image/png;base64, <?php echo $image_base64; ?>" /></td>
					<td align='left' valign='middle' width='80' style='padding: 0 6px 0 0;'><input type='text' class='txt' style='width: 80px; text-align: center;' name='code' id='code' value='' placeholder='CAPTCHA'></td>
					-->
					<td style='padding: 0;'><img src='resources/images/blank.gif' style='width: 1px; height: 40px; border: none;'></td>
					<td valign='middle' style='padding-left: 6px;'><img src='resources/images/icon_sidebar.png' title='Toggle Side Bar [Ctrl+Q]' class='control' onclick="toggle_sidebar();"></td>
					<td valign='middle' style='padding-left: 6px;'><img src='resources/images/icon_numbering.png' title='Toggle Line Numbers' class='control' onclick="toggle_option('numbering');"></td>
					<td valign='middle' style='padding-left: 6px;'><img src='resources/images/icon_invisibles.png' title='Toggle Invisibles' class='control' onclick="toggle_option('invisibles');"></td>
					<td valign='middle' style='padding-left: 6px;'><img src='resources/images/icon_indenting.png' title='Toggle Indent Guides' class='control' onclick="toggle_option('indenting');"></td>
					<td valign='middle' style='padding-left: 6px;'><img src='resources/images/icon_replace.png' title='Show Find/Replace [Ctrl+H]' class='control' onclick="editor.execCommand('replace');"></td>
					<td valign='middle' style='padding-left: 6px;'><img src='resources/images/icon_goto.png' title='Show Go To Line' class='control' onclick="editor.execCommand('gotoline');"></td>
					<td valign='middle' style='padding-left: 10px;'>
						<select id='mode' style='height: 23px; max-width: 70px;' onchange="editor.getSession().setMode('ace/mode/' + this.options[this.selectedIndex].value); focus_editor();">
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
							$modes['sh'] = 'SH';
							$modes['smarty'] = 'Smarty';
							$modes['svg'] = 'SVG';
							$modes['makefile'] = 'Makefile';
							$modes['c_cpp'] = 'C';
							$modes['c_cpp'] = 'CPP';
							$modes['pgsql'] = 'PGSQL';
							foreach ($modes as $value => $label) {
								$selected = ($value == $mode) ? 'selected' : null;
								echo "<option value='".$value."' ".$selected.">".$label."</option>\n";
							}
							?>
						</select>
					</td>
					<td valign='middle' style='padding-left: 4px;'>
						<select id='size' style='height: 23px;' onchange="document.getElementById('editor').style.fontSize = this.options[this.selectedIndex].value; focus_editor();">
							<?php
							$sizes = explode(',','9px,10px,11px,12px,14px,16px,18px,20px');
							if (!in_array($setting_size, $sizes)) {
								echo "<option value='".$setting_size."'>".$setting_size."</option>\n";
								echo "<option value='' disabled='disabled'></option>\n";
							}
							foreach ($sizes as $size) {
								$selected = ($size == $setting_size) ? 'selected' : null;
								echo "<option value='".$size."' ".$selected.">".$size."</option>\n";
							}
							?>
						</select>
					</td>
					<td valign='middle' style='padding-left: 4px; padding-right: 4px;'>
						<select id='theme' style='height: 23px; max-width: 100px;' onchange="editor.setTheme('ace/theme/' + this.options[this.selectedIndex].value); focus_editor();">
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
							foreach ($themes as $optgroup => $theme) {
								echo "<optgroup label='".$optgroup."'>\n";
								foreach ($theme as $value => $label) {
									$selected = (strtolower($label) == strtolower($setting_theme)) ? 'selected' : null;
									echo "<option value='".$value."' ".$selected.">".$label."</option>\n";
								}
								echo "</optgroup>\n";
							}
							?>
						</select>
					</td>
				</tr>
			</table>
			</form>
			<div id='editor' style="text-align: left; width: 100%; height: calc(100% - 30px); font-size: 12px;"></div>
		</td>
	</tr>
</table>

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

	//prevent form submit with enter key on file path input
		<?php key_press('enter', 'down', '#current_file', null, null, 'return false;', false); ?>

	//save file
		<?php key_press('ctrl+s', 'down', 'window', null, null, "save(); return false;", false); ?>

	//open file manager/clip library pane
		<?php key_press('ctrl+q', 'down', 'window', null, null, 'toggle_sidebar(); focus_editor(); return false;', false); ?>

	//remove certain keyboard shortcuts
		editor.commands.bindKey("Ctrl-T", null); //new browser tab
</script>

</body>
</html>
