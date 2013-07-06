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

function adminer_object() {

    class AdminerSoftware extends Adminer {

		function name() {
			// custom name in title and heading
			return 'Adminer';
		}
		/*
		function permanentLogin() {
			// key used for permanent login
			return "7bebc76d8680196752c6b961ef13c360";
		}

		function credentials() {
			global $db_host, $db_username, $db_password;
			// server, username and password for connecting to database
			return array($db_host.':'.$db_port, $db_username, $db_password);
		}

		function database() {
			global $db_name;
			// database name, will be escaped by Adminer
			return $db_name;
		}

		function login($login, $password) {
			// validate user submitted credentials
			return ($login == 'admin' && $password == '');
		}
		*/

    }

    return new AdminerSoftware;
}

// include original Adminer or Adminer Editor
	include "adminer.php";

?>