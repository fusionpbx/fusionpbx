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
Con	Portions created by the Initial Developer are Copyright (C) 2008-2025
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	James Rose <james.o.rose@gmail.com>
*/

//includes files
	require_once dirname(__DIR__, 2) . "/resources/require.php";
	require_once "resources/check_auth.php";
//check permissions
	if (permission_exists('system_view_info')
		|| permission_exists('system_view_cpu')
		|| permission_exists('system_view_hdd')
		|| permission_exists('system_view_ram')
		|| permission_exists('system_view_backup')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//load editor preferences/defaults
	if (permission_exists("system_view_support")) {
		$setting_size = !empty($_SESSION["editor"]["font_size"]["text"]) ? $_SESSION["editor"]["font_size"]["text"] : '12px';
		$setting_theme = !empty($_SESSION["editor"]["theme"]["text"]) ? $_SESSION["editor"]["theme"]["text"] : 'cobalt';
		$setting_invisibles = isset($_SESSION['editor']['invisibles']['text']) ? $_SESSION['editor']['invisibles']["text"] : 'false';
		$setting_indenting = isset($_SESSION['editor']['indent_guides']['text']) ? $_SESSION['editor']['indent_guides']["text"]: 'false';
		$setting_numbering = isset($_SESSION['editor']['line_numbers']['text']) ? $_SESSION['editor']['line_numbers']["text"] : 'true';
	}

//additional includes
	require_once "resources/header.php";
	require_once 'app/system/resources/functions/system_information.php';

//ace editor helpers
	if (permission_exists("system_view_support")) {
		echo "<script language='JavaScript' type='text/javascript' src='resources/javascript/copy_to_clipboard.js'></script>";
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

		//load editor value from hidden textarea
		echo "	function load_value() {\n";
		echo "		editor.session.setValue($('#system_information').val());";
		echo "	}\n";

		//copy the value from the editor to the clipboard
		echo "	function do_copy() {\n";
		echo "		$('#system_information').val(editor.session.getValue());\n";
		echo "		copy_to_clipboard();\n";
		echo "		alert(\"".$text['message-copied_to_clipboard']."\");\n";
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
	}

//Load an array of system information
	$system_information = system_information();

//set the page title
	$document['title'] = $text['title-sys-status'];

//system information
	echo "<b>".$text['header-sys-status']."</b>";
	echo "<br><br>";

	echo "<div class='card'>\n";
	echo "<table width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">\n";
	echo "<tr>\n";
	echo "	<th class='th' colspan='2' align='left'>".$text['title-sys-info']."</th>\n";
	echo "</tr>\n";
	if (permission_exists('system_view_info')) {
		echo "<tr>\n";
		echo "	<td width='20%' class=\"vncell\" style='text-align: left;'>\n";
		echo "		".$text['label-version']."\n";
		echo "	</td>\n";
		echo "	<td class=\"row_style1\">\n";
		echo "		".$system_information['version']."\n";
		echo "	</td>\n";
		echo "</tr>\n";

		$git_path = $system_information['git']['path'];
		if (file_exists($git_path)){
			if ($system_information['git']['status'] === 'unknown') {
				echo "<tr>\n";
				echo "	<td width='20%' class=\"vncell\" style='text-align: left;'>\n";
				echo "		".$text['label-git_info']."\n";
				echo "	</td>\n";
				echo "	<td class=\"row_style1\">\n";
				echo "		".$text['label-git_corrupted']."\n";
				echo "	</td>\n";
				echo "</tr>\n";
			}
			else {
				echo "<tr>\n";
				echo "	<td width='20%' class=\"vncell\" style='text-align: left;'>\n";
				echo "		".$text['label-git_info']."\n";
				echo "	</td>\n";
				echo "	<td class=\"row_style1\">\n";
				echo "		".$text['label-git_branch'].": ".$system_information['git']['branch']."<br>\n";
				echo "		".$text['label-git_commit'].": <a href='{$system_information['git']['origin']}/commit/{$system_information['git']['commit']}'>".$system_information['git']['commit']."</a><br>\n";
				echo "		".$text['label-git_origin'].": ".$system_information['git']['origin']."<br>\n";
				echo "		".$text['label-git_status'].": ".$system_information['git']['status'].($system_information['git']['age'])->format(' %R%a days ago')."<br>\n";
				echo "	</td>\n";
				echo "</tr>\n";
			}
		}

		echo "<tr>\n";
		echo "	<td width='20%' class=\"vncell\" style='text-align: left;'>\n";
		echo "		".$text['label-path']."\n";
		echo "	</td>\n";
		echo "	<td class=\"row_style1\">\n";
		echo "		".$_SERVER['PROJECT_ROOT']."\n";
		echo "	</td>\n";
		echo "</tr>\n";

		if ($system_information['switch']['version'] !== 'connection failed') {
			echo "<tr>\n";
			echo "	<td width='20%' class=\"vncell\" style='text-align: left;'>\n";
			echo "		".$text['label-switch']." ".$text['label-version']."\n";
			echo "	</td>\n";
			echo "	<td class=\"row_style1\">{$system_information['switch']['version']} ({$system_information['switch']['bits']})</td>\n";
			echo "</tr>\n";
			if ($system_information['switch']['git']['info'] !== 'connection failed') {
				echo "<tr>\n";
				echo "	<td width='20%' class=\"vncell\" style='text-align: left;'>\n";
				echo "		".$text['label-switch']." ".$text['label-git_info']."\n";
				echo "	</td>\n";
				echo "	<td class=\"row_style1\">{$system_information['switch']['git']['info']}</td>\n";
				echo "</tr>\n";
			}
		}

		echo "<tr>\n";
		echo "	<td width='20%' class=\"vncell\" style='text-align: left;'>\n";
		echo "	".$text['label-php']." ".$text['label-version']."\n";
		echo "	</td>\n";
		echo "	<td class=\"row_style1\">".$system_information['php']['version']."</td>\n";
		echo "</tr>\n";

		echo "</table>\n";
		echo "</div>\n";


		echo "<div class='card'>\n";
		echo "<table width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">\n";
		echo "<tr>\n";
		echo "	<th class='th' colspan='2' align='left'>".$text['title-os-info']."</th>\n";
		echo "</tr>\n";

		if ($system_information['os']['name'] !== 'permission denied') {
			echo "<tr>\n";
			echo "	<td width='20%' class=\"vncell\" style='text-align: left;'>\n";
			echo "		".$text['label-os']." \n";
			echo "	</td>\n";
			echo "	<td class=\"row_style1\">\n";
			echo "		".$system_information['os']['name']." \n";
			echo "	</td>\n";
			echo "</tr>\n";
		}
		if ($system_information['os']['version'] !== 'permission denied') {
			echo "<tr>\n";
			echo "	<td width='20%' class=\"vncell\" style='text-align: left;'>\n";
			echo "		".$text['label-version']." \n";
			echo "	</td>\n";
			echo "	<td class=\"row_style1\">\n";
			echo "		".$system_information['os']['version']." \n";
			echo "	</td>\n";
			echo "</tr>\n";
		}
		if (!empty($system_information['os']['kernel'])) {
			echo "<tr>\n";
			echo "	<td width='20%' class=\"vncell\" style='text-align: left;'>\n";
			echo "		".$text['label-kernel']." \n";
			echo "	</td>\n";
			echo "	<td class=\"row_style1\">\n";
			echo "		".$system_information['os']['kernel']." \n";
			echo "	</td>\n";
			echo "</tr>\n";
		}

		if ($system_information['os']['uptime'] !== 'unknown') {
			echo "<tr>\n";
			echo "	<td width='20%' class=\"vncell\" style='text-align: left;'>\n";
			echo "		Uptime\n";
			echo "	</td>\n";
			echo "	<td class=\"row_style1\">\n";
			echo "		".$system_information['os']['uptime']." \n";
			echo "	</td>\n";
			echo "</tr>\n";
		}
	}
	echo "<tr>\n";
	echo "	<td width='20%' class=\"vncell\" style='text-align: left;'>\n";
	echo "		Date\n";
	echo "	</td>\n";
	echo "	<td class=\"row_style1\">\n";
	echo "		".$system_information['os']['date']." \n";
	echo "	</td>\n";
	echo "</tr>\n";
	echo "</table>\n";
	echo "</div>\n";

//memory information
	if (permission_exists('system_view_ram')) {
		if ($system_information['os']['mem'] !== 'unknown' && $system_information['os']['mem'] !== 'permission denied') {
			echo "<div class='card'>\n";
			echo "<table width=\"100%\" border=\"0\" cellpadding=\"7\" cellspacing=\"0\">\n";
			echo "<tr>\n";
			echo "	<th colspan='2' align='left' valign='top'>".$text['title-mem']."</th>\n";
			echo "</tr>\n";
			echo "<tr>\n";
			echo "	<td width='20%' class=\"vncell\" style='text-align: left;'>\n";
			echo "	".$text['label-mem']."\n";
			echo "	</td>\n";
			echo "	<td class=\"row_style1\">\n";
			echo "	<pre>\n";
			echo "{$system_information['os']['mem']}<br>";
			echo "</pre>\n";
			echo "	</td>\n";
			echo "</tr>\n";
			echo "</table>\n";
			echo "</div>\n";
		}
	}

//cpu information
	if (permission_exists('system_view_cpu')) {
		if ($system_information['os']['cpu'] !== 'unknown' && $system_information['os']['cpu'] !== 'permission denied') {
			echo "<div class='card'>\n";
			echo "<table width=\"100%\" border=\"0\" cellpadding=\"7\" cellspacing=\"0\">\n";
			echo "<tr>\n";
			echo "	<th class='th' colspan='2' align='left' valign='top'>".$text['title-cpu']."</th>\n";
			echo "</tr>\n";
			echo "<tr>\n";
			echo "	<td width='20%' class=\"vncell\" style='text-align: left;'>\n";
			echo "	".$text['label-cpu']."\n";
			echo "	</td>\n";
			echo "	<td class=\"row_style1\">\n";
			echo "	<pre>\n";
			echo "{$system_information['os']['cpu']}<br>";
			echo "</pre>\n";
			echo "	</td>\n";
			echo "</tr>\n";
			echo "</table>\n";
			echo "</div>\n";
		}
	}

//drive space
	if (permission_exists('system_view_hdd')) {
		if (stristr(PHP_OS, 'Linux') || stristr(PHP_OS, 'FreeBSD')) {
			echo "<div class='card'>\n";
			echo "<table width=\"100%\" border=\"0\" cellpadding=\"7\" cellspacing=\"0\">\n";
			echo "<tr>\n";
			echo "	<th class='th' colspan='2' align='left'>".$text['title-drive']."</th>\n";
			echo "</tr>\n";
			echo "<tr>\n";
			echo "	<td width='20%' class=\"vncell\" style='text-align: left;'>\n";
			echo "		".$text['label-drive']." \n";
			echo "	</td>\n";
			echo "	<td class=\"row_style1\">\n";
			echo "<pre>\n";
			echo "{$system_information['os']['disk']['size']}<br>";
			echo "</pre>\n";
			echo "	</td>\n";
			echo "</tr>\n";
			echo "</table>\n";
			echo "</div>\n";
		}
		else if (stristr(PHP_OS, 'WIN')) {
			//disk_free_space returns the number of bytes available on the drive;
			//1 kilobyte = 1024 byte
			//1 megabyte = 1024 kilobyte
			$drive_letter = substr($_SERVER["DOCUMENT_ROOT"], 0, 2);
			$disk_size = round(disk_total_space($drive_letter)/1024/1024, 2);
			$disk_size_free = round(disk_free_space($drive_letter)/1024/1024, 2);
			$disk_percent_available = round(($disk_size_free/$disk_size) * 100, 2);

			echo "<div class='card'>\n";
			echo "<table width=\"100%\" border=\"0\" cellpadding=\"7\" cellspacing=\"0\">\n";
			echo "<tr>\n";
			echo "	<th class='th' colspan='2' align='left'>".$text['label-drive']."</th>\n";
			echo "</tr>\n";
			echo "<tr>\n";
			echo "	<td width='20%' class=\"vncell\" style='text-align: left;'>\n";
			echo "		".$text['label-drive-capacity']." \n";
			echo "	</td>\n";
			echo "	<td class=\"row_style1\">\n";
			echo "		{$system_information['os']['disk']['size']} mb\n";
			echo "	</td>\n";
			echo "</tr>\n";

			echo "<tr>\n";
			echo "	<td width='20%' class=\"vncell\" style='text-align: left;'>\n";
			echo "		".$text['label-drive-free']." \n";
			echo "	</td>\n";
			echo "	<td class=\"row_style1\">\n";
			echo "		{$system_information['os']['disk']['free']} mb\n";
			echo "	</td>\n";
			echo "</tr>\n";

			echo "<tr>\n";
			echo "	<td width='20%' class=\"vncell\" style='text-align: left;'>\n";
			echo "		".$text['label-drive-percent']." \n";
			echo "	</td>\n";
			echo "	<td class=\"row_style1\">\n";
			echo "		{$system_information['os']['disk']['available']}% \n";
			echo "	</td>\n";
			echo "</tr>\n";
			echo "</table>\n";
			echo "</div>\n";
		}
	}

//database information
	if (permission_exists('system_view_database')) {
		if ($system_information['database']['type'] == 'pgsql') {

			echo "<div class='card'>\n";
			echo "<table width=\"100%\" border=\"0\" cellpadding=\"7\" cellspacing=\"0\">\n";
			echo "<tr>\n";
			echo "	<th class='th' colspan='2' align='left'>".$text['title-database']."</th>\n";
			echo "</tr>\n";

			echo "<tr>\n";
			echo "	<td width='20%' class=\"vncell\" style='text-align: left;'>\n";
			echo "		".$text['label-name']." \n";
			echo "	</td>\n";
			echo "	<td class=\"row_style1\">\n";
			echo "		".$system_information['database']['name']."<br>\n";
			echo "	</td>\n";
			echo "</tr>\n";

			echo "<tr>\n";
			echo "	<td width='20%' class=\"vncell\" style='text-align: left;'>\n";
			echo "		".$text['label-version']." \n";
			echo "	</td>\n";
			echo "	<td class=\"row_style1\">\n";
			echo "		".$system_information['database']['version']."<br>\n";
			echo "	</td>\n";
			echo "</tr>\n";

			echo "<tr>\n";
			echo "	<td width='20%' class=\"vncell\" style='text-align: left;'>\n";
			echo "		".$text['label-database_connections']." \n";
			echo "	</td>\n";
			echo "	<td class=\"row_style1\">\n";
			echo "		".$system_information['database']['connections']."<br>\n";
			echo "	</td>\n";
			echo "</tr>\n";

			echo "<tr>\n";
			echo "	<td width='20%' class=\"vncell\" style='text-align: left;'>\n";
			echo "		".$text['label-databases']." \n";
			echo "	</td>\n";
			echo "	<td class=\"row_style1\">\n";
			echo "		<table border='0' cellpadding='3' cellspacing='0'>\n";
			echo "			<tr><td>". $text['label-name'] ."</td><td>&nbsp;</td><td style='text-align: left;'>". $text['label-size'] ."</td></tr>\n";
			foreach ($system_information['database']['sizes'] as $datname => $size) {
				echo "			<tr><td>".$datname ."</td><td>&nbsp;</td><td style='text-align: left;'>". $size ."</td></tr>\n";
			}
			echo "		</table>\n";
			echo "	</td>\n";
			echo "</tr>\n";

			echo "</table>\n";
			echo "</div>\n";
		}
	}

//memcache information
	if (permission_exists("system_view_memcache") && file_exists($_SERVER["PROJECT_ROOT"]."/app/sip_status/app_config.php")){
		echo "<div class='card'>\n";
		echo "<table width='100%' border='0' cellpadding='7' cellspacing='0'>\n";
		echo "	<tr>\n";
		echo "		<th class='th' colspan='2' align='left'>".$text['title-memcache']."</th>\n";
		echo "	</tr>\n";

		if ($system_information['memcache'] !== 'none' && $system_information['memcache'] !== 'permission denied or unavailable') {
				if (is_array($system_information['memcache']) && sizeof($system_information['memcache']) > 0) {
					foreach($system_information['memcache'] as $memcache_field => $memcache_value) {
						echo "<tr>\n";
						echo "	<td width='20%' class='vncell' style='text-align: left;'>".$memcache_field."</td>\n";
						echo "	<td class='row_style1'>".$memcache_value."</td>\n";
						echo "</tr>\n";
					}
				}
		}
		else {
			echo "<tr>\n";
			echo "	<td width='20%' class='vncell' style='text-align: left;'>".$text['label-memcache_status']."</td>\n";
			echo "	<td class='row_style1'>".$text['message-unavailable']."</td>\n";
			echo "</tr>\n";
		}

		echo "</table>\n";
		echo "</div>\n";
	}

	if (permission_exists("system_view_support")) {
		$system_support = "- Application\n";
		$system_support .= "  - version ".$system_information['version']."\n";
		$system_support .= "  - branch ".$system_information['git']['branch']."\n";
		$system_support .= "  - path ".$system_information['path']."\n";
		$system_support .= "- PHP\n";
		$system_support .= "  - version ".$system_information['php']['version']."\n";
		if (isset($system_information['switch']['version'])) {
			$system_support .= "- Switch\n";
			$system_support .= "  - version ".$system_information['switch']['version']."\n";
		}
		$system_support .= "- Database\n";
		$system_support .= "  - name ".$system_information['database']['name']."\n";
		$system_support .= "  - version ".$system_information['database']['version']."\n";
		$system_support .= "- Operating System\n";
		$system_support .= "  - name ".$system_information['os']['name']."\n";
		$system_support .= "  - version ".$system_information['os']['version']."\n";

		echo "<div class='card'>\n";
		echo "<table width='100%' border='0' cellpadding='7' cellspacing='0'>\n";
		echo "<tr>\n";
		echo "	<th align='left' style='border-bottom: none;'>".$text['label-support']."</th>\n";
		echo "	<th style='text-align: right; padding-right: 0; border-bottom: none;'>\n";
		echo "		<button type='button' class='btn btn-default' id='btn_copy' alt=\"".$text['label-copy']."\" title=\"".$text['label-copy']."\" onclick='do_copy();'>".$text['label-copy']."<i class='fas fa-regular fa-clipboard pl-5'></i></button>\n";
		echo "	</th>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "	<td colspan='2' style='padding: 0;'>\n";
		echo "		<textarea class='formfld' id='system_information' name='system_information' style='display: none;'>".escape($system_support)."</textarea>\n";
		echo "		<div id='editor' style='border-radius: 4px;'></div>\n";
		echo "		<table cellpadding='0' cellspacing='0' border='0' style='float: right; padding-top: 5px;'>\n";
		echo "			<tr>\n";
		echo "				<td valign='middle' style='padding-left: 6px;'><i class='fas fa-list-ul fa-lg ace_control' title=\"".$text['label-toggle_line_numbers']."\" onclick=\"toggle_option('numbering');\"></i></td>\n";
		echo "				<td valign='middle' style='padding-left: 6px;'><i class='fas fa-eye-slash fa-lg ace_control' title=\"".$text['label-toggle_invisibles']."\" onclick=\"toggle_option('invisibles');\"></i></td>\n";
		echo "				<td valign='middle' style='padding-left: 6px;'><i class='fas fa-indent fa-lg ace_control' title=\"".$text['label-toggle_indent_guides']."\" onclick=\"toggle_option('indenting');\"></i></td>\n";
		echo "				<td valign='middle' style='padding-left: 6px;'><i class='fas fa-search fa-lg ace_control' title=\"".$text['label-find_replace']."\" onclick=\"editor.execCommand('replace');\"></i></td>\n";
		echo "				<td valign='middle' style='padding-left: 6px;'><i class='fas fa-chevron-down fa-lg ace_control' title=\"".$text['label-go_to_line']."\" onclick=\"editor.execCommand('gotoline');\"></i></td>\n";
		echo "				<td valign='middle' style='padding-left: 15px;'>\n";
		echo "					<select id='size' class='formfld' onchange=\"document.getElementById('editor').style.fontSize = this.options[this.selectedIndex].value; focus_editor();\">\n";
		$sizes = explode(',','9px,10px,11px,12px,14px,16px,18px,20px');
		if (!in_array($setting_size, $sizes)) {
			echo "					<option value='".$setting_size."'>".escape($setting_size)."</option>\n";
			echo "					<option value='' disabled='disabled'></option>\n";
		}
		foreach ($sizes as $size) {
			$selected = $size == $setting_size ? 'selected' : null;
			echo "					<option value='".$size."' ".$selected.">".escape($size)."</option>\n";
		}
		echo "					</select>\n";
		echo "				</td>\n";
		echo "				<td valign='middle' style='padding-left: 4px; padding-right: 0px;'>\n";
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
		echo "					<select id='theme' class='formfld' onchange=\"editor.setTheme('ace/theme/' + this.options[this.selectedIndex].value); focus_editor();\">\n";
		foreach ($themes as $optgroup => $theme) {
			echo "					<optgroup label='".$optgroup."'>\n";
			foreach ($theme as $value => $label) {
				$selected = strtolower($label) == strtolower($setting_theme) ? 'selected' : null;
				echo "					<option value='".$value."' ".$selected.">".escape($label)."</option>\n";
			}
			echo "					</optgroup>\n";
		}
		echo "					</select>\n";
		echo "				</td>\n";
		echo "			</tr>\n";
		echo "		</table>\n";

		echo "	</td>\n";
		echo "</tr>\n";
		echo "</table>\n";

		echo "<script type='text/javascript' src='".PROJECT_PATH."/resources/ace/ace.js' charset='utf-8'></script>\n";
		echo "<script type='text/javascript'>\n";

		//load editor
		echo "	var editor = ace.edit('editor');\n";
		echo "	editor.setOptions({\n";
		//echo "		mode: 'ace/mode/json',\n";
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
		//echo "focus_editor();\n";

		//load value into editor
		echo "	load_value();\n";

		//remove certain keyboard shortcuts
		echo "	editor.commands.bindKey('Ctrl-T', null);\n"; //disable transpose letters - prefer new browser tab
		echo "	editor.commands.bindKey('Ctrl-F', null);\n"; //disable find - control broken with bootstrap
		echo "	editor.commands.bindKey('Ctrl-H', null);\n"; //disable replace - control broken with bootstrap

		echo "</script>\n";
	}

//include the footer
	require_once "resources/footer.php";

?>
