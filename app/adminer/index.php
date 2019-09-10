<?php

//includes
	include "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permission
	if (permission_exists('adminer')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//only allow users in the superadmin group to use this feature
	if (if_group("superadmin")) {
		//echo "access granted";
	}
	else {
		echo "access denied";
		exit;
	}

//auto login
	if (isset($_SESSION['adminer']['auto_login']['boolean'])) {
		function adminer_object() {
		    class AdminerSoftware extends Adminer {

				function name() {
					// custom name in title and heading
					return 'Adminer';
				}

				function permanentLogin($i = false) {
					// key used for permanent login
					if ($_SESSION['adminer']['auto_login']['boolean'] == 'true') {
						return "7bebc76d8680196752c6b961ef13c360";
					}
				}

				function credentials() {
					// server, username and password for connecting to database
					if ($_SESSION['adminer']['auto_login']['boolean'] == 'true') {
						global $db_host, $db_port, $db_username, $db_password;
						return array($db_host.':'.$db_port, $db_username, $db_password);
					}
				}

				function database() {
					// database name, will be escaped by Adminer
					if ($_SESSION['adminer']['auto_login']['boolean'] == 'true') {
						global $db_name;
						return $db_name;
					}
				}

				function login($login, $password) {
					// validate user submitted credentials
					return ($_SESSION['adminer']['auto_login']['boolean'] == 'true') ? true : false;
				}

		    }
		    return new AdminerSoftware;
		}
	}

// include original Adminer or Adminer Editor
	include "adminer.php";

?>
