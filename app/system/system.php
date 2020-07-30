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

//set the page title
	$document['title'] = $text['title-sys-status'];

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
		echo "		".software::version()."\n";
		echo "	</td>\n";
		echo "</tr>\n";

		$git_path = normalize_path_to_os($_SERVER['PROJECT_ROOT']."/.git");
		if(file_exists($git_path)){
			$git_exe = 'git';
			if (strtoupper(substr(PHP_OS, 0, 3)) === 'SUN') { $git_exe = shell_exec('which git'); }
			exec($git_exe.' --git-dir='.$git_path.' status', $dummy, $returnCode);
			if($returnCode){
				echo "<tr>\n";
				echo "	<td width='20%' class=\"vncell\" style='text-align: left;'>\n";
				echo "		".$text['label-git_info']."\n";
				echo "	</td>\n";
				echo "	<td class=\"row_style1\">\n";
				echo "		".$text['label-git_corrupted']."\n";
				echo "	</td>\n";
				echo "</tr>\n";
			}else{
				$git_branch = shell_exec($git_exe.' --git-dir='.$git_path.' name-rev --name-only HEAD');
				rtrim($git_branch);
				$git_commit = shell_exec($git_exe.' --git-dir='.$git_path.' rev-parse HEAD');
				rtrim($git_commit);
				$git_origin = shell_exec($git_exe.' --git-dir='.$git_path.' config --get remote.origin.url');
				rtrim($git_origin);
				$git_origin = preg_replace('/\.git$/','',$git_origin);
				$git_status = shell_exec($git_exe.' --git-dir='.$git_path.' status | grep "Your branch"');
				rtrim($git_status);
				$git_age = shell_exec($git_exe.' --git-dir='.$git_path.' log --pretty=format:%at "HEAD^!"');
				rtrim($git_age);
				$git_date = DateTime::createFromFormat('U', $git_age);
				$git_age = $git_date->diff(new DateTime('now'));
				echo "<tr>\n";
				echo "	<td width='20%' class=\"vncell\" style='text-align: left;'>\n";
				echo "		".$text['label-git_info']."\n";
				echo "	</td>\n";
				echo "	<td class=\"row_style1\">\n";
				echo "		".$text['label-git_branch'].": ".$git_branch."<br>\n";
				echo "		".$text['label-git_commit'].": <a href='$git_origin/commit/$git_commit'>".$git_commit."</a><br>\n";
				echo "		".$text['label-git_origin'].": ".$git_origin."<br>\n";
				echo "		".$text['label-git_status'].": ".$git_status.$git_age->format(' %R%a days ago')."<br>\n";
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

		$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
		if ($fp) {
			$switch_version = event_socket_request($fp, 'api version');
			preg_match("/FreeSWITCH Version (\d+\.\d+\.\d+(?:\.\d+)?).*\(.*?(\d+\w+)\s*\)/", $switch_version, $matches);
			$switch_version = $matches[1];
			$switch_bits = $matches[2];
			echo "<tr>\n";
			echo "	<td width='20%' class=\"vncell\" style='text-align: left;'>\n";
			echo "		".$text['label-switch']." ".$text['label-version']."\n";
			echo "	</td>\n";
			echo "	<td class=\"row_style1\">$switch_version ($switch_bits)</td>\n";
			echo "</tr>\n";
			preg_match("/\(git\s*(.*?)\s*\d+\w+\s*\)/", $switch_version, $matches);
			$switch_git_info = $matches[1];
			if(strlen($switch_git_info) > 0){
				echo "<tr>\n";
				echo "	<td width='20%' class=\"vncell\" style='text-align: left;'>\n";
				echo "		".$text['label-switch']." ".$text['label-git_info']."\n";
				echo "	</td>\n";
				echo "	<td class=\"row_style1\">$switch_git_info</td>\n";
				echo "</tr>\n";
			}
		}

		echo "<tr>\n";
		echo "	<td width='20%' class=\"vncell\" style='text-align: left;'>\n";
		echo "	".$text['label-php']." ".$text['label-version']."\n";
		echo "	</td>\n";
		echo "	<td class=\"row_style1\">".phpversion()."</td>\n";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "	<th class='th' colspan='2' align='left' style='padding-top:2em'>".$text['title-os-info']."</th>\n";
		echo "</tr>\n";

		if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
			echo "<!--\n";
			$data = explode("\n",shell_exec('systeminfo /FO CSV 2> nul'));
			$data = array_combine(str_getcsv($data[0]), str_getcsv($data[1]));
			$os_name = $data['OS Name'];
			$os_version = $data['OS Version'];
			unset($data);
			echo "-->\n";
		}
		else {
			echo "<!--\n";
			$os_kernel = shell_exec('uname -a');
			$os_name = shell_exec('lsb_release -is');
			$os_version = shell_exec('lsb_release -rs');
			echo "-->\n";
		}
		
		if (strlen($os_name) > 0) {
			echo "<tr>\n";
			echo "	<td width='20%' class=\"vncell\" style='text-align: left;'>\n";
			echo "		".$text['label-os']." \n";
			echo "	</td>\n";
			echo "	<td class=\"row_style1\">\n";
			echo "		".$os_name." \n";
			echo "	</td>\n";
			echo "</tr>\n";
		}
		if (strlen($os_version) > 0) {
			echo "<tr>\n";
			echo "	<td width='20%' class=\"vncell\" style='text-align: left;'>\n";
			echo "		".$text['label-version']." \n";
			echo "	</td>\n";
			echo "	<td class=\"row_style1\">\n";
			echo "		".$os_version." \n";
			echo "	</td>\n";
			echo "</tr>\n";
		}
		if (strlen($os_kernel) > 0) {
			echo "<tr>\n";
			echo "	<td width='20%' class=\"vncell\" style='text-align: left;'>\n";
			echo "		".$text['label-kernel']." \n";
			echo "	</td>\n";
			echo "	<td class=\"row_style1\">\n";
			echo "		".$os_kernel." \n";
			echo "	</td>\n";
			echo "</tr>\n";
		}
		unset($os_name, $os_version, $os_kernel);

		echo "<!--\n";
		$tmp_result = shell_exec('uptime');
		echo "-->\n";
		if (strlen($tmp_result) > 0) {
			echo "<tr>\n";
			echo "	<td width='20%' class=\"vncell\" style='text-align: left;'>\n";
			echo "		Uptime\n";
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
	echo "		Date\n";
	echo "	</td>\n";
	echo "	<td class=\"row_style1\">\n";
	echo "		".date('r')." \n";
	echo "	</td>\n";
	echo "</tr>\n";
	echo "</table>\n";
	echo "<br /><br>";

//memory information
	if (permission_exists('system_view_ram')) {
		//linux
		if (stristr(PHP_OS, 'Linux')) {
			echo "<!--\n";
			$shell_cmd = 'free -hw';
			$shell_result = shell_exec($shell_cmd);
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
				echo "<br /><br />";
			}
		}

		//freebsd
		if (stristr(PHP_OS, 'FreeBSD')) {
			echo "<!--\n";
			$shell_cmd = 'sysctl vm.vmtotal';
			$shell_result = shell_exec($shell_cmd);
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
				echo "<br /><br />";
			}
		}
		
		//Windows
		if (stristr(PHP_OS, 'WIN')) {
			echo "<!--\n";
			// connect to WMI
			$wmi = new COM('WinMgmts:root/cimv2');
			// Query this Computer for Total Physical RAM
			$res = $wmi->ExecQuery('Select TotalPhysicalMemory from Win32_ComputerSystem');
			// Fetch the first item from the results
			$system = $res->ItemIndex(0);
			$shell_result = round($system->TotalPhysicalMemory / 1024 /1024, 0);
			echo "-->\n";
			if (strlen($shell_result) > 0) {
				echo "<table width=\"100%\" border=\"0\" cellpadding=\"7\" cellspacing=\"0\">\n";
				echo "<tr>\n";
				echo "	<th class='th' colspan='2' align='left'>".$text['Physical Memory']."</th>\n";
				echo "</tr>\n";
				echo "<tr>\n";
				echo "	<td width='20%' class=\"vncell\" style='text-align: left;'>\n";
				echo "		".$text['label-mem']." \n";
				echo "	</td>\n";
				echo "	<td class=\"row_style1\">\n";
				echo "		$shell_result mb\n";
				echo "	</td>\n";
				echo "</tr>\n";
				echo "</table>\n";
				echo "<br /><br />";

			}
		}
	}

//cpu information
	if (permission_exists('system_view_cpu')) {
		//linux
		if (stristr(PHP_OS, 'Linux')) {
			echo "<!--\n";
			$shell_cmd = "ps -e -o pcpu,cpu,nice,state,cputime,args --sort pcpu | sed '/^ 0.0 /d'";
			$shell_result = shell_exec($shell_cmd);
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

				//$last_line = shell_exec($shell_cmd, $shell_result);
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
				echo "<br /><br />";
			}
		}

		//freebsd
		if (stristr(PHP_OS, 'FreeBSD')) {
			echo "<!--\n";
			$shell_cmd = 'top';
			$shell_result = shell_exec($shell_cmd);
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
				echo "<br /><br />";
			}
		}
	}

//drive space
	if (permission_exists('system_view_hdd')) {
		if (stristr(PHP_OS, 'Linux') || stristr(PHP_OS, 'FreeBSD')) {
			echo "<!--\n";
			$shell_cmd = 'df -hP --total';
			$shell_result = shell_exec($shell_cmd);
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
			echo "$shell_result<br>";
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
			echo "		$disk_size mb\n";
			echo "	</td>\n";
			echo "</tr>\n";

			echo "<tr>\n";
			echo "	<td width='20%' class=\"vncell\" style='text-align: left;'>\n";
			echo "		".$text['label-drive-free']." \n";
			echo "	</td>\n";
			echo "	<td class=\"row_style1\">\n";
			echo "		$disk_size_free mb\n";
			echo "	</td>\n";
			echo "</tr>\n";

			echo "<tr>\n";
			echo "	<td width='20%' class=\"vncell\" style='text-align: left;'>\n";
			echo "		".$text['label-drive-percent']." \n";
			echo "	</td>\n";
			echo "	<td class=\"row_style1\">\n";
			echo "		$disk_percent_available% \n";
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

		$memcache_fail = false;
		$mod = new modules;
		if ($mod -> active("mod_memcache")) {
			$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
			if ($fp) {
				$switch_cmd = "memcache status verbose";
				$switch_result = event_socket_request($fp, 'api '.$switch_cmd);
				$memcache_lines = preg_split('/\n/', $switch_result);
				foreach($memcache_lines as $memcache_line) {
					if (strlen(trim($memcache_line)) > 0 && substr_count($memcache_line, ': ') > 0) {
						$memcache_temp = explode(': ', $memcache_line);
						$memcache_status[$memcache_temp[0]] = $memcache_temp[1];
					}
				}

				if (is_array($memcache_status) && sizeof($memcache_status) > 0) {
					foreach($memcache_status as $memcache_field => $memcache_value) {
						echo "<tr>\n";
						echo "	<td width='20%' class='vncell' style='text-align: left;'>".$memcache_field."</td>\n";
						echo "	<td class='row_style1'>".$memcache_value."</td>\n";
						echo "</tr>\n";
					}
				}
				else { $memcache_fail = true; }
			}
			else { $memcache_fail = true; }

		}
		else { $memcache_fail = true; }

		if ($memcache_fail) {
			echo "<tr>\n";
			echo "	<td width='20%' class='vncell' style='text-align: left;'>".$text['label-memcache_status']."</td>\n";
			echo "	<td class='row_style1'>".$text['message-unavailable']."</td>\n";
			echo "</tr>\n";
		}

		echo "</table>\n";
		echo "<br /><br />\n";
	}

//include the footer
	require_once "resources/footer.php";

?>
