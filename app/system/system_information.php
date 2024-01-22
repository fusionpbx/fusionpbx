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
	  Tim Fry <tim@fusionpbx.com>
	 */

//includes files
	require_once dirname(__DIR__, 2) . "/resources/require.php";
	require_once "resources/check_auth.php";

//system information
	function system_information(): array {
		global $db_type;
		$sys_info = [];
		$esl = event_socket::create();

		//php and switch version
		if (permission_exists('system_view_info')) {
			$sys_info['version'] = software::version();

			$git_path = normalize_path_to_os($_SERVER['PROJECT_ROOT'] . "/.git");
			if (file_exists($git_path)) {
				$git_exe = 'git';
				if (strtoupper(substr(PHP_OS, 0, 3)) === 'SUN') {
					$git_exe = shell_exec('which git');
				}
				exec($git_exe . ' --git-dir=' . $git_path . ' status', $dummy, $returnCode);
				if ($returnCode) {
					$sys_info['git']['branch'] = 'unknown';
					$sys_info['git']['origin'] = 'unknown';
					$sys_info['git']['commit'] = 'unknown';
					$sys_info['git']['origin'] = 'unknown';
					$sys_info['git']['status'] = 'unknown';
				} else {
					$git_branch = shell_exec($git_exe . ' --git-dir=' . $git_path . ' name-rev --name-only HEAD');
					rtrim($git_branch);
					$git_commit = shell_exec($git_exe . ' --git-dir=' . $git_path . ' rev-parse HEAD');
					rtrim($git_commit);
					$git_origin = shell_exec($git_exe . ' --git-dir=' . $git_path . ' config --get remote.origin.url');
					rtrim($git_origin);
					$git_origin = preg_replace('/\.git$/', '', $git_origin);
					$git_status = shell_exec($git_exe . ' --git-dir=' . $git_path . ' status | grep "Your branch"');
					if (!empty($git_status))
						rtrim($git_status);
					$git_age = shell_exec($git_exe . ' --git-dir=' . $git_path . ' log --pretty=format:%at "HEAD^!"');
					rtrim($git_age);
					$git_date = DateTime::createFromFormat('U', $git_age);
					$git_age = $git_date->diff(new DateTime('now'));
					$sys_info['git']['branch'] = $git_branch;
					$sys_info['git']['origin'] = $git_origin;
					$sys_info['git']['commit'] = $git_commit;
					$sys_info['git']['origin'] = $git_origin;
					$sys_info['git']['status'] = $git_status;
				}
			}
			$sys_info['path'] = $_SERVER['PROJECT_ROOT'];

			if ($esl->is_connected()) {
				$switch_version = $esl->request('api version');
				preg_match("/FreeSWITCH Version (\d+\.\d+\.\d+(?:\.\d+)?).*\(.*?(\d+\w+)\s*\)/", $switch_version, $matches);
				$sys_info['switch']['version'] = $matches[1];
				$sys_info['switch']['bits'] = $matches[2];
				preg_match("/\(git\s*(.*?)\s*\d+\w+\s*\)/", $switch_version, $matches);
				$switch_git_info = $matches[1] ?? null;
				if (!empty($switch_git_info)) {
					$sys_info['switch']['git']['info'] = $switch_git_info;
				} else {
					$sys_info['switch']['git']['info'] = 'unknown';
				}
			} else {
				$sys_info['switch']['version'] = 'connection failed';
				$sys_info['switch']['bits'] = 'connection failed';
				$sys_info['switch']['git']['info'] = 'connection failed';
			}

			$sys_info['php']['version'] = phpversion();

			if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
				$data = explode("\n", shell_exec('systeminfo /FO CSV 2> nul'));
				$data = array_combine(str_getcsv($data[0]), str_getcsv($data[1]));
				$os_name = $data['OS Name'];
				$os_version = $data['OS Version'];
				unset($data);
			} else {
				$os_kernel = shell_exec('uname -a');
				$os_name = shell_exec('lsb_release -is');
				$os_version = shell_exec('lsb_release -rs');
			}
			$sys_info['os']['name'] = $os_name;
			$sys_info['os']['version'] = $os_version;
			if (!empty($os_kernel)) {
				$sys_info['os']['kernel'] = $os_kernel;
			}

			$tmp_result = shell_exec('uptime');
			if (!empty($tmp_result)) {
				$sys_info['os']['uptime'] = $tmp_result;
			} else {
				$sys_info['os']['uptime'] = 'unknown';
			}
		} else {
			$sys_info['os']['name'] = 'permission denied';
			$sys_info['os']['version'] = 'permission denied';
			$sys_info['os']['uptime'] = 'permission denied';
			$sys_info['php']['version'] = 'permission denied';
			$sys_info['version'] = 'permission denied';
		}

		$sys_info['os']['date'] = date('r');
		$sys_info['os']['type'] = PHP_OS;

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
				$sys_info = $res->ItemIndex(0);
				$shell_result = round($sys_info->TotalPhysicalMemory / 1024 / 1024, 0);
			}
			if (!empty($shell_result)) {
				$sys_info['os']['mem'] = $shell_result;
			} else {
				$sys_info['os']['mem'] = 'unknown';
			}
		} else {
			$sys_info['os']['mem'] = 'permission denied';
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
				$sys_info['os']['cpu'] = $shell_result;
			} else {
				$sys_info['os']['cpu'] = 'unknown';
			}
		} else {
			$sys_info['os']['cpu'] = 'permission denied';
		}

		//drive space
		if (permission_exists('system_view_hdd')) {
			if (stristr(PHP_OS, 'Linux') || stristr(PHP_OS, 'FreeBSD')) {
				$shell_cmd = 'df -hP --total';
				$shell_result = shell_exec($shell_cmd);
				if (!empty($shell_result)) {
					$sys_info['os']['disk']['size'] = $shell_result;
				}
			} else if (stristr(PHP_OS, 'WIN')) {
				//disk_free_space returns the number of bytes available on the drive;
				//1 kilobyte = 1024 byte
				//1 megabyte = 1024 kilobyte
				$drive_letter = substr($_SERVER["DOCUMENT_ROOT"], 0, 2);
				$disk_size = round(disk_total_space($drive_letter) / 1024 / 1024, 2);
				$disk_size_free = round(disk_free_space($drive_letter) / 1024 / 1024, 2);
				$disk_percent_available = round(($disk_size_free / $disk_size) * 100, 2);

				$sys_info['os']['disk']['size'] = $disk_size;
				$sys_info['os']['disk']['free'] = $disk_size_free;
				$sys_info['os']['disk']['available'] = $disk_percent_available;
			}
		} else {
			$sys_info['os']['disk'] = 'permission denied';
		}

		//database information
		if (permission_exists('system_view_database')) {
			if ($db_type == 'pgsql') {

				//database version
				$sql = "select version(); ";
				$database = new database;
				$database_version = $database->select($sql, null, 'column');

				//database connections
				$sql = "select count(*) from pg_stat_activity; ";
				$database_connections = $database->select($sql, null, 'column');

				//database size
				$sql = "SELECT pg_database.datname,";
				$sql .= "pg_size_pretty(pg_database_size(pg_database.datname)) AS size ";
				$sql .= "FROM pg_database;";
				$database_size = $database->select($sql, null, 'all');

				$sys_info['database']['type'] = 'pgsql';
				$sys_info['database']['version'] = $database_version;
				$sys_info['database']['connections'] = $database_connections;

				foreach ($database_size as $row) {
					$sys_info['database'][$row['datname']]['size'] = $row['size'];
				}
			}
		} else {
			$sys_info['database'] = 'permission denied';
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
						$sys_info['memcache'] = $memcache_status;
						$memcache_fail = false;
					}
				}
			}

			if ($memcache_fail) {
				$sys_info['memcache'] = 'none';
			}
		} else {
			$sys_info['memcache'] = 'permission denied or unavailable';
		}

		return $sys_info;
	}
