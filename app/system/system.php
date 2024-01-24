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
	Portions created by the Initial Developer are Copyright (C) 2008-2023
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

//additional includes
	require_once "resources/header.php";
	require_once 'app/system/resources/functions/system_information.php';

//Load an array of system information
	$system_information = system_information();

//set the page title
	$document['title'] = $text['title-sys-status'];

//system information
	echo "<b>".$text['header-sys-status']."</b>";
	echo "<br><br>";

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
		if(file_exists($git_path)){
			if($system_information['git']['status'] === 'unknown'){
				echo "<tr>\n";
				echo "	<td width='20%' class=\"vncell\" style='text-align: left;'>\n";
				echo "		".$text['label-git_info']."\n";
				echo "	</td>\n";
				echo "	<td class=\"row_style1\">\n";
				echo "		".$text['label-git_corrupted']."\n";
				echo "	</td>\n";
				echo "</tr>\n";
			}else{
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
			if($system_information['switch']['git']['info'] !== 'connection failed'){
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

		echo "<tr>\n";
		echo "	<th class='th' colspan='2' align='left' style='padding-top:2em'>".$text['title-os-info']."</th>\n";
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
	echo "<br /><br>";

//memory information
	if (permission_exists('system_view_ram')) {
		if ($system_information['os']['mem'] !== 'unknown' && $system_information['os']['mem'] !== 'permission denied') {
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
			echo "<br /><br />";
		}
	}

//cpu information
	if (permission_exists('system_view_cpu')) {
		if ($system_information['os']['cpu'] !== 'unknown' && $system_information['os']['cpu'] !== 'permission denied') {
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
			echo "<br /><br />";
		}
	}

//drive space
	if (permission_exists('system_view_hdd')) {
		if (stristr(PHP_OS, 'Linux') || stristr(PHP_OS, 'FreeBSD')) {
			echo "<!--\n";
			echo "-->\n";
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
		} else if (stristr(PHP_OS, 'WIN')) {
			//disk_free_space returns the number of bytes available on the drive;
			//1 kilobyte = 1024 byte
			//1 megabyte = 1024 kilobyte
			$drive_letter = substr($_SERVER["DOCUMENT_ROOT"], 0, 2);
			$disk_size = round(disk_total_space($drive_letter)/1024/1024, 2);
			$disk_size_free = round(disk_free_space($drive_letter)/1024/1024, 2);
			$disk_percent_available = round(($disk_size_free/$disk_size) * 100, 2);

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
		}
		echo "<br /><br />";
	}

//database information
	if (permission_exists('system_view_database')) {
		if ($system_information['database']['type'] == 'pgsql') {

			echo "<table width=\"100%\" border=\"0\" cellpadding=\"7\" cellspacing=\"0\">\n";
			echo "<tr>\n";
			echo "	<th class='th' colspan='2' align='left'>".$text['title-database']."</th>\n";
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
		}
		echo "<br /><br />";
	}

//memcache information
	if (permission_exists("system_view_memcache") && file_exists($_SERVER["PROJECT_ROOT"]."/app/sip_status/app_config.php")){
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
		echo "<br /><br />\n";
	}

	echo "<script src='resources/javascript/copy_to_clipboard.js'></script>";
	echo "<table width=\"100%\" border=\"0\" cellpadding=\"7\" cellspacing=\"0\">\n";
	echo "  <tr>\n";
	echo "	  <th class='th' colspan='2' align='left'>".$text['label-support']."</th>\n";
	echo "  </tr>\n";
	echo "<tr>\n";
	echo "	<td width='20%' class=\"vncell\" style='text-align: left;'>\n";
	echo "		<button type='button' id='btn_copy' alt='".$text['label-copy']."' title='' onclick='copy_to_clipboard()' class='btn btn-default' style='margin-left: 15px;'><span class='fas fa-regular fa-clipboard'></span><span class='button-label pad'>" . $text['title-copy_to_clipboard'] . "</span></button>\n";
	echo "	</td>\n";
	echo "	<td class=\"row_style1\">\n";
	echo "		<span id='system_information' name='system_information'>". json_encode($system_information, JSON_PRETTY_PRINT)."</span>\n";
	echo "	</td>\n";
	echo "</tr>\n";
	echo "</table>\n";

//include the footer
	require_once "resources/footer.php";

?>
