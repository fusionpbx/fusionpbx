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
	Portions created by the Initial Developer are Copyright (C) 2022
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//set the include path
	$document_root = dirname(__DIR__, 2);
	set_include_path($document_root);
	$_SERVER["DOCUMENT_ROOT"] = $document_root;
	$_SERVER["PROJECT_ROOT"] = $document_root;
	define("PROJECT_PATH", '');

//includes files
	require_once "resources/functions.php";

//add the auto loader
	require_once "resources/classes/auto_loader.php";
	$autoload = new auto_loader();

//include required classes
	require_once "resources/classes/text.php";
	require_once "resources/classes/template.php";
	require_once "core/install/resources/classes/install.php";

//start the session before text object stores values in session
	ini_set("session.cookie_httponly", 'true');
	ini_set("session.cookie_secure", 'false');
	ini_set("session.cookie_samesite", 'Lax');
	session_start();

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//set debug to true or false
	$debug = false;

//set the default domain_uuid
	$domain_uuid = uuid();
	//$_SESSION["domain_uuid"] = uuid();

//add the menu uuid
	$menu_uuid = 'b4750c3f-2a86-b00d-b7d0-345c14eca286';

//error reporting
	ini_set('display_errors', '1');
	error_reporting (E_ALL ^ E_NOTICE); // Report warnings
	//error_reporting (E_ALL); // Report everything
	//error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING ); //hide notices and warnings

//set the default time zone
	date_default_timezone_set('UTC');

//if the config file exists then disable the install page
	$config_exists = false;
	if (file_exists("/usr/local/etc/fusionpbx/config.conf")) {
		//bsd
		$config_path = "/usr/local/etc/fusionpbx";
		$config_exists = true;
	}
	elseif (file_exists("/etc/fusionpbx/config.conf")) {
		//linux
		$config_path = "/etc/fusionpbx";
		$config_exists = true;
	}
	elseif (file_exists(getenv('SystemDrive') . DIRECTORY_SEPARATOR . 'ProgramData' . DIRECTORY_SEPARATOR . 'fusionpbx' . DIRECTORY_SEPARATOR . 'config.conf')) {
		//windows
		$config_path = getenv('SystemDrive') . DIRECTORY_SEPARATOR . 'ProgramData' . DIRECTORY_SEPARATOR . 'fusionpbx' . DIRECTORY_SEPARATOR;
		$config_exists = true;
	}
	if ($config_exists) {
		//redirect with message
		header("Location: ".PROJECT_PATH."/?msg=".urlencode("Already Installed"));
		exit;
	}

//if the config.php exists then use it to create the config.conf file
	if (!$config_exists) {
		if (isset($config_path)) {
			if (is_writable($config_path)) {
				//include the config.php file
				include $config_path.'/config.php';

				//build the config file
				$install = new install;
				$install->database_host = $db_host;
				$install->database_port = $db_port;
				$install->database_name = $db_name;
				$install->database_username = $db_username;
				$install->database_password = $db_password;
				$install->config();

				//give time for the config file to be saved
				sleep(1);

				//redirect the user
				header("Location: /");
				exit;
			}
			else {
				//config directory is not writable run commands as root
				echo "Please run the following commands as root.<br /><br />\n";
				echo "cd ".$document_root."<br />\n";
				echo "php ".$document_root."/core/upgrade/upgrade.php<br />\n";
				unset($config_path);
				exit;
			}
		}
	}

//process and save the data
	if (!empty($_POST)) {
		foreach($_POST as $key => $value) {
			switch($key) {
				case 'admin_username':
				case 'admin_password':
				case 'domain_name':
				case 'database_host':
				case 'database_port':
				case 'database_name':
				case 'database_username':
				case 'database_password':
					$_SESSION['install'][$key] = $value;
			}
		}
		if (!empty($_REQUEST["step"]) && $_REQUEST["step"] == "install") {
			//show debug information
			if ($debug) {
				echo "<pre>\n";
				print_r($_SESSION['install']);
				echo "</pre>\n";
				exit;
			}

			//build the config file
			$install = new install;
			$install->database_host = $_SESSION['install']['database_host'];
			$install->database_port = $_SESSION['install']['database_port'];
			$install->database_name = $_SESSION['install']['database_name'];
			$install->database_username = $_SESSION['install']['database_username'];
			$install->database_password = $_SESSION['install']['database_password'];
			$result = $install->config();

			//end the script if the config path is not set
			if (!$result) {
				echo $install->message;
				exit;
			}

			//wait for the config to be saved to the file system
			sleep(1);

			//add the database schema
			$output = shell_exec('cd '.$_SERVER["DOCUMENT_ROOT"].' && php /var/www/fusionpbx/core/upgrade/upgrade_schema.php');

			//connect to the database
			$database = new database;

			//get the domain name
			$domain_name = $_SESSION['install']['domain_name'];

			//check to see if the domain name exists if it does update the domain_uuid
			$sql = "select domain_uuid from v_domains ";
			$sql .= "where domain_name = :domain_name ";
			$parameters = [];
			$parameters['domain_name'] = $domain_name;
			$domain_uuid = $database->select($sql, $parameters, 'column');
			unset($parameters);

			//set domain and user_uuid to true or false
			if (empty($domain_uuid)) {
				$domain_uuid = uuid();
				$domain_exists = false;
			}
			else {
				$domain_exists = true;
			}

			//if the domain name does not exist, then add the domain name
			if (!$domain_exists) {
				//add the domain permission
				$p = permissions::new();
				$p->add("domain_add", "temp");

				//prepare the array
				$array['domains'][0]['domain_uuid'] = $domain_uuid;
				$array['domains'][0]['domain_name'] = $domain_name;
				$array['domains'][0]['domain_enabled'] = 'true';

				//save to the user data
				$database->save($array);
				//$message = $database->message;
				unset($array);

				//remove the temporary permission
				$p->delete("domain_add", "temp");
			}

			//set the session domain ID and name
			$_SESSION['domain_uuid'] = $domain_uuid;
			$_SESSION['domain_name'] = $domain_name;

			//app defaults
			$output = shell_exec('cd '.$_SERVER["DOCUMENT_ROOT"].' && php /var/www/fusionpbx/core/upgrade/upgrade_domains.php');

			//prepare the user settings
			$admin_username = $_SESSION['install']['admin_username'];
			$admin_password = $_SESSION['install']['admin_password'];

			//prepare the password hash
			$user_salt = uuid();
			$password_hash = md5($user_salt . $admin_password);

			//get the user_uuid if the user exists
			$sql = "select user_uuid from v_users ";
			$sql .= "where domain_uuid = :domain_uuid ";
			$sql .= "and username = :username ";
			$parameters['domain_uuid'] = $domain_uuid;
			$parameters['username'] = $admin_username;
			$user_uuid = $database->select($sql, $parameters, 'column');
			unset($parameters);

			//if the user did not exist then get a new uuid
			if ($user_uuid == null) {
				$domain_exists = false;
				$user_uuid = uuid();
			}
			else {
				$user_exists = true;
			}

			//set the user_uuid
			$_SESSION['user_uuid'] = $user_uuid;

			//get the superadmin group_uuid
			$sql = "select group_uuid from v_groups ";
			$sql .= "where group_name = :group_name ";
			$parameters['group_name'] = 'superadmin';
			$group_uuid = $database->select($sql, $parameters, 'column');
			unset($parameters);

			//add the user permission
			$p = permissions::new();
			$p->add("user_add", "temp");
			$p->add("user_edit", "temp");
			$p->add("user_group_add", "temp");

			//save to the user data
			$array['users'][0]['domain_uuid'] = $domain_uuid;
			$array['users'][0]['user_uuid'] = $user_uuid;
			$array['users'][0]['username'] = $admin_username;
			$array['users'][0]['password'] = $password_hash;
			$array['users'][0]['salt'] = $user_salt;
			$array['users'][0]['user_enabled'] = 'true';
			$array['user_groups'][0]['user_group_uuid'] = uuid();
			$array['user_groups'][0]['domain_uuid'] = $domain_uuid;
			$array['user_groups'][0]['group_name'] = 'superadmin';
			$array['user_groups'][0]['group_uuid'] = $group_uuid;
			$array['user_groups'][0]['user_uuid'] = $user_uuid;
			$database->save($array);
			$message = $database->message;
			unset($array);

			//remove the temporary permission
			$p->delete("user_add", "temp");
			$p->delete("user_edit", "temp");
			$p->delete("user_group_add", "temp");

			//copy the files and directories from resources/install
			/*
			if (!$domain_exists) {
				require_once "resources/classes/install.php";
				$install = new install;
				$install->domain_uuid = $domain_uuid;
				$install->domain = $domain_name;
				$install->switch_conf_dir = $switch_conf_dir;
				$install->copy_conf();
				$install->copy();
			}
			*/

			//write the switch.conf.xml file
			if (!$domain_exists && file_exists($switch_conf_dir)) {
				switch_conf_xml();
			}

			#app defaults
			$output = shell_exec('cd '.$_SERVER["DOCUMENT_ROOT"].' && php /var/www/fusionpbx/core/upgrade/upgrade_domains.php');

			//install completed - prompt the user to login
			header("Location: /logout.php");
		}
	}

//set a default step if not already set
	if(empty($_REQUEST['step'])) {
		$_REQUEST['step'] = '1';
	}

//save an install log if debug is true
	//if ($debug) {
	//	$fp = fopen(sys_get_temp_dir()."/install.log", "w");
	//}

//get the domain
	$domain_array = explode(":", $_SERVER["HTTP_HOST"]);
	$domain_name = $domain_array[0];

//initialize a template object
	$view = new template();
	$view->engine = 'smarty';
	$view->template_dir = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/core/install/resources/views/';
	$view->cache_dir = sys_get_temp_dir();
	$view->init();

//assign default values to the template
	$view->assign("admin_username", "admin");
	$view->assign("admin_password", "");
	$view->assign("domain_name", $domain_name);
	$view->assign("database_host", "localhost");
	$view->assign("database_port", "5432");
	$view->assign("database_name", "fusionpbx");
	$view->assign("database_username", "fusionpbx");
	$view->assign("database_password", "");

//add translations
	foreach($text as $key => $value) {
		$view->assign(str_replace("-", "_", $key), $text[$key]);
	}

//debug information
	//if ($debug) {
	//	echo "<pre>\n";
	//	print_r($text);
	//	echo "</pre>\n";
	//}

//show the views
	//if ($_GET["step"] == "" || $_GET["step"] == "1") {
	//	$content = $view->render('language.htm');
	//}

	if (!empty($_REQUEST["step"]) && $_REQUEST["step"] == "1") {
		$content = $view->render('configuration.htm');
	}
	if (!empty($_REQUEST["step"]) && $_REQUEST["step"] == "2") {
		$content = $view->render('database.htm');
	}
	$view->assign("content", $content);
	echo $view->render('template.htm');

?>
