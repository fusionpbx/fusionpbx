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

//initialize variables we are going to use
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

//if the config.php exists deny access to install.php
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

//intialize variables
	$install_step = '';
	$return_install_step = '';

//process the the HTTP POST
	if (count($_POST) > 0) {
		$install_language = check_str($_POST["install_language"]);
		$install_step = check_str($_POST["install_step"]);
		$return_install_step = check_str($_POST["return_install_step"]);
		if(isset($_POST["event_host"])){
			$event_host		= check_str($_POST["event_host"]);
			$event_port		= check_str($_POST["event_port"]);
			$event_password	= check_str($_POST["event_password"]);
		}
		if(isset($_POST["db_type"])){
			$db_type					= $_POST["db_type"];
			$admin_username				= $_POST["admin_username"];
			$admin_password				= $_POST["admin_password"];
			$install_default_country	= $_POST["install_default_country"];
			$install_template_name		= $_POST["install_template_name"];
			$domain_name				= $_POST["domain_name"];
		}
	}

//set the install step if it is not set
	if(!$install_step) { $install_step = 'select_language'; }

//set the language for the install
	$_SESSION['domain']['language']['code'] = $install_language;

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//set a default enviroment if first_time
	//initialize some varibles to cut down on warnings
	$_SESSION['message'] = '';
	$v_link_label_play = '';
	$v_link_label_pause = '';
	$default_login = 0;
	$onload = '';

//buffer the content
	if (sizeof(ob_get_status())!=0) ob_end_clean(); //clean the buffer
	ob_start();

	$messages = array();
	if (!extension_loaded('PDO')) {
		$messages[] = "<b>PHP PDO was not detected</b>. Please install it before proceeding";
	}
	if (!(extension_loaded('pdo_pgsql') or extension_loaded('pdo_mysql') or extension_loaded('pdo_sqlite'))) {
		$messages[] = "<b>no database PDO driver was detected</b>. Please install one of pgsql, mysql or sqlite before proceeding";
	}

	echo "<div align='center'>\n";
	$msg = '';
	//make sure the includes directory is writable so the config.php file can be written.
		if (!is_writable($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/resources/pdo.php")) {
			$messages[] = "<b>Write access to ".$_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."</b> and its sub-directories are required during the install.";
		}
	//test for selinux
		if (file_exists('/usr/sbin/getenforce')) {
			$enforcing;
			exec('getenforce', $enforcing);
			if($enforcing[0] == 'Enforcing'){
				$messages[] = "<b>SELinux is enabled and enforcing</b> you must have a policy installed to let the webserver connect to the switch event socket<br/>".
				"<sm>You can use the following to find what ports are allowed<pre>semanage port -l | grep '^http_port_t'</pre></sm>";
			}
		}
	//test for windows and non sqlite
		if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' and strlen($db_type) > 0 and $db_type !='sqlite') {
			$messages[] = "<b>Windows requires a system DSN ODBC connection</b> this must be configured.";
		}

	//action code
	if($return_install_step == 'config_detail'){
		//check for all required data
		$existing_errors = count($messages);
		if (strlen($admin_username) == 0) { $messages[] = "Please provide the Admin Username"; }
		if (strlen($admin_password) == 0) {	$messages[] = "Please provide the Admin Password"; }
		elseif (strlen($admin_password) < 5) { $messages[] = "Please provide an Admin Password that is 5 or more characters.<br>\n"; }
		if ( count($messages) > $existing_errors) { $install_step = 'config_detail'; }
	}

	if($install_step =='execute') {
		//set the max execution time to 1 hour
		ini_set('max_execution_time',3600);
	}

	//display messages
	if (count($messages)>0) {
		echo "<br />\n";
		echo "<div align='center'>\n";
		echo "<table width='75%'>\n";
		echo "<tr>\n";
		echo "<th align='left'>Messages</th>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "<td class='row_style1'><strong><ul>\n";
		foreach ($messages as $message){
			echo "<li>$message</li>\n";
		}
		echo "</ul></strong></td>\n";
		echo "</tr>\n";
		echo "</table>\n";
		echo "</div>\n";
	}

	//includes and title
	$document['title'] = $text['title-install'];

	//view code
	if($install_step == 'select_language'){
		echo "	<form method='post' name='frm' action=''>\n";
		include "resources/page_parts/install_select_language.php";
		echo "	<input type='hidden' name='return_install_step' value='select_language'/>\n";
		echo "	<input type='hidden' name='install_step' value='detect_config'/>\n";
		echo "</form>\n";
	} elseif($install_step == 'detect_config'){
		if(!($event_host == '' || $event_host == 'localhost' || $event_host == '::1' || $event_host == '127.0.0.1' )){
			echo "<p><b>Warning</b> you have choosen a value other than localhost for event_host, this is unsoported at present</p>\n";
		}
		//if($detect_ok){
			echo "<form method='post' name='frm' action=''>\n";
			include "resources/page_parts/install_event_socket.php";
			echo "	<input type='hidden' name='install_language' value='".$_SESSION['domain']['language']['code']."'/>\n";
			echo "	<input type='hidden' name='return_install_step' value='detect_config'/>\n";
			echo "	<input type='hidden' name='install_step' value='config_detail'/>\n";
			echo "	<input type='hidden' name='event_host' value='$event_host'/>\n";
			echo "	<input type='hidden' name='event_port' value='$event_port'/>\n";
			echo "	<input type='hidden' name='event_password' value='$event_password'/>\n";
			//echo "	<div style='text-align:right'>\n";
			//echo "    <button type='button' class='btn' onclick=\"history.go(-1);\">".$text['button-back']."</button>\n";
			//echo "    <button type='submit' class='btn' id='next'>".$text['button-next']."</button>\n";
			//echo "	</div>\n";
			echo "</form>\n";
		//} else {
		//	echo "<form method='post' name='frm' action=''>\n";
		//	echo "	<div style='text-align:right'>\n";
		//	echo "    <button type='button' class='btn' onclick=\"history.go(-1);\">".$text['button-back']."</button>\n";
		//	echo "	</div>\n";
		//	echo "</form>\n";
		//}
	}
	elseif($install_step == 'config_detail'){
		//get the domain
		if(!$domain_name){
			$domain_array = explode(":", $_SERVER["HTTP_HOST"]);
			$domain_name = $domain_array[0];
		}
		include "resources/page_parts/install_config_detail.php";
	}
	elseif($install_step == 'config_database'){
		include "resources/page_parts/install_config_database.php";
	}
	elseif($install_step == 'execute'){
		echo "<p><b>".$text['header-installing'][$install_language]."</b></p>\n";
		//$protocol = 'http';
		//if($_SERVER['HTTPS']) { $protocol = 'https'; }
		//echo "<iframe src='$protocol://$domain_name/core/install/install_first_time.php' style='border:solid 1px #000;width:100%;height:auto'></iframe>";
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
			#set_error_handler("error_handler");
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
				header("Location: ".PROJECT_PATH."/logout.php");
				$_SESSION['message'] = 'Install complete';
			} else {
				echo "<form method='post' name='frm' action=''>\n";
				echo "	<div style='text-align:right'>\n";
				echo "    <button type='button' class='btn' onclick=\"history.go(-1);\">".$text['button-back']."</button>\n";
				echo "    <button type='button' class='btn' onclick=\"location.reload(true);\">".$text['button-execute']."</button>\n";
				echo "	</div>\n";
				echo "</form>\n";
			}
		}
	}
	else {
		echo "<p>Unkown install_step '$install_step'</p>\n";
	}

//initialize some defaults so we can be 'logged in'
	$_SESSION['username'] = 'install_enabled';
	$_SESSION['permissions'][]['permission_name'] = 'superadmin';
	$_SESSION['menu'] = '';

//add the content to the template and then send output
	$body = ob_get_contents(); //get the output from the buffer
	ob_end_clean(); //clean the buffer

//set a default template
	$default_template = 'default';
	$_SESSION['domain']['template']['name'] = $default_template;
	$_SESSION['theme']['menu_brand_type']['text'] = "text";

//set the default template path
	$template_path = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/themes/'.$default_template.'/template.php';

//get the content of the template
	$template_content = file_get_contents($template_path);

//replace the variables in the template
	$template_content = str_replace ("<!--{title}-->", $document['title'], $template_content); //<!--{title}--> defined in each individual page
	$template_content = str_replace ("<!--{head}-->", '', $template_content); //<!--{head}--> defined in each individual page
	//$template_content = str_replace ("<!--{menu}-->", $_SESSION["menu"], $template_content); //included in the theme
	$template_content = str_replace ("<!--{body}-->", $body, $template_content); //defined in /themes/default/template.php
	$template_content = str_replace ("<!--{project_path}-->", PROJECT_PATH, $template_content); //defined in /themes/default/template.php

//get the contents of the template and save it to the template variable
	ob_start();
	require_once "resources/classes/menu.php";
	eval('?>' . $template_content . '<?php ');
	$content = ob_get_contents(); //get the output from the buffer
	ob_end_clean(); //clean the buffer

//send the content to the browser and then clear the variable
	echo $content;

?>
