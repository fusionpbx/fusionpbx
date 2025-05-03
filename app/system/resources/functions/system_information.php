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
	  Portions created by the Initial Developer are Copyright (C) 2008-2025
	  the Initial Developer. All Rights Reserved.

	  Contributor(s):
	  Mark J Crane <markjcrane@fusionpbx.com>
	  James Rose <james.o.rose@gmail.com>
	  Tim Fry <tim@fusionpbx.com>
	 */

	/*
	 * Creates the following array structure:
	 * $array['version']
	 * $array['git']['path']
	 * $array['git']['branch']
	 * $array['git']['origin']
	 * $array['git']['commit']
	 * $array['git']['status']
	 * $array['git']['age']
	 * $array['git']['date']
	 * $array['path']
	 * $array['switch']['version']
	 * $array['switch']['bits']
	 * $array['switch']['git']['info']
	 * $array['php']['version']
	 * $array['os']['version']
	 * $array['os']['name']
	 * $array['os']['kernel'] (*nix only)
	 * $array['os']['uptime']
	 * $array['os']['date']
	 * $array['os']['type']
	 * $array['os']['mem']
	 * $array['os']['cpu']
	 * $array['os']['disk']['size']
	 * $array['os']['disk']['free'] (Windows only)
	 * $array['os']['disk']['available'] (Windows only)
	 * $array['database']['type']
	 * $array['database']['version']
	 * $array['database']['connections']
	 * $array['database']['sizes'][$datname] = $size
	 * $array['memcache']
	 */

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
	function system_information(): array {
		global $db_type;
		$system_information = [];
		$esl = event_socket::create();

		//php and switch version
		if (permission_exists('system_view_info')) {
			$system_information['version'] = software::version();

			$git_path = normalize_path_to_os($_SERVER['PROJECT_ROOT'] . "/.git");
			if (file_exists($git_path)) {
				$system_information['git']['path'] = $git_path;
				$git_exe = 'git';
				if (strtoupper(substr(PHP_OS, 0, 3)) === 'SUN') {
					$git_exe = shell_exec('which git');
				}
				exec($git_exe . ' --git-dir=' . $git_path . ' status', $dummy, $returnCode);
				if ($returnCode) {
					$system_information['git']['branch'] = 'unknown';
					$system_information['git']['origin'] = 'unknown';
					$system_information['git']['commit'] = 'unknown';
					$system_information['git']['status'] = 'unknown';
					$system_information['git']['age'] = 'unknown';
					$system_information['git']['date'] = 'unknown';
				} else {
					$git_branch = shell_exec($git_exe . ' --git-dir=' . $git_path . ' name-rev --name-only HEAD');
					$git_commit = shell_exec($git_exe . ' --git-dir=' . $git_path . ' rev-parse HEAD');
					$git_origin = shell_exec($git_exe . ' --git-dir=' . $git_path . ' config --get remote.origin.url');
					$git_origin = preg_replace('/\.git$/', '', $git_origin);
					$git_status = shell_exec($git_exe . ' --git-dir=' . $git_path . ' status | grep "Your branch"');
					$git_age = shell_exec($git_exe . ' --git-dir=' . $git_path . ' log --pretty=format:%at "HEAD^!"');

					$git_date = DateTime::createFromFormat('U', $git_age);
					$git_age = $git_date->diff(new DateTime('now'));
					$system_information['git']['branch'] = trim($git_branch) ?? '';
					$system_information['git']['origin'] = trim($git_origin) ?? '';
					$system_information['git']['commit'] = trim($git_commit) ?? '';
					$system_information['git']['status'] = trim($git_status) ?? '';
					$system_information['git']['age'] = $git_age ?? '';
					$system_information['git']['date'] = $git_date ?? '';
				}
			} else {
				$system_information['git']['path'] = 'unknown';
			}
			$system_information['path'] = $_SERVER['PROJECT_ROOT'];

			if ($esl->is_connected()) {
				$switch_version = $esl->request('api version');
				preg_match("/FreeSWITCH Version (\d+\.\d+\.\d+(?:\.\d+)?).*\(.*?(\d+\w+)\s*\)/", $switch_version, $matches);
				$system_information['switch']['version'] = $matches[1];
				$system_information['switch']['bits'] = $matches[2];
				preg_match("/\(git\s*(.*?)\s*\d+\w+\s*\)/", $switch_version, $matches);
				$switch_git_info = $matches[1] ?? null;
				if (!empty($switch_git_info)) {
					$system_information['switch']['git']['info'] = $switch_git_info;
				} else {
					$system_information['switch']['git']['info'] = 'unknown';
				}
			} else {
				$system_information['switch']['version'] = 'connection failed';
				$system_information['switch']['bits'] = 'connection failed';
				$system_information['switch']['git']['info'] = 'connection failed';
			}

			$system_information['php']['version'] = phpversion();

			if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
				$data = explode("\n", shell_exec('systeminfo /FO CSV 2> nul'));
				$data = array_combine(str_getcsv($data[0]), str_getcsv($data[1]));
				$os_name = trim($data['OS Name']);
				$os_version = trim($data['OS Version']);
				unset($data);
			} else {
				$os_kernel = trim(shell_exec('uname -a'));
				$os_name = trim(shell_exec('lsb_release -is'));
				$os_version = trim(shell_exec('lsb_release -rs'));
			}
			$system_information['os']['name'] = $os_name;
			$system_information['os']['version'] = $os_version;
			if (!empty($os_kernel)) {
				$system_information['os']['kernel'] = $os_kernel;
			}

			$tmp_result = shell_exec('uptime');
			if (!empty($tmp_result)) {
				$system_information['os']['uptime'] = $tmp_result;
			} else {
				$system_information['os']['uptime'] = 'unknown';
			}
		} else {
			$system_information['os']['name'] = 'permission denied';
			$system_information['os']['version'] = 'permission denied';
			$system_information['os']['uptime'] = 'permission denied';
			$system_information['php']['version'] = 'permission denied';
			$system_information['os']['version'] = 'permission denied';
		}

		$system_information['os']['date'] = date('r');
		$system_information['os']['type'] = PHP_OS;

		//memory information
		if (permission_exists('system_view_ram')) {
			//linux
			if (stristr(PHP_OS, 'Linux')) {
				$shell_cmd = 'free -hw';
				$shell_result = shell_exec($shell_cmd);
			}

			//freebsd
			if (stristr(PHP_OS, 'FreeBSD')) {
				$shell_cmd = 'sysctl vm.vmtotal';
				$shell_result = shell_exec($shell_cmd);
			}

			//Windows
			if (stristr(PHP_OS, 'WIN')) {
				// connect to WMI
				$wmi = new COM('WinMgmts:root/cimv2');
				// Query this Computer for Total Physical RAM
				$res = $wmi->ExecQuery('Select TotalPhysicalMemory from Win32_ComputerSystem');
				// Fetch the first item from the results
				$system_information = $res->ItemIndex(0);
				$shell_result = round($system_information->TotalPhysicalMemory / 1024 / 1024, 0);
			}
			if (!empty($shell_result)) {
				$system_information['os']['mem'] = $shell_result;
			} else {
				$system_information['os']['mem'] = 'unknown';
			}
		} else {
			$system_information['os']['mem'] = 'permission denied';
		}

		//cpu information
		if (permission_exists('system_view_cpu')) {
			//linux
			if (stristr(PHP_OS, 'Linux')) {
				$shell_cmd = "ps -e -o pcpu,cpu,nice,state,cputime,args --sort pcpu | sed '/^ 0.0 /d'";
				$shell_result = shell_exec($shell_cmd);
			}

			//freebsd
			if (stristr(PHP_OS, 'FreeBSD')) {
				$shell_cmd = 'top';
				$shell_result = shell_exec($shell_cmd);
			}

			if (!empty($shell_result)) {
				$system_information['os']['cpu'] = $shell_result;
			} else {
				$system_information['os']['cpu'] = 'unknown';
			}
		} else {
			$system_information['os']['cpu'] = 'permission denied';
		}

		//drive space
		if (permission_exists('system_view_hdd')) {
			if (stristr(PHP_OS, 'Linux') || stristr(PHP_OS, 'FreeBSD')) {
				$shell_cmd = 'df -hP'; //--total is ignored
				$shell_result = shell_exec($shell_cmd);
				if (!empty($shell_result)) {
					$system_information['os']['disk']['size'] = $shell_result;
				}
			} else if (stristr(PHP_OS, 'WIN')) {
				//disk_free_space returns the number of bytes available on the drive;
				//1 kilobyte = 1024 byte
				//1 megabyte = 1024 kilobyte
				$drive_letter = substr($_SERVER["DOCUMENT_ROOT"], 0, 2);
				$disk_size = round(disk_total_space($drive_letter) / 1024 / 1024, 2);
				$disk_size_free = round(disk_free_space($drive_letter) / 1024 / 1024, 2);
				$disk_percent_available = round(($disk_size_free / $disk_size) * 100, 2);

				$system_information['os']['disk']['size'] = $disk_size;
				$system_information['os']['disk']['free'] = $disk_size_free;
				$system_information['os']['disk']['available'] = $disk_percent_available;
			}
		} else {
			$system_information['os']['disk']['size'] = 'permission denied';
			$system_information['os']['disk']['free'] = 'permission denied';
			$system_information['os']['disk']['available'] = 'permission denied';
		}

		//database information
		if (permission_exists('system_view_database')) {
			if ($db_type == 'pgsql') {

				//database version
				$sql = "select version(); ";
				$database = new database;
				$database_name = $database->select($sql, null, 'column');
				$database_array = explode(' ', $database_name);

				//database connections
				$sql = "select count(*) from pg_stat_activity; ";
				$database_connections = $database->select($sql, null, 'column');

				//database size
				$sql = "SELECT pg_database.datname,";
				$sql .= "pg_size_pretty(pg_database_size(pg_database.datname)) AS size ";
				$sql .= "FROM pg_database;";
				$database_size = $database->select($sql, null, 'all');

				$system_information['database']['type'] = 'pgsql';
				$system_information['database']['name'] = $database_array[0];
				$system_information['database']['version'] = $database_array[1];
				$system_information['database']['connections'] = $database_connections;

				foreach ($database_size as $row) {
					$system_information['database']['sizes'][$row['datname']] = $row['size'];
				}
			}
		} else {
			$system_information['database'] = 'permission denied';
		}

		//memcache information
		if (permission_exists("system_view_memcache") && file_exists($_SERVER["PROJECT_ROOT"] . "/app/sip_status/app_config.php")) {
			$memcache_fail = true;
			$mod = new modules;
			if ($mod->active("mod_memcache")) {
				if ($esl->is_connected()) {
					$switch_cmd = "memcache status verbose";
					$switch_result = event_socket::api($switch_cmd);
					$memcache_lines = preg_split('/\n/', $switch_result);
					foreach ($memcache_lines as $memcache_line) {
						if (!empty(trim($memcache_line)) > 0 && substr_count($memcache_line, ': ')) {
							$memcache_temp = explode(': ', $memcache_line);
							$memcache_status[$memcache_temp[0]] = $memcache_temp[1];
						}
					}
					if (is_array($memcache_status) && sizeof($memcache_status) > 0) {
						$system_information['memcache'] = $memcache_status;
						$memcache_fail = false;
					}
				}
			}

			if ($memcache_fail) {
				$system_information['memcache'] = 'none';
			}
		} else {
			$system_information['memcache'] = 'permission denied or unavailable';
		}

		return $system_information;
	}
