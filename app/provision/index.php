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
	Copyright (C) 2008-2023 All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	Luis Daniel Lucio Quiroz <dlucio@okay.com.mx>
*/

//includes files
	require_once dirname(__DIR__, 2) . "/resources/require.php";
	require_once "resources/functions/device_by.php";

//logging
	openlog("FusionPBX", LOG_PID | LOG_PERROR, LOG_LOCAL0);

//set default variables
	$dir_count = 0;
	$file_count = 0;
	$row_count = 0;
	$device_template = '';
	$database = database::new(); //use an existing connection if possible

//define PHP variables from the HTTP values
	if (isset($_REQUEST['address'])) {
		$device_address = $_REQUEST['address'];
	}
	if (isset($_REQUEST['mac'])) {
		$device_address = $_REQUEST['mac'];
	}
	$file = $_REQUEST['file'] ?? '';
	$ext = $_REQUEST['ext'] ?? '';
	//if (!empty($_REQUEST['template'])) {
	//	$device_template = $_REQUEST['template'];
	//}

//get the device address for Cisco 79xx in the URL as &name=SEP000000000000
	if (empty($device_address)) {
		$name = $_REQUEST['name'];
		if (substr($name, 0, 3) == "SEP") {
			$device_address = strtolower(substr($name, 3, 12));
			unset($name);
		}
	}

// Escence make request based on UserID for Memory keys
	// The file name is fixed to `Account1_Extern.xml`.
	// (Account1 is the first account you register)
	if (empty($device_address) && !empty($ext)) {
		$domain_array = explode(":", $_SERVER["HTTP_HOST"]);
		$domain_name = $domain_array[0];
		$device = device_by_ext($ext, $domain_name);
		if ($device !== false && ($device['device_vendor'] == 'escene' || $device['device_vendor'] == 'grandstream')) {
			$device_address = $device['device_address'];
		}
	}

//send http error
	function http_error($error) {
		//$error_int_val = intval($error);
		$http_errors = [
			400 => "Bad Request",
			401 => "Unauthorized",
			402 => "Payment Required",
			403 => "Forbidden",
			404 => "Not Found",
			405 => "Method Not Allowed",
			406 => "Not Acceptable",
		];
		$error_message = $http_errors[$error] ?? '';
		if (!empty($error_message)) {
			header("HTTP/1.1 $error $error_message");
			echo "<html>\n";
			echo "<head><title>$error $error_message</title></head>\n";
			echo "<body bgcolor=\"white\">\n";
			echo "<center><h1>$error $error_message</h1></center>\n";
			echo "<hr><center>nginx/1.12.1</center>\n";
			echo "</body>\n";
			echo "</html>\n";
		}
		exit;
	}

//check alternate device address source
	if (empty($device_address)) {
		//set the http user agent
		//$_SERVER['HTTP_USER_AGENT'] = "Yealink SIP-T38G  38.70.0.125 00:15:65:00:00:00";
		//$_SERVER['HTTP_USER_AGENT'] = "Yealink SIP-T56A  58.80.0.25 001565f429a4";

		//Mitel: HTTP_USER_AGENT 'Mitel6940 MAC:14-00-E9-29-4C-6B V:6.4.0.4006-SIP' or Aastra: 'Aastra6731i MAC:00-08-5D-29-4C-6B V:3.3.1.4365-SIP'
		if (substr($_SERVER['HTTP_USER_AGENT'],0,5) == "Mitel" || substr($_SERVER['HTTP_USER_AGENT'],0,6) == "Aastra") {
			preg_match("/MAC:([A-F0-9-]{17})/", $_SERVER['HTTP_USER_AGENT'], $matches);
			$device_address = $matches[1];
			$device_address = preg_replace("#[^a-fA-F0-9./]#", "", $device_address);
		}

		//Audiocodes: $_SERVER['HTTP_USER_AGENT'] = "AUDC-IPPhone/2.2.8.61 (440HDG-Rev0; 00908F602AAC)"
		if (substr($_SERVER['HTTP_USER_AGENT'],0,12) == "AUDC-IPPhone") {
			$device_address = substr($_SERVER['HTTP_USER_AGENT'],-13);
			$device_address = preg_replace("#[^a-fA-F0-9./]#", "", $device_address);
		}

		//Fanvil: $_SERVER['HTTP_USER_AGENT'] = "Fanvil X5U-V2 2.12.1 0c3800000000)"
		if (substr($_SERVER['HTTP_USER_AGENT'],0,6) == "Fanvil") {
			$device_address = substr($_SERVER['HTTP_USER_AGENT'],-13);
			$device_address = preg_replace("#[^a-fA-F0-9./]#", "", $device_address);
			//syslog(LOG_WARNING, 'Fanvil Device Address is: '.$device_address);
		}

		//Flyingvoice: $_SERVER['HTTP_USER_AGENT'] = "Flyingvoice FIP13G V0.6.24 00:21:F2:22:AE:F1"
		if (strtolower(substr($_SERVER['HTTP_USER_AGENT'],0,11)) == "flyingvoice") {
			$device_address = substr($_SERVER['HTTP_USER_AGENT'],-17);
			$device_address = preg_replace("#[^a-fA-F0-9./]#", "", $device_address);
		}

		//Grandstream: $_SERVER['HTTP_USER_AGENT'] = "Grandstream Model HW GXP2135 SW 1.0.7.97 DevId 000b828aa872"
		if (substr($_SERVER['HTTP_USER_AGENT'],0,11) == "Grandstream") {
			$device_address = substr($_SERVER['HTTP_USER_AGENT'],-12);
			$device_address = preg_replace("#[^a-fA-F0-9./]#", "", $device_address);
		}

		//HTek: $_SERVER['HTTP_USER_AGENT'] = "Htek UC926 2.0.4.2 00:1f:c1:00:00:00"
		if (substr($_SERVER['HTTP_USER_AGENT'],0,4) == "Htek") {
			$device_address = substr($_SERVER['HTTP_USER_AGENT'],-17);
			$device_address = preg_replace("#[^a-fA-F0-9./]#", "", $device_address);
		}

		//Panasonic: $_SERVER['HTTP_USER_AGENT'] = "Panasonic_KX-UT670/01.022 (0080f000000)"
		if (substr($_SERVER['HTTP_USER_AGENT'],0,9) == "Panasonic") {
			$device_address = substr($_SERVER['HTTP_USER_AGENT'],-14);
			$device_address = preg_replace("#[^a-fA-F0-9./]#", "", $device_address);
		}

		//Snom: $userAgent = "Mozilla/4.0 (compatible; snomD785-SIP 10.1.169.16 2010.12-00001-gd311851f1 (Feb 25 2019 - 14:19:43) 00041396D9B4 SXM:0 UXM:0 UXMC:0)"
		if (substr($_SERVER['HTTP_USER_AGENT'],25,4) == "snom") {
			$snom_ua = explode(" ", $_SERVER['HTTP_USER_AGENT']);
			$device_address = $snom_ua[10];
			$device_address = preg_replace("#[^a-fA-F0-9./]#", "", $device_address);
		}

		//Yealink: 17 digit mac appended to the user agent, so check for a space exactly 17 digits before the end.
		if (strtolower(substr($_SERVER['HTTP_USER_AGENT'],0,7)) == "yealink" || strtolower(substr($_SERVER['HTTP_USER_AGENT'],0,5)) == "vp530") {
			if (strstr(substr($_SERVER['HTTP_USER_AGENT'],-4), ':')) { //remove colons if they exist
				$device_address = substr($_SERVER['HTTP_USER_AGENT'],-17);
				$device_address = preg_replace("#[^a-fA-F0-9./]#", "", $device_address);
			} else { //take mac as is - fixes T5X series
				$device_address = substr($_SERVER['HTTP_USER_AGENT'],-12);
			}
		}
	}

//prepare the device address
	if (isset($device_address)) {
		//normalize the device address to lower case
			$device_address = strtolower($device_address);
		//replace all non hexadecimal values and validate the device address
			$device_address = preg_replace("#[^a-fA-F0-9./]#", "", $device_address);
			//if (strlen($device_address) != 12) {
			//	echo "invalid mac address";
			//	exit;
			//}
	}

//get http_domain_filter from global settings only (can't be used per domain)
	$domain_filter = (new settings(['database' => $database]))->get('provision', 'http_domain_filter', true);

//get the domain_uuid, domain_name, device_name and device_vendor
	$sql = "select d.device_uuid, d.domain_uuid, d.device_vendor, n.domain_name ";
	$sql .= "from v_devices as d, v_domains as n ";
	$sql .= "where device_address = :device_address ";
	$sql .= "and d.domain_uuid = n.domain_uuid ";
	$parameters['device_address'] = $device_address;
	if ($domain_filter) {
		$sql .= "and n.domain_name = :domain_name";
		$parameters['domain_name'] = $_SERVER['HTTP_HOST'];
	}
	$row = $database->select($sql, $parameters, 'row');
	if (is_array($row)) {
		$device_uuid = $row['device_uuid'];
		$domain_uuid = $row['domain_uuid'];
		$domain_name = $row['domain_name'];
		$device_vendor = $row['device_vendor'];
	} else {
		$result = 'false';
	}
	unset($sql, $parameters);

//get the domain_name and domain_uuid
	if (empty($domain_uuid)) {
		//get the domain_name
			$domain_array = explode(":", $_SERVER["HTTP_HOST"]);
			$domain_name = $domain_array[0];

		//get the domain_uuid
			$sql = "select domain_uuid from v_domains ";
			$sql .= "where lower(domain_name) = lower(:domain_name) ";
			$parameters['domain_name'] = $domain_name;
			$domain_uuid = $database->select($sql, $parameters, 'column');
			unset($sql, $parameters);
	}

//send a request to a remote server to validate the MAC address and secret
	if (!empty($_SERVER['auth_server'])) {
		$result = send_http_request($_SERVER['auth_server'], 'mac='.url_encode($_REQUEST['mac']).'&secret='.url_encode($_REQUEST['secret']));
		if ($result == "false") {
			syslog(LOG_WARNING, '['.$_SERVER['REMOTE_ADDR']."] provision attempt but the remote auth server said no for ".escape($_REQUEST['mac']));
			http_error('404');
		}
	}

//use the device address to get the vendor
	if (empty($device_vendor)) {
		$device_vendor = device::get_vendor($device_address);
	}

//use settings object instead of session
	$settings = new settings(['database' => $database, 'domain_uuid' => $domain_uuid]);

//check if provisioning has been enabled
	if (!$settings->get('provision', 'enabled', false)) {
		syslog(LOG_WARNING, '['.$_SERVER['REMOTE_ADDR']."] provision attempt but provisioning is ".__line__." not enabled for ".escape($_REQUEST['mac']));
		http_error('404');
	}

//get all provision settings
	$provision = $settings->get('provision', null, []);

//check for a valid match
	if (empty($device_uuid) && !$settings->get('provision', 'auto_insert_enabled', false)) {
		http_error(403);
	}

//check the cidr range
	if (!empty($provision['cidr'])) {
		$found = false;
		foreach($provision['cidr'] as $cidr) {
			if (check_cidr($cidr, $_SERVER['REMOTE_ADDR'])) {
				$found = true;
				break;
			}
		}
		if (!$found) {
			syslog(LOG_WARNING, '['.$_SERVER['REMOTE_ADDR']."] provision attempt but failed CIDR check for ".escape($_REQUEST['mac']));
			http_error('404');
		}
	}

//http authentication - digest
	if (!empty($provision["http_auth_username"]) && empty($provision["http_auth_type"])) { $provision["http_auth_type"] = "digest"; }
	if (!empty($provision["http_auth_username"]) && $provision["http_auth_type"] === "digest" && !empty($provision["http_auth_enabled"]) && $provision["http_auth_enabled"]) {
		//function to parse the http auth header
			function http_digest_parse($txt) {
				//protect against missing data
				$needed_parts = array('nonce'=>1, 'nc'=>1, 'cnonce'=>1, 'qop'=>1, 'username'=>1, 'uri'=>1, 'response'=>1);
				$data = array();
				$keys = implode('|', array_keys($needed_parts));
				preg_match_all('@('.$keys.')=(?:([\'"])([^\2]+?)\2|([^\s,]+))@', $txt, $matches, PREG_SET_ORDER);
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
			$realm = $domain_name;

		//request authentication
			if (empty($_SERVER['PHP_AUTH_DIGEST'])) {
				http_digest_request($realm);
			}

		//check for valid digest authentication details
			if (isset($provision["http_auth_username"]) && strlen($provision["http_auth_username"]) > 0) {
				if (!($data = http_digest_parse($_SERVER['PHP_AUTH_DIGEST'])) || ($data['username'] != $provision["http_auth_username"])) {
					header('HTTP/1.1 401 Unauthorized');
					header("Content-Type: text/html");
					$content = 'Unauthorized '.$__line__;
					header("Content-Length: ".strval(strlen($content)));
					echo $content;
					exit;
				}
			}

		//generate the valid response
			$authorized = false;
			$auth_passwords = $settings->get('provision', 'http_auth_password', []);
			if (!$authorized && is_array($auth_passwords)) {
				foreach ($auth_passwords as $password) {
					$A1 = md5($provision["http_auth_username"].':'.$realm.':'.$password);
					$A2 = md5($_SERVER['REQUEST_METHOD'].':'.$data['uri']);
					$valid_response = md5($A1.':'.$data['nonce'].':'.$data['nc'].':'.$data['cnonce'].':'.$data['qop'].':'.$A2);
					if ($data['response'] == $valid_response) {
						$authorized = true;
						break;
					}
				}
				unset($password);
			}
			if (!$authorized) {
				header('HTTP/1.0 401 Unauthorized');
				header("Content-Type: text/html");
				$content = 'Unauthorized '.$__line__;
				header("Content-Length: ".strval(strlen($content)));
				echo $content;
				exit;
			}
	}

//http authentication - basic
	if (!empty($provision["http_auth_username"]) && $provision["http_auth_type"] === "basic" && $provision["http_auth_enabled"]) {
		if (!isset($_SERVER['PHP_AUTH_USER'])) {
			header('WWW-Authenticate: Basic realm="'.$domain_name.'"');
			header('HTTP/1.0 401 Authorization Required');
			header("Content-Type: text/html");
			$content = 'Authorization Required';
			header("Content-Length: ".strval(strlen($content)));
			echo $content;
			exit;
		}
		else {
			$authorized = false;
			$auth_passwords = $settings->get('provision', 'http_auth_password', []);
			foreach ($auth_passwords as $password) {
				if ($_SERVER['PHP_AUTH_PW'] == $password) {
					$authorized = true;
					break;
				}
			}
			unset($password, $auth_passwords);

			if (!$authorized) {
				//access denied
				syslog(LOG_WARNING, '['.$_SERVER['REMOTE_ADDR']."] provision attempt but failed http basic authentication for ".check_str($_REQUEST['mac']));
				header('HTTP/1.0 401 Unauthorized');
				header('WWW-Authenticate: Basic realm="'.$domain_name.'"');
				unset($_SERVER['PHP_AUTH_USER'],$_SERVER['PHP_AUTH_PW']);
				$content = 'Unauthorized';
				header("Content-Length: ".strval(strlen($content)));
				echo $content;
				exit;
			}
		}
	}

//if the password was defined in the settings then require the password.
	if (!empty($provision['password'])) {
		//deny access if the password doesn't match
		if ($provision['password'] != check_str($_REQUEST['password'])) {
			//log the failed auth attempt to the system, to be available for fail2ban.
			openlog('FusionPBX', LOG_NDELAY, LOG_AUTH);
			syslog(LOG_WARNING, '['.$_SERVER['REMOTE_ADDR']."] provision attempt bad password for ".check_str($_REQUEST['mac']));
			closelog();
			echo "access denied";
			return;
		}
	}

//start the buffer
	ob_start();

//output template to string for header processing
	$prov = new provision(['settings'=>$settings]);
	$prov->domain_uuid = $domain_uuid;
	$prov->device_address = $device_address;
	$prov->file = $file;
	$file_contents = $prov->render();

//clean the output buffer
	ob_clean();

//deliver the customized config over HTTP/HTTPS
	//need to make sure content-type is correct
	if (!empty($_REQUEST['content_type']) && $_REQUEST['content_type'] == 'application/octet-stream') {
		//format the device address and
			$device_address_formatted = $prov->format_address($device_address, $device_vendor);

		//replace the variable name with the value
			$file_name = str_replace("{\$address}", $device_address, $file);
			$file_name = str_replace("{\$mac}", $device_address, $file_name);

		//set the headers
			header('Content-Description: File Transfer');
			header('Content-Type: application/octet-stream');
			header('Content-Disposition: attachment; filename="'.basename($file_name).'"');
			header('Content-Transfer-Encoding: binary');
			header('Expires: 0');
			header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
			header('Pragma: public');
			header('Content-Length: '.strlen($file_contents));
	}
	else {
		$cfg_ext = ".cfg";
		if ($device_vendor === "aastra" && strrpos($file, $cfg_ext, 0) === strlen($file) - strlen($cfg_ext)) {
			header("Content-Type: text/plain");
		}
		else if ($device_vendor === "yealink" || $device_vendor === "flyingvoice") {
			header("Content-Type: text/plain");
		}
		else if ($device_vendor === "snom" && $device_template === "snom/m3") {
			$file_contents = utf8_decode($file_contents);
			header("Content-Type: text/plain; charset=iso-8859-1");
		}
		elseif (!empty($file_contents) && is_xml($file_contents)) {
			header("Content-Type: text/xml; charset=utf-8");
		}
		else {
			header("Content-Type: text/plain");
		}
	}

//send the content
	$file_size = strlen($file_contents);
	if (isset($_SERVER['HTTP_RANGE'])) {
		$ranges = $_SERVER['HTTP_RANGE'];
		list($unit, $range) = explode('=', $ranges, 2);
		list($start, $end) = explode('-', $range, 2);

		$start = empty($start) ? 0 : (int)$start;
		$end = empty($end) ? $file_size - 1 : min((int)$end, $file_size - 1);

		$length = $end - $start + 1;

		//add additional headers
		header('HTTP/1.1 206 Partial Content');
		header("Content-Length: $length");
		header("Content-Range: bytes $start-$end/$file_size");

		//output the requested range from the content variable
		echo substr($file_contents, $start, $length);
	}
	else {
		//add additional headers
		header('HTTP/1.1 200 OK');
		header("Content-Length: $file_size");
		header('Accept-Ranges: bytes');

		//send the entire content
		echo $file_contents;
	}

//close the
	closelog();

//device logs
	if (file_exists($_SERVER["PROJECT_ROOT"]."/app/device_logs/app_config.php")){
		require_once "app/device_logs/resources/device_logs.php";
	}

?>
