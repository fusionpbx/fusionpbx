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
	Portions created by the Initial Developer are Copyright (C) 2008-2016
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	Matthew Vale <github@mafoo.org>
*/
//add the required includes
	require_once "root.php";
	require_once "resources/functions.php";
	require_once "resources/classes/text.php";

//start a php session
	session_start();	
	header('X-Accel-Buffering: no');
	header('Content-Encoding: none;');
	while (ob_get_level() > 0) {
		ob_end_flush();
	}
	
//initialize variables we are going to use
	//global $db_type, $db_path, $db_host, $db_port, $db_name, $db_username, $db_password, $db_create, $db_create_username, $db_create_password;

	$event_host = '';
	$event_port = '';
	$event_password = '';
	$install_language = 'en-us';
	$admin_username = '';
	$admin_password = '';
	$install_default_country = 'US';
	$install_template_name = '';
	$domain_name = '';
	$db_type = '';
	$db_path = '';
	$db_host = '';
	$db_port = '';
	$db_name = '';
	$db_username = '';
	$db_password = '';
	$db_create = '';
	$db_create_username = '';
	$db_create_password = '';
	$db = NULL;
	
//detect the iso country code from the locale
	//$locale = Locale::getDefault();
	$timezone = 'UTC';
	if (is_link('/etc/localtime')) {
		// Mac OS X (and older Linuxes)
		// /etc/localtime is a symlink to the
		// timezone in /usr/share/zoneinfo.
		$filename = readlink('/etc/localtime');
		if (strpos($filename, '/usr/share/zoneinfo/') === 0) {
			$timezone = substr($filename, 20);
		}
	} elseif (file_exists('/etc/timezone')) {
		// Ubuntu / Debian.
		$data = file_get_contents('/etc/timezone');
		if ($data) {
			$timezone = rtrim($data);
		}
	} elseif (file_exists('/etc/sysconfig/clock')) {
		// RHEL / CentOS
		$data = parse_ini_file('/etc/sysconfig/clock');
		if (!empty($data['ZONE'])) {
			$timezone = $data['ZONE'];
		}
	}

//set the time zone
	date_default_timezone_set($timezone);

//if the config.php exists deny access to install_execute.php
	if (file_exists($_SERVER["PROJECT_ROOT"]."/resources/config.php")) {
		echo "access denied";
		exit;
	} elseif (file_exists("/etc/fusionpbx/config.php")) {
		echo "access denied";
		exit;
	} elseif (file_exists("/usr/local/etc/fusionpbx/config.php")) {
		echo "access denied";
		exit;
	}

//process the the HTTP GET
	if (count($_GET) > 0) {
		$install_language 			= check_str($_GET["install_language"]);
		$event_host					= check_str($_GET["event_host"]);
		$event_port					= check_str($_GET["event_port"]);
		$event_password				= check_str($_GET["event_password"]);
		$db_type					= check_str($_GET["db_type"]);
		$admin_username				= check_str($_GET["admin_username"]);
		$admin_password				= check_str($_GET["admin_password"]);
		$install_default_country	= check_str($_GET["install_default_country"]);
		$install_template_name		= check_str($_GET["install_template_name"]);
		$domain_name				= check_str($_GET["domain_name"]);
		@ $db_path 					= check_str($_GET["db_path"]); 
		@ $db_host 					= check_str($_GET["db_host"]); 
		@ $db_port 					= check_str($_GET["db_port"]); 
		@ $db_name 					= check_str($_GET["db_name"]); 
		@ $db_username 				= check_str($_GET["db_username"]); 
		@ $db_password 				= check_str($_GET["db_password"]); 
		@ $db_create 				= check_str($_GET["db_create"]); 
		@ $db_create_username 		= check_str($_GET["db_create_username"]); 
		@ $db_create_password 		= check_str($_GET["db_create_password"]); 
	}else{
		print "Error Missing params";
		exit;
	}

//set the language for the install
	$_SESSION['domain']['language']['code'] = $install_language;

//add multi-lingual support
	$language = new text;
	$text = $language->get();

?><!doctype html><html>
	<head>
		<title><?php echo $text['title-install'] ?></title>
	</head>
<?php
	flush();

//set the max execution time to 1 hour
	ini_set('max_execution_time',3600);

?>
	<body>
<?php
		require_once "core/install/resources/classes/detect_switch.php";
		$detect_switch = new detect_switch($event_host, $event_port, $event_password);
		$detect_ok = true;
		try {
			$detect_switch->detect();
		} catch(Exception $e){
			//echo "<p>Failed to detect configuration detect_switch reported: " . $e->getMessage() . "</p>\n";
			//$detect_ok = false;
		}
		if($detect_ok){
			$install_ok = true;
			echo "<pre style='text-align:left;'>\n";
			function error_handler($err_severity, $errstr, $errfile, $errline ) {
				if (0 === error_reporting()) { return false;}
				switch($err_severity)
				{
					case E_ERROR:               throw new Exception ($errstr . " in $errfile line: $errline");
					case E_PARSE:               throw new Exception ($errstr . " in $errfile line: $errline");
					case E_CORE_ERROR:          throw new Exception ($errstr . " in $errfile line: $errline");
					case E_COMPILE_ERROR:       throw new Exception ($errstr . " in $errfile line: $errline");
					case E_USER_ERROR:          throw new Exception ($errstr . " in $errfile line: $errline");
					case E_STRICT:              throw new Exception ($errstr . " in $errfile line: $errline");
					case E_RECOVERABLE_ERROR:   throw new Exception ($errstr . " in $errfile line: $errline");
					default:                    return false;
				}
			}
			set_error_handler("error_handler");
			try {
				require_once "resources/classes/global_settings.php";
				$global_settings = new global_settings($detect_switch, $domain_name);
				if(is_null($global_settings)){ throw new Exception("Error global_settings came back with null"); }		
				require_once "resources/classes/install_fusionpbx.php";
				$system = new install_fusionpbx($global_settings);
				$system->admin_username = $admin_username;
				$system->admin_password = $admin_password;
				$system->default_country = $install_default_country;
				$system->install_language = $install_language;
				$system->template_name = $install_template_name;

				require_once "resources/classes/install_switch.php";
				$switch = new install_switch($global_settings);

				//$switch->debug = true;
				//$system->debug = true;
				$switch->echo_progress = true;
				$system->echo_progress = true;

				$system->install_phase_1();
				$switch->install_phase_1();
				$system->install_phase_2();
				$switch->install_phase_2();
			} catch(Exception $e){
				echo "</pre>\n";
				echo "<p><b>Failed to install</b><br/>" . $e->getMessage() . "</p>\n";
				try {
					require_once "resources/classes/install_fusionpbx.php";
					$system = new install_fusionpbx($global_settings);
					$system->remove_config();
				} catch(Exception $e){
					echo "<p><b>Failed to remove config:</b> " . $e->getMessage() . "</p>\n";
				}
				$install_ok = false;
			}
			restore_error_handler();
			if($install_ok){
				echo "</pre>\n";
				$protocol = 'http';
				if($_SERVER['HTTPS']) { $protocol = 'https'; }
				?><h1>Instalation Complete</h1>
				<script type="text/javascript">
					window.top.location.href = "<?php echo "$protocol://$domain_name".PROJECT_PATH."/logout.php" ?>";
				</script>
				<?php
			} else {
				echo "<form method='post' name='frm' action=''>\n";
				echo "	<div style='text-align:right'>\n";
				echo "    <button type='button' class='btn' onclick=\"location.reload(true);\">".$text['button-execute']."</button>\n";
				echo "	</div>\n";
				echo "</form>\n";
			}
		}

?>
	</body>
</html>
