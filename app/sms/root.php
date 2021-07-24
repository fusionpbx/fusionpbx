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
*/

// make sure the PATH_SEPARATOR is defined
	umask(2);
	if (!defined("PATH_SEPARATOR")) {
		if (strpos($_ENV["OS"], "Win") !== false) {
			define("PATH_SEPARATOR", ";");
		} else {
			define("PATH_SEPARATOR", ":");
		}
	}

	if (!isset($output_format)) $output_format = (PHP_SAPI == 'cli') ? 'text' : 'html';

	// make sure the document_root is set
	$_SERVER["SCRIPT_FILENAME"] = str_replace("\\", '/', $_SERVER["SCRIPT_FILENAME"]);
	if(PHP_SAPI == 'cli'){
		chdir(pathinfo(realpath($_SERVER["PHP_SELF"]), PATHINFO_DIRNAME));
		$script_full_path = str_replace("\\", '/', getcwd() . '/' . $_SERVER["SCRIPT_FILENAME"]);
		$dirs = explode('/', pathinfo($script_full_path, PATHINFO_DIRNAME));
		if (file_exists('/project_root.php')) {
			$path = '/';
		} else {
			$i    = 1;
			$path = '';
			while ($i < count($dirs)) {
				$path .= '/' . $dirs[$i];
				if (file_exists($path. '/project_root.php')) {
					break;
				}
				$i++;
			}
		}
		$_SERVER["DOCUMENT_ROOT"] = $path;
	}else{
		$_SERVER["DOCUMENT_ROOT"]   = str_replace($_SERVER["PHP_SELF"], "", $_SERVER["SCRIPT_FILENAME"]);
	}
	$_SERVER["DOCUMENT_ROOT"]   = realpath($_SERVER["DOCUMENT_ROOT"]);
// try to detect if a project path is being used
	if (!defined('PROJECT_PATH')) {
		if (is_dir($_SERVER["DOCUMENT_ROOT"]. '/fusionpbx')) {
			define('PROJECT_PATH', '/fusionpbx');
		} elseif (file_exists($_SERVER["DOCUMENT_ROOT"]. '/project_root.php')) {
			define('PROJECT_PATH', '');
		} else {
			$dirs = explode('/', str_replace('\\', '/', pathinfo($_SERVER["PHP_SELF"], PATHINFO_DIRNAME)));
			$i    = 1;
			$path = $_SERVER["DOCUMENT_ROOT"];
			while ($i < count($dirs)) {
				$path .= '/' . $dirs[$i];
				if (file_exists($path. '/project_root.php')) {
					break;
				}
				$i++;
			}
			if(!file_exists($path. '/project_root.php')){
				die("Failed to locate the Project Root by searching for project_root.php please contact support for assistance");
			}
			$project_path = str_replace($_SERVER["DOCUMENT_ROOT"], "", $path);
			define('PROJECT_PATH', $project_path);
		}
		$_SERVER["PROJECT_ROOT"] = realpath($_SERVER["DOCUMENT_ROOT"] . PROJECT_PATH);
		set_include_path(get_include_path() . PATH_SEPARATOR . $_SERVER["PROJECT_ROOT"]);
	}

if (!class_exists('IP4Filter')) {
	class IP4Filter {

		private static $_IP_TYPE_SINGLE = 'single';
		private static $_IP_TYPE_WILDCARD = 'wildcard';
		private static $_IP_TYPE_MASK = 'mask';
		private static $_IP_TYPE_CIDR = 'CIDR';
		private static $_IP_TYPE_SECTION = 'section';
		private $_allowed_ips = array();

		public function __construct($allowed_ips) {
			$this->_allowed_ips = $allowed_ips;
		}

		public function check($ip, $allowed_ips = null) {
			$allowed_ips = $allowed_ips ? $allowed_ips : $this->_allowed_ips;

			foreach ($allowed_ips as $allowed_ip) {
				$type = $this->_judge_ip_type($allowed_ip);
				$sub_rst = call_user_func(array($this, '_sub_checker_' . $type), $allowed_ip, $ip);

				if ($sub_rst) {
					return true;
				}
			}

			return false;
		}

		private function _judge_ip_type($ip) {
			if (strpos($ip, '*')) {
				return self :: $_IP_TYPE_WILDCARD;
			}

			if (strpos($ip, '/')) {
				$tmp = explode('/', $ip);
				if (strpos($tmp[1], '.')) {
					return self :: $_IP_TYPE_MASK;
				} else {
					return self :: $_IP_TYPE_CIDR;
				}
			}

			if (strpos($ip, '-')) {
				return self :: $_IP_TYPE_SECTION;
			}

			if (ip2long($ip)) {
				return self :: $_IP_TYPE_SINGLE;
			}

			return false;
		}

		private function _sub_checker_single($allowed_ip, $ip) {
			return (ip2long($allowed_ip) == ip2long($ip));
		}

		private function _sub_checker_wildcard($allowed_ip, $ip) {
			$allowed_ip_arr = explode('.', $allowed_ip);
			$ip_arr = explode('.', $ip);
			for ($i = 0; $i < count($allowed_ip_arr); $i++) {
				if ($allowed_ip_arr[$i] == '*') {
					return true;
				} else {
					if (false == ($allowed_ip_arr[$i] == $ip_arr[$i])) {
						return false;
					}
				}
			}
		}

		private function _sub_checker_mask($allowed_ip, $ip) {
			list($allowed_ip_ip, $allowed_ip_mask) = explode('/', $allowed_ip);
			$begin = (ip2long($allowed_ip_ip) & ip2long($allowed_ip_mask)) + 1;
			$end = (ip2long($allowed_ip_ip) | (~ ip2long($allowed_ip_mask))) + 1;
			$ip = ip2long($ip);
			return ($ip >= $begin && $ip <= $end);
		}

		private function _sub_checker_section($allowed_ip, $ip) {
			list($begin, $end) = explode('-', $allowed_ip);
			$begin = ip2long($begin);
			$end = ip2long($end);
			$ip = ip2long($ip);
			return ($ip >= $begin && $ip <= $end);
		}

		private function _sub_checker_CIDR($CIDR, $IP) {
			list ($net, $mask) = explode('/', $CIDR);
			return ( ip2long($IP) & ~((1 << (32 - $mask)) - 1) ) == ip2long($net);
		}

	}

	function check_acl(){
		global $db, $debug, $domain_uuid, $domain_name;

		//select node_cidr from v_access_control_nodes where node_cidr != '';
		$sql = "select node_cidr from v_access_control_nodes where node_cidr != '' and node_type = 'allow'";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		if (count($result) == 0) {
			die("No ACL's");
		}
		foreach ($result as &$row) {
			$allowed_ips[] = $row['node_cidr'];
		}

		$acl = new IP4Filter($allowed_ips);

		return $acl->check($_SERVER['REMOTE_ADDR'],$allowed_ips);
	}
}
?>