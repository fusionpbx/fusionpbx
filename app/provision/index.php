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
	Copyright (C) 2008-2015 All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	Luis Daniel Lucio Quiroz <dlucio@okay.com.mx>
*/

include "root.php";
require_once "resources/require.php";
openlog("fusion-provisioning", LOG_PID | LOG_PERROR, LOG_LOCAL0);

//set default variables
	$dir_count = 0;
	$file_count = 0;
	$row_count = 0;
	$tmp_array = '';
	$device_template = '';

//define PHP variables from the HTTP values
	$mac = check_str($_REQUEST['mac']);
	$file = check_str($_REQUEST['file']);
	//if (strlen(check_str($_REQUEST['template'])) > 0) {
	//	$device_template = check_str($_REQUEST['template']);
	//}

//get the mac address for Cisco 79xx in the URL as &name=SEP000000000000
	if (empty($mac)){
		$name = check_str($_REQUEST['name']);
		if (substr($name, 0, 3) == "SEP") {
			$mac = strtolower(substr($name, 3, 12));
			unset($name);
		}
	}

//check alternate MAC source
	if (empty($mac)){
		//set the http user agent
			//$_SERVER['HTTP_USER_AGENT'] = "Yealink SIP-T38G  38.70.0.125 00:15:65:00:00:00";
		//Yealink: 17 digit mac appended to the user agent, so check for a space exactly 17 digits before the end.
			if (strtolower(substr($_SERVER['HTTP_USER_AGENT'],0,7)) == "yealink" || strtolower(substr($_SERVER['HTTP_USER_AGENT'],0,5)) == "vp530") {
				$mac = substr($_SERVER['HTTP_USER_AGENT'],-17);
				$mac = preg_replace("#[^a-fA-F0-9./]#", "", $mac);
			}
		//Panasonic: $_SERVER['HTTP_USER_AGENT'] = "Panasonic_KX-UT670/01.022 (0080f000000)"
			if (substr($_SERVER['HTTP_USER_AGENT'],0,9) == "Panasonic") {
				$mac = substr($_SERVER['HTTP_USER_AGENT'],-14);
				$mac = preg_replace("#[^a-fA-F0-9./]#", "", $mac);
			}
	}

//prepare the mac address
	if (isset($_REQUEST['mac'])) {
		//normalize the mac address to lower case
			$mac = strtolower($mac);
		//replace all non hexadecimal values and validate the mac address
			$mac = preg_replace("#[^a-fA-F0-9./]#", "", $mac);
			if (strlen($mac) != 12) {
				echo "invalid mac address";
				exit;
			}
	}

//get the domain_name and domain_uuid
	if ((!isset($_SESSION['provision']['http_domain_filter'])) or $_SESSION['provision']['http_domain_filter']['text'] == "false") {
		//get the domain_uuid
			$sql = "SELECT domain_uuid FROM v_devices ";
			$sql .= "WHERE device_mac_address = '".$mac."' ";
			$prep_statement = $db->prepare($sql);
			$prep_statement->execute();
			$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
			foreach($result as $row) {
				$domain_uuid = $row["domain_uuid"];
			}
			unset($result, $prep_statement);
			$_SESSION['domain_uuid'] = $domain_uuid;

		//get the domain name
			$domain_name = $_SESSION['domains'][$domain_uuid]['domain_name'];

		//set the PDO error mode
			$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

		//get the default settings
			$sql = "select * from v_default_settings ";
			$sql .= "where default_setting_enabled = 'true' ";
			try {
				$prep_statement = $db->prepare($sql . " order by default_setting_order asc ");
				$prep_statement->execute();
			}
			catch(PDOException $e) {
				$prep_statement = $db->prepare($sql);
				$prep_statement->execute();
			}
			$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
			//unset the previous settings
			foreach ($result as $row) {
				unset($_SESSION[$row['default_setting_category']]);
			}
			//set the settings as a session
			foreach ($result as $row) {
				$name = $row['default_setting_name'];
				$category = $row['default_setting_category'];
				$subcategory = $row['default_setting_subcategory'];
				if (strlen($subcategory) == 0) {
					if ($name == "array") {
						$_SESSION[$category][] = $row['default_setting_value'];
					}
					else {
						$_SESSION[$category][$name] = $row['default_setting_value'];
					}
				} else {
					if ($name == "array") {
						$_SESSION[$category][$subcategory][] = $row['default_setting_value'];
					}
					else {
						$_SESSION[$category][$subcategory]['uuid'] = $row['default_setting_uuid'];
						$_SESSION[$category][$subcategory][$name] = $row['default_setting_value'];
					}
				}
			}

		//get the domains settings
			if (strlen($_SESSION["domain_uuid"]) > 0) {
				$sql = "select * from v_domain_settings ";
				$sql .= "where domain_uuid = '" . $domain_uuid . "' ";
				$sql .= "and domain_setting_enabled = 'true' ";
				try {
					$prep_statement = $db->prepare($sql . " order by domain_setting_order asc ");
					$prep_statement->execute();
				}
				catch(PDOException $e) {
					$prep_statement = $db->prepare($sql);
					$prep_statement->execute();
				}
				$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
				//unset the arrays that domains are overriding
				foreach ($result as $row) {
					$name = $row['domain_setting_name'];
					$category = $row['domain_setting_category'];
					$subcategory = $row['domain_setting_subcategory'];
					if ($name == "array") {
						unset($_SESSION[$category][$subcategory]);
					}
				}
				//set the settings as a session
				foreach ($result as $row) {
					$name = $row['domain_setting_name'];
					$category = $row['domain_setting_category'];
					$subcategory = $row['domain_setting_subcategory'];
					if (strlen($subcategory) == 0) {
						//$$category[$name] = $row['domain_setting_value'];
						if ($name == "array") {
							$_SESSION[$category][] = $row['domain_setting_value'];
						}
						else {
							$_SESSION[$category][$name] = $row['domain_setting_value'];
						}
					} else {
						//$$category[$subcategory][$name] = $row['domain_setting_value'];
						if ($name == "array") {
							$_SESSION[$category][$subcategory][] = $row['domain_setting_value'];
						}
						else {
							$_SESSION[$category][$subcategory][$name] = $row['domain_setting_value'];
						}
					}
				}
			}
	}
	else {
		//get the domain_name
			$domain_array = explode(":", $_SERVER["HTTP_HOST"]);
			$domain_name = $domain_array[0];

		//get the domain_uuid
			$sql = "SELECT * FROM v_domains ";
			$sql .= "WHERE domain_name = '".$domain_name."' ";
			$prep_statement = $db->prepare($sql);
			$prep_statement->execute();
			$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
			foreach($result as $row) {
				$domain_uuid = $row["domain_uuid"];
			}
			unset($result, $prep_statement);
	}

//build the provision array
	foreach($_SESSION['provision'] as $key=>$val) {
		if (strlen($val['var']) > 0) { $value = $val['var']; }
		if (strlen($val['text']) > 0) { $value = $val['text']; }
		$provision[$key] = $value;
		unset($value);
	}

//check if provisioning has been enabled
	if ($provision["enabled"] != "true") {
		echo "access denied";
		exit;
	}

//send a request to a remote server to validate the MAC address and secret
	if (strlen($_SERVER['auth_server']) > 0) {
		$result = send_http_request($_SERVER['auth_server'], 'mac='.check_str($_REQUEST['mac']).'&secret='.check_str($_REQUEST['secret']));
		if ($result == "false") {
			echo "access denied";
			exit;
		}
	}

//use the mac address to get the vendor
	$device_vendor = device::get_vendor($mac);

//keep backwards compatibility
	if (strlen($_SESSION['provision']["cidr"]["text"]) > 0) {
		$_SESSION['provision']["cidr"][] = $_SESSION['provision']["cidr"]["text"];
	}

//check the cidr range
	if (is_array($_SESSION['provision']["cidr"])) {
		$found = false;
		foreach($_SESSION['provision']["cidr"] as $cidr) {
			if (check_cidr($cidr, $_SERVER['REMOTE_ADDR'])) {
				$found = true;
				break;
			}
		}
		if (!$found) {
			echo "access denied";
			exit;
		}
	}

//http authentication - digest
	if (strlen($provision["http_auth_username"]) > 0 && strlen($provision["http_auth_type"]) == 0) { $provision["http_auth_type"] = "digest"; }
	if (strlen($provision["http_auth_username"]) > 0 && strlen($provision["http_auth_password"]) > 0 && $provision["http_auth_type"] == "digest") {
		//function to parse the http auth header
			function http_digest_parse($txt) {
				//protect against missing data
				$needed_parts = array('nonce'=>1, 'nc'=>1, 'cnonce'=>1, 'qop'=>1, 'username'=>1, 'uri'=>1, 'response'=>1);
				$data = array();
				$keys = implode('|', array_keys($needed_parts));
				preg_match_all('@(' . $keys . ')=(?:([\'"])([^\2]+?)\2|([^\s,]+))@', $txt, $matches, PREG_SET_ORDER);
				foreach ($matches as $m) {
					$data[$m[1]] = $m[3] ? $m[3] : $m[4];
					unset($needed_parts[$m[1]]);
				}
				return $needed_parts ? false : $data;
			}

		//function to request digest authentication
			function http_digest_request($realm) {
				header('HTTP/1.1 401 Authorization Required');
				header('WWW-Authenticate: Digest realm="'.$realm.'", qop="auth", nonce="'.uniqid().'", opaque="'.md5($realm).'"');
				header("Content-Type: text/html");
				$content = 'Authorization Cancelled';
				header("Content-Length: ".strval(strlen($content)));
				echo $content;
				die();
			}

		//set the realm
			$realm = $_SESSION['domain_name'];

		//request authentication
			if (empty($_SERVER['PHP_AUTH_DIGEST'])) {
				http_digest_request($realm);
			}

		//check for valid digest authentication details
			if (!($data = http_digest_parse($_SERVER['PHP_AUTH_DIGEST'])) || ($data['username'] != $provision["http_auth_username"])) {
				header('HTTP/1.1 401 Unauthorized');
				header("Content-Type: text/html");
				$content = 'Unauthorized '.$__line__;
				header("Content-Length: ".strval(strlen($content)));
				echo $content;
				exit;
			}

		//generate the valid response
			$A1 = md5($provision["http_auth_username"] . ':' . $realm . ':' . $provision["http_auth_password"]);
			$A2 = md5($_SERVER['REQUEST_METHOD'].':'.$data['uri']);
			$valid_response = md5($A1.':'.$data['nonce'].':'.$data['nc'].':'.$data['cnonce'].':'.$data['qop'].':'.$A2);
			if ($data['response'] != $valid_response) {
				header('HTTP/1.0 401 Unauthorized');
				header("Content-Type: text/html");
				$content = 'Unauthorized '.$__line__;
				header("Content-Length: ".strval(strlen($content)));
				echo $content;
				exit;
			}
	}

//http authentication - basic
	if (strlen($provision["http_auth_username"]) > 0 && strlen($provision["http_auth_password"]) > 0 && $provision["http_auth_type"] == "basic") {
		if (!isset($_SERVER['PHP_AUTH_USER'])) {
			header('WWW-Authenticate: Basic realm="'.$_SESSION['domain_name'].'"');
			header('HTTP/1.0 401 Authorization Required');
			header("Content-Type: text/html");
			$content = 'Authorization Required';
			header("Content-Length: ".strval(strlen($content)));
			echo $content;
			exit;
		} else {
			if ($_SERVER['PHP_AUTH_USER'] == $provision["http_auth_username"] && $_SERVER['PHP_AUTH_PW'] == $provision["http_auth_password"]) {
				//authorized
			}
			else {
				//access denied
				header('HTTP/1.0 401 Unauthorized');
				header('WWW-Authenticate: Basic realm="'.$_SESSION['domain_name'].'"');
				unset($_SERVER['PHP_AUTH_USER'],$_SERVER['PHP_AUTH_PW']);
				$content = 'Unauthorized';
				header("Content-Length: ".strval(strlen($content)));
				echo $content;
				exit;
			}
		}
	}

//if password was defined in the system -> variables page then require the password.
	if (strlen($provision['password']) > 0) {
		//deny access if the password doesn't match
		if ($provision['password'] != check_str($_REQUEST['password'])) {
			//log the failed auth attempt to the system, to be available for fail2ban.
			openlog('FusionPBX', LOG_NDELAY, LOG_AUTH);
			syslog(LOG_WARNING, '['.$_SERVER['REMOTE_ADDR']."] provision attempt bad password for ".check_str($_REQUEST['mac']));
			closelog();
			echo "access denied 4";
			return;
		}
	}

//output template to string for header processing
	$prov = new provision;
	$prov->domain_uuid = $domain_uuid;
	$prov->mac = $mac;
	$prov->file = $file;
	$file_contents = $prov->render();

//deliver the customized config over HTTP/HTTPS
	//need to make sure content-type is correct
	if ($_REQUEST['content_type'] == 'application/octet-stream') {
		//format the mac address and
			$mac = $prov->format_mac($mac, $device_vendor);

		//replace the variable name with the value
			$file_name = str_replace("{\$mac}", $mac, $file);

		//set the headers
			header('Content-Description: File Transfer');
			header('Content-Type: application/octet-stream');
			header('Content-Disposition: attachment; filename="'.basename($file_name).'"');
			header('Content-Transfer-Encoding: binary');
			header('Expires: 0');
			header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
			header('Pragma: public');
			header('Content-Length: ' . strlen($file_contents));
	}
	else {
		$cfg_ext = ".cfg";
		if ($device_vendor === "aastra" && strrpos($file, $cfg_ext, 0) === strlen($file) - strlen($cfg_ext)) {
			header("Content-Type: text/plain");
			header("Content-Length: ".strlen($file_contents));
		} else if ($device_vendor === "yealink") {
			header("Content-Type: text/plain");
			header("Content-Length: ".strval(strlen($file_contents)));
		} else if ($device_vendor === "snom" && $device_template === "snom/m3") {
			$file_contents = utf8_decode($file_contents);
			header("Content-Type: text/plain; charset=iso-8859-1");
			header("Content-Length: ".strlen($file_contents));
		} else {
			header("Content-Type: text/xml; charset=utf-8");
			header("Content-Length: ".strlen($file_contents));
		}
	}
	echo $file_contents;
	closelog();

?>
