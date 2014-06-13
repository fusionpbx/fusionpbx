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
	require_once "app_languages.php";
	foreach($text as $key => $value) {
		$text[$key] = $value[$_SESSION['domain']['language']['code']];
	}
require_once "resources/header.php";

echo "<br />";
echo "<br />";

// OS Support
//
// For each section below wrap in an OS detection statement like:
//	if (stristr(PHP_OS, 'Linux')) {}
//
// Some possibilites for PHP_OS...
//
//	CYGWIN_NT-5.1
//	Darwin
//	FreeBSD
//	HP-UX
//	IRIX64
//	Linux
//	NetBSD
//	OpenBSD
//	SunOS
//	Unix
//	WIN32
//	WINNT
//	Windows
//

//system information
	echo "<table width=\"100%\" border=\"0\" cellpadding=\"7\" cellspacing=\"0\">\n";
	echo "<tr>\n";
	echo "	<th class='th' colspan='2' align='left'>".$text['title-sys-info']."</th>\n";
	echo "</tr>\n";
	if (permission_exists('system_view_info')) {
		echo "<tr>\n";
		echo "	<td width='20%' class=\"vncell\" style='text-align: left;'>\n";
		echo "		Version: \n";
		echo "	</td>\n";
		echo "	<td class=\"row_style1\">\n";
		echo "		".software_version()."\n";
		echo "	</td>\n";
		echo "</tr>\n";

		echo "<!--\n";
		$tmp_result = shell_exec('uname -a');
		echo "-->\n";
		if (strlen($tmp_result) > 0) {
			echo "<tr>\n";
			echo "	<td width='20%' class=\"vncell\" style='text-align: left;'>\n";
			echo "		".$text['label-os']." \n";
			echo "	</td>\n";
			echo "	<td class=\"row_style1\">\n";
			echo "		".$tmp_result." \n";
			echo "	</td>\n";
			echo "</tr>\n";
		}
		unset($tmp_result);

		echo "<!--\n";
		$tmp_result = shell_exec('uptime');
		echo "-->\n";
		if (strlen($tmp_result) > 0) {
			echo "<tr>\n";
			echo "	<td width='20%' class=\"vncell\" style='text-align: left;'>\n";
			echo "		Uptime: \n";
			echo "	</td>\n";
			echo "	<td class=\"row_style1\">\n";
			echo "		".$tmp_result." \n";
			echo "	</td>\n";
			echo "</tr>\n";
		}
		unset($tmp_result);
	}
	echo "<tr>\n";
	echo "	<td width='20%' class=\"vncell\" style='text-align: left;'>\n";
	echo "		Date: \n";
	echo "	</td>\n";
	echo "	<td class=\"row_style1\">\n";
	echo "		".date('r')." \n";
	echo "	</td>\n";
	echo "</tr>\n";
	echo "</table>\n";

	echo "<br />";
	echo "<br />";
	echo "<br />\n";


//memory information
	if (permission_exists('system_view_ram')) {
		//linux
		if (stristr(PHP_OS, 'Linux')) {
			echo "<!--\n";
			$shellcmd='free';
			$shell_result = shell_exec($shellcmd);
			echo "-->\n";
			if (strlen($shell_result) > 0) {
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
				echo "$shell_result<br>";
				echo "</pre>\n";
				unset($shell_result);
				echo "	</td>\n";
				echo "</tr>\n";
				echo "</table>\n";
				echo "<br />";
				echo "<br />";
				echo "<br />";
			}
		}

		//freebsd
		if (stristr(PHP_OS, 'FreeBSD')) {
			echo "<!--\n";
			$shellcmd='sysctl vm.vmtotal';
			$shell_result = shell_exec($shellcmd);
			echo "-->\n";
			if (strlen($shell_result) > 0) {
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
				echo "$shell_result<br>";
				echo "</pre>\n";
				unset($shell_result);
				echo "	</td>\n";
				echo "</tr>\n";
				echo "</table>\n";
				echo "<br />";
				echo "<br />";
				echo "<br />";
			}
		}
	}

//cpu information
	if (permission_exists('system_view_cpu')) {
		//linux
		if (stristr(PHP_OS, 'Linux')) {
			echo "<!--\n";
			$shellcmd="ps -e -o pcpu,cpu,nice,state,cputime,args --sort pcpu | sed '/^ 0.0 /d'";
			$shell_result = shell_exec($shellcmd);
			echo "-->\n";
			if (strlen($shell_result) > 0) {
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

				//$last_line = shell_exec($shellcmd, $shell_result);
				//foreach ($shell_result as $value) {
				//	echo substr($value, 0, 100);
				//	echo "<br />";
				//}

				echo "$shell_result<br>";

				echo "</pre>\n";
				unset($shell_result);
				echo "	</td>\n";
				echo "</tr>\n";
				echo "</table>\n";
				echo "<br />";
				echo "<br />";
				echo "<br />";
			}
		}

		//freebsd
		if (stristr(PHP_OS, 'FreeBSD')) {
			echo "<!--\n";
			$shellcmd='top';
			$shell_result = shell_exec($shellcmd);
			echo "-->\n";
			if (strlen($shell_result) > 0) {
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
				echo "$shell_result<br>";
				echo "</pre>\n";
				unset($shell_result);
				echo "	</td>\n";
				echo "</tr>\n";
				echo "</table>\n";
				echo "<br />";
				echo "<br />";
				echo "<br />";
			}
		}
	}

//drive space
	if (permission_exists('system_view_hdd')) {
		if (stristr(PHP_OS, 'Linux') || stristr(PHP_OS, 'FreeBSD')) {
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
			$shellcmd = 'df -h';
			$shell_result = shell_exec($shellcmd);
			echo "$shell_result<br>";
			echo "</pre>\n";
			echo "	</td>\n";
			echo "</tr>\n";
			echo "</table>\n";
		} else if (stristr(PHP_OS, 'WIN')) {
			//disk_free_space returns the number of bytes available on the drive;
			//1 kilobyte = 1024 byte
			//1 megabyte = 1024 kilobyte
			$driveletter = substr($_SERVER["DOCUMENT_ROOT"], 0, 2);
			$disksize = round(disk_total_space($driveletter)/1024/1024, 2);
			$disksizefree = round(disk_free_space($driveletter)/1024/1024, 2);
			$diskpercentavailable = round(($disksizefree/$disksize) * 100, 2);

			echo "<table width=\"100%\" border=\"0\" cellpadding=\"7\" cellspacing=\"0\">\n";
			echo "<tr>\n";
			echo "	<th class='th' colspan='2' align='left'>".$text['label-drive']."</th>\n";
			echo "</tr>\n";
			echo "<tr>\n";
			echo "	<td width='20%' class=\"vncell\" style='text-align: left;'>\n";
			echo "		".$text['label-drive-capacity']." \n";
			echo "	</td>\n";
			echo "	<td class=\"row_style1\">\n";
			echo "		$disksize mb\n";
			echo "	</td>\n";
			echo "</tr>\n";

			echo "<tr>\n";
			echo "	<td width='20%' class=\"vncell\" style='text-align: left;'>\n";
			echo "		".$text['label-drive-free']." \n";
			echo "	</td>\n";
			echo "	<td class=\"row_style1\">\n";
			echo "		$disksizefree mb\n";
			echo "	</td>\n";
			echo "</tr>\n";

			echo "<tr>\n";
			echo "	<td width='20%' class=\"vncell\" style='text-align: left;'>\n";
			echo "		".$text['label-drive-percent']." \n";
			echo "	</td>\n";
			echo "	<td class=\"row_style1\">\n";
			echo "		$diskpercentavailable% \n";
			echo "	</td>\n";
			echo "</tr>\n";
			echo "</table>\n";
			echo "\n";
		}
		echo "<br />";
		echo "<br />";
		echo "<br />";
	}

//memcache information
	if (permission_exists('system_view_memcache')) {

		echo "<table width='100%' border='0' cellpadding='7' cellspacing='0'>\n";
		echo "	<tr>\n";
		echo "		<th class='th' colspan='2' align='left'>".$text['title-memcache']."</th>\n";
		echo "	</tr>\n";

		$mc_fail = false;

		require_once "resources/classes/modules.php";
		$mod = new switch_modules;

		if ($mod -> active("mod_memcache")) {

			$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);

			if ($fp) {
				$switch_cmd = "memcache status verbose";
				$switch_result = event_socket_request($fp, 'api '.$switch_cmd);
				$mc_lines = preg_split('/\n/', $switch_result);
				foreach($mc_lines as $mc_line) {
					if (strlen(trim($mc_line)) > 0 && substr_count($mc_line, ': ') > 0) {
						$mc_temp = explode(': ', $mc_line);
						$mc_status[$mc_temp[0]] = $mc_temp[1];
					}
				}

				if (is_array($mc_status) && sizeof($mc_status) > 0) {
					foreach($mc_status as $mc_field => $mc_value) {
						echo "<tr>\n";
						echo "	<td width='20%' class='vncell' style='text-align: left;'>".$mc_field.": </td>\n";
						echo "	<td class='row_style1'>".$mc_value."</td>\n";
						echo "</tr>\n";
					}
				}
				else { $mc_fail = true; }
			}
			else { $mc_fail = true; }

		}
		else { $mc_fail = true; }

		if ($mc_fail) {
			echo "<tr>\n";
			echo "	<td width='20%' class='vncell' style='text-align: left;'>".$text['label-memcache_status']."</td>\n";
			echo "	<td class='row_style1'>".$text['message-unavailable']."</td>\n";
			echo "</tr>\n";
		}

		echo "</table>\n";
		echo "<br /><br /><br />\n";

	}

//backup
	if (permission_exists('zzz') && $db_type == 'sqlite') {
		require_once "core/backup/backupandrestore.php";
	}

//include the footer
	require_once "resources/footer.php";
?>
